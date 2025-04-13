<?php

namespace App\Providers;

use App\Contracts\IngredientRepositoryInterface;
use App\Contracts\OrderRepositoryInterface;
use App\Contracts\ProductRepositoryInterface;
use App\Repositories\IngredientRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\OrderService;
use App\Services\OrderServiceInterface;
use App\Services\StockManagementService;
use App\Services\StockManagementServiceInterface;
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
        // Repositories
        $this->app->singleton(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->singleton(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->singleton(IngredientRepositoryInterface::class, IngredientRepository::class);

        // Services
        $this->app->singleton(StockManagementServiceInterface::class, StockManagementService::class);
        $this->app->singleton(OrderServiceInterface::class, OrderService::class);

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
