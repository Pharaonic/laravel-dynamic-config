## Troubleshooting

### `config()` still returns the old value after `set()`

`set()` only stores the value. Call `load()` on the scope again, or wait for the next request where your middleware loads it:

```php
Config::dynamic('branch-12')->set('mail.from.address', 'branch@example.com');
Config::dynamic('branch-12')->load();
```

### `get()` returns `null` although `config()` has a value

`get()` reads only what is stored in that one scope, by exact key. It never falls back to the config files or another scope. Read resolved values with `config()`, and pass a default to `get()` when you need one.

### A value changed in the database is not picked up

The scope is cached. Change rows through `set()` and `forget()`, which forget the cache entry, or run `php artisan cache:clear` after changing rows directly. Set `cache.enabled` to `false` while debugging.

### "Table not found" during `php artisan migrate`

Some code calls `load()` while the application boots, before the `dynamic_configs` table exists. Skip loading in the console, or load scopes in a middleware instead of a service provider.

### `Call to undefined method Illuminate\Config\Repository::dynamic()`

The macro is registered when the package's service provider boots. Don't call `Config::dynamic()` from a service provider's `register()` method; use `boot()`, a middleware or later code. If package discovery is disabled in your application, register `Pharaonic\Laravel\DynamicConfig\DynamicConfigServiceProvider` yourself.

### One job sees another job's values

Queue workers keep the config repository between jobs. Restore it when each job ends, as shown in [Long-Running Processes](#long-running-processes).

### `UnsupportedValueException` when saving a value

Only null, booleans, integers, finite floats, UTF-8 strings and arrays of them can be stored. Convert objects to arrays or strings before calling `set()`, such as `$date->toIso8601String()` for a date.

### Changes on one server are not seen on another

Every server has its own cache when the store is `file` or `array`. Use a shared store such as Redis, or set `cache.ttl` so entries expire.
