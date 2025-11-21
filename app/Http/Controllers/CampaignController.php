<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
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

        // Carrega campanhas desse workspace via relacionamento com ad_accounts
        $campaigns = Campaign::query()
            ->with(['adAccount:id,name,provider,external_id'])
            ->whereHas('adAccount', function ($q) use ($workspace) {
                $q->where('workspace_id', $workspace->id);
            })
            ->orderBy('created_at', 'desc')
            ->get([
                'id',
                'ad_account_id',
                'provider',
                'external_id',
                'name',
                'status',
                'objective',
                'daily_budget',
                'created_at',
            ]);

        return Inertia::render('Campaigns/Index', [
            'workspace' => $workspace,
            'campaigns' => $campaigns,
        ]);
    }

    public function indexWithMetrics(Request $request)
    {
        $user = $request->user();

        $workspace = $user->workspaces()
            ->select('workspaces.id', 'workspaces.name', 'workspaces.slug')
            ->first();

        if (! $workspace) abort(403, 'Nenhum workspace.');

        // filtros de data
        $from = $request->query('from', now()->subDays(6)->toDateString());
        $to   = $request->query('to', now()->toDateString());

        // paginação
        $perPage = (int) $request->query('per_page', 15);

        // Query: campanhas do workspace + agregação na tabela ad_metrics_daily
        $query = DB::table('campaigns as c')
            ->join('ad_accounts as a', 'c.ad_account_id', 'a.id')
            ->leftJoin('ad_metrics_daily as m', function ($join) use ($workspace, $from, $to) {
                $join->on('m.campaign_id', '=', 'c.id')
                    ->where('m.workspace_id', '=', $workspace->id)
                    ->whereBetween('m.date', [$from, $to]);
            })
            ->where('a.workspace_id', $workspace->id)
            ->where('c.provider', 'meta') // opcional, remover se quiser todas
            ->groupBy('c.id', 'c.name', 'a.name', 'c.status')
            ->selectRaw('
            c.id,
            c.name as campaign_name,
            a.name as account_name,
            c.status,
            COALESCE(SUM(m.impressions),0) as impressions,
            COALESCE(SUM(m.clicks),0) as clicks,
            COALESCE(SUM(m.spend),0) as spend,
            COALESCE(SUM(m.conversions),0) as conversions,
            COALESCE(SUM(m.revenue),0) as revenue
        ')
            ->orderByDesc('spend');

        $paginated = $query->paginate($perPage)->withQueryString();

        // Calcula métricas derivadas por row (CTR, CPC, CPA, ROAS) no server-side
        $items = $paginated->getCollection()->transform(function ($row) {
            $ctr = ($row->impressions > 0) ? ($row->clicks / $row->impressions) * 100 : 0;
            $cpc = ($row->clicks > 0) ? ($row->spend / $row->clicks) : 0;
            $cpa = ($row->conversions > 0) ? ($row->spend / $row->conversions) : 0;
            $roas = ($row->spend > 0) ? ($row->revenue / $row->spend) : 0;

            return array_merge((array)$row, [
                'ctr' => round($ctr, 2),
                'cpc' => round($cpc, 2),
                'cpa' => round($cpa, 2),
                'roas' => round($roas, 2),
            ]);
        });

        // substituir collection e retornar via Inertia
        $paginated->setCollection($items);

        return Inertia::render('Campaigns/IndexWithMetrics', [
            'workspace' => $workspace,
            'campaigns' => $paginated,
            'filters'   => ['from' => $from, 'to' => $to],
        ]);
    }
}
