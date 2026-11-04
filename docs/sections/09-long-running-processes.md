## Long-Running Processes

`load()` changes the config repository of the running application. In a normal PHP-FPM request that application ends with the request. Processes that serve several requests or jobs with one application need more care.

### Queue workers

`php artisan queue:work` boots the application once and keeps its config repository between jobs. A scope one job loads is still loaded for the next job, which may belong to another branch or tenant.

Load scopes in a job middleware that puts the configuration back when the job ends:

```php title="app/Jobs/Middleware/LoadBranchConfig.php"
namespace App\Jobs\Middleware;

use Illuminate\Support\Facades\Config;

class LoadBranchConfig
{
    public function handle(object $job, callable $next): void
    {
        $config = config();
        $original = $config->all();

        try {
            Config::dynamic()->load();
            Config::dynamic($job->branch)->load();

            $next($job);
        } finally {
            foreach (array_diff_key($config->all(), $original) as $key => $value) {
                unset($config[$key]);
            }

            $config->set($original);
        }
    }
}
```

The `finally` block restores every top-level key to its value before the job and sets new top-level keys to `null`.

:::warning Services resolved while a scope is loaded
Restoring the configuration does not rebuild services that already read it, such as a mailer or a cache store resolved during the job. Load scopes before resolving the services that depend on them.
:::

### Laravel Octane

Octane gives every request its own copy of the config repository, and the package always uses the copy of the current request. Scopes loaded during one request don't leak into the next one, so load them in a middleware, as you would without Octane.

### Config cache

`php artisan config:cache` caches your config files only. Dynamic values are never written into `bootstrap/cache/config.php`; they are applied on top of the cached configuration each time you call `load()`. Clearing or rebuilding the config cache doesn't touch the stored values.

### Nothing loads at boot

The service provider never queries the database. Artisan commands, migrations, tests and queue workers start with your config files only, until your own code calls `load()`.
