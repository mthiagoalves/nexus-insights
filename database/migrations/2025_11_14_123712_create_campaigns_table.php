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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_account_id')->constrained()->cascadeOnDelete();

            $table->string('provider'); // meta, google_ads...
            $table->string('external_id')->index(); // id da campanha na plataforma

            $table->string('name');
            $table->string('status')->default('active'); // active, paused, deleted...
            $table->string('objective')->nullable();
            $table->decimal('daily_budget', 15, 2)->nullable();

            $table->json('extra_data')->nullable();

            $table->timestamps();

            $table->index(['provider', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
