<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AgentFormController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/agent-form', function () {
    return Inertia::render('AgentForm');
})->name('agentform.public');

Route::post('/agent-form', [AgentFormController::class, 'store'])->name('agentform.store');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
