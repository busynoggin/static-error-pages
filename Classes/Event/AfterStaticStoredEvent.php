<?php
declare(strict_types=1);

namespace BusyNoggin\StaticErrorPages\Event;

/**
 * Allows triggering an action immediately after
 * the HTML source of a static error page has
 * been saved in the cache backend.
 */
class AfterStaticStoredEvent
{
    public function __construct(
        private int|string $identifier,
        private string $source,
        private ?int $ttl,
        private bool $verifySsl,
        private ?string $forcedUrl
    ) {
    }

    public function getIdentifier(): int|string
    {
        return $this->identifier;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTtl(): ?int
    {
        return $this->ttl;
    }

    public function isVerifySsl(): bool
    {
        return $this->verifySsl;
    }

    public function getForcedUrl(): ?string
    {
        return $this->forcedUrl;
    }
}
