<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
}
