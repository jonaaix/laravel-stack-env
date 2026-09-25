<?php

declare(strict_types=1);

use Aaix\LaravelStackEnv\LoadEnvironmentVariables;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Foundation\Application;
use Illuminate\Support\Env;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

function bootedApplication(string $stack): Application
{
    $directory = temporaryDirectory();
    file_put_contents($directory.'/.env', '');
    file_put_contents($directory.'/.env.stack', $stack);

    $app = new Application($directory);
    (new LoadEnvironmentVariables)->bootstrap($app);

    return $app;
}

function startCommand(Application $app, string $command): void
{
    $app['events']->dispatch(new CommandStarting($command, new ArrayInput([]), new NullOutput));
}

afterEach(function () {
    foreach (['STACK_ENV_TEST_CMD_DATABASE', 'STACK_ENV_TEST_CMD_REAL'] as $name) {
        Env::getRepository()->clear($name);
        unset($_SERVER[$name], $_ENV[$name]);
        putenv($name);
    }
});

it('takes the stack values back before artisan test starts phpunit', function () {
    $app = bootedApplication("STACK_ENV_TEST_CMD_DATABASE=dev_database\n");

    startCommand($app, 'test');

    expect(Env::get('STACK_ENV_TEST_CMD_DATABASE'))->toBeNull()
        ->and($_SERVER)->not->toHaveKey('STACK_ENV_TEST_CMD_DATABASE')
        ->and(getenv('STACK_ENV_TEST_CMD_DATABASE'))->toBeFalse();
});

it('keeps the stack values for every other command', function () {
    $app = bootedApplication("STACK_ENV_TEST_CMD_DATABASE=dev_database\n");

    startCommand($app, 'migrate');

    expect(Env::get('STACK_ENV_TEST_CMD_DATABASE'))->toBe('dev_database');
});

it('leaves a real environment variable in place for artisan test', function () {
    $_SERVER['STACK_ENV_TEST_CMD_REAL'] = 'from_system';
    $app = bootedApplication("STACK_ENV_TEST_CMD_REAL=from_stack\n");

    startCommand($app, 'test');

    expect($_SERVER['STACK_ENV_TEST_CMD_REAL'])->toBe('from_system');
});
