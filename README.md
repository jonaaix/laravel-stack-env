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

## Quick Start

```bash
composer require aaix/laravel-stack-env
php artisan stack-env:install
```

That is the whole setup. The command registers the loader in `bootstrap/app.php`, keeps the
previous file as `bootstrap/app.php.bak`, and drops a commented, value-free `.env.stack` in the
project root. Put the values your stack dictates in there, commit the file, and remove those
keys from `.env.example`.

The package ships the loader only. It contains no env values and no `.env.stack` of its own —
which keys a project needs is the project's decision.

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

**Both skeletons are supported.** `stack-env:install` recognises the Laravel 11+ `bootstrap/app.php`
and the legacy one. If it recognises neither, it changes nothing and prints the binding to add by
hand.

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
