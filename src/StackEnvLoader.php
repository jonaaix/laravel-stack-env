<?php

declare(strict_types=1);

namespace Aaix\LaravelStackEnv;

use Dotenv\Dotenv;
use Dotenv\Repository\RepositoryInterface;

class StackEnvLoader
{
    public const STACK_FILE = '.env.stack';

    public function __construct(private readonly RepositoryInterface $repository) {}

    public function load(string $path, string $environmentFile): Dotenv
    {
        // The stack file is read first: immutability only shields real environment variables, so among the files the last one read wins.
        Dotenv::create($this->repository, $path, self::STACK_FILE)->safeLoad();

        return Dotenv::create($this->repository, $path, $environmentFile);
    }
}
