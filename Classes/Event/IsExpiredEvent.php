<?php
declare(strict_types=1);

namespace BusyNoggin\StaticErrorPages\Event;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * Allows changing whether or not the static file
 * associated with a given identifier is expired.
 *
 * Dispatched after the standard "is expired"
 * decision is reached.
 */
class IsExpiredEvent
{
    public function __construct(
        private int|string $identifier,
        private string $cacheIdentifier,
        private bool $expired,
        private FrontendInterface $cache
    ) {
    }

    public function getIdentifier(): int|string
    {
        return $this->identifier;
    }

    public function setIdentifier(int|string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getCacheIdentifier(): string
    {
        return $this->cacheIdentifier;
    }

    public function isExpired(): bool
    {
        return $this->expired;
    }

    public function setExpired(bool $expired): self
    {
        $this->expired = $expired;
        return $this;
    }

    public function getCache(): FrontendInterface
    {
        return $this->cache;
    }
}
