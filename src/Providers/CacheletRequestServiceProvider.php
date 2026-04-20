<?php

namespace Oxhq\Cachelet\Request\Providers;

use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Oxhq\Cachelet\Request\Http\Middleware\CacheletResponseCache;
use Oxhq\Cachelet\Request\Support\CacheletRequestManager;

class CacheletRequestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheletRequestManager::class, function ($app) {
            return new CacheletRequestManager((array) $app['config']->get('cachelet', []));
        });

        $this->app->alias(CacheletRequestManager::class, 'cachelet.request');
    }

    public function boot(): void
    {
        $alias = config('cachelet.request.middleware_alias', 'cachelet');

        $this->app['router']->aliasMiddleware($alias, CacheletResponseCache::class);

        Route::macro('cachelet', function (?string $prefix = null, array $options = []): Route {
            $action = $this->getAction();
            $action['cachelet'] = array_merge($action['cachelet'] ?? [], $options, array_filter([
                'prefix' => $prefix,
            ], static fn (mixed $value): bool => $value !== null));
            $this->setAction($action);
            $this->middleware(config('cachelet.request.middleware_alias', 'cachelet'));

            return $this;
        });
    }
}
