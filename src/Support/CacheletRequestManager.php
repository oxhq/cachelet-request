<?php

namespace Oxhq\Cachelet\Request\Support;

use Illuminate\Http\Request;
use Oxhq\Cachelet\Facades\Cachelet;

class CacheletRequestManager
{
    public function __construct(
        protected array $config = []
    ) {}

    public function for(Request $request, ?string $prefix = null, array $options = []): ResponseCacheProfile
    {
        return new ResponseCacheProfile($request, $this->config, $prefix, $options);
    }

    public function invalidatePrefix(string $prefix, string $reason = 'manual'): array
    {
        return Cachelet::for($this->normalizePrefix($prefix))->invalidatePrefix($reason);
    }

    public function invalidateRoute(string $routeName, string $reason = 'manual'): array
    {
        return $this->invalidatePrefix($routeName, $reason);
    }

    public function invalidateNamespace(string $namespace, string $reason = 'manual'): array
    {
        return $this->invalidatePrefix($namespace, $reason);
    }

    protected function normalizePrefix(string $prefix): string
    {
        $base = $this->config['request']['default_prefix'] ?? 'request';

        return str_starts_with($prefix, $base.':') ? $prefix : $base.':'.$prefix;
    }
}
