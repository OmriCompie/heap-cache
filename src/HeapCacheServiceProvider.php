<?php

namespace Ybaruchel\HeapCache;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class HeapCacheServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('heapCache', function () {
            return new HeapDriver();
        });

        $loader = AliasLoader::getInstance();
        $loader->alias('HeapCache', HeapFacade::class);
    }
}
