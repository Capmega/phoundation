<?php

namespace Phoundation\Web\Http\Html\Template;



/**
 * Template class
 *
 * This interface
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class TemplatePage
{
    /**
     * The Page object
     *
     * @var Page $page
     */
    protected Page $page;

    /**
     * The target page to execute
     *
     * @var string $target
     */
    protected string $target;



    /**
     * TemplatePage constructor
     *
     * @param Page $page
     */
    public function __construct(Page $page)
    {
        $this->page = $page;
    }



    /**
     * Returns a new TargetPage object
     *
     * @param Page $page
     * @return $this
     */
    public static function new(Page $page): static
    {
        return new static($page);
    }



    /**
     * Execute the page for the specified target
     *
     * @param string $target
     * @return void
     */
    public function execute(string $target): void
    {
        $this->target = $target;
    }




    /**
     * Build and send HTTP headers
     *
     * @return int
     */
    public abstract function buildHttpHeaders(): int;

    /**
     * Build the HTML header for the page
     *
     * @return string|null
     */
    public abstract function buildHtmlHeader(): ?string;

    /**
     * Build the page header
     *
     * @return string|null
     */
    public abstract function buildPageHeader(): ?string;

    /**
     * Build the page menu
     *
     * @return string|null
     */
    public abstract function buildMenu(): ?string;

    /**
     * Build the page footer
     *
     * @return string|null
     */
    public abstract function buildPageFooter(): ?string;
}