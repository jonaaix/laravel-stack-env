<?php

declare(strict_types=1);

use Aaix\LaravelStackEnv\StackEnvLoader;

function applicationRoot(string $skeleton = 'bootstrap-app-modern.php.stub'): string
{
    $path = temporaryDirectory();

    mkdir($path.'/bootstrap');
    file_put_contents($path.'/bootstrap/app.php', skeletonFixture($skeleton));

    return $path;
}

it('patches the bootstrap file and creates the stack env file', function () {
    $root = applicationRoot();
    $this->app->setBasePath($root);

    $this->artisan('stack-env:install')->assertSuccessful();

    expect(file_get_contents($root.'/bootstrap/app.php'))
        ->toContain('\Aaix\LaravelStackEnv\LoadEnvironmentVariables::class');

    expect(file_exists($root.'/bootstrap/app.php.bak'))->toBeTrue();
    expect(file_get_contents($root.'/'.StackEnvLoader::STACK_FILE))
        ->toContain('Stack environment defaults');
});

it('changes nothing on a second run', function () {
    $root = applicationRoot();
    $this->app->setBasePath($root);

    $this->artisan('stack-env:install')->assertSuccessful();

    $bootstrap = file_get_contents($root.'/bootstrap/app.php');
    file_put_contents($root.'/'.StackEnvLoader::STACK_FILE, "DB_CONNECTION=mysql\n");

    $this->artisan('stack-env:install')->assertSuccessful();

    expect(file_get_contents($root.'/bootstrap/app.php'))->toBe($bootstrap);
    expect(file_get_contents($root.'/'.StackEnvLoader::STACK_FILE))->toBe("DB_CONNECTION=mysql\n");
});

it('leaves an unrecognised bootstrap file untouched', function () {
    $root = applicationRoot('bootstrap-app-unpatchable.php.stub');
    $this->app->setBasePath($root);

    $original = file_get_contents($root.'/bootstrap/app.php');

    $this->artisan('stack-env:install')->assertFailed();

    expect(file_get_contents($root.'/bootstrap/app.php'))->toBe($original);
    expect(file_exists($root.'/'.StackEnvLoader::STACK_FILE))->toBeFalse();
});

it('fails when the application skeleton is missing', function () {
    $this->app->setBasePath(temporaryDirectory());

    $this->artisan('stack-env:install')->assertFailed();
});

it('warns when the stack env file would be ignored by git', function () {
    $root = applicationRoot();
    file_put_contents($root.'/.gitignore', "/vendor\n.env*\n");
    $this->app->setBasePath($root);

    $this->artisan('stack-env:install')
        ->expectsOutputToContain('.gitignore')
        ->assertSuccessful();

    expect(file_get_contents($root.'/.gitignore'))->toBe("/vendor\n.env*\n");
});

it('stays quiet when the stack env file is not ignored by git', function () {
    $root = applicationRoot();
    file_put_contents($root.'/.gitignore', "/vendor\n.env*\n!.env.stack\n");
    $this->app->setBasePath($root);

    $this->artisan('stack-env:install')
        ->doesntExpectOutputToContain('.gitignore')
        ->assertSuccessful();
});
