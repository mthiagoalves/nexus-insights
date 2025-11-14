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
        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_connection_id')->nullable()->constrained()->nullOnDelete();

            $table->string('provider'); // meta, google_ads, tiktok_ads, etc.
            $table->string('external_id')->index(); // id da conta na plataforma
            $table->string('name');
            $table->string('currency', 10)->nullable();
            $table->json('extra_data')->nullable(); // qualquer info adicional

            $table->timestamps();

            $table->index(['workspace_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_accounts');
    }
};
