<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Integration;

use FlavioMoreir4\Transfeera\Tests\TestCase;

/**
 * Base class for integration tests that hit the real Transfeera API.
 *
 * All tests extending this class will be skipped unless a .env file
 * with valid API credentials exists at the project root.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected function setUp(): void
    {
        if (! $this->envFileExists()) {
            $this->markTestSkipped(
                'Integration tests require a .env file with real API credentials. '
                .'Copy .env.example to .env and fill in your Transfeera sandbox credentials.'
            );
        }

        parent::setUp();
    }

    /**
     * Check whether a .env file exists at the project root.
     */
    protected function envFileExists(): bool
    {
        return file_exists($this->envPath());
    }

    /**
     * Path to the .env file expected at the project root.
     */
    protected function envPath(): string
    {
        return dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'.env';
    }
}
