## Configuration

The configuration lives under the `pharaonic.dynamic-config` key. Every option has a sensible default, so most applications never publish it.

```php title="config/pharaonic/dynamic-config.php"
return [
    'connection' => env('DYNAMIC_CONFIG_CONNECTION'),

    'table' => env('DYNAMIC_CONFIG_TABLE', 'dynamic_configs'),

    'cache' => [
        'enabled' => env('DYNAMIC_CONFIG_CACHE', true),
        'store' => env('DYNAMIC_CONFIG_CACHE_STORE'),
        'ttl' => null,
    ],
];
```

### Options

| Key | Environment variable | Default | Description |
| --- | --- | --- | --- |
| `connection` | `DYNAMIC_CONFIG_CONNECTION` | `null` | The database connection that stores the values. `null` uses the default connection. |
| `table` | `DYNAMIC_CONFIG_TABLE` | `dynamic_configs` | The table that stores the values. The migration creates it under this name. |
| `cache.enabled` | `DYNAMIC_CONFIG_CACHE` | `true` | Cache each scope's values. Set it to `false` to read the database on every call. |
| `cache.store` | `DYNAMIC_CONFIG_CACHE_STORE` | `null` | The cache store to use. `null` uses the default store. |
| `cache.ttl` | none | `null` | Seconds a cached scope lives. `null` keeps it until the scope is written. |

:::tip Central database
In a multi-database application, point `connection` at the central connection so every tenant's scope lives in one table.
:::

### The table

The migration creates one row per key and scope:

| Column | Type | Description |
| --- | --- | --- |
| `id` | big integer | Primary key. |
| `identifier` | nullable string | `null` for the global scope, otherwise the scope's identifier. |
| `key` | string | The dotted config key, such as `mail.from.address`. |
| `value` | long text | The value, encoded as JSON. |
| `created_at`, `updated_at` | timestamps | When the key was first stored and last changed. |

A unique index covers `identifier` and `key`. The package also keeps global keys unique on databases that let several `NULL` identifiers share a key.
