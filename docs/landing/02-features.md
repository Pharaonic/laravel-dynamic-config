---
view: components.packages.features
variant: compact
badge: Key Features
title: Everything you need to configure at runtime
subtitle: One macro on the Config facade, and Laravel's own config() for everything else.
items:
  - icon: laravel
    title: Native Config API
    text: "`Config::dynamic()` extends Laravel's Config facade, and you read final values with `config()`."
  - icon: grid
    title: Global and Named Scopes
    text: Store values for the whole application or per identifier, such as a company, a branch or a tenant.
  - icon: switch
    title: Explicit Layering
    text: Each `load()` applies one scope, and the scope you load last wins.
  - icon: code-brackets
    title: Safe Merging
    text: "`mail.from.address` overrides one value and keeps `mail.from.name`; associative arrays merge, lists are replaced."
  - icon: bolt
    title: Cached Scopes
    text: One cache entry per scope, forgotten automatically by `set()` and `forget()`.
  - icon: shield-check
    title: Typed and Safe
    text: Values keep their types as JSON, and unsupported values fail with errors that never include the value.
---
