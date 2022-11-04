<?php

namespace Templates;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Http;
use Phoundation\Web\Template;



/**
 * Phoundation template class
 *
 * This class is an example template for your website
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates
 */
class Phoundation extends Template
{
    public function buildHttpHeaders(): int
    {
        Http::setContentType('text/html');
        return Http::sendHeaders();
    }

    /**
     * Build the HTML header for the page
     *
     * @return string|null
     */
    public function buildHtmlHeader(): ?string
    {
        // TODO: Implement buildHtmlHeader() method.
        return Html::buildHeaders();
    }



    /**
     * Build the page header
     *
     * @return string|null
     */
    public function buildPageHeader(): ?string
    {
        // TODO: Implement buildPageHeader() method.
        return null;
    }



    /**
     * Build the page footer
     *
     * @return string|null
     */
    public function buildPageFooter(): ?string
    {
        // TODO: Implement buildPageFooter() method.
        return Html::buildFooters();
    }
}