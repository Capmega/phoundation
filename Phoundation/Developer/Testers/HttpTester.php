<?php

/**
 * Class HttpTester
 *
 * This class can execute HTTP tests on your local site
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Testers;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataUrlObject;
use Phoundation\Utils\Utils;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Phoundation\Web\Http\Interfaces\UrlsInterface;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Http\Urls;
use Phoundation\Web\Scraper\Basic;

class HttpTester
{
    use TraitDataUrlObject {
        setUrlObject as protected __setUrlObject;
    }


    /**
     * A cache of all URL's that needs scraping
     *
     * @var UrlsInterface $_urls
     */
    protected UrlsInterface $_urls;

    /**
     * Tracks URL's that have failed
     *
     * @var UrlsInterface $_failed_urls
     */
    protected UrlsInterface $_failed_urls;

    /**
     * Tracks the URL's that do not exist (give a 404)
     *
     * @var UrlsInterface $_not_exists_urls
     */
    protected UrlsInterface $_not_exists_urls;


    /**
     * HttpTester class constructor
     */
    public function __construct(UrlInterface $_url)
    {
        $this->setUrlObject($_url);
    }


    /**
     * Returns a new static object
     *
     * @param UrlInterface $_url
     *
     * @return static
     */
    public static function new(UrlInterface $_url): static
    {
        return new static($_url);
    }


    /**
     * Resets the HttpTester and adds the set URL to the URL's to be scanned
     *
     * @param UrlInterface|string|null $_url
     *
     * @return static
     */
    public function setUrlObject(UrlInterface|string|null $_url): static
    {
        $this->_urls = new Urls();
        $this->_urls->add($_url);

        return $this->__setUrlObject($_url);
    }


    /**
     * Returns the list with failed URL's
     *
     * @return UrlsInterface
     */
    public function getFailedUrlsObject(): UrlsInterface
    {
        return $this->_failed_urls;
    }


    /**
     * Returns the list with URL's that do not exist (give 404)
     *
     * @return UrlsInterface
     */
    public function getNotExistsUrls(): UrlsInterface
    {
        return $this->_not_exists_urls;
    }


    /**
     * Executes the tester
     *
     * @return static
     */
    public function execute(): static
    {
        while ($this->_urls->isNotEmpty()) {
            Log::success(ts('Processing ":count" URL\'s', [
                ':count' => $this->_urls->count(),
            ]));

            $url     = $this->_urls->extractFirstValue();
            $scraper = Basic::new($url)->execute();

            switch ($scraper->getHttpCode()) {
                case 200:
                    $urls = $scraper->getUrls();
showdie($urls->getSource());
                    $urls = $urls->keepMatchingValues(Url::newPrimaryDomainRootUrl(), Utils::MATCH_STARTS_WITH);

                    $this->_urls->addSource($urls)
                                 ->makeValuesUnique();

                    Log::success(ts('Found ":count" URL\'s on page', [
                        ':count' => $this->_urls->count(),
                    ]));

                case 301:
                    // no break

                case 302:
                    Log::notice(ts('URL ":url" redirects to ":location"', [
                        ':url'      => $scraper->getUrlObject(),
                        ':location' => $scraper->getRedirectLocation(),
                    ]));
                    break;

                case 404:
                    Log::notice(ts('URL ":url" does not exist', [
                        ':url'      => $scraper->getUrlObject(),
                        ':location' => $scraper->getRedirectLocation(),
                    ]));

                    $this->_not_exists_urls->add($url);
                    break;

                default:
                    Log::warning(ts('URL ":url" failed with HTTP code ":code"', [
                        ':url' => $url
                    ]));

                    $this->_failed_urls->add($url);
            }
        }

        return $this;
    }
}
