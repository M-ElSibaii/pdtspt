<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests refresh the database. Refuse to run against anything but a
        // dedicated *_test database (phpunit.xml sets DB_DATABASE=pdtspt_test), so a
        // missing/overridden setting can never wipe the development data.
        $db = (string) config('database.connections.' . config('database.default') . '.database');
        if (!str_ends_with($db, '_test') && $db !== ':memory:') {
            throw new \RuntimeException("Refusing to run tests against database '{$db}' — use a *_test database.");
        }
    }
}
