<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CuttingOptimizationController extends Controller
{
    private const ROLL_WIDTH = 2.0;
    private const ROLL_LENGTH = 100.0;

    public function index(): View
    {
        $orders = Order::query()
            ->with('items')
            ->where('order_status', Order::STATUS_DIBAYAR)
            ->latest('order_date')
            ->get();

        $demands = $this->buildDemands($orders);
        $rolls = $this->runGreedyAndDp($demands);
        $recommendations = $this->buildRecommendations($rolls);

        [$totalUsed, $totalCapacity] = $this->calculateUsage($rolls);

        return view('admin.cutting-optimization.index', [
            'orders' => $orders,
            'rolls' => $rolls,
            'recommendations' => $recommendations,
            'demandsCount' => count($demands),
            'usedPercent' => $totalCapacity > 0 ? ($totalUsed / $totalCapacity) * 100 : 0,
            'wastePercent' => $totalCapacity > 0 ? (($totalCapacity - $totalUsed) / $totalCapacity) * 100 : 0,
            'totalUsed' => $totalUsed,
            'totalWaste' => max(0, $totalCapacity - $totalUsed),
            'rollLength' => self::ROLL_LENGTH,
            'rollWidth' => self::ROLL_WIDTH,
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $orders = Order::query()
            ->with('items')
            ->where('order_status', Order::STATUS_DIBAYAR)
            ->latest('order_date')
            ->get();

        $demands = $this->buildDemands($orders);
        $rolls = $this->runGreedyAndDp($demands);
        $recommendations = $this->buildRecommendations($rolls);

        return response()->streamDownload(function () use ($recommendations) {
            $output = fopen('php://output', 'w');
            fputcsv($output, [
                'Prioritas',
                'Roll',
                'Order Code',
                'Pelanggan',
                'Produk',
                'Terpakai (m)',
                'Terpakai (m2)',
                'Sisa Roll (m)',
            ]);

            foreach ($recommendations as $row) {
                fputcsv($output, [
                    $row['priority_rank'],
                    'Roll #' . $row['roll_number'],
                    $row['order_code'],
                    $row['customer_name'],
                    $row['product_name'],
                    number_format($row['used_length'], 2, '.', ''),
                    number_format($row['used_area'], 2, '.', ''),
                    number_format($row['roll_remaining_length'], 2, '.', ''),
                ]);
            }

            fclose($output);
        }, 'cutting-optimization-' . now()->format('Ymd-His') . '.csv');
    }

    private function buildDemands($orders): array
    {
        $demands = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $totalArea = (float) $item->length * (float) $item->width * $item->quantity;
                $requiredLength = $totalArea / self::ROLL_WIDTH;

                // Split into chunks so each segment can fit into a single roll.
                $chunkIndex = 1;
                while ($requiredLength > 0) {
                    $segmentLength = min(self::ROLL_LENGTH, $requiredLength);
                    $segmentArea = $segmentLength * self::ROLL_WIDTH;

                    $demands[] = [
                        'order_id' => $order->id,
                        'order_code' => $order->order_code,
                        'customer_name' => $order->customer_name,
                        'product_name' => $item->product_name,
                        'used_length' => $segmentLength,
                        'used_area' => $segmentArea,
                        'label' => $order->order_code . '-S' . $chunkIndex,
                    ];

                    $requiredLength -= $segmentLength;
                    $chunkIndex++;
                }
            }
        }

        return $demands;
    }

    private function runGreedyAndDp(array $demands): array
    {
        $greedySelection = $this->runGreedySelection($demands);
        $priorityDemands = $greedySelection['priority_demands'];
        $selectedRollCount = $greedySelection['roll_count'];
        $remainingDemands = $priorityDemands;
        $rolls = [];

        // DP bekerja setelah greedy memilih jumlah roll dan prioritas demand.
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
                'waste_area' => max(0, self::ROLL_LENGTH - $usedLength) * self::ROLL_WIDTH,
            ];

            $remainingDemands = array_values($nextRemaining);
        }

        // Safety net untuk edge case jika masih ada demand tersisa.
        while (count($remainingDemands) > 0) {
            $demand = array_shift($remainingDemands);
            $assigned = false;

            foreach ($rolls as $rollIndex => $roll) {
                if ($roll['remaining_length'] < $demand['used_length']) {
                    continue;
                }

                $demand['assigned_roll_number'] = $rollIndex + 1;
                $rolls[$rollIndex]['assignments'][] = $demand;
                $rolls[$rollIndex]['used_length'] += $demand['used_length'];
                $rolls[$rollIndex]['remaining_length'] = max(0, self::ROLL_LENGTH - $rolls[$rollIndex]['used_length']);
                $rolls[$rollIndex]['used_area'] = $rolls[$rollIndex]['used_length'] * self::ROLL_WIDTH;
                $rolls[$rollIndex]['waste_area'] = $rolls[$rollIndex]['remaining_length'] * self::ROLL_WIDTH;
                $rolls[$rollIndex]['dp_best_fill_length'] = $rolls[$rollIndex]['used_length'];
                $rolls[$rollIndex]['dp_waste_length'] = $rolls[$rollIndex]['remaining_length'];
                $assigned = true;
                break;
            }

            if ($assigned) {
                continue;
            }

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
                'waste_area' => max(0, self::ROLL_LENGTH - $usedLength) * self::ROLL_WIDTH,
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

        // Greedy Heuristic:
        // 1) Tentukan prioritas potong berdasar efisiensi penggunaan roll.
        // 2) Tentukan roll terbaik untuk demand prioritas tersebut.
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

    private function buildRecommendations(array $rolls): array
    {
        $rows = [];

        foreach ($rolls as $rollIndex => $roll) {
            foreach ($roll['assignments'] as $assignment) {
                $rows[] = [
                    'priority_rank' => (int) ($assignment['priority_rank'] ?? 999999),
                    'roll_number' => $rollIndex + 1,
                    'order_code' => $assignment['order_code'],
                    'customer_name' => $assignment['customer_name'],
                    'product_name' => $assignment['product_name'],
                    'used_length' => (float) $assignment['used_length'],
                    'used_area' => (float) $assignment['used_area'],
                    'roll_remaining_length' => (float) $roll['remaining_length'],
                    'priority_score' => (float) ($assignment['priority_score'] ?? 0),
                ];
            }
        }

        usort($rows, fn($a, $b) => $a['priority_rank'] <=> $b['priority_rank']);

        return $rows;
    }

    private function knapsackSelectAssignments(array $demands, int $capacity): array
    {
        $states = [
            0 => [],
        ];

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

            // Opsi buat roll baru selalu ada selama demand bisa masuk kapasitas roll.
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

    private function calculateUsage(array $rolls): array
    {
        $totalUsed = array_sum(array_column($rolls, 'used_length'));
        $totalCapacity = count($rolls) * self::ROLL_LENGTH;

        return [$totalUsed, $totalCapacity];
    }
}
