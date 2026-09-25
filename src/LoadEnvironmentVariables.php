<?php

declare(strict_types=1);

namespace Aaix\LaravelStackEnv;

use Dotenv\Dotenv;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables as BaseLoadEnvironmentVariables;
use Illuminate\Support\Env;

class LoadEnvironmentVariables extends BaseLoadEnvironmentVariables
{
    private ?StackEnvLoader $stackLoader = null;

    public function bootstrap(Application $app): void
    {
        parent::bootstrap($app);

        if ($this->stackLoader === null) {
            return;
        }

        // `artisan test` hands its own environment to the phpunit process, where a stack value would outrank phpunit.xml; it only clears the keys of `.env`.
        $app['events']->listen(CommandStarting::class, function (CommandStarting $event): void {
            if ($event->command === 'test') {
                $this->stackLoader->forgetStackValues();
            }
        });
    }

    // Parameter stays untyped: the parent declares none, and narrowing it would be a fatal error.
    protected function createDotenv($app): Dotenv
    {
        $this->stackLoader = new StackEnvLoader(Env::getRepository());

        return $this->stackLoader->load(
            $app->environmentPath(),
            $app->environmentFile()
        );
    }
}
