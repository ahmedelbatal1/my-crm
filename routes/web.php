<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

    Route::resource('companies', CompanyController::class)->except('show');
    Route::resource('contacts', ContactController::class);
    Route::resource('deals', DealController::class);
    Route::resource('projects', ProjectController::class);

    Route::get('/projects/{project}/units/create', [UnitController::class, 'create']);
    Route::post('/projects/{project}/units', [UnitController::class, 'store']);
    Route::get('/units/{unit}/edit', [UnitController::class, 'edit']);
    Route::put('/units/{unit}', [UnitController::class, 'update']);
    Route::delete('/units/{unit}', [UnitController::class, 'destroy']);
});
