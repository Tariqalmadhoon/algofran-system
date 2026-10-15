<?php

$configuredDriver = strtolower(trim((string) env('HASH_DRIVER', 'bcrypt')));
$driver = match ($configuredDriver) {
    'bcrypt', 'bycrypt' => 'bcrypt',
    'argon', 'argon2id' => $configuredDriver,
    default => 'bcrypt',
};

return [
    'driver' => $driver,

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => env('HASH_VERIFY', true),
        'limit' => env('BCRYPT_LIMIT'),
    ],

    'argon' => [
        'memory' => env('ARGON_MEMORY', 65536),
        'threads' => env('ARGON_THREADS', 1),
        'time' => env('ARGON_TIME', 4),
        'verify' => env('HASH_VERIFY', true),
    ],

    'rehash_on_login' => true,
];
