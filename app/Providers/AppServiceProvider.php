<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

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
        if (app()->environment('local')) {
            $this->addDebugQueries();
        }
    }

    private function addDebugQueries(): void
    {
        DB::listen(function ($query) {
            $sql = $query->sql;
            foreach ($query->bindings as $binding) {
                $value = is_numeric($binding) ? $binding : "'" . $binding . "'";
                $sql   = preg_replace('/\?/', $value, $sql, 1);
            }
            Log::debug($sql, ['time' => $query->time]);
        });
    }
}
