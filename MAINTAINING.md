# Maintaining This Package

This file records package-specific maintainer facts that are safe to keep in the public repository.

## Purpose

Persist Laravel configuration values in a database table, in a global scope (identifier null), in named scopes, or in the scope of a saved Eloquent model, and merge one scope at a time into the config repository with `Config::dynamic($identifier)->load()`.

## Supported Laravel lines

One branch per Laravel major, from `9.x` to the newest major. Each line's PHP range, `illuminate/*` constraint, and tools follow the Pharaonic Laravel line table. The package starts at `9.x` because `Config::dynamic()` is a macro on `Illuminate\Config\Repository`, which is only `Macroable` from Laravel 9.24.0; no release ever supported Laravel 6–8. The `9.x` line requires `illuminate/*` `^9.33`: below 9.24 the macro cannot exist, and 9.33 is the first release that requires a Carbon release working on PHP 8.2.

## Core library

None. The package is Laravel glue only: the config repository, the query builder, and the cache.

## Public API invariants

Configuration keys (`pharaonic.dynamic-config.*`), publish tags, and registered Blade, Artisan, macro, and validation names are public API.

- Macro: `Config::dynamic(string|Model|null $identifier = null)` on `Illuminate\Config\Repository`, returning `DynamicConfig`. An empty identifier, or a model that is not saved or has no string or integer key, throws `InvalidArgumentException`.
- A model scope is the identifier `{getMorphClass()}:{getKey()}`, so `Config::dynamic($branch)` and `Config::dynamic('branch:12')` are the same scope. Changing this format orphans every stored model scope; there are no morph columns on purpose, to keep the `(identifier, key)` uniqueness rules.
- `DynamicConfig`: `load()`, `get($key, $default = null)`, `has($key)`, `set($key, $value)`, `forget($key)`, and `all()`. Its constructor is internal.
- `load()` merges only its own scope, never another identifier or the global scope. Associative arrays merge recursively into the current value; lists, scalars, null, and empty arrays replace it. Keys are applied in string order, so a parent key comes before its children. A later `load()` wins.
- `get()`, `has()`, and `all()` read only the stored values of their scope, by exact key. `all()` is flat, keyed by the dotted key, in string order.
- `set()` and `forget()` change the database and forget that scope's cache entry. They never change the config repository; only `load()` does.
- Values are stored as JSON (`JSON_PRESERVE_ZERO_FRACTION`): null, bool, int, finite float, UTF-8 string, and arrays of them. Anything else throws `Exceptions\UnsupportedValueException` (an `InvalidArgumentException`) naming the key, scope, and type, never the value.
- Configuration: `pharaonic.dynamic-config.connection`, `.table`, `.cache.enabled`, `.cache.store`, and `.cache.ttl`, with the `DYNAMIC_CONFIG_CONNECTION`, `DYNAMIC_CONFIG_TABLE`, `DYNAMIC_CONFIG_CACHE`, and `DYNAMIC_CONFIG_CACHE_STORE` environment variables.
- Cache key: `pharaonic:dynamic-config:` plus the SHA-1 of the JSON `[connection, table, identifier]`. Changing it makes every application read its scopes from the database once more.
- Publish tags: `pharaonic`, `laravel-dynamic-config`, `pharaonic-config`, `dynamic-config-config`, `pharaonic-migrations`, and `dynamic-config-migrations`.

## Package-specific constraints

- The migration file name `2026_11_03_000001_create_dynamic_configs_table.php` never changes, or applications run it again. Later schema changes go in new migrations.
- The table has a unique `(identifier, key)` index, but most databases let several NULL identifiers share a key. Named scopes use `upsert()`; the global scope updates an existing row or inserts one and then deletes older rows with the same key, and reads let the newest row win. Do not replace this with a plain `upsert()`.
- Nothing is loaded at boot. The provider only merges config, loads migrations, registers the macro, and publishes.
- No static state. `DynamicConfig` reaches the config repository, database, and cache through facades, so it always uses the current application (the per-request sandbox under Octane).

## Branch notes

Fixes land on the oldest affected line and are ported to every later line and `develop`.

## Documentation

https://pharaonic.dev/packages/laravel/dynamic-config

Keep this file factual and package-specific. General Pharaonic engineering policy belongs in the private engineering repository, not here.
