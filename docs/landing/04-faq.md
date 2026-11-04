---
view: components.home.faq
badge: FAQ
title: "{package.name}"
highlight: Questions
subtitle: "Quick answers about installing and using {package.name}."
---

## What is {package.name}?

{card.description} It's a free, open-source {technology.name} package by Pharaonic.

## How do I install {package.name}?

Run `composer require {package.composer}` in your project's root directory, then `php artisan migrate`.

## What does {package.name} require?

The latest release requires {package.requiresText}.

## Does loading a branch also load the global values?

No. Each `load()` applies exactly one scope. Call `Config::dynamic()->load()` and then `Config::dynamic('branch-12')->load()` to layer the branch on top of the global values.

## Does it query the database on every config() call?

No. `load()` merges the values into Laravel's config repository once, and each scope is cached until it is written. `config()` then reads memory as usual.

## Does it work with config:cache, Octane and queue workers?

Yes. Dynamic values are applied on top of the cached config at runtime and never written into the cache file. Octane gives each request its own config, and the documentation shows how to restore the config between queue jobs.

## Is {package.name} free to use?

Yes. {package.name} is open source under the {package.license} license, so you can use it in personal and commercial projects.

## Where can I find the {package.name} documentation?

Read the [{package.name} documentation]({package.docsUrl}) for setup, configuration, and usage examples.

## How do I report a bug or contribute to {package.name}?

Open an issue or a pull request on [GitHub]({package.githubUrl}), or ask in the [Pharaonic Discord](https://discord.gg/XQG9RhvEvf).
