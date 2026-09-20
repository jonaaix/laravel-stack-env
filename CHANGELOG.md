# Changelog

All notable changes to `aaix/laravel-stack-env` will be documented in this file.

## [1.0.2] - 2026-09-20

### Removed

- `stack-env:install`, along with everything that existed only to serve it: the bootstrap patcher
  and its exception, the service provider that registered nothing else, and the `.env.stack` stub.
  Setting the package up is two edits, and the README spells them out — they stay visible in the
  project's own diff instead of happening behind it.
- `orchestra/testbench` from the dev dependencies; nothing boots an application any more.

### Changed

- Requires Laravel 13 and PHP 8.4. The CI matrix drops Laravel 12 and PHP 8.3 accordingly.

## [1.0.1] - 2026-09-19

### Added

- `stack-env:install` verifies that the patched `bootstrap/app.php` parses before writing it. A
  result that would not parse is refused, the original file is left alone, and the binding is
  printed to add by hand.
- Tests that load the bootstrapper itself, covering signature compatibility with
  `Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables` and a full pass through Laravel's own
  env repository.
- GitHub Actions workflow across PHP 8.3–8.5 and Laravel 12–13.
- `.gitattributes`, so tests, fixtures and notes stay out of the dist tarball.

### Changed

- README rewritten with the package header, and two gotchas documented: `APP_ENV` cannot come from
  `.env.stack`, and both the Laravel 11+ and the legacy `bootstrap/app.php` are supported.

## [1.0.0] - 2026-09-19

Initial release.

### Added

- `.env.stack` as a committed env layer between the developer's `.env` and the `env()` fallbacks in
  `config/*.php`. Real environment variables win over `.env`, which wins over `.env.stack`.
- `stack-env:install` — registers the loader in `bootstrap/app.php` (backing up the previous file),
  creates a commented, value-free `.env.stack`, and warns when `.gitignore` would keep that file
  out of the repository. Recognises the Laravel 11+ and the legacy skeleton, and refuses anything
  else rather than guessing.
