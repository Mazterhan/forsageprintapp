<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\OrderProposal;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $timezone = 'Europe/Kiev';
        $now = now($timezone);

        $period = (string) $request->query('period', 'mtd');
        if (!in_array($period, ['all', 'ytd', 'mtd', 'wtd', 'custom'], true)) {
            $period = 'mtd';
        }

        $from = null;
        $to = null;
        $periodError = null;

        if ($period !== 'all') {
            if ($period === 'ytd') {
                $from = $now->copy()->startOfYear()->startOfDay();
                $to = $now->copy()->endOfDay();
            } elseif ($period === 'wtd') {
                $from = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                $to = $now->copy()->endOfDay();
            } elseif ($period === 'custom') {
                $fromInput = trim((string) $request->query('from', ''));
                $toInput = trim((string) $request->query('to', ''));

                if ($fromInput !== '' && $toInput !== '') {
                    try {
                        $from = Carbon::createFromFormat('Y-m-d', $fromInput, $timezone)->startOfDay();
                        $to = Carbon::createFromFormat('Y-m-d', $toInput, $timezone)->endOfDay();
                    } catch (\Throwable) {
                        $periodError = 'Некоректно вказано період. Вкажіть дати у форматі РРРР-ММ-ДД.';
                    }
                } else {
                    $periodError = 'Для кастомного періоду потрібно вказати обидві дати: "Від" і "До".';
                }

                if ($from && $to && $from->gt($to)) {
                    $periodError = 'Дата "Від" не може бути пізніше дати "До".';
                }

                if ($periodError) {
                    $period = 'mtd';
                    $from = $now->copy()->startOfMonth()->startOfDay();
                    $to = $now->copy()->endOfDay();
                }
            } else {
                $from = $now->copy()->startOfMonth()->startOfDay();
                $to = $now->copy()->endOfDay();
            }
        }

        $selectedClientIds = collect((array) $request->query('client_id', []))
            ->filter(static fn ($value) => is_numeric($value))
            ->map(static fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        $clients = Client::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $clientsById = $clients->keyBy('id');
        $clientIdByName = [];
        foreach ($clients as $client) {
            $normalized = mb_strtolower(trim((string) $client->name), 'UTF-8');
            if ($normalized !== '' && !array_key_exists($normalized, $clientIdByName)) {
                $clientIdByName[$normalized] = (int) $client->id;
            }
        }

        $query = OrderProposal::query()
            ->with('user:id,name')
            ->whereNull('deleted_date');

        if ($from && $to) {
            $fromUtc = $from->copy()->utc();
            $toUtc = $to->copy()->utc();

            $query->where(function ($outer) use ($fromUtc, $toUtc) {
                $outer
                    ->where(function ($q) use ($fromUtc, $toUtc) {
                        $q->where('corrections_count', '>', 0)
                            ->whereBetween('updated_at', [$fromUtc, $toUtc]);
                    })
                    ->orWhere(function ($q) use ($fromUtc, $toUtc) {
                        $q->where(function ($inner) {
                            $inner->whereNull('corrections_count')->orWhere('corrections_count', '<=', 0);
                        })->whereBetween('created_at', [$fromUtc, $toUtc]);
                    });
            });
        }

        $proposals = $query->get();

        $rows = [];
        foreach ($proposals as $proposal) {
            $payload = is_array($proposal->payload ?? null) ? $proposal->payload : [];
            $summary = Arr::get($payload, 'summary', []);
            $summary = is_array($summary) ? $summary : [];

            $stateClientId = Arr::get($payload, 'client_id');
            $stateClientId = is_numeric($stateClientId) ? (int) $stateClientId : null;

            $fallbackName = trim((string) (Arr::get($payload, 'client_name', '') ?: ($proposal->client_name ?? '')));
            $normalizedFallbackName = mb_strtolower($fallbackName, 'UTF-8');

            $resolvedClientId = $stateClientId;
            if (!$resolvedClientId && $normalizedFallbackName !== '' && isset($clientIdByName[$normalizedFallbackName])) {
                $resolvedClientId = $clientIdByName[$normalizedFallbackName];
            }

            if (!empty($selectedClientIds) && !in_array($resolvedClientId, $selectedClientIds, true)) {
                continue;
            }

            $resolvedClientName = $resolvedClientId && $clientsById->has($resolvedClientId)
                ? (string) $clientsById->get($resolvedClientId)->name
                : $fallbackName;

            $correctionsCount = (int) ($proposal->corrections_count ?? 0);
            $workingDate = $correctionsCount > 0
                ? optional($proposal->updated_at)
                : optional($proposal->created_at);
            $workingDateKyiv = $workingDate ? $workingDate->copy()->timezone($timezone) : null;

            $orderTotal = (float) ($proposal->total_cost ?? Arr::get($summary, 'order_total', 0));
            $orderTotalBeforeMinimum = (float) Arr::get($summary, 'order_total_before_minimum', $orderTotal);

            $minimumOrderApplied = $this->toBool(Arr::get($summary, 'minimum_order_applied', false));
            $minimumProductsApplied = $this->toBool(Arr::get($summary, 'minimum_products_applied', false));
            $hasWarnings = $this->toBool(Arr::get($summary, 'has_warnings', false));

            $products = Arr::get($payload, 'products', []);
            $products = is_array($products) ? $products : [];

            $productsCostSum = 0.0;
            $servicesCostSum = 0.0;
            $productTypeStats = [];
            $materialStats = [];
            $serviceStats = [];

            foreach ($products as $product) {
                if (!is_array($product)) {
                    continue;
                }

                $positionsCost = (float) ($product['positions_cost'] ?? 0);
                $servicesCost = (float) ($product['services_cost'] ?? 0);
                $productsCostSum += $positionsCost;
                $servicesCostSum += $servicesCost;

                $typeName = trim((string) ($product['productTypeName'] ?? ''));
                if ($typeName !== '') {
                    if (!isset($productTypeStats[$typeName])) {
                        $productTypeStats[$typeName] = ['count' => 0, 'sum' => 0.0];
                    }
                    $productTypeStats[$typeName]['count'] += 1;
                    $productTypeStats[$typeName]['sum'] += (float) ($product['total_cost'] ?? ($positionsCost + $servicesCost));
                }

                $materialName = trim((string) ($product['material'] ?? ''));
                if ($materialName !== '') {
                    if (!isset($materialStats[$materialName])) {
                        $materialStats[$materialName] = ['count' => 0, 'sum' => 0.0];
                    }
                    $materialStats[$materialName]['count'] += 1;
                    $materialStats[$materialName]['sum'] += (float) ($product['total_cost'] ?? ($positionsCost + $servicesCost));
                }

                $serviceRows = $product['service_rows'] ?? [];
                if (!is_array($serviceRows)) {
                    $serviceRows = [];
                }

                foreach ($serviceRows as $serviceRow) {
                    if (!is_array($serviceRow)) {
                        continue;
                    }
                    $serviceName = trim((string) ($serviceRow['name'] ?? ''));
                    $serviceCost = (float) ($serviceRow['cost'] ?? 0);
                    if ($serviceName === '' || $serviceCost <= 0) {
                        continue;
                    }
                    if (!isset($serviceStats[$serviceName])) {
                        $serviceStats[$serviceName] = ['count' => 0, 'sum' => 0.0];
                    }
                    $serviceStats[$serviceName]['count'] += 1;
                    $serviceStats[$serviceName]['sum'] += $serviceCost;
                }
            }

            $rows[] = [
                'proposal_id' => (int) $proposal->id,
                'proposal_number' => (string) $proposal->proposal_number,
                'working_date' => $workingDateKyiv,
                'working_date_key' => $workingDateKyiv ? $workingDateKyiv->format('Y-m-d') : null,
                'user_name' => trim((string) ($proposal->user?->name ?? '—')),
                'client_id' => $resolvedClientId,
                'client_name' => $resolvedClientName,
                'total_cost' => $orderTotal,
                'total_cost_before_minimum' => $orderTotalBeforeMinimum,
                'corrections_count' => $correctionsCount,
                'minimum_applied' => $minimumOrderApplied || $minimumProductsApplied,
                'has_warnings' => $hasWarnings,
                'products_cost_sum' => $productsCostSum,
                'services_cost_sum' => $servicesCostSum,
                'product_type_stats' => $productTypeStats,
                'material_stats' => $materialStats,
                'service_stats' => $serviceStats,
            ];
        }

        $proposalCount = count($rows);
        $totalRevenue = array_sum(array_map(static fn ($row) => (float) $row['total_cost'], $rows));
        $averageCheck = $proposalCount > 0 ? $totalRevenue / $proposalCount : 0.0;
        $medianCheck = $this->calculateMedian(array_map(static fn ($row) => (float) $row['total_cost'], $rows));

        $uniqueClients = [];
        foreach ($rows as $row) {
            if ($row['client_id']) {
                $uniqueClients['id:'.$row['client_id']] = true;
            } elseif (trim((string) $row['client_name']) !== '') {
                $uniqueClients['name:'.mb_strtolower(trim((string) $row['client_name']), 'UTF-8')] = true;
            }
        }

        $totalCorrections = array_sum(array_map(static fn ($row) => (int) $row['corrections_count'], $rows));
        $withCorrections = count(array_filter($rows, static fn ($row) => (int) $row['corrections_count'] > 0));
        $withMinimum = count(array_filter($rows, static fn ($row) => (bool) $row['minimum_applied']));
        $withWarnings = count(array_filter($rows, static fn ($row) => (bool) $row['has_warnings']));

        $dailyMap = [];
        $managerMap = [];
        $clientMap = [];
        $productTypeMap = [];
        $materialMap = [];
        $serviceMap = [];

        foreach ($rows as $row) {
            $dateKey = $row['working_date_key'];
            if ($dateKey) {
                if (!isset($dailyMap[$dateKey])) {
                    $dailyMap[$dateKey] = [
                        'count' => 0,
                        'sum' => 0.0,
                        'corrections' => 0,
                    ];
                }
                $dailyMap[$dateKey]['count'] += 1;
                $dailyMap[$dateKey]['sum'] += (float) $row['total_cost'];
                $dailyMap[$dateKey]['corrections'] += (int) $row['corrections_count'];
            }

            $managerKey = $row['user_name'] !== '' ? $row['user_name'] : '—';
            if (!isset($managerMap[$managerKey])) {
                $managerMap[$managerKey] = ['name' => $managerKey, 'count' => 0, 'sum' => 0.0, 'corrections' => 0];
            }
            $managerMap[$managerKey]['count'] += 1;
            $managerMap[$managerKey]['sum'] += (float) $row['total_cost'];
            $managerMap[$managerKey]['corrections'] += (int) $row['corrections_count'];

            $clientKey = $row['client_id']
                ? 'id:'.$row['client_id']
                : 'name:'.mb_strtolower(trim((string) $row['client_name']), 'UTF-8');
            $clientName = trim((string) $row['client_name']) !== '' ? (string) $row['client_name'] : '—';
            if (!isset($clientMap[$clientKey])) {
                $clientMap[$clientKey] = ['name' => $clientName, 'count' => 0, 'sum' => 0.0, 'corrections' => 0, 'warnings' => 0];
            }
            $clientMap[$clientKey]['count'] += 1;
            $clientMap[$clientKey]['sum'] += (float) $row['total_cost'];
            $clientMap[$clientKey]['corrections'] += (int) $row['corrections_count'];
            $clientMap[$clientKey]['warnings'] += $row['has_warnings'] ? 1 : 0;

            foreach ($row['product_type_stats'] as $name => $stat) {
                if (!isset($productTypeMap[$name])) {
                    $productTypeMap[$name] = ['name' => $name, 'count' => 0, 'sum' => 0.0];
                }
                $productTypeMap[$name]['count'] += (int) ($stat['count'] ?? 0);
                $productTypeMap[$name]['sum'] += (float) ($stat['sum'] ?? 0);
            }

            foreach ($row['material_stats'] as $name => $stat) {
                if (!isset($materialMap[$name])) {
                    $materialMap[$name] = ['name' => $name, 'count' => 0, 'sum' => 0.0];
                }
                $materialMap[$name]['count'] += (int) ($stat['count'] ?? 0);
                $materialMap[$name]['sum'] += (float) ($stat['sum'] ?? 0);
            }

            foreach ($row['service_stats'] as $name => $stat) {
                if (!isset($serviceMap[$name])) {
                    $serviceMap[$name] = ['name' => $name, 'count' => 0, 'sum' => 0.0];
                }
                $serviceMap[$name]['count'] += (int) ($stat['count'] ?? 0);
                $serviceMap[$name]['sum'] += (float) ($stat['sum'] ?? 0);
            }
        }

        $labels = [];
        $ordersSeries = [];
        $revenueSeries = [];
        $avgSeries = [];
        $correctionsSeries = [];

        if ($proposalCount > 0) {
            if ($from && $to) {
                $periodRange = CarbonPeriod::create($from->copy()->startOfDay(), '1 day', $to->copy()->startOfDay());
                foreach ($periodRange as $day) {
                    $key = $day->format('Y-m-d');
                    $dayData = $dailyMap[$key] ?? ['count' => 0, 'sum' => 0.0, 'corrections' => 0];
                    $labels[] = $day->format('d.m');
                    $ordersSeries[] = (int) $dayData['count'];
                    $revenueSeries[] = round((float) $dayData['sum'], 2);
                    $avgSeries[] = (int) $dayData['count'] > 0 ? round(((float) $dayData['sum']) / ((int) $dayData['count']), 2) : 0;
                    $correctionsSeries[] = (int) $dayData['corrections'];
                }
            } else {
                ksort($dailyMap);
                foreach ($dailyMap as $key => $dayData) {
                    $day = Carbon::createFromFormat('Y-m-d', $key, $timezone);
                    $labels[] = $day->format('d.m');
                    $ordersSeries[] = (int) $dayData['count'];
                    $revenueSeries[] = round((float) $dayData['sum'], 2);
                    $avgSeries[] = (int) $dayData['count'] > 0 ? round(((float) $dayData['sum']) / ((int) $dayData['count']), 2) : 0;
                    $correctionsSeries[] = (int) $dayData['corrections'];
                }
            }
        }

        $productsRevenue = array_sum(array_map(static fn ($row) => (float) $row['products_cost_sum'], $rows));
        $servicesRevenue = array_sum(array_map(static fn ($row) => (float) $row['services_cost_sum'], $rows));

        $topClients = collect(array_values($clientMap))->sortByDesc('sum')->take(10)->values();
        $topManagers = collect(array_values($managerMap))->sortByDesc('sum')->take(10)->values();
        $topProductTypes = collect(array_values($productTypeMap))->sortByDesc('sum')->take(10)->values();
        $topMaterials = collect(array_values($materialMap))->sortByDesc('sum')->take(10)->values();
        $topServices = collect(array_values($serviceMap))
            ->map(function ($item) {
                $count = (int) ($item['count'] ?? 0);
                $sum = (float) ($item['sum'] ?? 0);
                $item['avg'] = $count > 0 ? round($sum / $count, 2) : 0;
                return $item;
            })
            ->sortByDesc('sum')
            ->take(10)
            ->values();

        return view('dashboard', [
            'filters' => [
                'period' => $period,
                'from' => $from ? $from->format('Y-m-d') : '',
                'to' => $to ? $to->format('Y-m-d') : '',
                'client_id' => $selectedClientIds,
            ],
            'periodError' => $periodError,
            'clients' => $clients,
            'kpi' => [
                'proposal_count' => $proposalCount,
                'total_revenue' => round($totalRevenue, 2),
                'average_check' => round($averageCheck, 2),
                'median_check' => round($medianCheck, 2),
                'unique_clients' => count($uniqueClients),
                'with_corrections' => $withCorrections,
                'with_corrections_share' => $proposalCount > 0 ? round(($withCorrections / $proposalCount) * 100, 2) : 0,
                'total_corrections' => $totalCorrections,
                'with_minimum' => $withMinimum,
                'with_warnings' => $withWarnings,
                'with_warnings_share' => $proposalCount > 0 ? round(($withWarnings / $proposalCount) * 100, 2) : 0,
                'products_revenue' => round($productsRevenue, 2),
                'services_revenue' => round($servicesRevenue, 2),
            ],
            'series' => [
                'labels' => $labels,
                'orders' => $ordersSeries,
                'revenue' => $revenueSeries,
                'avg' => $avgSeries,
                'corrections' => $correctionsSeries,
            ],
            'topClients' => $topClients,
            'topManagers' => $topManagers,
            'topProductTypes' => $topProductTypes,
            'topMaterials' => $topMaterials,
            'topServices' => $topServices,
        ]);
    }

    private function calculateMedian(array $values): float
    {
        $values = array_values(array_filter($values, static fn ($v) => is_numeric($v)));
        $count = count($values);

        if ($count === 0) {
            return 0.0;
        }

        sort($values, SORT_NUMERIC);
        $middle = (int) floor($count / 2);

        if ($count % 2 === 0) {
            return ((float) $values[$middle - 1] + (float) $values[$middle]) / 2;
        }

        return (float) $values[$middle];
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value > 0;
        }

        if (is_string($value)) {
            $normalized = mb_strtolower(trim($value), 'UTF-8');
            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
