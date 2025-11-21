<?php

namespace App\Jobs;

use App\Models\PlatformConnection;
use App\Models\AdAccount;
use App\Models\Campaign;
use App\Models\AdMetricDaily;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncMetaInsightsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $workspaceId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $workspaceId)
    {
        $this->workspaceId = $workspaceId;
        // Opcional: setar timeout/retries aqui
        $this->connection = config('queue.default'); // usa a conexão padrão
        $this->queue = 'default';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $connection = PlatformConnection::where('workspace_id', $this->workspaceId)
            ->where('provider', 'meta')
            ->first();

        if (! $connection) {
            Log::info("SyncMetaInsightsJob: no meta connection for workspace {$this->workspaceId}");
            return;
        }

        $apiVersion  = config('services.meta.api_version', 'v20.0');
        $accessToken = $connection->access_token;

        $adAccounts = AdAccount::where('workspace_id', $this->workspaceId)
            ->where('provider', 'meta')
            ->where('platform_connection_id', $connection->id)
            ->get();

        foreach ($adAccounts as $acc) {
            $rawId = $acc->extra_data['raw_id'] ?? null;
            if (! $rawId) {
                Log::warning("SyncMetaInsightsJob: ad account {$acc->id} has no raw_id");
                continue;
            }

            try {
                $resp = Http::withToken($accessToken)
                    ->get("https://graph.facebook.com/{$apiVersion}/{$rawId}/insights", [
                        'level'          => 'campaign',
                        'time_increment' => 1,
                        'date_preset'    => 'yesterday', // ajuste aqui se quiser outro período
                        'fields'         => 'campaign_id,date_start,impressions,clicks,spend,actions,action_values',
                        'limit'          => 500,
                    ]);

                if (! $resp->ok()) {
                    Log::warning('Meta insights job warning', [
                        'account' => $rawId,
                        'workspace' => $this->workspaceId,
                        'body'    => $resp->body(),
                    ]);
                    continue;
                }

                $body = $resp->json();

                foreach ($body['data'] ?? [] as $row) {
                    $campaignExternalId = $row['campaign_id'] ?? null;
                    $date               = $row['date_start'] ?? null;
                    if (! $campaignExternalId || ! $date) {
                        continue;
                    }

                    $campaign = Campaign::where('ad_account_id', $acc->id)
                        ->where('provider', 'meta')
                        ->where('external_id', $campaignExternalId)
                        ->first();

                    $campaignId = $campaign?->id;

                    $impressions = (int) ($row['impressions'] ?? 0);
                    $clicks      = (int) ($row['clicks'] ?? 0);
                    $spend       = (float) ($row['spend'] ?? 0);

                    $conversions = 0;
                    $revenue     = 0.0;

                    if (! empty($row['actions']) && is_array($row['actions'])) {
                        foreach ($row['actions'] as $action) {
                            $type  = $action['action_type'] ?? null;
                            $value = (int) ($action['value'] ?? 0);

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
                            'workspace_id'  => $this->workspaceId,
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
                }
            } catch (\Throwable $e) {
                Log::error('SyncMetaInsightsJob exception', [
                    'message' => $e->getMessage(),
                    'account' => $acc->id,
                    'workspace' => $this->workspaceId,
                ]);
            }
        }
    }
}
