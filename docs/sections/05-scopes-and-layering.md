## Scopes and Layering

A scope is the set of values stored under one identifier. The global scope uses the identifier `null`. A named scope uses any non-empty string, such as `company-1`, `branch-12` or a tenant key.

### One scope per load

Each `load()` applies exactly one scope:

```php
Config::dynamic()->load();            // only rows whose identifier is NULL
Config::dynamic('branch-12')->load(); // only rows whose identifier is "branch-12"
```

Loading a named scope never loads the global scope or any other identifier for you. The package has no idea of parent scopes.

### Layer scopes explicitly

To stack layers, load them in order. Each load overrides the previous ones where they conflict:

```php title="app/Http/Middleware/LoadBranchConfig.php"
Config::dynamic()->load();             // config files < global
Config::dynamic('company-1')->load();  // < company-1
Config::dynamic('branch-12')->load();  // < branch-12
```

The order is yours. Loading `branch-12` first and the global scope second lets the global values win:

```php
Config::dynamic()->set('mail.from.address', 'global@example.com');
Config::dynamic('branch-12')->set('mail.from.address', 'branch@example.com');

Config::dynamic('branch-12')->load();
Config::dynamic()->load();

config('mail.from.address'); // "global@example.com"
```

Keys that a later scope does not mention keep the value an earlier scope gave them:

```php
Config::dynamic()->set('mail.from.name', 'Global');
Config::dynamic('branch-12')->set('mail.from.address', 'branch@example.com');

Config::dynamic()->load();
Config::dynamic('branch-12')->load();

config('mail.from'); // ['address' => 'branch@example.com', 'name' => 'Global']
```

### Identifiers

An identifier is a plain string. `Config::dynamic('')` throws an `InvalidArgumentException`, and passing an array of identifiers is a `TypeError`: load several scopes with several calls.

:::tip Name identifiers by type
Prefix identifiers with their type, such as `company-1` and `branch-12`, so two kinds of scope with the same number never share values.
:::
