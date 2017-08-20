# Laravel 5.5 HeapCache
========

**HeapCache cache driver caches runtime data in the heap memory allocated to PHP.**

## Installation

Require this package with composer:

```php
composer require ybaruchel/heap-cache
```
That's it :)

## Usage

You can now start using the cache driver using the Facade.

 ```php
HeapCache::has('key');
```

 ```php
HeapCache::get('key', 'default_value');
```

 ```php
HeapCache::pull('key', 'default_value');
```

 ```php
HeapCache::put('key', 'value', $minutes);
```

 ```php
HeapCache::add('key', 'value', $minutes);
```

 ```php
HeapCache::forever('key', 'value');
```

 ```php
HeapCache::remember('key', 'value', function () {
    return 'hello world';
});
```

 ```php
HeapCache::sear('key', 'value', function () {
    return 'hello world';
});
```

 ```php
HeapCache::rememberForever('key', function () {
    return 'hello world';
});
```

 ```php
HeapCache::many(['first_key', 'second_key']);
```

 ```php
HeapCache::putMany(['first_key' => 'first_value', 'second_key' => 'second_value'], $minutes);
```

 ```php
HeapCache::forget('key');
```

 ```php
HeapCache::flush();
```

 ```php
HeapCache::getPrefix();
```