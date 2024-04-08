<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Template;

use Phoundation\Web\Html\Template\Interfaces\TemplatePageInterface;
use Phoundation\Web\Requests\Response;

/**
 * Class TemplatePage
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
abstract class TemplatePage implements TemplatePageInterface
{
    /**
     * Returns the page instead of sending it to the client
     *
     * This WILL send the HTTP headers, but will return the HTML instead of sending it to the browser
     *
     * @return string|null
     */
    abstract public function execute(): ?string;


    /**
     * Build the page body
     *
     * @return string|null
     */
    public function renderBody(): ?string
    {
        return execute();
    }


    /**
     * Build the HTML footer
     *
     * @return string|null
     */
    public function renderHtmlFooters(): ?string
    {
        $footers = Response::renderHtmlFooters();
        if (Response::getBuildBodyWrapper()) {
            return $footers . '
                      </body>
                  </html>';
        }

        return $footers . '
               </html>';
    }


    /**
     * Build and send HTTP headers
     *
     * @param string $output
     *
     * @return void
     */
    abstract public function renderHttpHeaders(string $output): void;
}
