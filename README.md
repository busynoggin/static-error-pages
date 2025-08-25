Static Error Page Integration
=============================

> This TYPO3 extension makes it possible to configure an error page (404, etc.) in TYPO3 which gets its content from
> either a local TYPO3 page or an external URL, but which stores the output of said page as a local file (this can be
> changed to store in any way you prefer, via TYPO3's caching framework).

The purpose of this is two-fold:

1. Ensure maximum performance with the shortest possible path from error to delivering the error page.
2. Make it possible to add the static file as an ErrorDocument in the HTTP server (possible when using the default
   storage method which writes a true .html file to the local filesystem).

The extension tries to be smart about how/when to re-write the cached file. Rather than checking and writing this file
when errors happen, a console command (schedulable with EXT:scheduler) is used which can be executed with any frequency
and only regenerates the static file when it is expired (TTL defined by command parameter).

Additionally, the extension will listen for page changes and if a page is changed which matches a configured page in a
scheduler task that executes the command that writes the static file, it re-writes the static file immediately. Thus,
any change you make to your error page in TYPO3 immediately causes a new error page to be written.

_The extension utilizes the TYPO3 caching framework as storage to make it easier for you to change how/where the static
file is stored._

Configuration
-------------

Configuring a TYPO3 site to use this feature is relatively simple:

* In the site configuration, create error handler sections for each of the error types you wish to handle this way,
  e.g. `404`, `403` and so on. Each type of error requires a separate error handler.
* For each failure code, configure the "FQCN" of the handler: `BusyNoggin\StaticErrorPages\Handler\PageErrorHandler`.
* For each failure code, configure the page (either select a TYPO3 page one or use a full URL).

This enables the handling of the particular type/types of errors. Then, in order to generate the static files of the
pages/urls you configured:

* Create a new scheduler task of type `Execute console commands (scheduler)`.
* Select `notfound:static: Interact with static version of 404 page` as the command to run.
* Configure the options:
  * Set the "live-identifier" to either a page UID or external URL.
  * Enable the "force" option if you want this scheduler task to always write the file (usually not necessary; setting
    the "ttl" option makes it so the file is only rewritten when the file expires, by default this happens every hour).
    Just click "Add option" and enter a `1` in the value field.
  * Enable the "ttl" option if you want to specify a custom lifetime of the static page. By default this lifetime is
    one hour and the file will always be rewritten after that time has passed.
  * Enable the "no-verify-ssl" option if your site or the external URL uses https:// and the certificate is not signed
    by a verified certificate authority (e.g. if you self-signed the certificate or you are using a https-enabled local
    development environment like DDEV). Just click "Add option" and enter a `1` in the value field.

You can set any frequency you like on the scheduled task. If you enable the "force" option you can use the frequency to
determine how frequently the file is rewritten. If you use the "ttl" option (or just rely on the default TTL) you can
set the frequency lower or higher than the TTL, but probably you would want to set it lower.

> Note that one scheduled task is necessary for each error document as identified by either the full URL or the page
> UID. So, if you have 6 different TYPO3 pages for different error types on different page tree branches, you will need
> to create 6 scheduled tasks - one for each page. This will cause 6 different static files to be created.

#### Note about changing the cache backend

If for some reason your setup requires the error page to be stored in a different way - for example, if you are using
a distributed setup with muliple servers and want to store the file in Redis - you'll need to change the cache backend
to one that stores files in your preferred way. Do this by setting the cache backend like you normally would:

```
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['static_error_pages']['backend'] = ...
```

When you do this, it is important to configure the scheduler task or external cron to _force_ recreation of the file and
manage the frequency yourself. If you configured the "live-identifier" as a TYPO3 page then changes made to this page in
the TYPO3 backend will still cause the file (cache entry) to be rewritten on every change.

Usage as ErrorDocument in HTTP server
-------------------------------------

> This approach is only possible with the default storage (cache) backend shipped with the extension!

With a standard TYPO3 setup, some URLs are handled with TYPO3's page lookups while others (those in specific locations)
are handled as normal files without a page lookup. An error code triggered by such a normal file will by default show a
different error page than the one shown when a page lookup fails.

This extension provides a way to configure an ErrorDocument in the HTTP server and point this to the generated/cached
static file which is saved as a proper HTML file. Because of the way the static file is regenerated, the static file is
not removed when it expires - rather it is left in place and simply overwritten on the next command to regenerate it.
This means that it is totally safe to point to this file from the HTTP server configuration to use it as error page.

Examples for common HTTP servers:

#### Apache

```
# Apache, within global configuration, VHOST configuration or
# .htaccess (if .htaccess is enabled). Use absolute path!
# One entry is required per error code.

ErrorDocument 404 /var/www/html/var/cache/data/static_error_pages/https___mydomain_com_error-page.html
```

#### Nginx

```
# Nginx, within global configuration or VHOST (server block)
# configuration. Note that the "error_page" must be a relative
# path to a file in the public root - and our static file is
# not located within the public folder. So we declare an alias
# that serves our custom page on the (virtual) relative URL.
# Multiple error codes can be specified in the "error_page" line.

error_page 404 403 /my-error-page.html;
location /my-error-page.html {
    alias /var/www/html/var/cache/data/static_error_pages/https___mydomain_com_error-page.html;
}
```

The path to the static file is based on the full URL - either the full URL you entered, or the resolved full URL of the
page as determined by the "base" defined in the site configuration that's active for the page that you entered (with any
conditions applied, e.g. application context). This full URL is then processed to a format that works as a standard file
name by replacing any invalid characters (`[':', '/', '?', '=', '.', '%', '&']`) with an underscore, and always has the
`.html` suffix added. So, for example a full URL of `https://mydomain.com/my-error-page` becomes a local file name of
`https___mydomain_com_my-error-page.html`. URLs that contain a file extension will also include the file extension in
the converted name: `https://mydomain.com/my-error-page.html` becomes `https___mydomain_com_my-error-page_html.html`.

