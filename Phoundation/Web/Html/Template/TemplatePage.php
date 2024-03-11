<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Template;

use Phoundation\Web\Interfaces\WebRequestInterface;
use Phoundation\Web\Interfaces\WebResponseInterface;
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
     *
     * @param WebRequestInterface $request
     * @param WebResponseInterface $response
     * @return string|null
     */
    abstract public function execute(WebRequestInterface $request, WebResponseInterface $response): ?string;


    /**
     * Build the page body
     *
     * @param WebRequestInterface $request
     * @param WebResponseInterface $response
     * @return string|null
     */
    public function renderBody(WebRequestInterface $request, WebResponseInterface $response): ?string
    {
        return execute_web_script($request, $response);
    }


    /**
     * Build the HTML footer
     *
     * @return string|null
     */
    public function renderHtmlFooters(): ?string
    {
        $footers = Page::renderHtmlFooters();

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
    abstract public function renderHttpHeaders(string $output): void;
}
