<?php

namespace Templates\Phoundation;

use Phoundation\Web\Http\Http;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Page;
use Plugins\Mdb\NavBar;
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
class TemplatePage extends \Phoundation\Web\TemplatePage
{
    /**
     * Build the HTTP headers for the page
     *
     * @return int
     * @throws Throwable
     */
    public function buildHttpHeaders(): int
    {
        Http::setContentType('text/html');
        Page::setDoctype('html');
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
        Page::loadCss('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
        Page::loadCss('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap');
        Page::loadCss('css/mdb');

        // Load basic MDB javascript library
        Page::loadJavascript('js/mdb');

        // Set basic page details
        Page::setTitle(tr('Phoundation'));
        Page::setFavIcon('mdb-favicon.ico');

        return Page::buildHeaders();
    }



    /**
     * Build the page header
     *
     * @return string|null
     */
    public function buildPageHeader(): ?string
    {
        $html = '<body>' .
            NavBar::new()->render();

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
        $html  = Page::buildFooters() . '
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