<?php

namespace Ybaruchel\HeapCache;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Cache\Repository;

class HeapDriver implements Repository
{
    private static $savedCache = [];

    /**
     * Determine if an item exists in the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return isset(self::$savedCache[$key]);
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }

        if (!$this->validateItem($key)) {
            $this->forget($key);
        }

        return self::$savedCache[$key];
    }

    /**
     * Retrieve an item from the cache and delete it.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        $item = $this->get($key, $default);
        $this->forget($key);
        return $item;
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  float|int  $minutes
     * @return void
     */
    public function put($key, $value, $minutes)
    {
        self::$savedCache[$key] = [
            'minutes' => $minutes,
            'created_at' => time(),
            'value' => $value,
        ];
    }

    /**
     * Store an item in the cache if the key does not exist.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  \DateTime|float|int  $minutes
     * @return bool
     */
    public function add($key, $value, $minutes)
    {
        if ($this->has($key)) {
            return false;
        }

        self::$savedCache[$key] = [
            'minutes' => $minutes,
            'created_at' => time(),
            'value' => $value,
        ];

        return true;
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        $item = $this->get($key);
        if (!$item) {
            return false;
        }
        $this->put($key, $item['value'] + $value, $item['minutes']);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        $item = $this->get($key);
        if (!$item) {
            return false;
        }
        $this->put($key, $item['value'] - $value, $item['minutes']);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function forever($key, $value)
    {
        $this->put($key, $value, 'forever');
    }

    /**
     * If the key was found - returns execution, else, will store and execute.
     *
     * @param string $key
     * @param \DateTime|float|int $minutes
     * @param Closure $callback
     * @return mixed|null
     */
    public function remember($key, $minutes, Closure $callback)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        return tap($callback(), function ($value) use ($key, $minutes) {
            $this->put($key, $value, $minutes);
        });
    }

    /**
     * Get an item from the cache, or store the default value forever.
     *
     * @param  string   $key
     * @param  \Closure  $callback
     * @return mixed
     */
    public function sear($key, Closure $callback)
    {
        return $this->rememberForever($key, $callback);
    }

    /**
     * Get an item from the cache, or store the default value forever.
     *
     * @param  string   $key
     * @param  \Closure  $callback
     * @return mixed
     */
    public function rememberForever($key, Closure $callback)
    {
        $value = $this->get($key);

        // If the item exists in the cache we will just return this immediately and if
        // not we will execute the given Closure and cache the result of that for a
        // given number of minutes so it's available for all subsequent requests.
        if ($this->has($key)) {
            return $value;
        }

        $this->forever($key, $value = $callback());

        return $value;
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param  array  $keys
     * @return array
     */
    public function many(array $keys)
    {
        return array_reduce($keys, function ($carry, $item) {
            $carry[$item] = $this->get($item);
            return $carry;
        });
    }

    /**
     * Store multiple items in the cache for a given number of minutes.
     *
     * @param  array  $values
     * @param  float|int  $minutes
     * @return void
     */
    public function putMany(array $values, $minutes)
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value, $minutes);
        }
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        unset(self::$savedCache[$key]);
        return true;
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        self::$savedCache = [];
        return true;
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return Config::get('cache.prefix');
    }

    /**
     * Validates expire time of saved item.
     *
     * @param $savedItem
     * @return null
     */
    private function validateItem($savedItem)
    {
        $timeExpired = time() - strtotime($savedItem['created_at']) > $savedItem['minutes'] * 60;
        if (is_integer($savedItem['minutes']) && $timeExpired) {
            return false;
        }
        return true;
    }
}