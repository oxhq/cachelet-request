<?php

namespace Oxhq\Cachelet\Request\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Oxhq\Cachelet\Contracts\CacheletBuilderInterface;
use Oxhq\Cachelet\Facades\Cachelet;
use Oxhq\Cachelet\ValueObjects\CacheScope;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResponseCacheProfile
{
    public function __construct(
        protected Request $request,
        protected array $config = [],
        protected ?string $prefix = null,
        protected array $options = [],
    ) {}

    public function remember(\Closure $callback): SymfonyResponse
    {
        $serialized = $this->builder()->remember(fn (): array => $this->serializeResponse($callback()));

        return $this->restoreResponse($serialized);
    }

    public function staleWhileRevalidate(\Closure $callback, ?\Closure $fallback = null): SymfonyResponse
    {
        $serialized = $this->builder()->staleWhileRevalidate(
            fn (): array => $this->serializeResponse($callback()),
            $fallback ? fn (): array => $this->serializeResponse($fallback()) : null,
        );

        return $this->restoreResponse($serialized);
    }

    public function fetch(): ?SymfonyResponse
    {
        $serialized = $this->builder()->fetch();

        if (! is_array($serialized)) {
            return null;
        }

        return $this->restoreResponse($serialized);
    }

    public function builder(): CacheletBuilderInterface
    {
        $builder = Cachelet::for($this->resolvedPrefix())
            ->from($this->payload())
            ->withMetadata([
                'route' => $this->routeName(),
                'method' => $this->request->method(),
                'path' => $this->request->path(),
            ]);

        $builder->asModule('request');
        $this->applyScopeToBuilder($builder);

        if ($tags = ($this->options['tags'] ?? [])) {
            $builder->withTags($tags);
        }

        if (array_key_exists('ttl', $this->options)) {
            $builder->ttl($this->options['ttl']);
        }

        if (isset($this->options['version'])) {
            $builder->versioned($this->options['version']);
        }

        return $builder;
    }

    public function scope(CacheScope $scope): static
    {
        $this->options['scope'] = $scope;

        return $this;
    }

    public function resolvedPrefix(): string
    {
        $base = $this->config['request']['default_prefix'] ?? 'request';
        $seed = $this->options['namespace']
            ?? $this->prefix
            ?? $this->routeName()
            ?? $this->request->path();

        return $base.':'.$this->normalizeSegment((string) $seed);
    }

    public function payload(): array
    {
        $payload = [
            'method' => $this->request->method(),
            'route' => $this->routeName(),
            'path' => $this->request->path(),
        ];

        $vary = array_replace_recursive($this->config['request']['vary'] ?? [], $this->options['vary'] ?? []);

        if (($vary['query'] ?? true) === true) {
            $payload['query'] = $this->request->query();
        } elseif (is_array($vary['query'] ?? null)) {
            $payload['query'] = Arr::only($this->request->query(), $vary['query']);
        }

        $headers = $vary['headers'] ?? [];

        if ($headers !== []) {
            $payload['headers'] = collect($headers)
                ->mapWithKeys(fn (string $header): array => [$header => $this->request->headers->all($header)])
                ->all();
        }

        if ($vary['auth'] ?? false) {
            $user = $this->request->user();
            $payload['auth'] = $user
                ? ['guest' => false, 'id' => method_exists($user, 'getAuthIdentifier') ? $user->getAuthIdentifier() : null]
                : ['guest' => true];
        }

        if ($vary['locale'] ?? false) {
            $payload['locale'] = app()->getLocale();
        }

        if (isset($this->options['payload']) && is_callable($this->options['payload'])) {
            $payload['custom'] = call_user_func($this->options['payload'], $this->request);
        }

        return $payload;
    }

    protected function serializeResponse(SymfonyResponse $response): array
    {
        if (! $this->shouldStore($response)) {
            throw new \RuntimeException('The response type or status is not cacheable by Cachelet request caching.');
        }

        return [
            'status' => $response->getStatusCode(),
            'headers' => $response->headers->all(),
            'content' => $response->getContent(),
        ];
    }

    protected function restoreResponse(array $payload): SymfonyResponse
    {
        return new Response(
            $payload['content'] ?? '',
            $payload['status'] ?? 200,
            $payload['headers'] ?? [],
        );
    }

    protected function shouldStore(SymfonyResponse $response): bool
    {
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return false;
        }

        $methods = array_map('strtoupper', $this->config['request']['cache_methods'] ?? ['GET', 'HEAD']);
        $statuses = $this->config['request']['cache_statuses'] ?? [200];

        return in_array(strtoupper($this->request->method()), $methods, true)
            && in_array($response->getStatusCode(), $statuses, true);
    }

    protected function routeName(): ?string
    {
        return $this->request->route()?->getName();
    }

    protected function applyScopeToBuilder(CacheletBuilderInterface $builder): void
    {
        $scope = $this->options['scope'] ?? $this->makeInferredScope($this->resolvedPrefix());

        $source = array_key_exists('scope', $this->options) ? 'explicit' : 'inferred';

        if ($source === 'inferred') {
            $builder->withInferredScope($scope);

            return;
        }

        $builder->scope($scope);
    }

    protected function makeInferredScope(string $identifier): ?CacheScope
    {
        return CacheScope::inferred($identifier);
    }

    protected function normalizeSegment(string $value): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9:_-]+/', '_', trim($value));
        $normalized = trim((string) $normalized, '_');

        return $normalized === '' ? 'request' : $normalized;
    }
}
