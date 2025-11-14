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

        // 🔹 Agregando métricas do ad_metrics_daily para este workspace
        $row = DB::table('ad_metrics_daily')
            ->where('workspace_id', $workspace->id)
            ->selectRaw('
                COALESCE(SUM(spend), 0)          AS total_spend,
                COALESCE(SUM(clicks), 0)         AS total_clicks,
                COALESCE(SUM(conversions), 0)    AS total_conversions,
                COALESCE(SUM(revenue), 0)        AS total_revenue
            ')
            ->first();

        $metrics = [
            'totalSpend'   => (float) ($row->total_spend ?? 0),
            // por enquanto usamos clicks como "sessions" placeholder
            'sessions'     => (int) ($row->total_clicks ?? 0),
            'conversions'  => (int) ($row->total_conversions ?? 0),
            'revenue'      => (float) ($row->total_revenue ?? 0),
        ];

        return Inertia::render('Dashboard', [
            'metrics' => $metrics,
        ]);
    }
}
