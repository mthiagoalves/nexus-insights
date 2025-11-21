<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $workspace = $user->workspaces()
            ->select('workspaces.id', 'workspaces.name', 'workspaces.slug')
            ->first();

        if (! $workspace) {
            abort(403, 'Nenhum workspace encontrado para este usuário.');
        }

        // parâmetros de filtro (padrão: últimos 7 dias)
        $from = $request->query('from', now()->subDays(6)->toDateString()); // 7 dias incluindo hoje
        $to   = $request->query('to', now()->toDateString());

        // KPIs totais no período
        $kpi = DB::table('ad_metrics_daily')
            ->where('workspace_id', $workspace->id)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('
                COALESCE(SUM(spend), 0) AS total_spend,
                COALESCE(SUM(clicks), 0) AS total_clicks,
                COALESCE(SUM(conversions), 0) AS total_conversions,
                COALESCE(SUM(revenue), 0) AS total_revenue
            ')
            ->first();

        $metricsSummary = [
            'totalSpend'  => (float) ($kpi->total_spend ?? 0),
            'sessions'    => (int) ($kpi->total_clicks ?? 0),
            'conversions' => (int) ($kpi->total_conversions ?? 0),
            'revenue'     => (float) ($kpi->total_revenue ?? 0),
        ];

        // Série diária (agregada por date)
        $series = DB::table('ad_metrics_daily')
            ->where('workspace_id', $workspace->id)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('date, COALESCE(SUM(spend),0) as spend, COALESCE(SUM(revenue),0) as revenue, COALESCE(SUM(impressions),0) as impressions, COALESCE(SUM(clicks),0) as clicks')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($row) {
                return [
                    'date' => $row->date,
                    'spend' => (float) $row->spend,
                    'revenue' => (float) $row->revenue,
                    'impressions' => (int) $row->impressions,
                    'clicks' => (int) $row->clicks,
                ];
            });

        return Inertia::render('Dashboard', [
            'metrics' => $metricsSummary,
            'series'  => $series,
            'filters' => [
                'from' => $from,
                'to'   => $to,
            ],
        ]);
    }
}
