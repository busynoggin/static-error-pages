<?php
declare(strict_types=1);

namespace BusyNoggin\StaticErrorPages\Service;

use BusyNoggin\StaticErrorPages\Cache\RawFileBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Site\SiteFinder;

class StaticVersionFetcher
{
    private FrontendInterface $frontend;

    public function __construct(private SiteFinder $siteFinder, private CacheManager $cacheManager)
    {
        $this->frontend = $this->cacheManager->getCache('static_error_pages');
    }

    public function readSourceCodeOfErrorPage(int|string $identifier): ?string
    {
        $cacheIdentifier = $this->convertUrlToCacheIdentifier($this->resolveUrlFromIdentifier($identifier));
        if (($backend = $this->frontend->getBackend()) && $backend instanceof RawFileBackend) {
            // Our own cache backend has a custom method that will return the source of the static file even if the
            // file is expired. We want to use this method to avoid problems if an error is raised after expiry, before
            // a new version of the static file is written.
            return $backend->getWithoutExpirationCheck($cacheIdentifier);
        }
        // Other cache backends may return FALSE if the entry is expired. Those other types of backends need to be
        // carefully handled to avoid them returning FALSE (in short: they must always be updated *before* expiry).
        return $this->frontend->get($cacheIdentifier) ?: null;
    }

    public function fetchAndStoreStaticVersion(int|string $identifier, bool $verifySsl = true, int $ttl = 0): void
    {
        $url = $this->resolveUrlFromIdentifier($identifier);

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if (!$verifySsl) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }

        $source = curl_exec($curl);

        $cacheIdentifier = $this->convertUrlToCacheIdentifier($url);

        $this->frontend->set($cacheIdentifier, $source, [], $ttl ?: null);
    }

    public function isExpired(int|string $identifier): bool
    {
        $cacheIdentifier = $this->convertUrlToCacheIdentifier($this->resolveUrlFromIdentifier($identifier));
        // Please note: using ->get() is intentional: ->has() will return TRUE regardless of expired or not, whereas
        // ->get() will return FALSE if the entry is expired. Even though the custom cache backend shipped with this
        // extension would return FALSE from ->has(), we do it this way to avoid any problems with other cache backends.
        return !is_string($this->frontend->get($cacheIdentifier));
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
