<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Workspace;
use App\Jobs\SyncMetaInsightsJob;

class SyncAllWorkspacesMetaInsights extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meta:sync-all-insights';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch SyncMetaInsightsJob for all workspaces with Meta connection';

    public function handle(): int
    {
        $workspaces = Workspace::all();

        $dispatched = 0;
        foreach ($workspaces as $ws) {
            // se quiser, checar se workspace tem PlatformConnection meta antes de dispatch
            SyncMetaInsightsJob::dispatch($ws->id);
            $dispatched++;
        }

        $this->info("Dispatched SyncMetaInsightsJob for {$dispatched} workspaces.");
        return 0;
    }
}
