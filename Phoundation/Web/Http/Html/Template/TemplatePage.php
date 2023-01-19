<?php

namespace Phoundation\Web\Http\Html\Template;

use Phoundation\Core\Core;
use Phoundation\Web\Http\Html\Components\Menu;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Page;



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
     * @return Menu|null
     */
    public function getSecondaryMenu(): ?Menu
    {
        return $this->secondary_menu;
    }


    /**
     * Sets the side panel menu
     *
     * @param Menu|null $secondary_menu
     * @return static
     */
    public function setSecondaryMenu(?Menu $secondary_menu): static
    {
        $this->secondary_menu = $secondary_menu;
        return $this;
    }


    /**
     * Returns the navbar top menu
     *
     * @return Menu|null
     */
    public function getPrimaryMenu(): ?Menu
    {
        return $this->primary_menu;
    }


    /**
     * Sets the navbar top menu
     *
     * @param Menu|null $primary_menu
     * @return static
     */
    public function setPrimaryMenu(?Menu $primary_menu): static
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
        Page::htmlHeadersSent(true);

        if (Core::getFailed()) {
            // We're running in failed mode, only show the body
            $output .= $body;
        } else {
            $output .= $this->buildPageHeader();
            $output .= $this->buildMenu();
            $output .= $body;
            $output .= $this->buildPageFooter();
        }

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
        if (Core::getFailed()) {
            $this->primary_menu   = Menu::new();
            $this->secondary_menu = Menu::new();
            return;
        }

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
        return execute_page($target);
    }



    /**
     * Build and send HTTP headers
     *
     * @param string $output
     * @return void
     */
    abstract public function buildHttpHeaders(string $output): void;

    /**
     * Build the HTML header for the page
     *
     * @return string|null
     */
    abstract public function buildHtmlHeader(): ?string;

    /**
     * Build the page header
     *
     * @return string|null
     */
    abstract public function buildPageHeader(): ?string;

    /**
     * Build the page menu
     *
     * @return string|null
     */
    abstract public function buildMenu(): ?string;

    /**
     * Build the page footer
     *
     * @return string|null
     */
    abstract public function buildPageFooter(): ?string;
}