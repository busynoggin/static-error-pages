<?php
declare(strict_types=1);

namespace BusyNoggin\StaticErrorPages\Event;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * Allows changing the source that is resolved from
 * a static file.
 *
 * Dispatched after the source has been read through
 * the usual method.
 */
class AfterSourceReadEvent
{
    public function __construct(
        private int|string $identifier,
        private string $cacheIdentifier,
        private string $source,
        private FrontendInterface $cache
    ) {
    }

    public function getIdentifier(): int|string
    {
        return $this->identifier;
    }

    public function getCacheIdentifier(): string
    {
        return $this->cacheIdentifier;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getCache(): FrontendInterface
    {
        return $this->cache;
    }
}
