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
                    $pieceWidth = (float) ($assignment['piece_width'] ?? self::ROLL_WIDTH);

                    $availableRolls = ProductRoll::query()
                        ->where('product_id', $productId)
                        ->where('width', $pieceWidth)
                        ->where('area', '>', 0)
                        ->orderBy('length', 'asc')
                        ->get();

                    // If there is no exact width match, allow a wider roll that can still
                    // host this strip. This supports width splitting such as 7 -> 2 + 2 + 2 + 1.
                    if ($availableRolls->isEmpty()) {
                        $availableRolls = ProductRoll::query()
                            ->where('product_id', $productId)
                            ->where('area', '>', 0)
                            ->where('width', '>=', $pieceWidth)
                            ->orderBy('width', 'asc')
                            ->orderBy('length', 'asc')
                            ->get();

                        if ($availableRolls->isEmpty()) {
                            $availableRolls = ProductRoll::query()
                                ->where('product_id', $productId)
                                ->where('area', '>', 0)
                                ->orderByRaw('ABS(width - ?) ASC', [$pieceWidth])
                                ->orderBy('length', 'asc')
                                ->get();
                        }
                    }

                    $usedFromRolls = [];

                    foreach ($availableRolls as $pr) {
                        if ($neededArea <= 0) {
                            break;
                        }

                        /** @var ProductRoll $pr */
                        $take = min($pr->area, $neededArea);
                        if ($take <= 0) {
                            continue;
                        }

                        $before = (float) $pr->area;
                        $currentArea = (float) $pr->area;
                        $currentWidth = (float) $pr->width;
                        $afterArea = max(0, $currentArea - $take);
                        $afterLength = $currentWidth > 0 ? round($afterArea / $currentWidth, 2) : 0;

                        ProductRoll::query()
                            ->whereKey($pr->id)
                            ->update([
                                'area' => $afterArea,
                                'length' => $afterLength,
                            ]);

                        $neededArea -= $take;

                        $usedFromRolls[] = [
                            'product_roll_id' => $pr->id,
                            'taken_area' => $take,
                            'before_area' => $before,
                            'after_area' => $afterArea,
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
            $pieceLength = (float) $item->length;
            $pieceWidth = (float) $item->width;
            $widthStrips = $this->splitWidthIntoStrips($pieceWidth);

            for ($pieceIndex = 1; $pieceIndex <= (int) $item->quantity; $pieceIndex++) {
                foreach ($widthStrips as $widthIndex => $stripWidth) {
                    $remainingLength = $pieceLength;
                    $segmentIndex = 1;

                    while ($remainingLength > 0) {
                        $segmentLength = min(self::ROLL_LENGTH, $remainingLength);

                        $demands[] = [
                            'order_id' => $order->id,
                            'order_code' => $order->order_code,
                            'product_id' => $item->product_id,
                            'product_name' => $item->product_name,
                            'piece_width' => $stripWidth,
                            'original_piece_width' => $pieceWidth,
                            'piece_length' => $pieceLength,
                            'used_length' => $segmentLength,
                            'used_area' => $segmentLength * $stripWidth,
                            'label' => $order->order_code . '-P' . $pieceIndex . '-W' . ($widthIndex + 1) . '-S' . $segmentIndex,
                        ];

                        $remainingLength -= $segmentLength;
                        $segmentIndex++;
                    }
                }
            }
        }

        return $demands;
    }

    private function splitWidthIntoStrips(float $pieceWidth): array
    {
        $strips = [];
        $remainingWidth = $pieceWidth;

        while ($remainingWidth > 0) {
            $stripWidth = min(self::ROLL_WIDTH, $remainingWidth);
            $strips[] = round($stripWidth, 2);
            $remainingWidth -= $stripWidth;
        }

        return $strips ?: [$pieceWidth];
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
