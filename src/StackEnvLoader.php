<?php

declare(strict_types=1);

namespace Aaix\LaravelStackEnv;

use Dotenv\Dotenv;
use Dotenv\Repository\RepositoryInterface;

class StackEnvLoader
{
    public const STACK_FILE = '.env.stack';

    /** @var list<string> */
    private array $stackKeys = [];

    public function __construct(private readonly RepositoryInterface $repository) {}

    public function load(string $path, string $environmentFile): Dotenv
    {
        // The stack file is read first: immutability only shields real environment variables, so among the files the last one read wins.
        $this->stackKeys = array_keys(Dotenv::create($this->repository, $path, self::STACK_FILE)->safeLoad());

        return Dotenv::create($this->repository, $path, $environmentFile);
    }

    public function forgetStackValues(): void
    {
        foreach ($this->stackKeys as $name) {
            $this->repository->clear($name);
        }
    }
}
