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

        return $menu->setMenu([
            tr('Dashboard') => [
                'url'  => '/',
                'icon' => 'fa-tachometer-alt',
            ],
            tr('System') => [
                'icon' => '',
            ],
            tr('Accounts') => [
                'icon' => 'fa-users',
                'menu' => [
                    tr('Accounts') => [
                        'url'  => '/accounts/users.html',
                        'icon' => ''
                    ],
                    tr('Roles') => [
                        'url'  => '/accounts/roles.html',
                        'icon' => ''
                    ],
                    tr('Rights') => [
                        'url'  => '/accounts/rights.html',
                        'icon' => ''
                    ],
                    tr('Groups') => [
                        'url'  => '/accounts/groups.html',
                        'icon' => ''
                    ],
                    tr('Switch user') => [
                        'url'  => '/accounts/switch',
                        'icon' => ''
                    ]
                ]
            ],
            tr('Security') => [
                'icon' => 'fa-lock',
                'menu' => [
                    tr('Authentications log') => [
                        'url'  => '/security/log/authentications.html',
                        'icon' => 'fa-key'
                    ],
                    tr('Activity log') => [
                        'url'  => '/security/log/activity',
                        'icon' => 'fa-tasks'
                    ]
                ],
            ],
            tr('Libraries') => [
                'url'  => '/libraries.html',
                'icon' => 'fa-book'
            ],
            tr('Key / Values store') => [
                'url'  => '/system/key-values.html',
                'icon' => 'fa-database'
            ],
            tr('Storage system') => [
                'icon' => 'fa-database',
                'menu' => [
                    tr('Collections') => [
                        'url'  => '/storage/collections.html',
                        'icon' => 'fa-paperclip'
                    ],
                    tr('Documents')  => [
                        'url'  => '/storage/documents.html',
                        'icon' => 'fa-files-o'
                    ],
                    tr('Resources') => [
                        'url'  => '/storage/resources.html',
                        'icon' => 'list-ul'
                    ]
                ]
            ],
            tr('Servers') => [
                'icon' => '',
                'menu' => [
                    tr('Servers') => [
                        'url'  => '/servers/servers.html',
                        'icon' => 'fa-server'
                    ],
                    tr('Forwards') => [
                        'url'  => '/servers/forwards.html',
                        'icon' => 'fa-arrow-right'
                    ],
                    tr('SSH accounts') => [
                        'url'  => '/servers/ssh-accounts.html',
                        'icon' => 'fa-gear'
                    ],
                    tr('Databases') => [
                        'url'  => '/servers/databases.html',
                        'icon' => 'fa-database'
                    ],
                    tr('Database accounts') => [
                        'url'  => '/servers/database-accounts.html',
                        'icon' => 'fa-users'
                    ],
                ]
            ],
            tr('Hardware') => [
                'icon' => 'fa-camera',
                'menu' => [
                    tr('devices') => [
                        'url'  => '/hardware/devices.html',
                        'icon' => 'fa-usb'
                    ],
                    tr('Scanners') => [
                        'icon' => 'fa-printer',
                        'menu' => [
                            tr('Document') => [
                                'icon' => 'fa-',
                                'menu' => [
                                    tr('Drivers') => [
                                        'url'  => '/hardware/scanners/document/drivers.html',
                                        'icon' => ''
                                    ],
                                    tr('Devices') => [
                                        'url'  => '/hardware/scanners/document/devices.html',
                                        'icon' => ''
                                    ],
                                ]
                            ],
                            tr('Finger print') => [
                                'url'  => '/hardware/scanners/finger-print.html',
                                'icon' => ''
                            ],
                        ]
                    ]
                ]
            ],
            tr('Admin') => [
                'icon' => '',
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
                        'url'  => '/companies/companies.html',
                        'icon' => 'fa-building'
                    ],
                    tr('Branches') => [
                        'url'  => '/companies/branches.html',
                        'icon' => 'fa-building'
                    ],
                    tr('Departments') => [
                        'url'  => '/companies/departments.html',
                        'icon' => 'fa-sitemap'
                    ],
                    tr('Employees') => [
                        'url'  => '/companies/employees.html',
                        'icon' => 'fa-users'
                    ],
                    tr('Inventory') => [
                        'url'  => '/companies/inventory/inventory.html',
                        'icon' => 'fa-shopping-cart'
                    ]
                ]
            ],
            tr('Other') => [
                'icon' => ''
            ],
            tr('About') => [
                'url'  => '/about',
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

        return $menu->setMenu([
            tr('Profile') => [
                'url'  => '/profile.html',
                'icon' => ''
            ],
            tr('Sign out') => [
                'url'  => '/sign-out.html',
                'icon' => ''
            ],
        ]);
    }
}