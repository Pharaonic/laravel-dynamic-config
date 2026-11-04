## Basic Usage

Every call starts with `Config::dynamic()`, which returns one scope: the global scope when you pass nothing (or `null`), or a named scope when you pass an identifier.

```php
use Illuminate\Support\Facades\Config;

Config::dynamic();            // the global scope
Config::dynamic(null);        // the same global scope
Config::dynamic('branch-12'); // the "branch-12" scope
```

`config()->dynamic()` works too, because the macro lives on the config repository itself.

### Persist a value

`set()` stores a value in the scope's table rows:

```php
Config::dynamic()->set('mail.from.address', 'global@example.com');

Config::dynamic('branch-12')->set('mail.from.address', 'branch@example.com');
```

Setting the same key again replaces its value, so a scope never holds a key twice.

### Apply a scope

`load()` merges the scope's stored values into Laravel's config repository:

```php
config('mail.from.address'); // "default@example.com" from config/mail.php

Config::dynamic()->load();

config('mail.from.address'); // "global@example.com"
```

From then on, everything that reads configuration sees the new value: `config()`, `Config::get()`, and the mail, cache or service classes Laravel resolves afterwards.

### Read the final value

Read resolved configuration with Laravel itself, never with the scope:

```php
config('mail.from.address');
Config::get('mail.from.address');
```

:::info Stored values and resolved values
`Config::dynamic('branch-12')->get('mail.from.address')` returns what is **stored** in that scope, or the default. `config('mail.from.address')` returns the **resolved** value after the config files and every scope you have loaded so far.
:::
