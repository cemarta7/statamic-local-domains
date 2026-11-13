# Statamic Local Domains

Use `somnio.test`, `somnio.remote.test`, or another local hostname with the
same single-site Statamic project. No `.env` or `resources/sites.yaml`
changes are needed.

## Requirements

- PHP 8.2 or later
- Laravel 11–13 and Statamic 5–6
- An application already running with `APP_ENV=local`
- DNS, web server aliases, and HTTPS certificates configured for each hostname

## Install

Until this package is listed on Packagist, add its GitHub repository:

```bash
composer config repositories.statamic-local-domains vcs https://github.com/cemarta7/statamic-local-domains
composer require --dev cemarta7/statamic-local-domains:dev-main
```

Laravel discovers the provider automatically. Install with `--dev` because
this package is intended for local development.

## How it works

A middleware runs before Statamic's web middleware. For a local application
with one Statamic site, it temporarily replaces that site's absolute URL
with its path. Statamic then resolves the full URL from the current request.
For example, `https://somnio.test/en/` becomes `/en/`.

The original setting is restored when the request finishes, including when
an exception occurs. The middleware does not run for console commands or
queue jobs. It does not change `APP_URL`.

Production environments and multi-site installations are left unchanged.
Multi-site configurations need separate rules because different domains
can identify different sites.

This package does not configure DNS, certificates, cookies, CORS, or Vite.
It does not rewrite hard-coded links or links built directly from `APP_URL`.
Control panel routes keep the original site configuration, so saving site
settings does not save a temporary URL. Use the site's original hostname
for control panel features that depend on its configured URL.

## Development

```bash
composer install
composer test
```

MIT license.
