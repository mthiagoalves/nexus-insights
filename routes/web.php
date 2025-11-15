<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MetaAuthController;
use App\Http\Controllers\MetaInsightsSyncController;
use App\Http\Controllers\MetaSyncController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/connections', [ConnectionController::class, 'index'])
        ->name('connections.index');

    Route::get('/campaigns', [CampaignController::class, 'index'])
        ->name('campaigns.index');

    Route::get('/auth/meta/redirect', [MetaAuthController::class, 'redirect'])
        ->name('meta.redirect');

    Route::get('/auth/meta/callback', [MetaAuthController::class, 'callback'])
        ->name('meta.callback');

    Route::post('/meta/sync-campaigns', MetaSyncController::class)->name('meta.syncCampaigns');

    Route::post('/meta/sync-insights', MetaInsightsSyncController::class)
        ->name('meta.syncInsights');
});

require __DIR__ . '/settings.php';
