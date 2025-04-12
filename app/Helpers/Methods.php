<?php

if(! function_exists('isLocalEnvironment')) {
    function isLocalEnvironment(): bool
    {
        return app()->environment('local');
    }
}

if(! function_exists('isTestingEnvironment')) {
    function isTestingEnvironment(): bool
    {
        return app()->environment('testing');
    }
}

if(! function_exists('isProductionEnvironment')) {
    function isProductionEnvironment(): bool
    {
        return app()->environment('production');
    }
}

if(! function_exists('getLowStockThresholdPercentage')) {
    /**
     * return the value in percent like 0.50
     * @return float
     */
    function getLowStockThresholdPercentage(): float
    {
        return (config('ingredients.low_stock_threshold_percentage', 50) / 100);
    }
}
