<?php

declare(strict_types=1);

use Aaix\LaravelStackEnv\Exceptions\BootstrapPatchFailed;
use Aaix\LaravelStackEnv\Support\BootstrapPatcher;

it('wires the loader into the application builder skeleton', function () {
    $patched = (new BootstrapPatcher)->patch(skeletonFixture('bootstrap-app-modern.php.stub'));

    expect($patched)
        ->toContain('$app = Application::configure')
        ->toContain('\Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class')
        ->toContain('\Aaix\LaravelStackEnv\LoadEnvironmentVariables::class')
        ->not->toContain('return Application::configure')
        ->toEndWith('return $app;'.PHP_EOL);

    expect(lintsCleanly($patched))->toBeTrue();
});

it('wires the loader into the legacy skeleton', function () {
    $original = skeletonFixture('bootstrap-app-legacy.php.stub');
    $patched = (new BootstrapPatcher)->patch($original);

    expect($patched)
        ->toContain('\Aaix\LaravelStackEnv\LoadEnvironmentVariables::class')
        ->toEndWith('return $app;'.PHP_EOL);

    expect(substr_count($patched, 'return $app;'))->toBe(1);
    expect(lintsCleanly($patched))->toBeTrue();
});

it('keeps the binding above the comment block that precedes the return', function () {
    $patched = (new BootstrapPatcher)->patch(skeletonFixture('bootstrap-app-legacy.php.stub'));

    expect(strpos($patched, '$app->singleton('.PHP_EOL.'    \Illuminate'))
        ->toBeLessThan((int) strrpos($patched, '/*'));
});

it('reports a file it has already patched', function () {
    $patcher = new BootstrapPatcher;
    $original = skeletonFixture('bootstrap-app-modern.php.stub');

    expect($patcher->isPatched($original))->toBeFalse()
        ->and($patcher->isPatched($patcher->patch($original)))->toBeTrue();
});

it('refuses a file that matches neither skeleton', function () {
    (new BootstrapPatcher)->patch(skeletonFixture('bootstrap-app-unpatchable.php.stub'));
})->throws(BootstrapPatchFailed::class);

it('refuses a result that would not parse', function () {
    (new BootstrapPatcher)->patch('<?php'.PHP_EOL.'$app = new stdClass('.PHP_EOL.'return $app;'.PHP_EOL);
})->throws(BootstrapPatchFailed::class, 'would not parse');

it('refuses a file whose anchor appears more than once', function () {
    $contents = str_replace(
        '})->create();',
        '})->create();'.PHP_EOL.'$other = Foo::bar()->create();',
        skeletonFixture('bootstrap-app-modern.php.stub')
    );

    (new BootstrapPatcher)->patch($contents);
})->throws(BootstrapPatchFailed::class);
