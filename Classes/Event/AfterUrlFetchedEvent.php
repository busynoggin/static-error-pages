<?php
declare(strict_types=1);

namespace BusyNoggin\StaticErrorPages\Event;

/**
 * Allows changing the HTML source of a fetched page.
 *
 * Dispatched right after the HTML source is fetched.
 */
class AfterUrlFetchedEvent
{
    public function __construct(
        private int|string $identifier,
        private bool $verifySsl,
        private int $ttl,
        private string $url,
        private string $content,
        private bool $allowCache = true
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

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function isAllowCache(): bool
    {
        return $this->allowCache;
    }

    public function setAllowCache(bool $allowCache): self
    {
        $this->allowCache = $allowCache;
        return $this;
    }
}
