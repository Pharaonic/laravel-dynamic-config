<p align="center"><a href="https://pharaonic.dev/packages/laravel/dynamic-config" target="_blank"><img src="https://raw.githubusercontent.com/Pharaonic/laravel-dynamic-config/develop/docs/cover.webp"></a></p>

<p align="center">
  <a href="https://php.net" target="_blank"><img src="https://img.shields.io/static/v1?label=PHP&message=8.1%20|%208.2%20|%208.3&color=blue&style=flat-square" alt="PHP Version : 8.1, 8.2, 8.3"></a>
  <a href="https://laravel.com" target="_blank"><img src="https://img.shields.io/static/v1?label=Laravel&message=10.x&color=F05340&style=flat-square" alt="Laravel Version : 10.x"></a>
  <img src="https://img.shields.io/static/v1?label=License&message=MIT&color=brightgreen&style=flat-square" alt="License">
  <img src="https://github.com/Pharaonic/laravel-dynamic-config/actions/workflows/build.yml/badge.svg?branch=10.x" alt="Build">
  <br>
  <a href="https://packagist.org/packages/Pharaonic/laravel-dynamic-config" target="_blank"><img src="https://img.shields.io/static/v1?label=Packagist&message=pharaonic/laravel-dynamic-config&color=blue&logo=packagist&logoColor=white" alt="Source"></a>
  <a href="https://packagist.org/packages/pharaonic/laravel-dynamic-config" target="_blank"><img src="https://poser.pugx.org/pharaonic/laravel-dynamic-config/v" alt="Packagist Version"></a>
  <a href="https://packagist.org/packages/pharaonic/laravel-dynamic-config" target="_blank"><img src="https://poser.pugx.org/pharaonic/laravel-dynamic-config/downloads" alt="Packagist Downloads"></a>
</p>

<h3 align="center">Store Laravel configuration values in the database, globally, per identifier or per model, and load them into config() when you choose.</h3>
<br>

## Installation

```bash
composer require pharaonic/laravel-dynamic-config
php artisan migrate
```

The service provider is registered automatically. Publish the configuration when you need to change it:

```bash
php artisan vendor:publish --tag=dynamic-config-config
```

## Usage

```php
use Illuminate\Support\Facades\Config;

// Persist values: the global scope, one identifier, or one saved model.
Config::dynamic()->set('mail.from.address', 'global@example.com');
Config::dynamic('branch-12')->set('mail.from.address', 'branch@example.com');
Config::dynamic($company)->set('app.timezone', 'Africa/Cairo');

// Apply them, in the order you want them to win.
Config::dynamic()->load();
Config::dynamic('branch-12')->load();

// Read the final value with Laravel itself.
config('mail.from.address'); // branch@example.com
```

A model scope is stored under the identifier `{morph class}:{key}`. Each `load()` merges one scope only, and nothing is loaded automatically. `get()`, `has()`, `all()` and `forget()` work on the values stored in that one scope; `config()` returns the resolved runtime value.

Every scope is cached until it is written. Loaded values live only in memory, on top of Laravel's config cache. Long-running processes such as queue workers keep them between jobs, so load each scope where its requests or jobs start.

## Documentation

Full documentation: [Laravel Dynamic Config Documentation](https://pharaonic.dev/packages/laravel/dynamic-config).

## Support & Security

See [SUPPORT.md](SUPPORT.md) and [SECURITY.md](SECURITY.md). Contributions are welcome; see [CONTRIBUTING.md](CONTRIBUTING.md).

## License

MIT. See [LICENSE](LICENSE).
