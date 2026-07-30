<?php

declare(strict_types=1);

use FlavioMoreir4\Transfeera\Facades\Transfeera;

/**
 * Example integration tests — placeholder for real API calls.
 *
 * These tests connect to the actual Transfeera sandbox API and are
 * automatically skipped when no .env file with credentials is present.
 *
 * To run them:
 *   1. Copy .env.example to .env at the project root.
 *   2. Fill in your Transfeera sandbox client_id and client_secret.
 *   3. Run: vendor/bin/pest --testsuite=Integration
 */
test('sandbox credentials are reachable', function () {
    $balance = Transfeera::statement()->getBalance();

    expect($balance->balance)->toBeInt();
})->skip(fn () => ! file_exists(dirname(__DIR__).'/.env'),
    'No .env file found — integration tests require real credentials.'
);

test('list pix keys from sandbox', function () {
    $pixKeys = Transfeera::pixKeys()->list();

    expect($pixKeys)->toBeArray();
})->skip(fn () => ! file_exists(dirname(__DIR__).'/.env'),
    'No .env file found — integration tests require real credentials.'
);
