<?php

declare(strict_types=1);

function temporaryDirectory(): string
{
    $path = sys_get_temp_dir().'/stack-env-'.bin2hex(random_bytes(6));

    mkdir($path, 0777, true);

    return $path;
}
