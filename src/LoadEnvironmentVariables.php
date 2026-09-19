<?php

declare(strict_types=1);

namespace Aaix\LaravelStackEnv;

use Dotenv\Dotenv;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables as BaseLoadEnvironmentVariables;
use Illuminate\Support\Env;

class LoadEnvironmentVariables extends BaseLoadEnvironmentVariables
{
    // Parameter stays untyped: the parent declares none, and narrowing it would be a fatal error.
    protected function createDotenv($app): Dotenv
    {
        return (new StackEnvLoader(Env::getRepository()))->load(
            $app->environmentPath(),
            $app->environmentFile()
        );
    }
}
