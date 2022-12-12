<?php

namespace Phoundation\Web\Http\Html\Template;

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
     * The components that this template can use to build the page
     *
     * @var TemplateComponents $components
     */
    protected TemplateComponents $components;

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
     * The navigation menu
     *
     * @var array|null $navigation_menu
     */
    protected ?array $navigation_menu = null;

    /**
     * The sidebar menu
     *
     * @var array|null $sidebar_menu
     */
    protected ?array $sidebar_menu = null;



    /**
     * TemplatePage constructor
     */
    public function __construct(TemplateComponents $components, TemplateMenus $menus)
    {
        $this->menus      = $menus;
        $this->components = $components;

        $this->loadMenus();
    }



    /**
     * Returns a new TargetPage object
     *
     * @param TemplateComponents $components
     * @param TemplateMenus $menus
     * @return static
     */
    public static function new(TemplateComponents $components, TemplateMenus $menus): static
    {
        return new static($components, $menus);
    }



    /**
     * Returns the sidebar menu
     *
     * @return array|null
     */
    public function getSidebarMenu(): ?array
    {
        return $this->sidebar_menu;
    }


    /**
     * Sets the sidebar menu
     *
     * @param array|null $sidebar_menu
     * @return static
     */
    public function setSidebarMenu(?array $sidebar_menu): static
    {
        $this->sidebar_menu = $sidebar_menu;
        return $this;
    }


    /**
     * Returns the navbar top menu
     *
     * @return array|null
     */
    public function getNavigationMenu(): ?array
    {
        return $this->navigation_menu;
    }


    /**
     * Sets the navbar top menu
     *
     * @param array|null $navigation_menu
     * @return static
     */
    public function setNavigationMenu(?array $navigation_menu): static
    {
        $this->navigation_menu = $navigation_menu;
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
        include($target);
        $body = '';

        // Get all output buffers and restart buffer
        while (ob_get_level()) {
            $body .= ob_get_contents();
            ob_end_clean();
        }

        ob_start(chunk_size: 4096);

        // Build HTML and minify the output
        $output = $this->buildHtmlHeader();
        WebPage::htmlHeadersSent(true);

        $output .= $this->buildPageHeader();
        $output .= $this->buildMenu();
        $output .= $body;
        $output .= $this->buildPageFooter();
        $output .= $this->buildHtmlFooter();
        $output = Html::minify($output);

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
        $this->navigation_menu = sql()->getColumn('SELECT `value` FROM `key_value_store` WHERE `key` = :key', [':key' => 'navigation_menu']);
        $this->sidebar_menu    = sql()->getColumn('SELECT `value` FROM `key_value_store` WHERE `key` = :key', [':key' => 'sidebar_menu']);

        if (!$this->navigation_menu) {
            $this->navigation_menu = $this->menus->getNavigationMenu();
        }

        if (!$this->sidebar_menu) {
            $this->sidebar_menu = $this->menus->getSidebarMenu();
        }
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