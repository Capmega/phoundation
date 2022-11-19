<?php

namespace Templates\Phoundation;

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
     * Execute the specified target
     *
     * @param string $target
     * @return void
     */
    public function execute(string $target): void
    {
        parent::execute($target);
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