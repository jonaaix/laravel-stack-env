<?php

declare(strict_types=1);

namespace Aaix\LaravelStackEnv;

use Aaix\LaravelStackEnv\Console\InstallCmd;
use Illuminate\Support\ServiceProvider;

class StackEnvServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([InstallCmd::class]);
        }
    }
}
