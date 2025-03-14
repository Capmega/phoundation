<?php

/**
 * Class Basic
 *
 * This class is a basic web page scraper
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Scraper;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataRequestMethod;
use Phoundation\Data\Traits\TraitDataUrlObject;
use Phoundation\Exception\UnspecifiedException;
use Phoundation\Exception\UnsupportedException;
use Phoundation\Network\Curl\Get;
use Phoundation\Network\Curl\Interfaces\CurlInterface;
use Phoundation\Network\Curl\Post;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Phoundation\Web\Http\Interfaces\UrlsInterface;
use Phoundation\Web\Http\Urls;
use Phoundation\Web\Scraper\Exception\NoHtmlException;

class Basic
{
    use TraitDataUrlObject;
    use TraitDataRequestMethod;


    /**
     * The cURL object to scrape with
     *
     * @var CurlInterface $curl
     */
    protected CurlInterface $curl;

    /**
     * Cache for URL's found in the scraped page
     *
     * @var UrlsInterface $urls
     */
    protected UrlsInterface $urls;

    /**
     * Cache for the HTML in the page
     *
     * @var string $html
     */
    protected string $html;

    /**
     * Cache for HTTP headers
     *
     * @var IteratorInterface $http_headers
     */
    protected IteratorInterface $http_headers;


    /**
     * Scraper class constructor
     *
     * @param UrlInterface|null $url
     */
    public function __construct(?UrlInterface $url = null)
    {
        $this->setUrlObject($url)
             ->setRequestMethod(EnumHttpRequestMethod::get);
    }


    /**
     * Returns a new Scraper class
     *
     * @param UrlInterface|null $url
     *
     * @return static
     */
    public static function new(?UrlInterface $url = null): static
    {
        return new static($url);
    }


    /**
     * Returns access to the cURL object
     *
     * @return CurlInterface
     */
    public function getCurlObject(): CurlInterface
    {
        if (empty($this->curl)) {
            switch ($this->request_method) {
                case EnumHttpRequestMethod::get:
                    $this->curl = Get::new();
                    break;

                case EnumHttpRequestMethod::post:
                    $this->curl = Post::new();
                    break;

                case null:
                    throw new UnspecifiedException(tr('The specified request method ":method" is not supported', [
                        ':method' => $this->request_method
                    ]));

                default:
                    throw new UnsupportedException(tr('The specified request method ":method" is not supported', [
                        ':method' => $this->request_method
                    ]));
            }
        }

        return $this->curl;
    }


    /**
     * Returns the scraped page itself
     *
     * @return string
     */
    public function getHtml(): string
    {
        if (empty($this->html)) {
            $html = Strings::from($this->curl->getResultData(), '<DOCTYPE', needle_required: true, case_insensitive: true);

            if (!$html) {
                $html = Strings::from($this->curl->getResultData(), '<html', needle_required: true, case_insensitive: true);

                if (!$html) {
                    throw new NoHtmlException(tr('The URL "":url" does not contain any HTML', [
                        ':url' => $this->curl->getUrl()
                    ]));
                }

                $html = '<html' . $html;

            } else {
                $html = '<DOCTYPE' . $html;
            }

            $this->html = $html;
        }

        return $this->html;
    }


    /**
     * Returns the HTTP status code of the response
     *
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->curl->getHttpCode();
    }


    /**
     * Returns the HTTP request headers for the page
     *
     * @return IteratorInterface
     */
    public function getRequestHeaders(): IteratorInterface
    {
        return new Iterator($this->curl->getRequestHeaders());
    }


    /**
     * Returns the HTTP response headers for the page
     *
     * @return IteratorInterface
     */
    public function getResponseHeaders(): IteratorInterface
    {
        return new Iterator($this->curl->getResponseHeaders());
    }


    /**
     * Returns the HTTP response cookies for the page
     *
     * @return IteratorInterface
     */
    public function getResponseCookies(): IteratorInterface
    {
        return new Iterator($this->curl->getCookies());
    }


    /**
     * Returns the URL's found in the scraped page
     *
     * @todo Get a more efficient regex for this
     * @return UrlsInterface
     */
    public function getUrls(): UrlsInterface
    {
        $return = [];

        preg_match_all('/(\w+\:\/\/.+)(?=[a-z0-9-_]+)/i', $this->getHtml(), $matches);
show($matches);

//        foreach ($matches[1] as $url) {
//            preg_match_all('/(\w+\:\/\/.+)[^\w]+/i', $url, $submatches);
//show($url);
//show($submatches);
//            $return[] = $submatches[1][0];
//        }
showdie($return);
        return new Urls($return);
    }


    /**
     * Returns the URL's found in the anchors in the scraped page
     *
     * @return UrlsInterface
     */
    public function getAnchorUrls(): UrlsInterface
    {
        preg_match_all('/a[\s]+[^>]*?href[\s]?=[\s\"\']+(.*?)[\"\']+.*?>"."([^<]+|.*?)?<\/a>/', $this->getHtml(), $matches);
showdie($matches);
        return new Urls($matches[1]);
    }


    /**
     * Returns the location where to redirect to
     *
     * @return UrlInterface
     */
    public function getRedirectLocation(): UrlInterface
    {
        return $this->curl->getRedirectLocation();
    }


    /**
     * Get the specified URL using the HTTP GET method
     *
     * @return static
     */
    public function execute(): static
    {
        Log::action(ts('Scraping URL ":url" using the HTTP GET method', [
            ':url' => $this->o_url
        ]));

        $this->getCurlObject()->setUrl($this->o_url)
                              ->execute();

        return $this;
    }
}
