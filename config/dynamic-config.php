<?php

declare(strict_types=1);

return [
    /**
     * The database connection that stores the dynamic values. Null uses the
     * default connection.
     */
    'connection' => env('DYNAMIC_CONFIG_CONNECTION'),

    /**
     * The table that stores the dynamic values.
     */
    'table' => env('DYNAMIC_CONFIG_TABLE', 'dynamic_configs'),

    /**
     * Each scope's values are cached as one entry and forgotten whenever that
     * scope is written. `store` null uses the default cache store, and `ttl`
     * null keeps the entry until the next write.
     */
    'cache' => [
        'enabled' => env('DYNAMIC_CONFIG_CACHE', true),
        'store' => env('DYNAMIC_CONFIG_CACHE_STORE'),
        'ttl' => null,
    ],
];
