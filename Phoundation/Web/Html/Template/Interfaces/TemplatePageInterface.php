<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Template\Interfaces;

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
