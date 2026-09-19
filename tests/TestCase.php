<?php

declare(strict_types=1);

namespace Aaix\LaravelStackEnv\Tests;

use Aaix\LaravelStackEnv\StackEnvServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            StackEnvServiceProvider::class,
        ];
    }
}
