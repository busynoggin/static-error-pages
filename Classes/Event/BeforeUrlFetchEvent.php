<?php
declare(strict_types=1);

namespace BusyNoggin\StaticErrorPages\Event;

/**
 * Allows either changing the identifier, or changing the URL to
 * circumvent how the URL would normally be resolved.
 *
 * Dispatched before any other action is taken, before resolving
 * the HTML source to save as static file.
 */
class BeforeUrlFetchEvent
{
    private ?string $url = null;

    public function __construct(private int|string $identifier, private bool $verifySsl, private int $ttl)
    {
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

    public function isVerifySsl(): bool
    {
        return $this->verifySsl;
    }

    public function setVerifySsl(bool $verifySsl): self
    {
        $this->verifySsl = $verifySsl;
        return $this;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function setTtl(int $ttl): self
    {
        $this->ttl = $ttl;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }
}
