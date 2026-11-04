## API Reference

### Config macro

| Method | Description | Returns |
| --- | --- | --- |
| `Config::dynamic(string\|Model\|null $identifier = null)` | The global scope (`null`), the named scope, or the scope of a saved Eloquent model (`{morph class}:{key}`). Also available as `config()->dynamic()`. Throws `InvalidArgumentException` for an empty identifier or a model that is not saved. | `DynamicConfig` |

### Scope methods

`Pharaonic\Laravel\DynamicConfig\DynamicConfig`, returned by `Config::dynamic()`.

| Method | Description | Returns |
| --- | --- | --- |
| `load()` | Merges this scope's stored values into the config repository. | `void` |
| `get(string $key, mixed $default = null)` | The value stored for the exact key in this scope, or `$default` (a closure default is called). | `mixed` |
| `has(string $key)` | Whether the exact key is stored in this scope, including a stored `null`. | `bool` |
| `set(string $key, mixed $value)` | Stores the value and forgets this scope's cache entry. Throws `InvalidArgumentException` for an invalid key and `UnsupportedValueException` for an unsupported value. | `void` |
| `forget(string $key)` | Deletes the key from this scope and forgets this scope's cache entry. | `void` |
| `all()` | Every stored key and value of this scope, flat and sorted by key. | `array` |

### Exceptions

| Exception | Thrown when |
| --- | --- |
| `Pharaonic\Laravel\DynamicConfig\Exceptions\UnsupportedValueException` | `set()` receives a closure, object, resource, non-finite float or non-UTF-8 string, even nested in an array. Extends `InvalidArgumentException`. |
| `InvalidArgumentException` | `Config::dynamic('')`, or `set()` with an empty key or a key with an empty segment. |

### Publish tags

| Tag | Publishes |
| --- | --- |
| `dynamic-config-config` | `config/pharaonic/dynamic-config.php` |
| `dynamic-config-migrations` | The migration into `database/migrations` |
| `pharaonic-config`, `pharaonic-migrations` | The same files, together with other Pharaonic packages' |
| `laravel-dynamic-config`, `pharaonic` | Both files |
