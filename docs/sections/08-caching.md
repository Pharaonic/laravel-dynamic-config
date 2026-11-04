## Caching

Each scope is read from the database once and kept in the cache as a single entry. `load()`, `get()`, `has()` and `all()` all read that entry, so a request that loads two scopes queries the database at most twice, and not at all once both scopes are cached.

After `load()`, `config()` reads Laravel's config repository in memory and never queries the database.

### Invalidation

`set()` and `forget()` forget the cache entry of their own scope. Other scopes stay cached, and nothing else in your cache is touched.

```php
Config::dynamic('branch-12')->set('mail.from.address', 'branch@example.com');
// the "branch-12" entry is forgotten; the global entry is kept
```

:::warning Direct database changes
A row you change with a query, a seeder or a database client is not seen while its scope is cached. Write through `set()` and `forget()`, or clear the cache after changing rows directly.
:::

### Options

| Option | Effect |
| --- | --- |
| `cache.enabled` | `false` reads the database on every `load()`, `get()`, `has()` and `all()`. |
| `cache.store` | The store that holds the entries, such as `redis`. `null` uses the default store. |
| `cache.ttl` | Seconds before an entry expires on its own. `null` keeps it until the scope is written. |

### Cache keys

An entry's key is `pharaonic:dynamic-config:` followed by a SHA-1 hash of the connection, the table and the identifier. The global scope and an identifier named `global` never share an entry, and a changed connection or table never reads an old entry.

:::tip Shared cache, several servers
Use a shared store such as Redis or Memcached when several servers serve the same application, so a write on one server invalidates the entry for all of them. With the `file` or `array` store, set a `ttl`.
:::
