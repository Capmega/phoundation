<?php

namespace Phoundation\Web\Http\Html\Template;



use Phoundation\Cache\Cache;
use Phoundation\Core\Log;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Http;

/**
 * Template class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class TemplatePage
{
    /**
     * The target page to execute
     *
     * @var string $target
     */
    protected string $target;



    /**
     * TemplatePage constructor
     */
    public function __construct()
    {
    }



    /**
     * Returns a new TargetPage object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }



    /**
     * Returns the page instead of sending it to the client
     *
     * This WILL send the HTTP headers, but will return the HTML instead of sending it to the browser
     * @param string $target
     * @return string|null
     */
    public function execute(string $target): ?string
    {
        $body = include($target);

        // Build HTML and minify the output
        $html = $this->buildHtmlHeader();
        self::$html_headers_sent = true;

        $html .= $this->buildPageHeader();
        $html .= $this->buildMenu();
        $html .= $body;
        $html .= $this->buildPageFooter();
        $html .= $this->buildHtmlFooter();
        $html  = Html::minify($html);

        // Send headers
        $length = $this->buildHttpHeaders();

        Log::success(tr('Sent ":length" bytes of HTTP to client', [':length' => $length]), 3);

        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'HEAD') {
            // HEAD request, do not send any HTML whatsoever
            return null;
        }

        switch (Http::getHttpCode()) {
            case 304:
                // 304 requests indicate the browser to use it's local cache, send nothing
                // no-break

            case 429:
                // 429 Tell the client that it made too many requests, send nothing
                return null;
        }
    }



    /**
     * Returns the page instead of sending it to the client
     *
     * This WILL send the HTTP headers, but will return the HTML instead of sending it to the browser
     * @return string|null
     */
    public static function get(): ?string
    {
        $body = '';

        /// Get all output buffers
        while(ob_get_level()) {
            $body .= ob_get_contents();
            ob_end_clean();
        }

        ob_start(chunk_size: 4096);
        return $body;
    }



    /**
     * Build and send HTTP headers
     *
     * @return int
     */
    public abstract function buildHttpHeaders(): int;

    /**
     * Build the HTML header for the page
     *
     * @return string|null
     */
    public abstract function buildHtmlHeader(): ?string;

    /**
     * Build the page header
     *
     * @return string|null
     */
    public abstract function buildPageHeader(): ?string;

    /**
     * Build the page menu
     *
     * @return string|null
     */
    public abstract function buildMenu(): ?string;

    /**
     * Build the page footer
     *
     * @return string|null
     */
    public abstract function buildPageFooter(): ?string;
}