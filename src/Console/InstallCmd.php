<?php

declare(strict_types=1);

namespace Aaix\LaravelStackEnv\Console;

use Aaix\LaravelStackEnv\Exceptions\BootstrapPatchFailed;
use Aaix\LaravelStackEnv\StackEnvLoader;
use Aaix\LaravelStackEnv\Support\BootstrapPatcher;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InstallCmd extends Command
{
    protected $signature = 'stack-env:install {--force : Overwrite an existing bootstrap/app.php.bak}';

    protected $description = 'Register the stack env loader in bootstrap/app.php and create the stack env file';

    public function handle(Filesystem $files, BootstrapPatcher $patcher): int
    {
        $bootstrapFile = $this->laravel->basePath('bootstrap/app.php');

        if (! $files->exists($bootstrapFile)) {
            $this->components->error('bootstrap/app.php was not found. This command expects a Laravel application skeleton.');

            return self::FAILURE;
        }

        $contents = $files->get($bootstrapFile);

        if ($patcher->isPatched($contents)) {
            $this->components->info('bootstrap/app.php already registers the stack env loader.');
        } elseif (! $this->writePatchedBootstrapFile($files, $patcher, $bootstrapFile, $contents)) {
            return self::FAILURE;
        }

        $this->createStackFile($files);
        $this->warnWhenStackFileIsIgnored($files);
        $this->showNextSteps();

        return self::SUCCESS;
    }

    private function writePatchedBootstrapFile(
        Filesystem $files,
        BootstrapPatcher $patcher,
        string $path,
        string $contents
    ): bool {
        try {
            $patched = $patcher->patch($contents);
        } catch (BootstrapPatchFailed $exception) {
            $this->components->error($exception->getMessage().' Nothing was changed.');
            $this->line('Add the binding to bootstrap/app.php by hand:');
            $this->newLine();
            $this->line($patcher->manualInstructions());
            $this->newLine();

            return false;
        }

        $backup = $path.'.bak';

        if ($files->exists($backup) && ! $this->option('force')) {
            $this->components->error('bootstrap/app.php.bak already exists. Re-run with --force to overwrite it.');

            return false;
        }

        $files->copy($path, $backup);
        $files->put($path, $patched);

        $this->components->info('bootstrap/app.php now registers the stack env loader, the previous file is kept as bootstrap/app.php.bak.');

        return true;
    }

    private function createStackFile(Filesystem $files): void
    {
        $target = $this->laravel->basePath(StackEnvLoader::STACK_FILE);

        if ($files->exists($target)) {
            $this->components->info(StackEnvLoader::STACK_FILE.' already exists and was left untouched.');

            return;
        }

        $files->copy(__DIR__.'/../../stubs/.env.stack.stub', $target);

        $this->components->info(StackEnvLoader::STACK_FILE.' was created in the project root.');
    }

    private function warnWhenStackFileIsIgnored(Filesystem $files): void
    {
        $path = $this->laravel->basePath('.gitignore');

        if (! $files->exists($path)) {
            return;
        }

        if (! $this->isIgnoredByGit($files->get($path))) {
            return;
        }

        $this->components->warn(
            StackEnvLoader::STACK_FILE.' is covered by a pattern in .gitignore. The layer only reaches your '
            .'colleagues and CI once the file is committed, so exclude it from that pattern.'
        );
    }

    private function isIgnoredByGit(string $gitignore): bool
    {
        $ignored = false;

        foreach (preg_split('/\R/', $gitignore) ?: [] as $line) {
            $pattern = trim($line);

            if ($pattern === '' || str_starts_with($pattern, '#')) {
                continue;
            }

            $negated = str_starts_with($pattern, '!');
            $pattern = ltrim(ltrim($pattern, '!'), '/');

            if (fnmatch($pattern, StackEnvLoader::STACK_FILE)) {
                $ignored = ! $negated;
            }
        }

        return $ignored;
    }

    private function showNextSteps(): void
    {
        $this->newLine();
        $this->components->bulletList([
            'Move the values your stack dictates into '.StackEnvLoader::STACK_FILE.' and commit the file.',
            'Drop those keys from .env.example, so they are documented in one place only.',
            'Verify with: php artisan config:show database',
        ]);
    }
}
