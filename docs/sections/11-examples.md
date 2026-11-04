## Examples

### 1. Per-branch configuration in a middleware

Load the global scope and the signed-in user's branch on every request, so mail, services and app settings follow the branch.

- ===Middleware

  ```php title="app/Http/Middleware/LoadBranchConfig.php"
  namespace App\Http\Middleware;

  use Closure;
  use Illuminate\Http\Request;
  use Illuminate\Support\Facades\Config;

  class LoadBranchConfig
  {
      public function handle(Request $request, Closure $next)
      {
          Config::dynamic()->load();

          if ($branch = $request->user()?->branch_id) {
              Config::dynamic("branch-{$branch}")->load();
          }

          return $next($request);
      }
  }
  ```

- ===Kernel

  ```php title="app/Http/Kernel.php"
  protected $middlewareGroups = [
      'web' => [
          // ...
          \App\Http\Middleware\LoadBranchConfig::class,
      ],
  ];
  ```

- ===Usage

  ```php title="app/Http/Controllers/InvoiceController.php"
  Mail::to($customer)->send(new InvoiceMail($invoice));
  // sent from config('mail.from.address') of the branch
  ```

### 2. A settings form

Save validated settings into a branch scope. The new values apply from the next request, when the middleware loads the scope again.

```php title="app/Http/Controllers/BranchSettingsController.php"
namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class BranchSettingsController extends Controller
{
    public function edit(Branch $branch)
    {
        $scope = Config::dynamic("branch-{$branch->id}");

        return view('branches.settings', [
            'branch' => $branch,
            'fromAddress' => $scope->get('mail.from.address', config('mail.from.address')),
            'fromName' => $scope->get('mail.from.name', config('mail.from.name')),
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'from_address' => ['nullable', 'email'],
            'from_name' => ['nullable', 'string', 'max:100'],
        ]);

        $scope = Config::dynamic("branch-{$branch->id}");

        foreach (['mail.from.address' => 'from_address', 'mail.from.name' => 'from_name'] as $key => $field) {
            if (filled($data[$field] ?? null)) {
                $scope->set($key, $data[$field]);
            } else {
                $scope->forget($key);
            }
        }

        return back()->with('status', 'Settings saved.');
    }
}
```

Forgetting an empty field brings the branch back to the global or config-file value instead of storing an empty string.

### 3. Global feature flags

Keep application-wide switches in the global scope and load it in a service provider for web requests.

```php title="app/Providers/AppServiceProvider.php"
use Illuminate\Support\Facades\Config;

public function boot(): void
{
    if (! $this->app->runningInConsole()) {
        Config::dynamic()->load();
    }
}
```

```php title="Anywhere"
Config::dynamic()->set('features.new_checkout', true);

if (config('features.new_checkout')) {
    // ...
}
```

The console check keeps `php artisan migrate` working before the table exists. Load the scope in your commands and jobs where they need it.

### 4. Tenant settings on a central connection

Store every tenant's scope in the central database, whichever connection the tenant itself uses.

```bash title=".env" no-line-numbers
DYNAMIC_CONFIG_CONNECTION=central
DYNAMIC_CONFIG_CACHE_STORE=redis
```

```php title="app/Tenancy/BootstrapTenant.php"
Config::dynamic()->load();
Config::dynamic("tenant-{$tenant->getKey()}")->load();
```

### 5. Copying a scope

`all()` returns the stored keys of a scope, so copying one branch's settings to a new branch takes one loop:

```php
foreach (Config::dynamic('branch-12')->all() as $key => $value) {
    Config::dynamic('branch-13')->set($key, $value);
}
```
