<?php

namespace Phoundation\Web\Http\Html\Template;

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
    public function __construct()
    {
        $this->loadMenus();
    }



    /**
     * Returns a new TargetPage object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
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
        while(ob_get_level()) {
            $body .= ob_get_contents();
            ob_end_clean();
        }

        ob_start(chunk_size: 4096);

        // Build HTML and minify the output
        $output = $this->buildHtmlHeader();
        Page::htmlHeadersSent(true);

        $output .= $this->buildPageHeader();
        $output .= $this->buildMenu();
        $output .= $body;
        $output .= $this->buildPageFooter();
        $output .= $this->buildHtmlFooter();
        $output  = Html::minify($output);

        // Build headers
        $this->buildHttpHeaders();

        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'HEAD') {
            // HEAD request, do not send any HTML whatsoever
            return null;
        }

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

        if (!$this->navigation_menu and !$this->sidebar_menu) {
            // No menus configured at all! Add at least something to the navigation menu!
            $this->navigation_menu = [
                tr('System') => [
                    tr('Accounts') => [
                        tr('Accounts')       => '/accounts/users',
                        tr('Roles')       => '/accounts/roles',
                        tr('Rights')      => '/accounts/rights',
                        tr('Groups')      => '/accounts/groups',
                        tr('Switch user') => '/accounts/switch'
                    ],
                    tr('Security') => [
                        tr('Authentications log') => '/security/log/authentications',
                        tr('Activity log')        => '/security/log/activity'
                    ],
                    tr('Libraries')          => '/libraries',
                    tr('Key / Values store') => '/system/key-values',
                    tr('Storage system') => [
                        tr('Collections') => '/storage/collections',
                        tr('Documents')   => '/storage/documents',
                        tr('Resources')   => '/storage/resources',
                    ],
                    tr('Servers') => [
                        tr('Servers')      => '/servers/servers',
                        tr('Forwardings')  => '/servers/forwardings',
                        tr('SSH accounts') => '/servers/ssh-accounts',
                        tr('Databases') => '/servers/databases',
                        tr('Database accounts') => '/servers/database-accounts',
                    ],
                    tr('Hardware') => [
                        tr('devices')  => '/hardware/devices',
                        tr('Scanners') => [
                            tr('Document') => [
                                tr('Drivers') => '/hardware/scanners/document/drivers',
                                tr('Devices') => '/hardware/scanners/document/devices',
                            ],
                            tr('Finger print') => '/hardware/scanners/finger-print',
                        ]
                    ],
                ],
                tr('Admin') => [
                    tr('Customers') => '/admin/customers/customers',
                    tr('Providers') => '/admin/providers/providers',
                    tr('Companies') => [
                        tr('Companies') => '/companies/companies',
                        tr('Branches') => '/companies/branches',
                        tr('Departments') => '/companies/departments',
                        tr('Employees') => '/companies/employees',
                        tr('Inventory') => '/companies/inventory/inventory',
                    ],
                ],
                tr('About') => '/about'
            ];
        }
    }



    /**
     * Build and send HTTP headers
     *
     * @return void
     */
    public abstract function buildHttpHeaders(): void;

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