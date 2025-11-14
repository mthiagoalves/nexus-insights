<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\DashboardController;
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
});

require __DIR__ . '/settings.php';
