<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ad_metrics_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('ad_account_id')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('ad_id')->nullable();

            $table->string('provider'); // meta, google_ads...

            $table->date('date')->index();

            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->decimal('spend', 15, 2)->default(0);
            $table->unsignedBigInteger('conversions')->default(0);
            $table->decimal('revenue', 15, 2)->default(0);

            $table->json('extra_data')->nullable();

            $table->timestamps();

            $table->index(['workspace_id', 'provider', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_metrics_daily');
    }
};
