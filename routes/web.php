<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AgentFormController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Foundation\Application;
use App\Services\AgentFormService;
use App\Services\HorizonMetricsService;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/agent-form', function () {
    return Inertia::render('AgentForm');
})->name('agentform.public');

Route::post('/agent-form', [AgentFormController::class, 'store'])->name('agentform.store');

// API routes for dashboard metrics (temporarily without auth for testing)
Route::get('/api/metrics/agentform', function (AgentFormService $agentFormService) {
    return response()->json($agentFormService->getStatistics());
})->name('api.metrics.agentform');

Route::get('/api/metrics/horizon', function (HorizonMetricsService $horizonMetricsService) {
    return response()->json($horizonMetricsService->getDashboardMetrics());
})->name('api.metrics.horizon');

// Protected routes (restore these in production)
// Route::middleware(['auth', 'verified'])->group(function () {
//     Route::get('/api/metrics/agentform', function (AgentFormService $agentFormService) {
//         return response()->json($agentFormService->getStatistics());
//     })->name('api.metrics.agentform');
//
//     Route::get('/api/metrics/horizon', function (HorizonMetricsService $horizonMetricsService) {
//         return response()->json($horizonMetricsService->getDashboardMetrics());
//     })->name('api.metrics.horizon');
// });

// Temporary test routes (remove in production)
Route::get('/test/metrics/agentform', function (AgentFormService $agentFormService) {
    return response()->json($agentFormService->getStatistics());
});

Route::get('/test/metrics/horizon', function (HorizonMetricsService $horizonMetricsService) {
    return response()->json($horizonMetricsService->getDashboardMetrics());
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
