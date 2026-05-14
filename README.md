# cachelet-request

Route response caching with explicit vary dimensions and Cachelet coordinates.

`cachelet-request` turns response caches into inspectable request-family entries with route metadata, vary inputs, namespace invalidation, and safe bypass behavior.

## Install

```bash
composer require oxhq/cachelet-request
```

## Best Fit

Use this package when route responses are expensive, cacheable, and need explicit vary rules.

It provides:

- route `->cachelet()` integration
- middleware-driven response caching
- vary by query string, headers, locale, and authenticated user
- namespace invalidation for request caches
- bypass behavior for streamed, binary, and non-cacheable responses
- canonical `module = request` coordinates and telemetry

## Example

```php
Route::get('/users', UserIndexController::class)
    ->name('users.index')
    ->cachelet(600, [
        'vary' => ['query' => true, 'auth' => true],
        'namespace' => 'users',
    ]);
```

## Contract

Defaults in `0.2.x`:

- cacheable methods: `GET`, `HEAD`
- cacheable statuses: `200`
- streamed and binary responses are bypassed
- non-cacheable SWR refresh callbacks preserve the last good cacheable payload

## Docs

- [`../../docs/operations.md`](../../docs/operations.md)
- [`../../docs/operator-questions.md`](../../docs/operator-questions.md)
- [`../../docs/install-matrix.md`](../../docs/install-matrix.md)
