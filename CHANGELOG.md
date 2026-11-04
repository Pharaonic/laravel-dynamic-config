# Changelog

All notable changes to this project are documented here.

## 9.0.0 - Unreleased

### Added

- `Config::dynamic(string|Model|null $identifier)` on Laravel's config repository, returning the global scope (null), one named scope, or the scope of one saved Eloquent model (stored as `{morph class}:{key}`).
- `load()`, `get()`, `has()`, `set()`, `forget()` and `all()` on a scope.
- The `dynamic_configs` migration and the `pharaonic.dynamic-config` configuration (connection, table, cache).

### Compatibility

- Laravel 9.33 or later on PHP 8.0, 8.1 or 8.2. Laravel 9.24 is the first release whose config repository accepts macros, and 9.33 the first that requires a Carbon release working on PHP 8.2.
