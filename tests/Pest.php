<?php

declare(strict_types=1);

use Aaix\LaravelStackEnv\Tests\TestCase;

uses(TestCase::class)->in('Feature');

function skeletonFixture(string $name): string
{
    return (string) file_get_contents(__DIR__.'/fixtures/'.$name);
}

function temporaryDirectory(): string
{
    $path = sys_get_temp_dir().'/stack-env-'.bin2hex(random_bytes(6));

    mkdir($path, 0777, true);

    return $path;
}

function lintsCleanly(string $php): bool
{
    $file = tempnam(sys_get_temp_dir(), 'stack-env-lint').'.php';

    file_put_contents($file, $php);

    exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1', $output, $status);

    unlink($file);

    return $status === 0;
}
