# cachelet-request

Read-only split of the Cachelet monorepo package at `packages/cachelet-request`.

Request and response caching integration for Cachelet.

## Install

```bash
composer require oxhq/cachelet-request
```

## Features

- Route `->cachelet()` integration
- Middleware-driven response caching
- Vary by query string, headers, locale, and authenticated user
- Namespace invalidation for request caches
- Canonical `module = request` coordinates and telemetry

## Example

```php
Route::get('/users', UserIndexController::class)
    ->name('users.index')
    ->cachelet(600, [
        'vary' => ['query' => true, 'auth' => true],
        'namespace' => 'users',
    ]);
```

## Request Contract

`cachelet-request` caches only configured cacheable methods and statuses. In `0.2.x` that means:

- methods default to `GET` and `HEAD`
- statuses default to `200`
- streamed and binary responses are bypassed

Vary dimensions are explicit:

- query string, full or partial
- selected headers
- authenticated user identity vs guest
- locale
- custom payload callback

Use namespace or route-prefix invalidation for request caches. Do not assume CDN/proxy cache orchestration or fragment caching in this module.
