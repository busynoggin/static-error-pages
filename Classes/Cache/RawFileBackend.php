<?php
declare(strict_types=1);

namespace BusyNoggin\StaticErrorPages\Cache;

use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Cache\Backend\TransientBackendInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * Slightly adapted SimpleFileBackend with three differences:
 *
 * - Masquerades as a TransientBackend implementation to prevent the Frontend
 *   from doing serialize() before passing the value to this class, which
 *   causes the cached file to contain a raw string with HTML of the fetched
 *   page-not-found identifier.
 * - Adds a ".html" suffix to the cache entry (invisible to the consumer) so
 *   the stored file may be used as ErrorDocument in the HTTP server and be
 *   recognized as a HTML format.
 * - Supports "lifetime" through setting mtime on the file to a date in the
 *   future and returning FALSE from ->get() and ->has() if the file's mtime
 *   is in the past. The physical file however is left in place to avoid
 *   causing an "additional 404 when looking for ErrorDocument" error from
 *   the HTTP server, if the static file is configured as ErrorDocument.
 *
 * Other than this, the backend behaves precisely like the SimpleFileBackend.
 */
class RawFileBackend extends SimpleFileBackend implements TransientBackendInterface
{
    public function setCache(FrontendInterface $cache)
    {
        parent::setCache($cache);
        $this->cacheEntryFileExtension = '.html';
    }

    public function getWithoutExpirationCheck(string $entryIdentifier): ?string
    {
        return parent::get($entryIdentifier) ?: null;
    }

    public function get($entryIdentifier)
    {
        if (filemtime($this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension) < time()) {
            return false;
        }
        return parent::get($entryIdentifier);
    }

    public function has($entryIdentifier): bool
    {
        if (filemtime($this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension) < time()) {
            return false;
        }
        return parent::has($entryIdentifier);
    }

    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null): void
    {
        parent::set($entryIdentifier, $data, $tags, $lifetime);

        if ($lifetime) {
            touch($this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension, time() + $lifetime);
        }
    }

    public function flush()
    {
        // void - flushing is not allowed.
    }

    public function flushByTags(array $tags)
    {
        // void - flushing is not allowed.
    }
}
