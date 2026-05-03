<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductRoll;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderAllocationService
{
    private const ROLL_WIDTH = 2.0;
    private const ROLL_LENGTH = 100.0;

    /**
     * Allocate rolls and decrement `area` on ProductRolls for a paid order.
     * Returns allocation summary.
     */
    public function allocateForOrder(Order $order): array
    {
        $order->loadMissing('items');

        $notes = [];
        if ($order->notes) {
            $decoded = json_decode($order->notes, true);
            $notes = is_array($decoded) ? $decoded : [];
        }

        if (!empty($notes['allocations_applied'])) {
            return $notes['allocations'] ?? [];
        }

        $demands = $this->buildDemandsForOrder($order);
        $virtualRolls = $this->runGreedyAndDp($demands);

        $allocations = [];

        DB::transaction(function () use ($order, $virtualRolls, &$allocations, &$notes) {
            foreach ($virtualRolls as $rollIndex => $roll) {
                foreach ($roll['assignments'] as $assignment) {
                    $neededArea = (float) $assignment['used_area'];
                    $productId = $assignment['product_id'];

                    $availableRolls = ProductRoll::query()
                        ->where('product_id', $productId)
                        ->where('area', '>', 0)
                        ->orderBy('area', 'asc')
                        ->get();

                    $usedFromRolls = [];

                    foreach ($availableRolls as $pr) {
                        if ($neededArea <= 0) {
                            break;
                        }

                        $take = min($pr->area, $neededArea);
                        if ($take <= 0) {
                            continue;
                        }

                        $before = (float) $pr->area;
                        $pr->area = max(0, $pr->area - $take);
                        $pr->save();

                        $neededArea -= $take;

                        $usedFromRolls[] = [
                            'product_roll_id' => $pr->id,
                            'taken_area' => $take,
                            'before_area' => $before,
                            'after_area' => (float) $pr->area,
                        ];
                    }

                    if ($neededArea > 0) {
                        Log::warning('Tidak cukup area roll untuk memenuhi alokasi permintaan.', [
                            'order_id' => $order->id,
                            'assignment' => $assignment,
                            'remaining_area_unfilled' => $neededArea,
                        ]);
                    }

                    $allocations[] = array_merge($assignment, [
                        'allocated_from' => $usedFromRolls,
                        'unfilled_area' => max(0, $neededArea),
                    ]);
                }
            }

            $notes['allocations'] = $allocations;
            $notes['allocations_applied'] = true;

            $order->notes = json_encode($notes, JSON_UNESCAPED_UNICODE);
            $order->save();
        });

        return $allocations;
    }

    private function buildDemandsForOrder(Order $order): array
    {
        $demands = [];

        foreach ($order->items as $item) {
            $totalArea = (float) $item->length * (float) $item->width * (int) $item->quantity;
            $requiredLength = $totalArea / self::ROLL_WIDTH;

            $chunkIndex = 1;
            while ($requiredLength > 0) {
                $segmentLength = min(self::ROLL_LENGTH, $requiredLength);
                $segmentArea = $segmentLength * self::ROLL_WIDTH;

                $demands[] = [
                    'order_id' => $order->id,
                    'order_code' => $order->order_code,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'used_length' => $segmentLength,
                    'used_area' => $segmentArea,
                    'label' => $order->order_code . '-S' . $chunkIndex,
                ];

                $requiredLength -= $segmentLength;
                $chunkIndex++;
            }
        }

        return $demands;
    }

    private function runGreedyAndDp(array $demands): array
    {
        // Reuse the same logic as controller but simplified for per-order demands.
        $greedySelection = $this->runGreedySelection($demands);
        $priorityDemands = $greedySelection['priority_demands'];
        $selectedRollCount = $greedySelection['roll_count'];
        $remainingDemands = $priorityDemands;
        $rolls = [];

        for ($rollNumber = 1; $rollNumber <= $selectedRollCount; $rollNumber++) {
            if (count($remainingDemands) === 0) {
                break;
            }

            $packResult = $this->knapsackSelectAssignments($remainingDemands, (int) round(self::ROLL_LENGTH * 100));
            $selectedIndexes = $packResult['selected_indexes'];

            if (count($selectedIndexes) === 0) {
                break;
            }

            $selectedMap = array_flip($selectedIndexes);
            $currentAssignments = [];
            $nextRemaining = [];

            foreach ($remainingDemands as $index => $demand) {
                if (isset($selectedMap[$index])) {
                    $demand['assigned_roll_number'] = $rollNumber;
                    $currentAssignments[] = $demand;
                    continue;
                }

                $nextRemaining[] = $demand;
            }

            usort($currentAssignments, fn($a, $b) => $a['priority_rank'] <=> $b['priority_rank']);
            $usedLength = array_sum(array_column($currentAssignments, 'used_length'));

            $rolls[] = [
                'capacity_length' => self::ROLL_LENGTH,
                'used_length' => $usedLength,
                'remaining_length' => max(0, self::ROLL_LENGTH - $usedLength),
                'assignments' => $currentAssignments,
                'dp_best_fill_length' => $packResult['best_fill_length'],
                'dp_waste_length' => max(0, self::ROLL_LENGTH - $packResult['best_fill_length']),
                'used_area' => $usedLength * self::ROLL_WIDTH,
            ];

            $remainingDemands = array_values($nextRemaining);
        }

        while (count($remainingDemands) > 0) {
            $demand = array_shift($remainingDemands);
            $demand['assigned_roll_number'] = count($rolls) + 1;
            $usedLength = (float) $demand['used_length'];

            $rolls[] = [
                'capacity_length' => self::ROLL_LENGTH,
                'used_length' => $usedLength,
                'remaining_length' => max(0, self::ROLL_LENGTH - $usedLength),
                'assignments' => [$demand],
                'dp_best_fill_length' => $usedLength,
                'dp_waste_length' => max(0, self::ROLL_LENGTH - $usedLength),
                'used_area' => $usedLength * self::ROLL_WIDTH,
            ];
        }

        return $rolls;
    }

    private function runGreedySelection(array $demands): array
    {
        $virtualRolls = [];
        $unassignedDemands = array_values($demands);
        $priorityDemands = [];
        $priorityRank = 1;

        while (count($unassignedDemands) > 0) {
            $bestPlacement = $this->findBestPlacement($unassignedDemands, $virtualRolls);
            $demand = $unassignedDemands[$bestPlacement['demand_index']];

            if ($bestPlacement['roll_index'] === null) {
                $virtualRolls[] = [
                    'capacity_length' => self::ROLL_LENGTH,
                    'used_length' => 0,
                    'remaining_length' => self::ROLL_LENGTH,
                    'assignments' => [],
                ];
                $bestPlacement['roll_index'] = count($virtualRolls) - 1;
            }

            $demand['priority_rank'] = $priorityRank;
            $demand['priority_score'] = round($bestPlacement['utilization'], 4);
            $demand['greedy_roll_number'] = $bestPlacement['roll_index'] + 1;

            $virtualRolls[$bestPlacement['roll_index']]['assignments'][] = $demand;
            $virtualRolls[$bestPlacement['roll_index']]['used_length'] += $demand['used_length'];
            $virtualRolls[$bestPlacement['roll_index']]['remaining_length'] =
                $virtualRolls[$bestPlacement['roll_index']]['capacity_length'] - $virtualRolls[$bestPlacement['roll_index']]['used_length'];

            $priorityDemands[] = $demand;
            array_splice($unassignedDemands, $bestPlacement['demand_index'], 1);
            $priorityRank++;
        }

        return [
            'priority_demands' => $priorityDemands,
            'roll_count' => count($virtualRolls),
        ];
    }

    private function knapsackSelectAssignments(array $demands, int $capacity): array
    {
        $states = [0 => []];

        foreach ($demands as $index => $demand) {
            $weight = (int) round(((float) $demand['used_length']) * 100);
            $snapshot = $states;

            foreach ($snapshot as $sum => $selectedIndexes) {
                $newSum = $sum + $weight;
                if ($newSum > $capacity || isset($states[$newSum])) {
                    continue;
                }

                $states[$newSum] = [...$selectedIndexes, $index];
            }
        }

        $bestFill = 0;
        for ($sum = $capacity; $sum >= 0; $sum--) {
            if (isset($states[$sum])) {
                $bestFill = $sum;
                break;
            }
        }

        return [
            'best_fill_length' => $bestFill / 100,
            'selected_indexes' => $states[$bestFill] ?? [],
        ];
    }

    private function findBestPlacement(array $demands, array $rolls): array
    {
        $best = null;

        foreach ($demands as $demandIndex => $demand) {
            $demandLength = (float) $demand['used_length'];

            if ($demandLength <= self::ROLL_LENGTH) {
                $newRollUtilization = $demandLength / self::ROLL_LENGTH;
                $newRollRemainder = self::ROLL_LENGTH - $demandLength;

                if (
                    $best === null
                    || $newRollUtilization > $best['utilization']
                    || ($newRollUtilization === $best['utilization'] && $newRollRemainder < $best['remainder'])
                    || (
                        $newRollUtilization === $best['utilization']
                        && $newRollRemainder === $best['remainder']
                        && $demandLength > $best['demand_length']
                    )
                ) {
                    $best = [
                        'demand_index' => $demandIndex,
                        'roll_index' => null,
                        'utilization' => $newRollUtilization,
                        'remainder' => $newRollRemainder,
                        'demand_length' => $demandLength,
                    ];
                }
            }

            foreach ($rolls as $rollIndex => $roll) {
                if ($roll['remaining_length'] < $demandLength) {
                    continue;
                }

                $newUsedLength = $roll['used_length'] + $demandLength;
                $utilization = $newUsedLength / self::ROLL_LENGTH;
                $remainder = self::ROLL_LENGTH - $newUsedLength;

                if (
                    $best === null
                    || $utilization > $best['utilization']
                    || ($utilization === $best['utilization'] && $remainder < $best['remainder'])
                    || (
                        $utilization === $best['utilization']
                        && $remainder === $best['remainder']
                        && $demandLength > $best['demand_length']
                    )
                ) {
                    $best = [
                        'demand_index' => $demandIndex,
                        'roll_index' => $rollIndex,
                        'utilization' => $utilization,
                        'remainder' => $remainder,
                        'demand_length' => $demandLength,
                    ];
                }
            }
        }

        return $best;
    }
}
