<?php

namespace App\Http\Controllers;

use App\Models\AdAccount;
use App\Models\Campaign;
use App\Models\PlatformConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaSyncController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $workspace = $user->workspaces()
            ->select('workspaces.id', 'workspaces.name', 'workspaces.slug')
            ->first();

        if (! $workspace) {
            return back()->with('error', 'Nenhum workspace encontrado.');
        }

        $connection = PlatformConnection::where('workspace_id', $workspace->id)
            ->where('provider', 'meta')
            ->first();

        if (! $connection) {
            return back()->with('error', 'Nenhuma conexão Meta encontrada para este workspace.');
        }

        $apiVersion  = config('services.meta.api_version', 'v20.0');
        $accessToken = $connection->access_token;

        // Pega todas as ad accounts vinculadas a essa conexão
        $adAccounts = AdAccount::where('workspace_id', $workspace->id)
            ->where('provider', 'meta')
            ->where('platform_connection_id', $connection->id)
            ->get();

        if ($adAccounts->isEmpty()) {
            return back()->with('error', 'Nenhuma ad account Meta encontrada para sincronizar.');
        }

        $totalImported = 0;

        foreach ($adAccounts as $acc) {
            $rawId = $acc->extra_data['raw_id'] ?? null; // normalmente algo como "act_1234567890"

            if (! $rawId) {
                continue;
            }

            try {
                $resp = Http::withToken($accessToken)
                    ->get("https://graph.facebook.com/{$apiVersion}/{$rawId}/campaigns", [
                        'fields' => 'id,name,status,objective,daily_budget',
                        'limit'  => 200,
                    ]);

                if (! $resp->ok()) {
                    Log::warning('Meta campaigns sync error', [
                        'account' => $rawId,
                        'body'    => $resp->body(),
                    ]);
                    continue;
                }

                $data = $resp->json();

                if (! isset($data['data']) || ! is_array($data['data'])) {
                    continue;
                }

                foreach ($data['data'] as $item) {
                    $externalId = $item['id'] ?? null;

                    if (! $externalId) {
                        continue;
                    }

                    Campaign::updateOrCreate(
                        [
                            'ad_account_id' => $acc->id,
                            'provider'      => 'meta',
                            'external_id'   => $externalId,
                        ],
                        [
                            'name'         => $item['name'] ?? 'Campanha Meta',
                            'status'       => $item['status'] ?? 'unknown',
                            'objective'    => $item['objective'] ?? null,
                            'daily_budget' => isset($item['daily_budget'])
                                ? ((float) $item['daily_budget']) / 100
                                : null, // Meta retorna em centavos às vezes
                            'extra_data'   => $item,
                        ]
                    );

                    $totalImported++;
                }
            } catch (\Throwable $e) {
                Log::error('Meta campaigns sync exception', [
                    'message'  => $e->getMessage(),
                    'account'  => $acc->id,
                    'raw_id'   => $rawId,
                ]);
            }
        }

        if ($totalImported === 0) {
            return back()->with('warning', 'Nenhuma campanha nova encontrada na Meta.');
        }

        return back()->with('success', "Sincronização concluída. {$totalImported} campanhas importadas/atualizadas.");
    }
}
