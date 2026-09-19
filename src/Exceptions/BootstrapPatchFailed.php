<?php

declare(strict_types=1);

namespace Aaix\LaravelStackEnv\Exceptions;

use RuntimeException;

class BootstrapPatchFailed extends RuntimeException
{
    public static function noAnchor(): self
    {
        return new self('bootstrap/app.php matches neither the Laravel 11+ nor the legacy skeleton.');
    }

    public static function ambiguousAnchor(string $anchor): self
    {
        return new self("bootstrap/app.php contains \"{$anchor}\" more than once.");
    }

    public static function unparsableResult(string $reason): self
    {
        return new self("The patched bootstrap/app.php would not parse: {$reason}.");
    }
}
