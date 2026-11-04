<?php

declare(strict_types=1);

namespace Pharaonic\Laravel\DynamicConfig;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Pharaonic\Laravel\DynamicConfig\Exceptions\UnsupportedValueException;

/**
 * The persisted values of one dynamic config scope: the global scope
 * (identifier null), one named identifier, or one saved model, stored as the
 * identifier "{morph class}:{key}". Returned by Config::dynamic().
 *
 * get(), has() and all() read only this scope's stored values; load() merges
 * them into the config repository, where config() reads the final value.
 */
final class DynamicConfig
{
    private ?string $identifier;

    /**
     * @internal Use Config::dynamic($identifier).
     */
    public function __construct(string|Model|null $identifier = null)
    {
        if ($identifier instanceof Model) {
            $key = $identifier->getKey();

            if (! $identifier->exists || (! is_int($key) && (! is_string($key) || $key === ''))) {
                throw new InvalidArgumentException('The dynamic config model ['.$identifier::class.'] must be saved with a string or integer key.');
            }

            $identifier = $identifier->getMorphClass().':'.$key;
        }

        if ($identifier === '') {
            throw new InvalidArgumentException('The dynamic config identifier must be null, a non-empty string or a saved model.');
        }

        $this->identifier = $identifier;
    }

    /**
     * Merge this scope's values into the config repository. Associative
     * arrays merge recursively into the current value; scalars and lists
     * replace it. A later load() wins over an earlier one.
     */
    public function load(): void
    {
        foreach ($this->all() as $key => $value) {
            $current = Config::get((string) $key);

            Config::set((string) $key, self::isMergeable($value) && is_array($current) ? self::merge($current, $value) : $value);
        }
    }

    /**
     * The value stored for the key in this scope, without falling back to
     * other scopes or to the config files.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->all();

        return array_key_exists($key, $values) ? $values[$key] : value($default);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * Store the value for the key in this scope. The config repository only
     * changes on the next load().
     *
     * @throws UnsupportedValueException
     */
    public function set(string $key, mixed $value): void
    {
        if ($key === '' || in_array('', explode('.', $key), true)) {
            throw new InvalidArgumentException("The dynamic config key [{$key}] must be a dot-separated key without empty segments.");
        }

        $this->ensureSupported($key, $value);

        $row = [
            'value' => json_encode($value, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR),
            'updated_at' => $now = Carbon::now(),
        ];

        if ($this->identifier !== null) {
            $this->table()->upsert(
                [['identifier' => $this->identifier, 'key' => $key, 'created_at' => $now] + $row],
                ['identifier', 'key'],
                ['value', 'updated_at'],
            );
        } elseif ($this->scope()->where('key', $key)->exists()) {
            $this->scope()->where('key', $key)->update($row);
        } else {
            $id = $this->table()->insertGetId(['identifier' => null, 'key' => $key, 'created_at' => $now] + $row);

            // A unique index does not stop two NULL identifiers sharing a key,
            // so concurrent inserts keep only the newest row.
            $this->scope()->where('key', $key)->where('id', '<', $id)->delete();
        }

        $this->forgetCache();
    }

    /**
     * Delete the key from this scope. Other scopes and the config files keep
     * their values.
     */
    public function forget(string $key): void
    {
        $this->scope()->where('key', $key)->delete();

        $this->forgetCache();
    }

    /**
     * Every value stored in this scope, keyed by its dotted key, in key order.
     *
     * @return array<array-key, mixed>
     */
    public function all(): array
    {
        if (! $this->setting('cache.enabled')) {
            return $this->fetch();
        }

        $ttl = $this->setting('cache.ttl');

        /** @var array<array-key, mixed> */
        return $this->store()->remember($this->cacheKey(), is_numeric($ttl) ? (int) $ttl : null, fn (): array => $this->fetch());
    }

    /**
     * @return array<array-key, mixed>
     */
    private function fetch(): array
    {
        // Ordered by id, so the newest row wins while a concurrent duplicate
        // of a global key still exists. String key order puts a parent key
        // before its children, so load() sets "mail" before "mail.from.address".
        return $this->scope()->orderBy('id')->pluck('value', 'key')
            ->map(fn (mixed $value): mixed => is_string($value) ? json_decode($value, true, 512, JSON_THROW_ON_ERROR) : null)
            ->sortKeys(SORT_STRING)
            ->all();
    }

    private function ensureSupported(string $key, mixed $value): void
    {
        if (is_array($value)) {
            foreach ($value as $nested => $item) {
                $this->ensureSupported("{$key}.{$nested}", $item);
            }

            return;
        }

        $type = match (true) {
            is_float($value) && ! is_finite($value) => 'non-finite float',
            is_string($value) && preg_match('//u', $value) !== 1 => 'non-UTF-8 string',
            $value === null, is_scalar($value) => null,
            default => get_debug_type($value),
        };

        if ($type !== null) {
            throw new UnsupportedValueException($key, $type, $this->identifier);
        }
    }

    /**
     * @phpstan-assert-if-true array<array-key, mixed> $value
     */
    private static function isMergeable(mixed $value): bool
    {
        return is_array($value) && Arr::isAssoc($value);
    }

    /**
     * @param  array<array-key, mixed>  $base
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private static function merge(array $base, array $values): array
    {
        foreach ($values as $key => $value) {
            $current = $base[$key] ?? null;

            $base[$key] = self::isMergeable($value) && is_array($current) ? self::merge($current, $value) : $value;
        }

        return $base;
    }

    private function table(): Builder
    {
        $connection = $this->setting('connection');
        $table = $this->setting('table');

        return DB::connection(is_string($connection) ? $connection : null)
            ->table(is_string($table) ? $table : 'dynamic_configs');
    }

    private function scope(): Builder
    {
        return $this->identifier === null
            ? $this->table()->whereNull('identifier')
            : $this->table()->where('identifier', $this->identifier);
    }

    private function forgetCache(): void
    {
        if ($this->setting('cache.enabled')) {
            $this->store()->forget($this->cacheKey());
        }
    }

    private function store(): CacheRepository
    {
        $store = $this->setting('cache.store');

        return Cache::store(is_string($store) ? $store : null);
    }

    private function cacheKey(): string
    {
        // Hashing the connection, table and identifier keeps "global" apart
        // from an identifier named "global" and keeps keys store-safe.
        return 'pharaonic:dynamic-config:'.sha1((string) json_encode([
            $this->setting('connection'),
            $this->setting('table'),
            $this->identifier,
        ]));
    }

    private function setting(string $key): mixed
    {
        return Config::get("pharaonic.dynamic-config.{$key}");
    }
}
