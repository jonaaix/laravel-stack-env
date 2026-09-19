<?php

declare(strict_types=1);

use Aaix\LaravelStackEnv\StackEnvLoader;
use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;

function isolatedRepository(): RepositoryInterface
{
    return RepositoryBuilder::createWithNoAdapters()
        ->addAdapter(ArrayAdapter::class)
        ->immutable()
        ->make();
}

function environmentDirectory(?string $env, ?string $stack): string
{
    $path = temporaryDirectory();

    if ($env !== null) {
        file_put_contents($path.'/.env', $env);
    }

    if ($stack !== null) {
        file_put_contents($path.'/.env.stack', $stack);
    }

    return $path;
}

function loadLayers(RepositoryInterface $repository, string $path): void
{
    // phpdotenv reads a missing file through @file_get_contents; Pest surfaces that suppressed warning anyway.
    set_error_handler(static fn (): bool => true, E_WARNING);

    try {
        (new StackEnvLoader($repository))->load($path, '.env')->safeLoad();
    } finally {
        restore_error_handler();
    }
}

it('applies a value that only the stack file defines', function () {
    $repository = isolatedRepository();

    loadLayers($repository, environmentDirectory("APP_NAME=personal\n", "DB_CONNECTION=mysql\n"));

    expect($repository->get('DB_CONNECTION'))->toBe('mysql');
});

it('lets the developer env file win over the stack file', function () {
    $repository = isolatedRepository();

    loadLayers($repository, environmentDirectory("DB_CONNECTION=sqlite\n", "DB_CONNECTION=mysql\n"));

    expect($repository->get('DB_CONNECTION'))->toBe('sqlite');
});

it('leaves a real environment variable untouched', function () {
    $_SERVER['STACK_ENV_PRECEDENCE_PROBE'] = 'from_system';

    $repository = RepositoryBuilder::createWithDefaultAdapters()->immutable()->make();

    loadLayers($repository, environmentDirectory(
        "STACK_ENV_PRECEDENCE_PROBE=from_env\n",
        "STACK_ENV_PRECEDENCE_PROBE=from_stack\n"
    ));

    expect($repository->get('STACK_ENV_PRECEDENCE_PROBE'))->toBe('from_system');

    unset($_SERVER['STACK_ENV_PRECEDENCE_PROBE']);
});

it('works when the stack file is missing', function () {
    $repository = isolatedRepository();

    loadLayers($repository, environmentDirectory("DB_CONNECTION=sqlite\n", null));

    expect($repository->get('DB_CONNECTION'))->toBe('sqlite');
});

it('works when the developer env file is missing', function () {
    $repository = isolatedRepository();

    loadLayers($repository, environmentDirectory(null, "DB_CONNECTION=mysql\n"));

    expect($repository->get('DB_CONNECTION'))->toBe('mysql');
});
