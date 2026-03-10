<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Support;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class CacheStore
{
    public function __construct(
        private readonly ?CacheInterface $cache,
        private readonly int $ttl,
    ) {}

    /**
     * @template T of object
     *
     * @param  callable(array<string, mixed>): T  $hydrator
     * @return T|null
     */
    public function fetch(string $key, callable $hydrator): ?object
    {
        if (! $this->cache instanceof CacheInterface) {
            return null;
        }

        try {
            /** @var array<string, mixed>|null $cached */
            $cached = $this->cache->get($key);
        } catch (InvalidArgumentException) {
            return null;
        }

        if (! is_array($cached)) {
            return null;
        }

        try {
            return $hydrator($cached);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public function store(string $key, array $value): void
    {
        if (! $this->cache instanceof CacheInterface) {
            return;
        }

        try {
            $this->cache->set($key, $value, $this->ttl);
        } catch (InvalidArgumentException) {
            // Silently ignore cache write failures.
        }
    }
}
