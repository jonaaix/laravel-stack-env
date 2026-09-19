<?php

declare(strict_types=1);

use Aaix\LaravelStackEnv\LoadEnvironmentVariables;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables as FrameworkBootstrapper;
use Illuminate\Support\Env;

function fakeApplication(string $directory): object
{
    return new class($directory)
    {
        public function __construct(private string $directory) {}

        public function environmentPath(): string
        {
            return $this->directory;
        }

        public function environmentFile(): string
        {
            return '.env';
        }
    };
}

it('keeps a signature the framework bootstrapper accepts', function () {
    $override = new ReflectionMethod(LoadEnvironmentVariables::class, 'createDotenv');
    $framework = new ReflectionMethod(FrameworkBootstrapper::class, 'createDotenv');

    expect($override->getDeclaringClass()->getName())->toBe(LoadEnvironmentVariables::class)
        ->and($override->getNumberOfParameters())->toBe($framework->getNumberOfParameters())
        ->and($override->getParameters()[0]->hasType())->toBeFalse();
});

it('loads both layers through the framework repository', function () {
    $directory = temporaryDirectory();

    file_put_contents($directory.'/.env', "STACK_ENV_BOOT_ENV_ONLY=from_env\nSTACK_ENV_BOOT_SHARED=from_env\n");
    file_put_contents($directory.'/.env.stack', "STACK_ENV_BOOT_SHARED=from_stack\nSTACK_ENV_BOOT_STACK_ONLY=from_stack\n");

    (new ReflectionMethod(LoadEnvironmentVariables::class, 'createDotenv'))
        ->invoke(new LoadEnvironmentVariables, fakeApplication($directory))
        ->safeLoad();

    expect(Env::get('STACK_ENV_BOOT_STACK_ONLY'))->toBe('from_stack')
        ->and(Env::get('STACK_ENV_BOOT_ENV_ONLY'))->toBe('from_env')
        ->and(Env::get('STACK_ENV_BOOT_SHARED'))->toBe('from_env');
});
