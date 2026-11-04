## Stored Values

A scope's `get()`, `has()`, `all()`, `set()` and `forget()` work on that scope's stored rows only. They never look at the config files or at other scopes.

### Reading stored values

```php
$scope = Config::dynamic('branch-12');

$scope->get('mail.from.address');                         // "branch@example.com", or null
$scope->get('mail.from.address', 'fallback@example.com'); // the default when the key is not stored
$scope->get('mail.from.address', fn () => 'lazy@example.com');

$scope->has('mail.from.address'); // true

$scope->all();
// [
//     'mail.from.address' => 'branch@example.com',
//     'services.mailgun.domain' => 'branch.example.com',
// ]
```

`all()` is flat: each stored key with its value, sorted by key. Keys match exactly, so after `set('mail.from', [...])`, `has('mail.from')` is true while `has('mail.from.address')` and `has('mail')` are false.

A stored `null` counts as present: `has()` returns `true` and `get()` returns `null`, not the default.

### Writing and deleting

```php
Config::dynamic('branch-12')->set('mail.from.address', 'branch@example.com');

Config::dynamic('branch-12')->forget('mail.from.address');
```

`forget()` deletes the key from that scope only. Other scopes and the config files keep their values, and forgetting a key that isn't stored does nothing.

:::warning Writes don't change the loaded config
`set()` and `forget()` change the database and that scope's cache entry, not the config repository. A value you set becomes visible to `config()` on the next `load()`. A value you forget stays in the loaded config until the next request or job starts without it.
:::

### Keys

A key is a dotted config key with no empty segments. `''`, `.`, `mail.`, `.mail` and `mail..from` throw an `InvalidArgumentException` on `set()`.

### Supported values

Values are stored as JSON and come back with their type:

| Type | Example |
| --- | --- |
| `null` | `null` |
| `bool` | `true`, `false` |
| `int` | `42`, `-7` |
| `float` | `1.5`, `2.0` (stays a float) |
| `string` | `'branch@example.com'`, `'42'` (stays a string) |
| `array` | lists, associative arrays and nested arrays of the types above |

Anything else throws `Pharaonic\Laravel\DynamicConfig\Exceptions\UnsupportedValueException`, before anything is written: closures, objects, resources, `NAN`, `INF` and strings that are not valid UTF-8.

```php
use Pharaonic\Laravel\DynamicConfig\Exceptions\UnsupportedValueException;

try {
    Config::dynamic()->set('mail', ['from' => ['address' => new stdClass]]);
} catch (UnsupportedValueException $exception) {
    $exception->getMessage();
    // The dynamic config [mail.from.address] of the global scope cannot store a value of type [stdClass]; ...
}
```

The message names the key, the scope and the type, never the value, so secrets don't end up in logs. The exception extends `InvalidArgumentException`.
