<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

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
          if (DB::connection() instanceof \Illuminate\Database\SQLiteConnection) {
            $pdo = DB::connection()->getPdo();
            
            // Создаем пользовательскую функцию UPPER для кириллицы
            $pdo->sqliteCreateFunction('upper', function ($string) {
                if ($string === null) {
                    return null;
                }
                return mb_convert_case($string, MB_CASE_UPPER, 'UTF-8');
            }, 1);
            
            // Создаем пользовательскую функцию LOWER для кириллицы
            $pdo->sqliteCreateFunction('lower', function ($string) {
                if ($string === null) {
                    return null;
                }
                return mb_convert_case($string, MB_CASE_LOWER, 'UTF-8');
            }, 1);
        }
    
    }
}
