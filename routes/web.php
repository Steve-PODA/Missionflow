<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ActivityController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Route publique (L'accueil pour les non-connectés)
// Tu peux choisir d'afficher une page de présentation ou rediriger vers le login
Route::get('/', function () {
    return redirect()->route('login');
});

// TOUTES les routes ci-dessous demandent d'être connecté
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard (accessible à tous les rôles)
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Missions — lecture : admin, manager, technicien
    Route::get('/missions', [MissionController::class, 'index'])
        ->middleware('permission:view missions')
        ->name('missions.index');

    // Missions — écriture : admin, manager uniquement
    Route::post('/missions', [MissionController::class, 'store'])
        ->middleware('permission:create missions')
        ->name('missions.store');

    Route::patch('/missions/{mission}/status', [MissionController::class, 'updateStatus'])
        ->middleware('permission:update mission status')
        ->name('missions.updateStatus');

    Route::put('/missions/{mission}', [MissionController::class, 'update'])
        ->middleware('permission:edit missions')
        ->name('missions.update');

    Route::patch('/missions/{mission}/reschedule', [MissionController::class, 'reschedule'])
        ->middleware('permission:edit missions')
        ->name('missions.reschedule');

    // Personnel — lecture : admin, manager, technicien
    Route::get('/personnel', [PersonnelController::class, 'index'])
        ->middleware('permission:view personnel')
        ->name('personnel.index');

    // Personnel — écriture : admin, manager uniquement
    Route::patch('/personnel/{user}/availability', [PersonnelController::class, 'updateAvailability'])
        ->middleware('permission:manage personnel')
        ->name('personnel.availability');

    // Recherche globale
    Route::get('/search', SearchController::class)->name('search');

    // Rapports — admin et manager uniquement
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('permission:edit missions')
        ->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])
        ->middleware('permission:edit missions')
        ->name('reports.export');

    // Journal d'activité — admin uniquement
    Route::get('/activity', [ActivityController::class, 'index'])
        ->middleware('permission:manage users')
        ->name('activity.index');

    // Gestion des utilisateurs — admin uniquement
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:manage users')
        ->name('users.index');
    Route::post('/users', [UserController::class, 'store'])
        ->middleware('permission:manage users')
        ->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])
        ->middleware('permission:manage users')
        ->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:manage users')
        ->name('users.destroy');

    // Profil (accessible à tous)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';