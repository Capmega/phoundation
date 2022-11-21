<?php

namespace Templates\Phoundation;

use Phoundation\Cache\Cache;
use Phoundation\Core\Log;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Http;
use Templates\Phoundation\Components\NavigationBar;
use Throwable;



/**
 * Phoundation template class
 *
 * This class is an example template for your website
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Phoundation
 */
class TemplatePage extends \Phoundation\Web\Http\Html\Template\TemplatePage
{
    /**
     * Returns the page instead of sending it to the client
     *
     * This WILL send the HTTP headers, but will return the HTML instead of sending it to the browser
     * @param string $target
     * @return string|null
     */
    public function execute(string $target): ?string
    {
        $body = parent::get();

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

        // Write to cache and return HTML
        return Cache::write(self::$hash, $html);
    }



    /**
     * Build the HTTP headers for the page
     *
     * @return int
     * @throws Throwable
     */
    public function buildHttpHeaders(): int
    {
        Http::setContentType('text/html');
        $this->page->setDoctype('html');
        return Http::sendHeaders();
    }



    /**
     * Build the HTML header for the page
     *
     * @return string|null
     */
    public function buildHtmlHeader(): ?string
    {
        // Load basic MDB and fonts CSS
        $this->page->loadCss('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
        $this->page->loadCss('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap');
        $this->page->loadCss('css/mdb');

        // Load basic MDB javascript library
        $this->page->loadJavascript('js/mdb');

        // Set basic page details
        $this->page->setTitle(tr('Phoundation'));
        $this->page->setFavIcon('mdb-favicon.ico');

        return $this->page->buildHeaders();
    }



    /**
     * Build the page header
     *
     * @return string|null
     */
    public function buildPageHeader(): ?string
    {
        $html = '<body>' .
            NavigationBar::new()->render();

        return $html;
    }



    /**
     * Build the page footer
     *
     * @return string|null
     */
    public function buildPageFooter(): ?string
    {
        $html = '';

        return $html;
    }



    /**
     * Build the HTML footer
     *
     * @return string|null
     */
    public function buildHtmlFooter(): ?string
    {
        $html  = $this->page->buildFooters() . '
        </body>
        </html>';

        return $html;
    }



    /**
     * Build the HTML menu
     *
     * @return string|null
     */
    public function buildMenu(): ?string
    {
        return null;
    }



    /**
     * Build the HTML body
     *
     * @return string|null
     */
    public function buildBody(): ?string
    {
        // TODO: Implement buildBody() method.
    }
}