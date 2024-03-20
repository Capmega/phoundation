<?php

declare(strict_types=1);

namespace Phoundation\Network\Relay;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Log\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Network\Curl\Interfaces\CurlInterface;
use Phoundation\Network\Curl\Post;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Requests\Response;
use Stringable;
use Throwable;


/**
 * Relays web requests
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Grafana
 */
class Relay
{
    /**
     * The cURL object that does the HTTP traffic
     *
     * @var CurlInterface $curl
     */
    protected CurlInterface $curl;

    /**
     * Search / replace on the resulting page URL's
     *
     * @var array $page_replace
     */
    protected array $page_replace;


    /**
     * Relay class constructor
     *
     * @param Stringable|string $url
     */
    public function __construct(Stringable|string $url)
    {
        $this->curl = Post::new($url)->setMethod('GET');
    }


    /**
     * Returns a new Relay object
     *
     * @param Stringable|string $url
     * @return static
     */
    public static function new(Stringable|string $url): static
    {
        return new static($url);
    }


    /**
     * Returns the cURL object
     *
     * @return CurlInterface
     */
    public function getCurl(): CurlInterface
    {
        return $this->curl;
    }


    /**
     * Returns how the page URL's should be searched / replaced (from key to value)
     *
     * @return array
     */
    public function getPageReplace(): array
    {
        return $this->page_replace;
    }


    /**
     * Sets how the page URL's should be searched / replaced (from key to value)
     *
     * @param array $page_replace
     * @return static
     */
    public function setPageReplace(array $page_replace): static
    {
        $this->page_replace = $page_replace;
        return $this;
    }


    /**
     * Returns the URL that will be relayed
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->curl->getUrl();
    }


    /**
     * Sets the URL that will be relayed
     *
     * @param Stringable|string|null $url
     * @return static
     */
    public function setUrl(Stringable|string|null $url): static
    {
        $this->curl->setUrl($url);
        return $this;
    }


    /**
     * Relays the current request directly to the given URL
     *
     * @return void
     */
    #[NoReturn] public function get(): void
    {
        Log::action(tr('Relaying URL ":url"', [':url' => $this->curl->getUrl()]));
        Response::setBuildBody(false);

        try {
            $page = $this->curl
                ->addRequestHeaders(Arrays::extractPrefix($_SERVER, 'HTTP_'))
                ->execute();

        } catch (Throwable $e){
            switch ($this->curl->getHttpCode()) {
                case null:
                    throw new OutOfBoundsException(tr('Relay request has not yet been executed, somehow?'));

                case 404:
            }

            throw $e;
        }

        $data    = $page->getResultData();
        $headers = $page->getResultHeaders();

        if ($this->page_replace) {
            // Search / replace the URL's
            $data = str_replace(array_keys($this->page_replace), array_values($this->page_replace), $data);
        }

        // Relay filtered headers
        foreach ($headers as $header) {
            $test = strtolower($header);
            // Transfer filter may screw things up, don't use it.
            if (str_contains($test, 'transfer')) {
                continue;
            }

            // Do NOT send somewhere else (in this case, probably something like localhost
            // TODO Maybe later make this optional or filtered for certain domains?
            if (str_starts_with($test, 'location:')) {
                continue;
            }

            header($header);
        }

        // Relay the data
        echo $data;

//        // Get extension for headers
//        $extension = substr((string) $url, -3, 3);
//        $extension = strtolower($extension);
//
//        header('Cache-Control: no-cache, must-revalidate');
//        header('Pragma: no-cache'); //keeps ie happy
//        header('Content-type: ' . (($extension === '.js') ? 'text/javascript; charset=UTF-8' : 'text/html; charset=UTF-8'));
//        header('Content-Length: ' . strlen($page));

        ob_end_clean();
        ob_end_flush();
        flush();

        echo $data;
        exit();
    }
}
