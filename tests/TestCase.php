<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\RateLimiter;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear rate-limiter state between tests so accumulated hits from
        // one test never leak a spurious 429 into another. RefreshDatabase
        // resets the DB but not the cache-backed limiter counters.
        RateLimiter::clear('api');
    }
}