<?php

namespace App\Http\Controllers;

use App\Models\AdAccount;
use App\Models\AdMetricDaily;
use App\Models\Campaign;
use App\Models\PlatformConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaInsightsSyncController extends Controller
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

        $adAccounts = AdAccount::where('workspace_id', $workspace->id)
            ->where('provider', 'meta')
            ->where('platform_connection_id', $connection->id)
            ->get();

        if ($adAccounts->isEmpty()) {
            return back()->with('error', 'Nenhuma ad account Meta encontrada para sincronizar métricas.');
        }

        $datePreset = 'last_7d';
        $totalRows  = 0;

        foreach ($adAccounts as $acc) {
            $rawId = $acc->extra_data['raw_id'] ?? null; 

            if (! $rawId) {
                continue;
            }

            try {
                // Insights a nível de campanha, dia a dia
                $resp = Http::withToken($accessToken)
                    ->get("https://graph.facebook.com/{$apiVersion}/{$rawId}/insights", [
                        'level'          => 'campaign',
                        'time_increment' => 1,
                        'date_preset'    => $datePreset,
                        'fields'         => 'campaign_id,date_start,impressions,clicks,spend,actions,action_values',
                        'limit'          => 500,
                    ]);

                if (! $resp->ok()) {
                    Log::warning('Meta insights sync error', [
                        'account' => $rawId,
                        'body'    => $resp->body(),
                    ]);
                    continue;
                }

                $body = $resp->json();

                if (! isset($body['data']) || ! is_array($body['data'])) {
                    continue;
                }

                foreach ($body['data'] as $row) {
                    $campaignExternalId = $row['campaign_id'] ?? null;
                    $date               = $row['date_start'] ?? null;

                    if (! $campaignExternalId || ! $date) {
                        continue;
                    }

                    // Localiza a campaign interna
                    $campaign = Campaign::where('ad_account_id', $acc->id)
                        ->where('provider', 'meta')
                        ->where('external_id', $campaignExternalId)
                        ->first();

                    $campaignId = $campaign?->id;

                    // Métricas básicas
                    $impressions = (int) ($row['impressions'] ?? 0);
                    $clicks      = (int) ($row['clicks'] ?? 0);
                    $spend       = (float) ($row['spend'] ?? 0);

                    // Conversões / receita: tentamos achar em "actions" e "action_values"
                    $conversions = 0;
                    $revenue     = 0.0;

                    if (! empty($row['actions']) && is_array($row['actions'])) {
                        foreach ($row['actions'] as $action) {
                            $type  = $action['action_type'] ?? null;
                            $value = (int) ($action['value'] ?? 0);

                            // Ajuste aqui conforme o tipo de conversão que você usa
                            if (in_array($type, ['purchase', 'offsite_conversion', 'offsite_conversion.fb_pixel_purchase'])) {
                                $conversions += $value;
                            }
                        }
                    }

                    if (! empty($row['action_values']) && is_array($row['action_values'])) {
                        foreach ($row['action_values'] as $av) {
                            $type  = $av['action_type'] ?? null;
                            $value = (float) ($av['value'] ?? 0);

                            if (in_array($type, ['purchase', 'offsite_conversion.fb_pixel_purchase'])) {
                                $revenue += $value;
                            }
                        }
                    }

                    AdMetricDaily::updateOrCreate(
                        [
                            'workspace_id'  => $workspace->id,
                            'ad_account_id' => $acc->id,
                            'campaign_id'   => $campaignId,
                            'ad_id'         => null,
                            'provider'      => 'meta',
                            'date'          => $date,
                        ],
                        [
                            'impressions' => $impressions,
                            'clicks'      => $clicks,
                            'spend'       => $spend,
                            'conversions' => $conversions,
                            'revenue'     => $revenue,
                            'extra_data'  => $row,
                        ]
                    );

                    $totalRows++;
                }
            } catch (\Throwable $e) {
                Log::error('Meta insights sync exception', [
                    'message' => $e->getMessage(),
                    'acc_id'  => $acc->id,
                ]);
            }
        }

        if ($totalRows === 0) {
            return back()->with('warning', 'Nenhuma métrica nova encontrada na Meta (últimos 7 dias).');
        }

        return back()->with('success', "Métricas sincronizadas com sucesso ({$totalRows} linhas).");
    }
}
