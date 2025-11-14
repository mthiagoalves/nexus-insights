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
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();

            $table->string('provider'); // meta, google_ads...
            $table->string('external_id')->index(); // id do ad/criativo na plataforma

            $table->string('name')->nullable();
            $table->string('status')->default('active');

            $table->json('creative_data')->nullable(); // json com headline, image, etc.
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
        Schema::dropIfExists('ads');
    }
};
