<?php

namespace Ybaruchel\HeapCache;

use \Illuminate\Support\Facades\Facade;

class HeapFacade extends Facade
{
    /**
     * Get the registered name of the component. This tells $this->app what record to return
     * (e.g. $this->app[‘heapCache’])
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'heapCache'; }
}
