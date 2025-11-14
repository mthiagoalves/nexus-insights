<?php

namespace App\Http\Controllers;

use App\Models\PlatformConnection;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConnectionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // workspace atual (por enquanto pegamos o primeiro)
        $workspace = $user->workspaces()
            ->select('workspaces.id', 'workspaces.name', 'workspaces.slug')
            ->first();

        if (! $workspace) {
            abort(403, 'Nenhum workspace encontrado para este usuário.');
        }

        $connections = PlatformConnection::query()
            ->where('workspace_id', $workspace->id)
            ->orderBy('provider')
            ->get([
                'id',
                'provider',
                'name',
                'created_at',
                'extra_data',
            ]);

        return Inertia::render('Connections/Index', [
            'workspace'   => $workspace,
            'connections' => $connections,
            'availableProviders' => [
                [
                    'key'  => 'meta',
                    'name' => 'Meta (Facebook & Instagram Ads)',
                ],
                [
                    'key'  => 'google_analytics',
                    'name' => 'Google Analytics (GA4)',
                ],
                [
                    'key'  => 'google_ads',
                    'name' => 'Google Ads',
                ],
                // Depois você adiciona TikTok, YouTube etc.
            ],
        ]);
    }
}
