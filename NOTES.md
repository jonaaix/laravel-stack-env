# Verification notes

Checked against the versions actually installed, not against documentation.

| | Version |
| --- | --- |
| PHP | 8.5.7 |
| laravel/framework | v13.32.0 |
| vlucas/phpdotenv | v5.7.0 |
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

The binding has to be made on the finished container instead, which is the edit the README
asks for:

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
a few `$app->singleton(...)` calls, `return $app;`. The README documents the edit for both
shapes; on the legacy one the binding simply joins the existing `singleton()` calls.

## V6 — `.gitignore`

Confirmed for both the fresh skeleton (`.env`, `.env.backup`, `.env.production`) and `/app`
(`.env`). Neither carries an `.env.*` glob, so the stack file is committed by default. The
README still tells the reader to check, because a project-specific `.env*` pattern would
silently defeat the whole layer.

## V7 — `php artisan test` — **deviation, found in the first real project**

Collision's `TestCommand` starts phpunit as a child process that inherits the artisan process's
environment. Before it does, `clearEnv()` clears the keys of `.env` from the repository — and
only those, read by name from that one file. Every key the stack file set therefore reaches
phpunit as a real environment variable in `$_SERVER`.

phpunit's `<env>` sets `$_ENV` and `putenv()`, even with `force="true"`, but not `$_SERVER`, and
Laravel's repository reads `$_SERVER` first. So `DB_DATABASE` from `.env.stack` outranked the
test database in `phpunit.xml`: the suite ran against the development database, and
`RefreshDatabase` wiped it. Running `vendor/bin/phpunit` directly was unaffected.

The bootstrapper now remembers which keys the stack file actually set — `safeLoad()` returns
only those, so a real environment variable is never among them — and clears exactly those when
`CommandStarting` fires for `test`. The event dispatcher is bound in the application
constructor, so the listener can be registered from the bootstrapper itself. With a config
cache the stack file is never read, and nothing is registered.

Reproduced in the project with the `<server>` workaround removed: the released version reports
the development database inside a test, the fixed one the test database.

## Naming

Vendor `aaix` and the `Aaix\Laravel*` namespace follow the sibling packages
(`aaix/laravel-islands` → `Aaix\LaravelIslands`). Hence `aaix/laravel-stack-env` and
`Aaix\LaravelStackEnv`.

`laravel/framework` is required rather than `illuminate/support`, because the bootstrapper
extends a class from `Illuminate\Foundation`, which only ships in the full framework.

## End-to-end result

Fresh Laravel 13 project, package linked as a path repository, integrated by following the
README by hand:

| Case | Result |
| --- | --- |
| `.env.stack` sets `DB_CONNECTION=mysql`, key absent from `.env` | `mysql` |
| plus `DB_CONNECTION=sqlite` in `.env` | `sqlite` |
| plus `DB_CONNECTION=pgsql` in the process environment | `pgsql` |
| after `config:cache` | `mysql` |
