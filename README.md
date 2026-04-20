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

## Example

```php
Route::get('/users', UserIndexController::class)
    ->name('users.index')
    ->cachelet(600, [
        'vary' => ['query' => true, 'auth' => true],
        'namespace' => 'users',
    ]);
```
