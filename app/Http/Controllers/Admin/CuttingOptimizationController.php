<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductRoll;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CuttingOptimizationController extends Controller
{
    private const ROLL_WIDTH = 2.0;
    private const ROLL_LENGTH = 100.0;

    public function index(): View
    {
        $orders = Order::query()
            ->with(['items.product', 'items.product.rolls'])
            ->where('order_status', Order::STATUS_DIBAYAR)
            ->latest('order_date')
            ->limit(100)
            ->get();

        $demands = $this->buildDemands($orders);
        $productRolls = ProductRoll::all()->map(fn($r) => [
            'id' => $r->id,
            'length' => (float) $r->length,
            'width' => (float) $r->width,
            'area' => (float) $r->area,
        ])->all();

        $rolls = $this->runGreedyAndDp($demands, $productRolls);
        $recommendations = $this->buildRecommendations($rolls);

        [$totalUsed, $totalCapacity] = $this->calculateUsage($rolls);

        return view('admin.cutting-optimization.index', [
            'orders' => $orders,
            'rolls' => $rolls,
            'recommendations' => $recommendations,
            'demandsCount' => count($demands),
            'totalUsed' => $totalUsed,
            'totalWaste' => max(0, $totalCapacity - $totalUsed),
            'rollLength' => self::ROLL_LENGTH,
            'rollWidth' => self::ROLL_WIDTH,
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $orders = Order::query()
            ->with(['items.product', 'items.product.rolls'])
            ->where('order_status', Order::STATUS_DIBAYAR)
            ->latest('order_date')
            ->limit(100)
            ->get();

        $demands = $this->buildDemands($orders);
        $productRolls = ProductRoll::all()->map(fn($r) => [
            'id' => $r->id,
            'length' => (float) $r->length,
            'width' => (float) $r->width,
            'area' => (float) $r->area,
        ])->all();
        $rolls = $this->runGreedyAndDp($demands, $productRolls);
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
                    $row['display_priority'] ?? $row['priority_rank'],
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
            $orderDateStr = $order->order_date instanceof \Carbon\Carbon
                ? $order->order_date->toDateTimeString()
                : (string) $order->order_date;

            foreach ($order->items as $item) {
                $totalArea = (float) $item->length * (float) $item->width * $item->quantity;

                // Pick the smallest roll that can still satisfy this item, so stock is not biased to the largest roll.
                $productRoll = null;
                $candidateRolls = $item->product?->rolls?->sortBy([
                    ['area', 'asc'],
                    ['length', 'asc'],
                    ['width', 'asc'],
                    ['id', 'asc'],
                ]) ?? collect();

                foreach ($candidateRolls as $candidateRoll) {
                    $candidateWidth = (float) $candidateRoll->width;
                    $candidateLength = (float) $candidateRoll->length;
                    $requiredLengthOnCandidate = $candidateWidth > 0.00001 ? ($totalArea / $candidateWidth) : 0;

                    if ($requiredLengthOnCandidate <= $candidateLength + 0.00001) {
                        $productRoll = $candidateRoll;
                        break;
                    }
                }

                if ($productRoll === null && $candidateRolls->isNotEmpty()) {
                    $productRoll = $candidateRolls->first();
                }

                $rollWidth = $productRoll ? (float) $productRoll->width : self::ROLL_WIDTH;
                $rollLength = $productRoll ? (float) $productRoll->length : self::ROLL_LENGTH;

                // Safety: ensure valid dimensions to prevent infinite loops / excessive chunks
                if ($rollWidth <= 0.00001 || $rollLength <= 0.00001) {
                    $rollWidth = self::ROLL_WIDTH;
                    $rollLength = self::ROLL_LENGTH;
                }

                // Convert total area to required length on that roll width.
                $requiredLength = $totalArea / $rollWidth;

                // Split into chunks so each segment can fit into a single roll of that product.
                $chunkIndex = 1;
                $maxChunksPerItem = 500; // hard limit to prevent memory explosion
                while ($requiredLength > 0.00001 && $chunkIndex <= $maxChunksPerItem) {
                    $segmentLength = min($rollLength, $requiredLength);
                    $segmentArea = $segmentLength * $rollWidth;

                    $demands[] = [
                        'order_id' => $order->id,
                        'order_code' => $order->order_code,
                        'order_date' => $orderDateStr,
                        'customer_name' => $order->customer_name,
                        'product_name' => $item->product_name,
                        'used_length' => $segmentLength,
                        'used_area' => $segmentArea,
                        'label' => $order->order_code . '-S' . $chunkIndex,
                        'product_roll_id' => $productRoll?->id,
                        'product_roll_width' => $rollWidth,
                        'product_roll_length' => $rollLength,
                        'item_length' => (float) $item->length,
                        'item_width' => (float) $item->width,
                        'item_quantity' => (int) $item->quantity,
                    ];

                    $requiredLength -= $segmentLength;
                    $chunkIndex++;
                }
            }
        }

        return $demands;
    }

    private function runGreedyAndDp(array $demands, array $availableProductRolls = []): array
    {
        $greedySelection = $this->runGreedySelection($demands, $availableProductRolls);
        $priorityDemands = $greedySelection['priority_demands'];
        $virtualRolls = $greedySelection['virtual_rolls'] ?? [];
        $selectedRollCount = count($virtualRolls);
        $remainingDemands = $priorityDemands;
        $rolls = [];

        // DP bekerja setelah greedy memilih jumlah roll dan prioritas demand.
        for ($rollNumber = 1; $rollNumber <= $selectedRollCount; $rollNumber++) {
            if (count($remainingDemands) === 0) {
                break;
            }

            $rollCapacity = $virtualRolls[$rollNumber - 1]['capacity_length'] ?? self::ROLL_LENGTH;
            $rollWidth = $virtualRolls[$rollNumber - 1]['width'] ?? self::ROLL_WIDTH;
            $rollProductId = $virtualRolls[$rollNumber - 1]['product_roll_id'] ?? null;

            // Filter demands to only those with matching product_roll_id
            $demandsForThisRoll = array_values(array_filter(
                $remainingDemands,
                fn($d) => ($d['product_roll_id'] ?? null) === $rollProductId
            ));

            $packResult = $this->knapsackSelectAssignments($demandsForThisRoll, (int) round($rollCapacity * 100), $rollWidth);
            $selectedIndexes = $packResult['selected_indexes'];

            if (count($selectedIndexes) === 0) {
                break;
            }

            $selectedMap = array_flip($selectedIndexes);
            $currentAssignments = [];
            $nextRemaining = [];

            foreach ($demandsForThisRoll as $index => $demand) {
                if (isset($selectedMap[$index])) {
                    $demand['assigned_roll_number'] = $rollNumber;
                    $currentAssignments[] = $demand;
                    continue;
                }

                $nextRemaining[] = $demand;
            }

            $remainingDemands = array_values(array_merge(
                array_values(array_filter($remainingDemands, fn($d) => ($d['product_roll_id'] ?? null) !== $rollProductId)),
                $nextRemaining
            ));

            usort($currentAssignments, fn($a, $b) => $a['priority_rank'] <=> $b['priority_rank']);
            $usedLength = 0;
            foreach ($currentAssignments as $ca) {
                $usedLength += ((float) $ca['used_area']) / $rollWidth;
            }

            $rolls[] = [
                'capacity_length' => $rollCapacity,
                'width' => $rollWidth,
                'used_length' => $usedLength,
                'remaining_length' => max(0, $rollCapacity - $usedLength),
                'assignments' => $currentAssignments,
                'dp_best_fill_length' => $packResult['best_fill_length'],
                'dp_waste_length' => max(0, $rollCapacity - $packResult['best_fill_length']),
                'used_area' => array_sum(array_column($currentAssignments, 'used_area')),
                'waste_area' => max(0, $rollCapacity - $usedLength) * $rollWidth,
            ];
        }

        // Safety net untuk edge case jika masih ada demand tersisa.
        while (count($remainingDemands) > 0) {
            $demand = array_shift($remainingDemands);
            $assigned = false;

            foreach ($rolls as $rollIndex => $roll) {
                // Ensure demand matches the product of this roll (check first assignment's product_roll_id)
                if (!empty($roll['assignments'])) {
                    $firstAssignmentProdRollId = $roll['assignments'][0]['product_roll_id'] ?? null;
                    $demandProdRollId = $demand['product_roll_id'] ?? null;
                    // Only allow assignment if product_roll_id matches (or both are null)
                    if ($firstAssignmentProdRollId !== $demandProdRollId) {
                        continue;
                    }
                }

                // compute how much length this demand would use on this roll's width
                $rollWidth = $roll['width'] ?? self::ROLL_WIDTH;
                $demandLenForThisRoll = ((float) $demand['used_area']) / $rollWidth;
                if ($roll['remaining_length'] < $demandLenForThisRoll) {
                    continue;
                }

                $demand['assigned_roll_number'] = $rollIndex + 1;
                $rolls[$rollIndex]['assignments'][] = $demand;
                $rolls[$rollIndex]['used_length'] += $demandLenForThisRoll;
                $rolls[$rollIndex]['remaining_length'] = max(0, $rolls[$rollIndex]['capacity_length'] - $rolls[$rollIndex]['used_length']);
                $rolls[$rollIndex]['used_area'] = array_sum(array_column($rolls[$rollIndex]['assignments'], 'used_area'));
                $rolls[$rollIndex]['waste_area'] = $rolls[$rollIndex]['remaining_length'] * ($rolls[$rollIndex]['width'] ?? self::ROLL_WIDTH);
                $rolls[$rollIndex]['dp_best_fill_length'] = $rolls[$rollIndex]['used_length'];
                $rolls[$rollIndex]['dp_waste_length'] = $rolls[$rollIndex]['remaining_length'];
                $assigned = true;
                break;
            }

            if ($assigned) {
                continue;
            }

            $demand['assigned_roll_number'] = count($rolls) + 1;
            $newRollCapacity = $demand['product_roll_length'] ?? self::ROLL_LENGTH;
            $newRollWidth = $demand['product_roll_width'] ?? self::ROLL_WIDTH;
            $usedLength = (float) ($demand['used_area'] / $newRollWidth);

            $rolls[] = [
                'capacity_length' => $newRollCapacity,
                'width' => $newRollWidth,
                'used_length' => $usedLength,
                'remaining_length' => max(0, $newRollCapacity - $usedLength),
                'assignments' => [$demand],
                'dp_best_fill_length' => $usedLength,
                'dp_waste_length' => max(0, $newRollCapacity - $usedLength),
                'used_area' => $demand['used_area'],
                'waste_area' => max(0, $newRollCapacity - $usedLength) * $newRollWidth,
            ];
        }

        return $rolls;
    }

    private function runGreedySelection(array $demands, array $availableProductRolls = []): array
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
                $capacity = $demand['product_roll_length'] ?? self::ROLL_LENGTH;
                $width = $demand['product_roll_width'] ?? self::ROLL_WIDTH;
                $prodRollId = $demand['product_roll_id'] ?? null;

                $virtualRolls[] = [
                    'capacity_length' => $capacity,
                    'width' => $width,
                    'product_roll_id' => $prodRollId,
                    'used_length' => 0,
                    'remaining_length' => $capacity,
                    'assignments' => [],
                ];
                $bestPlacement['roll_index'] = count($virtualRolls) - 1;
            }

            $demand['priority_rank'] = $priorityRank;
            $demand['priority_score'] = round($bestPlacement['utilization'], 4);
            $demand['greedy_roll_number'] = $bestPlacement['roll_index'] + 1;

            $virtualRolls[$bestPlacement['roll_index']]['assignments'][] = $demand;
            $usedLen = $demand['used_area'] / ($virtualRolls[$bestPlacement['roll_index']]['width'] ?? self::ROLL_WIDTH);
            $virtualRolls[$bestPlacement['roll_index']]['used_length'] += $usedLen;
            $virtualRolls[$bestPlacement['roll_index']]['remaining_length'] =
                $virtualRolls[$bestPlacement['roll_index']]['capacity_length'] - $virtualRolls[$bestPlacement['roll_index']]['used_length'];

            $priorityDemands[] = $demand;
            array_splice($unassignedDemands, $bestPlacement['demand_index'], 1);
            $priorityRank++;
        }

        return [
            'priority_demands' => $priorityDemands,
            'roll_count' => count($virtualRolls),
            'virtual_rolls' => $virtualRolls,
        ];
    }

    private function buildRecommendations(array $rolls): array
    {
        $rows = [];

        foreach ($rolls as $rollIndex => $roll) {
            $rollWidth = $roll['width'] ?? self::ROLL_WIDTH;
            $remaining = $roll['capacity_length'] ?? self::ROLL_LENGTH;
            foreach ($roll['assignments'] as $assignment) {
                $usedLengthOnRoll = $rollWidth > 0 ? ((float) $assignment['used_area']) / $rollWidth : (float) $assignment['used_length'];
                $remaining_before = $remaining;
                $remaining -= $usedLengthOnRoll;
                $remaining_after = max(0, $remaining);

                $rows[] = [
                    'priority_rank' => (int) ($assignment['priority_rank'] ?? 999999),
                    'roll_number' => $rollIndex + 1,
                    'order_id' => (int) ($assignment['order_id'] ?? 0),
                    'order_code' => $assignment['order_code'],
                    'order_date' => $assignment['order_date'] ?? null,
                    'customer_name' => $assignment['customer_name'],
                    'product_name' => $assignment['product_name'],
                    'used_length' => (float) $usedLengthOnRoll,
                    'used_area' => (float) $assignment['used_area'],
                    // roll_remaining_length now is the remaining AFTER this assignment so each row on same roll differs
                    'roll_remaining_length' => (float) $remaining_after,
                    'roll_remaining_before' => (float) $remaining_before,
                    'priority_score' => (float) ($assignment['priority_score'] ?? 0),
                ];
            }
        }

        usort($rows, function ($a, $b) {
            // Urutkan berdasarkan priority_rank (urutan greedy) agar sesuai rekomendasi algoritma
            $priorityCompare = ($a['priority_rank'] ?? 999999) <=> ($b['priority_rank'] ?? 999999);
            if ($priorityCompare !== 0) {
                return $priorityCompare;
            }

            // Jika priority sama, urutkan berdasarkan roll_number
            return ($a['roll_number'] ?? 0) <=> ($b['roll_number'] ?? 0);
        });

        foreach ($rows as $index => &$row) {
            $row['display_priority'] = $index + 1;
        }
        unset($row);

        return $rows;
    }

    private function knapsackSelectAssignments(array $demands, int $capacity, float $rollWidth = self::ROLL_WIDTH): array
    {
        $n = count($demands);

        // Safety fallback: if too many demands, skip DP and use greedy
        if ($n > 200) {
            return $this->greedyPackDemands($demands, $capacity, $rollWidth);
        }

        $weights = [];
        foreach ($demands as $demand) {
            $demandLengthForRoll = ((float) $demand['used_area']) / $rollWidth;
            $weights[] = (int) round($demandLengthForRoll * 100);
        }

        // Predecessor tracking: memory O(capacity) instead of O(capacity * n)
        $prevSum = array_fill(0, $capacity + 1, -1);
        $prevIdx = array_fill(0, $capacity + 1, -1);
        $reachable = array_fill(0, $capacity + 1, false);
        $reachable[0] = true;

        for ($i = 0; $i < $n; $i++) {
            $w = $weights[$i];
            if ($w <= 0) {
                continue;
            }
            // iterate backward for 0/1 knapsack
            for ($s = $capacity - $w; $s >= 0; $s--) {
                if ($reachable[$s] && ! $reachable[$s + $w]) {
                    $reachable[$s + $w] = true;
                    $prevSum[$s + $w] = $s;
                    $prevIdx[$s + $w] = $i;
                }
            }
        }

        $bestFill = 0;
        for ($s = $capacity; $s >= 0; $s--) {
            if ($reachable[$s]) {
                $bestFill = $s;
                break;
            }
        }

        // Reconstruct selected indexes by tracing back predecessors
        $selectedIndexes = [];
        $s = $bestFill;
        while ($s > 0 && $prevIdx[$s] !== -1) {
            $selectedIndexes[] = $prevIdx[$s];
            $s = $prevSum[$s];
        }

        return [
            'best_fill_length' => $bestFill / 100,
            'selected_indexes' => array_reverse($selectedIndexes),
        ];
    }

    /**
     * Greedy fallback when there are too many demands for DP.
     */
    private function greedyPackDemands(array $demands, int $capacity, float $rollWidth = self::ROLL_WIDTH): array
    {
        // Sort by weight descending (best fit for roll capacity)
        $indexedDemands = [];
        foreach ($demands as $index => $demand) {
            $demandLengthForRoll = ((float) $demand['used_area']) / $rollWidth;
            $indexedDemands[] = [
                'index' => $index,
                'weight' => (int) round($demandLengthForRoll * 100),
            ];
        }

        usort($indexedDemands, fn($a, $b) => $b['weight'] <=> $a['weight']);

        $selectedIndexes = [];
        $currentSum = 0;

        foreach ($indexedDemands as $item) {
            if ($item['weight'] <= 0) {
                $selectedIndexes[] = $item['index'];
                continue;
            }
            if ($currentSum + $item['weight'] <= $capacity) {
                $selectedIndexes[] = $item['index'];
                $currentSum += $item['weight'];
            }
        }

        return [
            'best_fill_length' => $currentSum / 100,
            'selected_indexes' => $selectedIndexes,
        ];
    }

    private function findBestPlacement(array $demands, array $rolls): array
    {
        $best = null;

        foreach ($demands as $demandIndex => $demand) {
            // compute demand length in meters as stored (may be tied to its product roll)
            $demandLength = (float) $demand['used_length'];

            // Option to create a new virtual roll: prefer product's roll length if available
            $candidateCapacity = $demand['product_roll_length'] ?? self::ROLL_LENGTH;
            $candidateWidth = $demand['product_roll_width'] ?? self::ROLL_WIDTH;
            $demandLenOnCandidate = ((float) $demand['used_area']) / $candidateWidth;

            if ($demandLenOnCandidate <= $candidateCapacity) {
                $newRollUtilization = $demandLenOnCandidate / $candidateCapacity;
                $newRollRemainder = $candidateCapacity - $demandLenOnCandidate;

                if (
                    $best === null
                    || $newRollUtilization > $best['utilization']
                    || ($newRollUtilization === $best['utilization'] && $newRollRemainder < $best['remainder'])
                    || (
                        $newRollUtilization === $best['utilization']
                        && $newRollRemainder === $best['remainder']
                        && $demandLenOnCandidate > $best['demand_length']
                    )
                ) {
                    $best = [
                        'demand_index' => $demandIndex,
                        'roll_index' => null,
                        'utilization' => $newRollUtilization,
                        'remainder' => $newRollRemainder,
                        'demand_length' => $demandLenOnCandidate,
                    ];
                }
            }

            foreach ($rolls as $rollIndex => $roll) {
                // compute demand length in the context of this roll's width
                $rollWidth = $roll['width'] ?? self::ROLL_WIDTH;
                $dLenForThisRoll = ((float) $demand['used_area']) / $rollWidth;

                if ($roll['remaining_length'] < $dLenForThisRoll) {
                    continue;
                }

                $newUsedLength = $roll['used_length'] + $dLenForThisRoll;
                $utilization = $newUsedLength / ($roll['capacity_length'] ?? self::ROLL_LENGTH);
                $remainder = ($roll['capacity_length'] ?? self::ROLL_LENGTH) - $newUsedLength;

                if (
                    $best === null
                    || $utilization > $best['utilization']
                    || ($utilization === $best['utilization'] && $remainder < $best['remainder'])
                    || (
                        $utilization === $best['utilization']
                        && $remainder === $best['remainder']
                        && $dLenForThisRoll > $best['demand_length']
                    )
                ) {
                    $best = [
                        'demand_index' => $demandIndex,
                        'roll_index' => $rollIndex,
                        'utilization' => $utilization,
                        'remainder' => $remainder,
                        'demand_length' => $dLenForThisRoll,
                    ];
                }
            }
        }

        return $best;
    }

    private function calculateUsage(array $rolls): array
    {
        $totalUsed = array_sum(array_column($rolls, 'used_length'));
        $totalCapacity = array_sum(array_column($rolls, 'capacity_length')) ?: (count($rolls) * self::ROLL_LENGTH);

        return [$totalUsed, $totalCapacity];
    }
}
