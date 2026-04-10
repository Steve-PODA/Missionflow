<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema; // <--- Ligne ajoutée

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Corrige l'erreur "1071 La clé est trop longue"
        Schema::defaultStringLength(191); // <--- Ligne ajoutée

        Vite::prefetch(concurrency: 3);
    }
}