Because the filename is based on the full URL you can generate multiple static files - for example if your TYPO3 site
contains multiple sites with different URLs. You then simply have to define error documents in your HTTP configuration
within a "location" block or other rule that matches each site. Similarly, you can generate dedicated error documents
for each type of error (404, 403, 500, etc.) which show different error messages. Or you can use the same static file
for multiple different error types or domains.

PSR-14 Events
-------------

Several events are included with the extension:

* `BusyNoggin\StaticErrorPages\Event\AfterSourceReadEvent` which allows you to the HTML source after it is read from
  a static file.
* `BusyNoggin\StaticErrorPages\Event\AfterStaticStoredEvent` which allows you to trigger an action right after a static
  file has been written.
* `BusyNoggin\StaticErrorPages\Event\AfterUrlFetchedEvent` which allows you to change the HTML of a fetched URL before
  it is saved as a static file.
* `BusyNoggin\StaticErrorPages\Event\AfterUrlResolvedEvent` which allows you to change the URL before a page is fetched.
* `BusyNoggin\StaticErrorPages\Event\BeforeUrlFetchEvent` which allows you to change the identifier, TTL or "verify ssl"
  options before any URL fetching begins.
* `BusyNoggin\StaticErrorPages\Event\ErrorPageEvent` which allows you to change the HTML source of a static page right
  before it is delivered as a response from TYPO3 - or simply trigger various actions when an error is handled.
* `BusyNoggin\StaticErrorPages\Event\IsExpiredEvent` which allows you to change the "is expired?" decision.

These events are subscribed to the same way any other PSR-14 event is subscribed to in TYPO3.

In `Services.yaml` of your extension:

```yaml
  MyVendor\MyExtension\EventListener\ErrorPageEventListener:
    public: true
    tags:
      - name: event.listener
        identifier: 'my-error-page-event-listener'
        method: 'handleEvent'
        event: BusyNoggin\StaticErrorPages\Event\ErrorPageEvent
```

And the event listener class:

```php
namespace MyVendor\MyExtension\EventListener;

use BusyNoggin\StaticErrorPages\Event\ErrorPageEvent;

class ErrorPageEventListener
{
    public function handleEvent(ErrorPageEvent $event): void
    {
        // Your code to manipulate the event data or trigger other actions
    }
}
```
