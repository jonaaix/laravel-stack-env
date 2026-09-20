<p align="center">
  <a href="https://github.com/jonaaix/laravel-stack-env">
    <img src="https://raw.githubusercontent.com/jonaaix/laravel-stack-env/main/icon.svg" alt="Laravel Stack Env Logo" width="220">
  </a>
</p>

<h1 align="center">Laravel Stack Env</h1>

<p align="center">
Puts a committed <code>.env.stack</code> underneath every developer's <code>.env</code> &mdash; the stack configures
itself, the personal file shrinks to secrets.
</p>

<p align="center">
  <a href="https://packagist.org/packages/aaix/laravel-stack-env"><img src="https://img.shields.io/packagist/v/aaix/laravel-stack-env.svg?style=flat-square" alt="Latest Version on Packagist"></a>
  <a href="https://packagist.org/packages/aaix/laravel-stack-env"><img src="https://img.shields.io/packagist/dt/aaix/laravel-stack-env.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="https://github.com/jonaaix/laravel-stack-env/actions/workflows/run-tests.yml"><img src="https://img.shields.io/github/actions/workflow/status/jonaaix/laravel-stack-env/run-tests.yml?branch=main&label=tests&style=flat-square" alt="GitHub Actions"></a>
  <a href="https://github.com/jonaaix/laravel-stack-env/blob/main/LICENSE.md"><img src="https://img.shields.io/packagist/l/aaix/laravel-stack-env.svg?style=flat-square" alt="License"></a>
</p>

---

## Setup

```bash
composer require aaix/laravel-stack-env
```

Then two edits, both yours to make.

**1. Register the loader in `bootstrap/app.php`.** It has to be a container binding on the
finished application: a service provider is registered several bootstrappers after the
environment is read, and `withSingletons()` on the application builder is just as late.

On the slim skeleton the file returns the builder expression directly, so assign it first:

```php
$app = Application::configure(basePath: dirname(__DIR__))
    // ...
    ->create();

$app->singleton(
    \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
    \Aaix\LaravelStackEnv\LoadEnvironmentVariables::class,
);

return $app;
```

On the classic skeleton the `$app` variable already exists — put the same `singleton()` call next
to the other bindings, above `return $app;`.

**2. Create `.env.stack` in the project root and commit it.** The package ships no values and no
file of its own; which keys a project needs is the project's decision.

```dotenv
# Stack environment defaults
#
# Committed. Applies to everyone working on this project, and is overridden by
# each developer's own .env. Keys defined here do not belong in .env.example.

DB_CONNECTION=mysql
DB_HOST=mysql
REDIS_HOST=redis
```

Check that `.gitignore` does not swallow the file — a `.env*` pattern would, and the layer only
reaches your colleagues and CI once the file is committed.

## Precedence

Highest first:

1. Real environment variables — container, shell, CI
2. `.env` — the developer's own file, not in version control
3. `.env.stack` — committed, applies to everyone
4. The `env()` fallback in `config/*.php`

Level 3 is why no config file has to be touched: once `.env.stack` defines `DB_CONNECTION`,
`env('DB_CONNECTION', 'sqlite')` in `config/database.php` never reaches its fallback. The
framework defaults are not overwritten, they become unreachable.

To override a value locally, put the key in your own `.env`. It wins over `.env.stack` without
any further ceremony.

## Verifying

```bash
php artisan config:show database
php artisan about --only=drivers
```

## Notes

**Config caching is unproblematic.** `.env.stack` is committed, so it is present wherever
`config:cache` runs and its values are baked into the cache like any other. The usual rule still
holds: call `env()` in `config/*.php` and nowhere else.

**`APP_ENV` does not belong in `.env.stack`.** Laravel picks the environment-specific file
(`.env.production` and friends) before any env file is read, so it only ever sees `APP_ENV` from
a real environment variable. That is unchanged framework behaviour — the same is true of `.env`.

**Nothing is generated and nothing is patched.** The package ships the loader and nothing else —
no install command, no file that rewrites `bootstrap/app.php` behind your back. The two edits
above are the whole integration, and they stay visible in your own diff.

## How it works

`Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables` is replaced by a subclass that reads
`.env.stack` before the developer's `.env`. phpdotenv's immutable repository shields real
environment variables from both files, while the file read last wins between the two — which puts
`.env` above `.env.stack`.

The swap is a container binding made on the finished application, before the kernel bootstraps.
A service provider would be too late: providers are registered several bootstrappers after the
environment is loaded. For the same reason `withSingletons()` on the application builder does not
work here — `NOTES.md` has the measurements.

## License

MIT.
