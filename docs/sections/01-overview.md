:::badges
- Laravel Package {color=blue}
- {release.label} {color=green}
- {package.license} License {color=purple}
:::

# Laravel Dynamic Config

Laravel Dynamic Config stores configuration values in a database table and merges them into Laravel's own config repository when you ask it to. Values live in a global scope or in named scopes such as a company or a branch. `Config::dynamic()->load()` applies one scope, and you keep reading the result with `config()` and `Config::get()`.

The whole mental model fits in three lines:

```php no-copy
Config::dynamic('branch-12')->set('mail.from.address', 'branch@example.com'); // persist
Config::dynamic('branch-12')->load();                                         // apply
config('mail.from.address');                                                  // read
```

:::features
### Native Config API {icon="laravel"}
A dynamic macro on Laravel's Config facade, with no extra facade and no custom getter for the final value.

### Global and Named Scopes {icon="grid"}
Store values globally or per identifier, such as a company, a branch or a tenant.

### Explicit Layering {icon="switch"}
Each load applies one scope, and the scope you load last wins.

### Safe Merging {icon="code-brackets"}
A nested key or an associative array overrides its target and keeps its siblings.

### Typed Values {icon="badge-check"}
Null, booleans, integers, floats, strings and arrays come back with their original types.

### Cached Scopes {icon="bolt"}
One cache entry per scope, forgotten automatically whenever that scope is written.
:::

:::info Quick Tip
Nothing is loaded automatically. Load the scopes you need where your request or job starts, in the order you want them to win.
:::
