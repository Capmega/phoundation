<?php

namespace Phoundation\Web\Http\Html\Template;

use Phoundation\Web\Http\Html\Components\Menu;



/**
 * TemplateMenus class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class TemplateMenus
{
    protected static string $menu_class = Menu::class;



    /**
     * Returns the default side panel menu
     *
     * @return Menu
     */
    public static function getSecondaryMenu(): Menu
    {
        return new self::$menu_class();
    }



    /**
     * Returns the default top navbar top menu
     *
     * @return Menu
     */
    public static function getPrimaryMenu(): Menu
    {
        $menu = new self::$menu_class();

        return $menu->setSource([
            tr('Dashboard') => [
                'url'  => '/admin/',
                'icon' => 'fa-tachometer-alt',
            ],
            tr('System') => [
                'icon' => '',
            ],
            tr('Accounts') => [
                'icon' => 'fa-users',
                'menu' => [
                    tr('Users') => [
                        'url'  => '/admin/accounts/users.html',
                        'icon' => 'fa-users'
                    ],
                    tr('Roles') => [
                        'url'  => '/admin/accounts/roles.html',
                        'icon' => 'fa-users'
                    ],
                    tr('Rights') => [
                        'url'  => '/admin/accounts/rights.html',
                        'icon' => 'fa-lock'
                    ],
                    tr('Groups') => [
                        'url'  => '/admin/accounts/groups.html',
                        'icon' => 'fa-users'
                    ],
                    tr('Switch user') => [
                        'url'  => '/admin/accounts/switch',
                        'icon' => 'fa-user'
                    ]
                ]
            ],
            tr('Security') => [
                'icon' => 'fa-lock',
                'menu' => [
                    tr('Authentications log') => [
                        'url'  => '/admin/security/log/authentications.html',
                        'icon' => 'fa-key'
                    ],
                    tr('Activity log') => [
                        'url'  => '/admin/security/log/activity',
                        'icon' => 'fa-tasks'
                    ]
                ],
            ],
            tr('Key / Values store') => [
                'url'  => '/admin/system/key-values.html',
                'icon' => 'fa-database'
            ],
            tr('Storage system') => [
                'icon' => 'fa-database',
                'menu' => [
                    tr('Collections') => [
                        'url'  => '/admin/storage/collections.html',
                        'icon' => 'fa-paperclip'
                    ],
                    tr('Documents')  => [
                        'url'  => '/admin/storage/documents.html',
                        'icon' => 'fa-book'
                    ],
                    tr('Resources') => [
                        'url'  => '/admin/storage/resources.html',
                        'icon' => 'fa-list-ul'
                    ]
                ]
            ],
            tr('Servers') => [
                'icon' => 'fa-server',
                'menu' => [
                    tr('Servers') => [
                        'url'  => '/admin/servers/servers.html',
                        'icon' => 'fa-server'
                    ],
                    tr('Forwards') => [
                        'url'  => '/admin/servers/forwards.html',
                        'icon' => 'fa-arrow-right'
                    ],
                    tr('SSH accounts') => [
                        'url'  => '/admin/servers/ssh-accounts.html',
                        'icon' => 'fa-gear'
                    ],
                    tr('Databases') => [
                        'url'  => '/admin/servers/databases.html',
                        'icon' => 'fa-database'
                    ],
                    tr('Database accounts') => [
                        'url'  => '/admin/servers/database-accounts.html',
                        'icon' => 'fa-users'
                    ],
                ]
            ],
            tr('Hardware') => [
                'icon' => 'fa-camera',
                'menu' => [
                    tr('Devices') => [
                        'url'  => '/admin/hardware/devices.html',
                        'icon' => 'fa-camera'
                    ],
                    tr('Scanners') => [
                        'icon' => 'fa-print',
                        'menu' => [
                            tr('Document') => [
                                'icon' => 'fa-book',
                                'menu' => [
                                    tr('Drivers') => [
                                        'url'  => '/admin/hardware/scanners/document/drivers.html',
                                        'icon' => ''
                                    ],
                                    tr('Devices') => [
                                        'url'  => '/admin/hardware/scanners/document/devices.html',
                                        'icon' => ''
                                    ],
                                ]
                            ],
                            tr('Finger print') => [
                                'url'  => '/admin/hardware/scanners/finger-print.html',
                                'icon' => ''
                            ],
                        ]
                    ]
                ]
            ],
            tr('Phoundation') => [
                'url'  => '/admin/phoundation.html',
                'icon' => '',
                'menu' => [
                    tr('Configuration') => [
                        'url'  => '/admin/phoundation/configuration.html',
                        'icon' => ''
                    ],
                    tr('Routing') => [
                        'url'  => '/admin/phoundation/routing.html',
                        'icon' => ''
                    ],
                    tr('Libraries') => [
                        'url'  => '/admin/phoundation/libraries.html',
                        'icon' => ''
                    ],
                    tr('Plugins') => [
                        'url'  => '/admin/phoundation/plugins.html',
                        'icon' => ''
                    ],
                    tr('Templates') => [
                        'url'  => '/admin/phoundation/templates.html',
                        'icon' => ''
                    ],
                ]
            ],
            tr('Admin') => [
                'icon' => 'fa-sprocket',
            ],
            tr('Customers') => [
                'url'  => '/admin/customers/customers.html',
                'icon' => 'fa-users'
            ],
            tr('Providers') => [
                'url'  => '/admin/providers/providers.html',
                'icon' => 'fa-users'
            ],
            tr('Business') => [
                'icon' => 'fa-building',
                'menu' => [
                    tr('Companies') => [
                        'url'  => '/admin/companies/companies.html',
                        'icon' => 'fa-building'
                    ],
                    tr('Branches') => [
                        'url'  => '/admin/companies/branches.html',
                        'icon' => 'fa-building'
                    ],
                    tr('Departments') => [
                        'url'  => '/admin/companies/departments.html',
                        'icon' => 'fa-sitemap'
                    ],
                    tr('Employees') => [
                        'url'  => '/admin/companies/employees.html',
                        'icon' => 'fa-users'
                    ],
                    tr('Inventory') => [
                        'url'  => '/admin/companies/inventory/inventory.html',
                        'icon' => 'fa-shopping-cart'
                    ]
                ]
            ],
            tr('Other') => [
                'icon' => ''
            ],
            tr('About') => [
                'url'  => '/admin/about',
                'icon' => ''
            ]
        ]);
    }



    /**
     * Returns the default menu for the profile image
     *
     * @return Menu
     */
    public static function getProfileImageMenu(): Menu
    {
        $menu = new self::$menu_class();

        return $menu->setSource([
            tr('Profile') => [
                'url'  => '/admin/profile.html',
                'icon' => ''
            ],
            tr('Sign out') => [
                'url'  => '/admin/sign-out.html',
                'icon' => ''
            ],
        ]);
    }
}