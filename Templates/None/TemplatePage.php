<?php

declare(strict_types=1);


namespace Templates\None;

use Phoundation\Web\Page;


/**
 * None template class
 *
 * 
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
 */
class TemplatePage extends \Phoundation\Web\Html\Template\TemplatePage
{
    /**
     * Execute, builds and returns the page output according to the template.
     *
     * Either use the default execution steps from parent::execute($target), or write your own execution steps here.
     * Once the output has been generated it should be returned.
     *
     * @param string $target
     * @return string|null
     */
    public function execute(string $target): ?string
    {
        return parent::execute($target);
    }


    /**
     * Build the HTTP headers for the page
     *
     * @param string $output
     * @return void
     *
     */
    public function buildHttpHeaders(string $output): void
    {
        Page::setContentType('text/html');
        Page::setDoctype('html');
    }


    /**
     * Build the HTML header for the page
     *
     * @return string|null
     */
    public function buildHtmlHeader(): ?string
    {
        return Page::buildHeaders();
    }


    /**
     * Build the page header
     *
     * @return string|null
     */
    public function buildPageHeader(): ?string
    {
        return '';
    }


    /**
     * Build the page footer
     *
     * @return string|null
     */
    public function buildPageFooter(): ?string
    {
        return '';
    }


    /**
     * Build the HTML footer
     *
     * @return string|null
     */
    public function buildHtmlFooter(): ?string
    {
        return Page::buildFooters();
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
     * @param string $target
     * @return string|null
     */
    public function buildBody(string $target): ?string
    {
        return parent::buildBody($target);
    }


    /**
     * @return string|null
     */
    public function buildProfileImage(): ?string
    {
        // TODO: Implement buildProfileImage() method.
    }


    /**
     * Builds and returns the top panel HTML
     *
     * @return string|null
     */
    protected function buildTopPanel(): ?string
    {
        return '';
    }


    /**
     * Builds and returns the sidebar HTML
     *
     * @return string|null
     */
    protected function buildSidePanel(): ?string
    {
        return '';
    }


    /**
     * Builds the body header
     *
     * @return string
     */
    protected function buildBodyHeader(): string
    {
        return '';
    }
}