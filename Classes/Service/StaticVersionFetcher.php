<?php
declare(strict_types=1);

namespace BusyNoggin\StaticErrorPages\Service;

use BusyNoggin\StaticErrorPages\Cache\RawFileBackend;
use BusyNoggin\StaticErrorPages\Event\AfterSourceReadEvent;
use BusyNoggin\StaticErrorPages\Event\AfterStaticStoredEvent;
use BusyNoggin\StaticErrorPages\Event\AfterUrlFetchedEvent;
use BusyNoggin\StaticErrorPages\Event\AfterUrlResolvedEvent;
use BusyNoggin\StaticErrorPages\Event\BeforeUrlFetchEvent;
use BusyNoggin\StaticErrorPages\Event\IsExpiredEvent;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Site\SiteFinder;

class StaticVersionFetcher
{
    private FrontendInterface $frontend;

    public function __construct(
        private SiteFinder $siteFinder,
        private CacheManager $cacheManager,
        private EventDispatcher $dispatcher
    ) {
        $this->frontend = $this->cacheManager->getCache('static_error_pages');
    }

    public function readSourceCodeOfErrorPage(int|string $identifier): ?string
    {
        $cacheIdentifier = $this->convertUrlToCacheIdentifier($this->resolveUrlFromIdentifier($identifier));
        if (($backend = $this->frontend->getBackend()) && $backend instanceof RawFileBackend) {
            // Our own cache backend has a custom method that will return the source of the static file even if the
            // file is expired. We want to use this method to avoid problems if an error is raised after expiry, before
            // a new version of the static file is written.
            $source = $backend->getWithoutExpirationCheck($cacheIdentifier);
        } else {
            // Other cache backends may return FALSE if the entry is expired. Those other types of backends need to be
            // carefully handled to avoid them returning FALSE (in short: they must always be updated *before* expiry).
            $source = $this->frontend->get($cacheIdentifier) ?: null;
        }

        $event = new AfterSourceReadEvent($identifier, $cacheIdentifier, $source, $this->frontend);
        $this->dispatcher->dispatch($event);

        return $event->getSource();
    }

    public function fetchAndStoreStaticVersion(int|string $identifier, bool $verifySsl = true, int $ttl = 0): void
    {
        $beforeEvent = new BeforeUrlFetchEvent($identifier, $verifySsl, $ttl);
        $this->dispatcher->dispatch($beforeEvent);

        $url = $beforeEvent->getUrl() ?? $this->resolveUrlFromIdentifier($beforeEvent->getIdentifier());

        $urlEvent = new AfterUrlResolvedEvent($identifier, $verifySsl, $ttl, $url);
        $this->dispatcher->dispatch($urlEvent);

        $curl = curl_init($urlEvent->getUrl());
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if (!$beforeEvent->isVerifySsl()) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }

        $source = curl_exec($curl);

        $afterUrlEvent = new AfterUrlFetchedEvent($identifier, $verifySsl, $ttl, $url, $source);
        $this->dispatcher->dispatch($afterUrlEvent);

        $cacheIdentifier = $this->convertUrlToCacheIdentifier($url);

        $this->frontend->set($cacheIdentifier, $afterUrlEvent->getContent(), [], $ttl ?: null);

        $afterStoreEvent = new AfterStaticStoredEvent($identifier, $afterUrlEvent->getContent(), $ttl ?: null);
        $this->dispatcher->dispatch($afterStoreEvent);
    }

    public function isExpired(int|string $identifier): bool
    {
        $cacheIdentifier = $this->convertUrlToCacheIdentifier($this->resolveUrlFromIdentifier($identifier));
        // Please note: using ->get() is intentional: ->has() will return TRUE regardless of expired or not, whereas
        // ->get() will return FALSE if the entry is expired. Even though the custom cache backend shipped with this
        // extension would return FALSE from ->has(), we do it this way to avoid any problems with other cache backends.
        $expired = !is_string($this->frontend->get($cacheIdentifier));

        $event = new IsExpiredEvent($identifier, $cacheIdentifier, $expired, $this->frontend);
        $this->dispatcher->dispatch($event);

        return $event->isExpired();
    }

    private function resolveUrlFromIdentifier(int|string $identifier): string
    {
        $url = $identifier;
        if (is_int($identifier) || ctype_digit($identifier)) {
            $site = $this->siteFinder->getSiteByPageId((integer) $identifier);
            $url = $site->getRouter()->generateUri($identifier);
        }
        return (string) $url;
    }

    private function convertUrlToCacheIdentifier(string $url): string
    {
        /** @var string $replaced */
        $replaced = str_replace([':', '/', '?', '=', '.', '%', '&'], '_', $url);
        return $replaced;
    }
}
