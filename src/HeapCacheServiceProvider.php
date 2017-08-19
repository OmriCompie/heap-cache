<?php

namespace Ybaruchel\HeapCache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class HeapCacheServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Cache::extend('heap-cache', function ($app) {
            return Cache::repository(new HeapDriver());
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
