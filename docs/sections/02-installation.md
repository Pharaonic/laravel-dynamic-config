## Installation

Install the package with Composer and create its table.

### Requirements

- PHP 8.1, 8.2 or 8.3
- Laravel 10.x

### Composer Installation

```bash title="Terminal" no-line-numbers
composer require pharaonic/laravel-dynamic-config
```

The service provider is registered automatically and adds the `dynamic()` macro to the config repository.

### Run Migrations

The package loads its migration automatically, so the `dynamic_configs` table is created with your other migrations:

```bash title="Terminal" no-line-numbers
php artisan migrate
```

To change the migration before it runs, publish it into `database/migrations`:

```bash title="Terminal" no-line-numbers
php artisan vendor:publish --tag=dynamic-config-migrations
```

### Publish Configuration

Publish the configuration only when you need another connection, table name or cache setup:

```bash title="Terminal" no-line-numbers
php artisan vendor:publish --tag=dynamic-config-config
```

The file is published to `config/pharaonic/dynamic-config.php`.

:::success Installation Complete
You're all set! Store your first value with `Config::dynamic()->set()`.
:::
