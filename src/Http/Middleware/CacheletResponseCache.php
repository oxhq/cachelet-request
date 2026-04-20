<?php

namespace Oxhq\Cachelet\Request\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Oxhq\Cachelet\Request\Support\CacheletRequestManager;
use Symfony\Component\HttpFoundation\Response;

class CacheletResponseCache
{
    public function __construct(
        protected CacheletRequestManager $manager,
    ) {}

    public function handle(Request $request, Closure $next, ?string $prefix = null): Response
    {
        $options = (array) ($request->route()?->getAction('cachelet') ?? []);
        $resolvedPrefix = $prefix ?? ($options['prefix'] ?? null);
        $profile = $this->manager->for($request, $resolvedPrefix, $options);

        if (($options['stale'] ?? false) === true) {
            return $profile->staleWhileRevalidate(fn (): Response => $next($request));
        }

        return $profile->remember(fn (): Response => $next($request));
    }
}
