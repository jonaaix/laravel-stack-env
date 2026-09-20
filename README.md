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

Database driver, service hostnames, ports, queue and mail transport are the same for everyone
working on a project — they follow from its stack, not from who checked it out. Put them in a
committed `.env.stack` and a fresh clone runs without a single manual setting. Nobody's `.env`
repeats them, `.env.example` stops listing them, and no `config/*.php` has to be touched.

Highest wins:

1. **Real environment variables** — container, shell, CI
2. **`.env`** — the developer's own file, not in version control
3. **`.env.stack`** — committed, applies to everyone
4. The `env()` fallback in `config/*.php`

Every key is decided on its own: `.env` overrides exactly the keys it defines and leaves the rest
of `.env.stack` in place.

## Setup

```bash
composer require aaix/laravel-stack-env
```

**1. Register the loader in `bootstrap/app.php`.** On the slim skeleton the file returns the
builder expression directly, so assign it first:

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

On the classic skeleton `$app` already exists — put the same `singleton()` call next to the other
bindings, above `return $app;`.

**2. Create `.env.stack` in the project root and commit it.** Make sure `.gitignore` does not
swallow it: a `.env*` pattern would, and the layer only reaches your colleagues and CI once the
file is in the repository.

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
REDIS_HOST=redis
QUEUE_CONNECTION=redis
```

That is the whole integration. Check it with `php artisan config:show database`.

## License

MIT.
