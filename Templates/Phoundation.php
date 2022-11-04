<?php

namespace Templates;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Http;
use Phoundation\Web\Page;
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
        $html = Html::buildHeaders();

        $html .= '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
                <meta http-equiv="x-ua-compatible" content="ie=edge" />
                <title>Material Design for Bootstrap</title>
                <!-- MDB icon -->
                <link rel="icon" href="img/mdb-favicon.ico" type="image/x-icon" />
                <!-- Font Awesome -->
                <link
                        rel="stylesheet"
                        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
                />
                <!-- Google Fonts Roboto -->
                <link
                        rel="stylesheet"
                        href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap"
                />
                <!-- MDB -->
                <link rel="stylesheet" href="css/mdb.min.css" />
            </head>';
        return $html;
    }



    /**
     * Build the page header
     *
     * @return string|null
     */
    public function buildPageHeader(): ?string
    {
        $html = '<body>
                <!-- Start your project here-->
                <div class="container">';

        return $html;
    }



    /**
     * Build the page footer
     *
     * @return string|null
     */
    public function buildPageFooter(): ?string
    {
        $html = '</div>';

        return $html;
    }



    /**
     * Build the HTML footer
     *
     * @return string|null
     */
    public function buildHtmlFooter(): ?string
    {
        $html = Html::buildFooters();

        $html .= '  <!-- End your project here-->
                    <!-- MDB -->
                    <script type="text/javascript" src="js/mdb.min.js"></script>
                    <!-- Custom scripts -->
                    <script type="text/javascript"></script>
                    </body>
                    </html>';

        return $html;
    }
}