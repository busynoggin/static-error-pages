<?php
declare(strict_types=1);

namespace BusyNoggin\StaticErrorPages\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * Allows circumventing the normal cache-based fetching of static error
 * pages, for example to modify the identifier (which then changes the
 * fetch target and cache identifier), or changing the cache identifier
 * (which changes the ID of this error page in caches) or providing a
 * custom error page source (which then circumventes use of caches).
 *
 * Dispatched before the error page source is read from caches.
 */
class BeforeSourceReadEvent
{
    private ?string $source = null;

    public function __construct(
        private int|string $identifier,
        private string $cacheIdentifier,
        private ServerRequestInterface $request
    ) {
    }

    public function setIdentifier(int|string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getIdentifier(): int|string
    {
        return $this->identifier;
    }

    public function setCacheIdentifier(string $cacheIdentifier): self
    {
        $this->cacheIdentifier = $cacheIdentifier;
        return $this;
    }

    public function getCacheIdentifier(): string
    {
        return $this->cacheIdentifier;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
