---
view: components.packages.quick-look
title: A quick look
subtitle: Persist values per scope, load them in order, and read the result with config().
file: app/Http/Middleware/LoadBranchConfig.php
language: php
code: |
  use Illuminate\Support\Facades\Config;

  // Persist: once, from a settings page or a seeder.
  Config::dynamic()->set('mail.from.address', 'global@example.com');
  Config::dynamic('branch-12')->set('mail.from.address', 'branch@example.com');

  // Apply: on every request, in the order you want them to win.
  Config::dynamic()->load();
  Config::dynamic('branch-12')->load();

  // Read: with Laravel itself.
  config('mail.from.address'); // "branch@example.com"
---
