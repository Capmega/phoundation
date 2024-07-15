<?php

namespace Phoundation\Web\Html\Template\Interfaces;

/**
 * Interface TemplatePageInterface
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
interface TemplatePageInterface
{
    /**
     * Returns the page instead of sending it to the client
     *
     * This WILL send the HTTP headers, but will return the HTML instead of sending it to the browser
     *
     * @return string|null
     */
    public function execute(): ?string;


    /**
     * Build the page body
     *
     * @return string|null
     */
    public function renderMain(): ?string;


    /**
     * Build the HTML footer
     *
     * @return string|null
     */
    public function renderHtmlFooters(): ?string;


    /**
     * Build and send HTTP headers
     *
     * @param string $output
     *
     * @return void
     */
    public function renderHttpHeaders(string $output): void;
}
