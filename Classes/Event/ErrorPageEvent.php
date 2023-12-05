<?php
declare(strict_types=1);

namespace BusyNoggin\StaticErrorPages\Event;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Allows changing the HTML source that is returned
 * as response body when an error page is displayed;
 * or triggering certain actions when this extension
 * handles a page error.
 *
 * Dispatched after HTML source is read, before the
 * response is sent.
 */
class ErrorPageEvent
{
    public function __construct(
        private int $statusCode,
        private string $source,
        private ServerRequestInterface $request,
        private string $message,
        private array $reasons
    ) {
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
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

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getReasons(): array
    {
        return $this->reasons;
    }
}
