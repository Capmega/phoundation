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
     * @var UrlsInterface $o_urls
     */
    protected UrlsInterface $o_urls;

    /**
     * Tracks URL's that have failed
     *
     * @var UrlsInterface $o_failed_urls
     */
    protected UrlsInterface $o_failed_urls;

    /**
     * Tracks the URL's that do not exist (give a 404)
     *
     * @var UrlsInterface $o_not_exists_urls
     */
    protected UrlsInterface $o_not_exists_urls;


    /**
     * HttpTester class constructor
     */
    public function __construct(UrlInterface $o_url)
    {
        $this->setUrlObject($o_url);
    }


    /**
     * Returns a new static object
     *
     * @param UrlInterface $o_url
     *
     * @return static
     */
    public static function new(UrlInterface $o_url): static
    {
        return new static($o_url);
    }


    /**
     * Resets the HttpTester and adds the set URL to the URL's to be scanned
     *
     * @param UrlInterface|string|null $o_url
     *
     * @return static
     */
    public function setUrlObject(UrlInterface|string|null $o_url): static
    {
        $this->o_urls = new Urls();
        $this->o_urls->add($o_url);

        return $this->__setUrlObject($o_url);
    }


    /**
     * Returns the list with failed URL's
     *
     * @return UrlsInterface
     */
    public function getFailedUrlsObject(): UrlsInterface
    {
        return $this->o_failed_urls;
    }


    /**
     * Returns the list with URL's that do not exist (give 404)
     *
     * @return UrlsInterface
     */
    public function getNotExistsUrls(): UrlsInterface
    {
        return $this->o_not_exists_urls;
    }


    /**
     * Executes the tester
     *
     * @return static
     */
    public function execute(): static
    {
        while ($this->o_urls->isNotEmpty()) {
            Log::success(ts('Processing ":count" URL\'s', [
                ':count' => $this->o_urls->count(),
            ]));

            $url     = $this->o_urls->extractFirstValue();
            $scraper = Basic::new($url)->execute();

            switch ($scraper->getHttpCode()) {
                case 200:
                    $urls = $scraper->getUrls();
showdie($urls->getSource());
                    $urls = $urls->keepMatchingValues(Url::newPrimaryDomainRootUrl(), Utils::MATCH_STARTS_WITH);

                    $this->o_urls->addSource($urls)
                                 ->makeValuesUnique();

                    Log::success(ts('Found ":count" URL\'s on page', [
                        ':count' => $this->o_urls->count(),
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

                    $this->o_not_exists_urls->add($url);
                    break;

                default:
                    Log::warning(ts('URL ":url" failed with HTTP code ":code"', [
                        ':url' => $url
                    ]));

                    $this->o_failed_urls->add($url);
            }
        }

        return $this;
    }
}
