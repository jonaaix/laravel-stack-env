# Verification notes

Checked against the versions actually installed, not against documentation.

| | Version |
| --- | --- |
| PHP | 8.5.7 |
| laravel/framework | v13.32.0 |
| vlucas/phpdotenv | v5.7.0 |
| orchestra/testbench | ^10.0 \| ^11.0 |
| PHPUnit | 12.5.33 |

## V1 — `LoadEnvironmentVariables::createDotenv()`

Confirmed. The method exists as `protected function createDotenv($app)` with no parameter type
and no return type. The override therefore keeps `$app` untyped — narrowing it to
`Application` would be a fatal error — and only adds the covariant `: Dotenv` return type.

`bootstrap()` returns early on `configurationIsCached()`, so the override needs no cache guard
of its own. It calls `safeLoad()` on the returned instance inside a `try` block that catches
`InvalidFileException`, so a parse error in either file still reaches the framework's own error
output. `checkForSpecificEnvironmentFile()` runs before `createDotenv()`, so
`$app->environmentFile()` already carries `.env.<APP_ENV>` where one applies.

## V2 — `withSingletons()` on the ApplicationBuilder — **deviation**

`withSingletons(array $singletons)` exists and takes `[abstract => concrete]`, but it is
unusable for a bootstrapper. It registers the binding through `$app->registered()`, and those
callbacks only fire inside `Application::registerConfiguredProviders()` — which runs in the
`RegisterProviders` bootstrapper, four steps after `LoadEnvironmentVariables`. `withBindings()`
and `withScopedSingletons()` use the same mechanism.

Reproduced: after `Application::configure(...)->withSingletons([...])->create()`,
`$app->bound(LoadEnvironmentVariables::class)` is `false` and `make()` still returns the
framework class.

The binding has to be made on the finished container instead, which is what `InstallCmd`
patches in:

```php
$app = Application::configure(...)->...->create();

$app->singleton(
    \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
    \Aaix\LaravelStackEnv\LoadEnvironmentVariables::class,
);

return $app;
```

## V3 — repository immutability — **deviation, changed the design**

`Illuminate\Support\Env::getRepository()` builds an immutable repository, but phpdotenv's
immutability is narrower than "first write wins". `ImmutableWriter` refuses a write only when
the key is *externally* defined — present in the repository and not recorded in its own
`$loaded` set. Values written by dotenv itself are recorded there and can be overwritten by a
later dotenv load.

Consequences for the layering:

- a real environment variable (container, shell, CI) is protected against both files — as assumed;
- between the two files, **the file read last wins** — the opposite of the assumption.

The loader therefore reads `.env.stack` **first** and the developer's `.env` **second**, and
returns the `.env` instance for the framework to `safeLoad()`. Covered by
`tests/Unit/EnvPrecedenceTest.php`, including a case built on
`RepositoryBuilder::createWithDefaultAdapters()->immutable()` so the real Laravel repository
shape is exercised.

## V4 — `Dotenv::create()`

Confirmed:
`create(RepositoryInterface $repository, $paths, $names = null, bool $shortCircuit = true, ?string $fileEncoding = null)`.
`safeLoad()` swallows `InvalidPathException`, so a missing file is not an error. It reads
through `@file_get_contents`, which raises a suppressed PHP warning — harmless at runtime, but
Pest reports it, so the precedence test silences `E_WARNING` around the load.

## V5 — `bootstrap/app.php` — **deviation**

The fresh skeleton ends on `})->create();` and holds no `$app` variable, as assumed. The IMS
application (`/app`) however still runs the **legacy** skeleton: `$app = new Application(...)`,
a few `$app->singleton(...)` calls, `return $app;`. `BootstrapPatcher` therefore handles both
shapes and refuses anything else instead of guessing. In the legacy file the binding is placed
above the comment block that precedes `return $app;`.

## V6 — `.gitignore`

Confirmed for both the fresh skeleton (`.env`, `.env.backup`, `.env.production`) and `/app`
(`.env`). Neither carries an `.env.*` glob, so the stack file is committed by default. The
install command still checks and warns, honouring negation patterns.

## Naming

Vendor `aaix` and the `Aaix\Laravel*` namespace follow the sibling packages
(`aaix/laravel-islands` → `Aaix\LaravelIslands`, commands under `src/Console` with the `Cmd`
suffix). Hence `aaix/laravel-stack-env`, `Aaix\LaravelStackEnv`, `InstallCmd`.

`laravel/framework` is required rather than `illuminate/support`, because the bootstrapper
extends a class from `Illuminate\Foundation`, which only ships in the full framework.

## End-to-end result

Fresh Laravel 13 project, package linked as a path repository:

| Case | Result |
| --- | --- |
| `.env.stack` sets `DB_CONNECTION=mysql`, key absent from `.env` | `mysql` |
| plus `DB_CONNECTION=sqlite` in `.env` | `sqlite` |
| plus `DB_CONNECTION=pgsql` in the process environment | `pgsql` |
| second `stack-env:install` | both files unchanged |
| after `config:cache` | `mysql` |
