<?php

declare(strict_types=1);

namespace Aaix\LaravelStackEnv\Support;

use Aaix\LaravelStackEnv\Exceptions\BootstrapPatchFailed;
use Aaix\LaravelStackEnv\LoadEnvironmentVariables as StackBootstrapper;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables as FrameworkBootstrapper;

class BootstrapPatcher
{
    private const MODERN_OPENING = 'return Application::configure';

    private const MODERN_ANCHOR = '->create();';

    private const LEGACY_ANCHOR = 'return $app;';

    public function isPatched(string $contents): bool
    {
        return str_contains($contents, StackBootstrapper::class);
    }

    public function patch(string $contents): string
    {
        if (str_contains($contents, self::MODERN_OPENING)) {
            return $this->patchApplicationBuilderSkeleton($contents);
        }

        if (str_contains($contents, self::LEGACY_ANCHOR)) {
            return $this->patchLegacySkeleton($contents);
        }

        throw BootstrapPatchFailed::noAnchor();
    }

    public function binding(): string
    {
        return '$app->singleton('.PHP_EOL
            .'    \\'.FrameworkBootstrapper::class.'::class,'.PHP_EOL
            .'    \\'.StackBootstrapper::class.'::class,'.PHP_EOL
            .');';
    }

    public function manualInstructions(): string
    {
        return 'Assign the application to a variable, register the binding and return it:'.PHP_EOL.PHP_EOL
            .'    $app = /* the existing expression */;'.PHP_EOL.PHP_EOL
            .'    '.str_replace(PHP_EOL, PHP_EOL.'    ', $this->binding()).PHP_EOL.PHP_EOL
            .'    return $app;';
    }

    private function patchApplicationBuilderSkeleton(string $contents): string
    {
        $this->assertOccursOnce($contents, self::MODERN_OPENING);
        $this->assertOccursOnce($contents, self::MODERN_ANCHOR);

        $contents = str_replace(self::MODERN_OPENING, '$app = Application::configure', $contents);

        $end = strpos($contents, self::MODERN_ANCHOR) + strlen(self::MODERN_ANCHOR);

        if (trim(substr($contents, $end)) !== '') {
            throw BootstrapPatchFailed::noAnchor();
        }

        return substr($contents, 0, $end)
            .PHP_EOL.PHP_EOL.$this->binding()
            .PHP_EOL.PHP_EOL.'return $app;'.PHP_EOL;
    }

    private function patchLegacySkeleton(string $contents): string
    {
        $this->assertOccursOnce($contents, self::LEGACY_ANCHOR);

        $position = $this->skipPrecedingBlockComment(
            $contents,
            (int) strpos($contents, self::LEGACY_ANCHOR)
        );

        return substr($contents, 0, $position)
            .$this->binding().PHP_EOL.PHP_EOL
            .substr($contents, $position);
    }

    private function skipPrecedingBlockComment(string $contents, int $position): int
    {
        $before = rtrim(substr($contents, 0, $position));

        if (! str_ends_with($before, '*/')) {
            return $position;
        }

        $opening = strrpos($before, '/*');

        return $opening === false ? $position : $opening;
    }

    private function assertOccursOnce(string $contents, string $anchor): void
    {
        if (substr_count($contents, $anchor) !== 1) {
            throw BootstrapPatchFailed::ambiguousAnchor($anchor);
        }
    }
}
