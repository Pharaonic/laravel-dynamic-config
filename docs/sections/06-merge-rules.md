## Merge Rules

`load()` writes each stored key into the config repository without deleting unrelated configuration.

| Stored value | Current config value | Result |
| --- | --- | --- |
| Scalar or `null` | anything | Replaced. |
| List (`['a', 'b']`) | anything | Replaced, never appended. |
| Empty array `[]` | anything | Replaced with `[]`. |
| Associative array | array | Merged recursively, key by key. |
| Associative array | missing or scalar | Replaced. |

### Dotted keys keep their siblings

A dotted key changes only its own leaf:

```php
// config/mail.php: 'from' => ['address' => 'default@example.com', 'name' => 'My App']

Config::dynamic('branch-12')->set('mail.from.address', 'branch@example.com');
Config::dynamic('branch-12')->load();

config('mail.from.address'); // "branch@example.com"
config('mail.from.name');    // "My App"
```

Keys the config files don't have are created:

```php
Config::dynamic()->set('services.mailgun.domain', 'example.com');
Config::dynamic()->load();

config('services.mailgun.domain'); // "example.com"
```

### Associative arrays merge recursively

```php
// config: 'services.mailgun' => ['domain' => 'base.com', 'secret' => 'base-secret', 'options' => ['timeout' => 5, 'retries' => 1]]

Config::dynamic()->set('services.mailgun', ['domain' => 'dynamic.com', 'options' => ['timeout' => 10]]);
Config::dynamic()->load();

config('services.mailgun');
// ['domain' => 'dynamic.com', 'secret' => 'base-secret', 'options' => ['timeout' => 10, 'retries' => 1]]
```

### Lists are replaced

```php
// config: 'app.hosts' => ['a.test', 'b.test', 'c.test']

Config::dynamic()->set('app.hosts', ['z.test']);
Config::dynamic()->load();

config('app.hosts'); // ['z.test']
```

A list nested inside an associative array is replaced too, while the array's other keys merge.

### Parent keys come first

Within one scope, keys are applied in string order, so a parent key is always applied before its children:

```php
Config::dynamic()->set('mail.from.address', 'child@example.com');
Config::dynamic()->set('mail', ['from' => ['address' => 'parent@example.com']]);

Config::dynamic()->load();

config('mail.from.address'); // "child@example.com"
```

:::info Config cache
`load()` only changes the config repository in memory. It never writes `bootstrap/cache/config.php`, so `php artisan config:cache` keeps caching your config files, and dynamic values are applied on top of them at runtime.
:::
