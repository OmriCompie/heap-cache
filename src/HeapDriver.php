<?php

namespace Ybaruchel\HeapCache;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Cache\Repository;

class HeapDriver implements Repository
{
    private static $savedCache = [];

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key)
    {
        if (!$this->has($key)) {
            return null;
        }

        if (!$this->validateItem($key)) {
            $this->forget($key);
        }

        return self::$savedCache[$key];
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
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        unset(self::$savedCache[$key]);
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        self::$savedCache = [];
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
     * Checks if key exists in cache.
     *
     * @param $key
     * @return mixed|null
     */
    public static function has($key)
    {
        return isset(self::$savedCache[$key]);
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
        if (! is_null($value = $this->get($key))) {
            return $value;
        }

        return tap($callback(), function ($value) use ($key, $minutes) {
            $this->put($key, $value, $minutes);
        });
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