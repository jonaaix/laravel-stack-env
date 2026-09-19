# Laravel Stack Env

A committed env layer for Laravel. `.env.stack` lives in the project root, is checked into git
and holds the values the project's stack dictates — database driver, service hostnames, ports,
queue and mail transport. Every developer's `.env` shrinks to the handful of keys that are
genuinely personal or secret.

The package ships the loader only. It contains no env values and no `.env.stack` of its own;
which keys a project needs is the project's decision.

## Installation

```bash
composer require aaix/laravel-stack-env
php artisan stack-env:install
```

The command registers the loader in `bootstrap/app.php` (keeping the previous file as
`bootstrap/app.php.bak`), creates an empty, commented `.env.stack` in the project root, and
warns if `.gitignore` would keep that file out of the repository.

It recognises both the Laravel 11+ skeleton and the legacy one. If it recognises neither, it
changes nothing and prints the binding to add by hand.

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

Keys defined in `.env.stack` do not belong in `.env.example` — otherwise the same value is
documented in two places and drifts apart.

## Verifying

```bash
php artisan config:show database
php artisan about --only=drivers
```

## Config caching

Unproblematic. `.env.stack` is committed, so it is present wherever `config:cache` runs and its
values are baked into the cache like any other. The usual rule still holds: call `env()` in
`config/*.php` and nowhere else.

## How it works

`Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables` is replaced by a subclass that reads
`.env.stack` before the developer's `.env`. phpdotenv's immutable repository shields real
environment variables from both files, while the file read last wins between the two — which
puts `.env` above `.env.stack`.

The swap is a container binding made on the finished application, before the kernel bootstraps.
A service provider would be too late: providers are registered several bootstrappers after the
environment is loaded. For the same reason `withSingletons()` on the application builder does
not work here — see `NOTES.md`.

## License

MIT.
