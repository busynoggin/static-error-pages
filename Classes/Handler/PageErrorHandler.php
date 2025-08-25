<?php
declare(strict_types=1);

namespace BusyNoggin\StaticErrorPages\Handler;

use BusyNoggin\StaticErrorPages\Event\ErrorPageEvent;
use BusyNoggin\StaticErrorPages\Service\StaticVersionFetcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageErrorHandler implements PageErrorHandlerInterface
{
    private StaticVersionFetcher $fetcher;
    private EventDispatcher $dispatcher;
    private int $statusCode;
    private array $errorHandlerConfiguration;

    public function __construct(int $statusCode, array $configuration)
    {
        $this->statusCode = $statusCode;
        $this->errorHandlerConfiguration = $configuration;

        /** @var StaticVersionFetcher $cacheManager */
        $fetcher = GeneralUtility::makeInstance(StaticVersionFetcher::class);
        $this->fetcher = $fetcher;

        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
        $this->dispatcher = $eventDispatcher;
    }

    public function handlePageError(
        ServerRequestInterface $request,
        string $message,
        array $reasons = []
    ): ResponseInterface {
        $identifier = $this->errorHandlerConfiguration['error_content_source'];
        $identifier = str_replace('t3://page?uid=', '', $identifier);
        $source = $this->fetcher->readSourceCodeOfErrorPage($identifier);

        $event = new ErrorPageEvent($this->statusCode, $source, $request, $message, $reasons);
        $this->dispatcher->dispatch($event);

        return new HtmlResponse($event->getSource(), $event->getStatusCode());
    }
}
