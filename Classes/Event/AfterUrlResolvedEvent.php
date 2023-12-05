<?php
declare(strict_types=1);

namespace BusyNoggin\StaticErrorPages\Event;

/**
 * Allows changing the URL after it is resolved.
 *
 * Dispatched after URL is resolved but before URL's content is fetched.
 */
class AfterUrlResolvedEvent
{
    public function __construct(
        private int|string $identifier,
        private bool $verifySsl,
        private int $ttl,
        private string $url
    ) {
    }

    public function getIdentifier(): int|string
    {
        return $this->identifier;
    }

    public function isVerifySsl(): bool
    {
        return $this->noVerifySsl;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }
}
