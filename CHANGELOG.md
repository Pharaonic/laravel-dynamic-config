# Changelog

All notable changes to this project are documented here.

## 12.0.0 - Unreleased

### Added

- `Config::dynamic(string|Model|null $identifier)` on Laravel's config repository, returning the global scope (null), one named scope, or the scope of one saved Eloquent model (stored as `{morph class}:{key}`).
- `load()`, `get()`, `has()`, `set()`, `forget()` and `all()` on a scope.
- The `dynamic_configs` migration and the `pharaonic.dynamic-config` configuration (connection, table, cache).

### Compatibility

- Laravel 12.x on PHP 8.2, 8.3, 8.4 or 8.5.
