<?php

namespace Phoundation\Web\Http\Html\Template;

use Phoundation\Web\Http\Html\Components\Menu;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\WebPage;



/**
 * Template class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class TemplatePage
{
    /**
     * The page menus for this template
     *
     * @var TemplateMenus $menus
     */
    protected TemplateMenus $menus;

    /**
     * The target page to execute
     *
     * @var string $target
     */
    protected string $target;

    /**
     * The page primary menu
     *
     * @var Menu $primary_menu
     */
    protected Menu $primary_menu;

    /**
     * The page secondary menu
     *
     * @var Menu $secondary_menu
     */
    protected Menu $secondary_menu;



    /**
     * TemplatePage constructor
     */
    public function __construct(TemplateMenus $menus)
    {
        $this->menus = $menus;
        $this->loadMenus();
    }



    /**
     * Returns a new TargetPage object
     *
     * @param TemplateMenus $menus
     * @return static
     */
    public static function new(TemplateMenus $menus): static
    {
        return new static($menus);
    }



    /**
     * Returns the side panel menu
     *
     * @return array|null
     */
    public function getSecondaryMenu(): ?array
    {
        return $this->secondary_menu;
    }


    /**
     * Sets the side panel menu
     *
     * @param array|null $secondary_menu
     * @return static
     */
    public function setSecondaryMenu(?array $secondary_menu): static
    {
        $this->secondary_menu = $secondary_menu;
        return $this;
    }


    /**
     * Returns the navbar top menu
     *
     * @return array|null
     */
    public function getPrimaryMenu(): ?array
    {
        return $this->primary_menu;
    }


    /**
     * Sets the navbar top menu
     *
     * @param array|null $primary_menu
     * @return static
     */
    public function setPrimaryMenu(?array $primary_menu): static
    {
        $this->primary_menu = $primary_menu;
        return $this;
    }


    /**
     * Returns the page instead of sending it to the client
     *
     * This WILL send the HTTP headers, but will return the HTML instead of sending it to the browser
     * @param string $target
     * @return string|null
     */
    public function execute(string $target): ?string
    {
        $body = $this->buildBody($target);

        // Build HTML and minify the output
        $output = $this->buildHtmlHeader();
        WebPage::htmlHeadersSent(true);

        $output .= $this->buildPageHeader();
        $output .= $this->buildMenu();
        $output .= $body;
        $output .= $this->buildPageFooter();
        $output .= $this->buildHtmlFooter();
        $output  = Html::minify($output);

        // Build Template specific HTTP headers
        $this->buildHttpHeaders($output);

        return $output;
    }



    /**
     * Load the menu contents from database
     *
     * @return void
     */
    protected function loadMenus(): void
    {
        $primary_menu   = sql()->getColumn('SELECT `value` FROM `key_value_store` WHERE `key` = :key', [':key' => 'primary_menu']);
        $secondary_menu = sql()->getColumn('SELECT `value` FROM `key_value_store` WHERE `key` = :key', [':key' => 'secondary_menu']);

        if ($primary_menu) {
            $this->primary_menu = Menu::new($primary_menu);
        } else {
            $this->primary_menu = $this->menus->getPrimaryMenu();
        }

        if ($secondary_menu) {
            $this->secondary_menu = Menu::new($secondary_menu);
        } else {
            $this->secondary_menu = $this->menus->getSecondaryMenu();
        }
    }



    /**
     * Build the page body
     *
     * @param string $target
     * @return string|null
     */
    public function buildBody(string $target): ?string
    {
        include($target);
        $body = '';

        // Get all output buffers and restart buffer
        while (ob_get_level()) {
            $body .= ob_get_contents();
            ob_end_clean();
        }

        ob_start(chunk_size: 4096);
        return $body;
    }



    /**
     * Build and send HTTP headers
     *
     * @param string $output
     * @return void
     */
    public abstract function buildHttpHeaders(string $output): void;

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