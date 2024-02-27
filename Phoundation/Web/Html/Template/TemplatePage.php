<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Template;

use Phoundation\Web\Page;


/**
 * Class TemplatePage
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class TemplatePage
{
    /**
     * Returns the page instead of sending it to the client
     *
     * This WILL send the HTTP headers, but will return the HTML instead of sending it to the browser
     * @param string $target
     * @param bool $main_content_only
     * @return string|null
     */
    abstract public function execute(string $target, bool $main_content_only = false): ?string;


    /**
     * Build the page body
     *
     * @param string $target
     * @param bool $main_content_only
     * @return string|null
     */
    public function buildBody(string $target, bool $main_content_only = false): ?string
    {
        return execute_page($target);
    }


    /**
     * Build the HTML footer
     *
     * @return string|null
     */
    public function buildHtmlFooters(): ?string
    {
        $footers = Page::buildHtmlFooters();

        if (Page::getBuildBodyWrapper()) {
            return        $footers . '
                      </body>
                  </html>';
        }

        return     $footers . '
               </html>';
    }


    /**
     * Build and send HTTP headers
     *
     * @param string $output
     * @return void
     */
    abstract public function buildHttpHeaders(string $output): void;
}
