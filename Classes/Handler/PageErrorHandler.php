<?php
declare(strict_types=1);

namespace BusyNoggin\StaticErrorPages\Handler;

use BusyNoggin\StaticErrorPages\Service\StaticVersionFetcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageErrorHandler implements PageErrorHandlerInterface
{
    private StaticVersionFetcher $fetcher;
    private int $statusCode;
    private array $errorHandlerConfiguration;

    public function __construct(int $statusCode, array $configuration)
    {
        $this->statusCode = $statusCode;
        $this->errorHandlerConfiguration = $configuration;
        /** @var StaticVersionFetcher $cacheManager */
        $fetcher = GeneralUtility::makeInstance(StaticVersionFetcher::class);
        $this->fetcher = $fetcher;
    }

    public function handlePageError(
        ServerRequestInterface $request,
        string $message,
        array $reasons = []
    ): ResponseInterface {
        $identifier = $this->errorHandlerConfiguration['errorContentSource'];
        $identifier = str_replace('t3://page?uid=', '', $identifier);
        $source = $this->fetcher->readSourceCodeOfErrorPage($identifier);
        return new HtmlResponse($source, $this->statusCode);
    }
}
