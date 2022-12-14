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
            tr('System') => [
                'icon'    => '',
                'submenu' => [
                    tr('Accounts') => [
                        'icon'    => '',
                        'submenu' => [
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
                        'icon'    => '',
                        'submenu' => [
                            tr('Authentications log') => [
                                'url'  => '/security/log/authentications.html',
                                'icon' => ''
                            ],
                            tr('Activity log') => [
                                'url'  => '/security/log/activity',
                                'icon' => ''
                            ]
                        ],
                    ],
                    tr('Libraries') => [
                        'url'  => '/libraries.html',
                        'icon' => ''
                    ],
                    tr('Key / Values store') => [
                        'url'  => '/system/key-values.html',
                        'icon' => ''
                    ],
                    tr('Storage system') => [
                        'icon'    => '',
                        'submenu' => [
                            tr('Collections') => [
                                'url'  => '/storage/collections.html',
                                'icon' => ''
                            ],
                            tr('Documents')  => [
                                'url'  => '/storage/documents.html',
                                'icon' => ''
                            ],
                            tr('Resources') => [
                                'url'  => '/storage/resources.html',
                                'icon' => ''
                            ]
                        ]
                    ],
                    tr('Servers') => [
                        'icon'    => '',
                        'submenu' => [
                            tr('Servers') => [
                                'url'  => '/servers/servers.html',
                                'icon' => ''
                            ],
                            tr('Forwards') => [
                                'url'  => '/servers/forwards.html',
                                'icon' => ''
                            ],
                            tr('SSH accounts') => [
                                'url'  => '/servers/ssh-accounts.html',
                                'icon' => ''
                            ],
                            tr('Databases') => [
                                'url'  => '/servers/databases.html',
                                'icon' => ''
                            ],
                            tr('Database accounts') => [
                                'url'  => '/servers/database-accounts.html',
                                'icon' => ''
                            ],
                        ]
                    ],
                    tr('Hardware') => [
                        'icon'    => '',
                        'submenu' => [
                            tr('devices') => [
                                'url'  => '/hardware/devices.html',
                                'icon' => ''
                            ],
                            tr('Scanners') => [
                                'icon'    => '',
                                'submenu' => [
                                    tr('Document') => [
                                        'icon'    => '',
                                        'submenu' => [
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
                    ]
                ],
            ],
            tr('Admin') => [
                'icon'    => '',
                'submenu' => [
                    tr('Customers') => [
                        [
                            'url'  => '/admin/customers/customers.html',
                            'icon' => ''
                        ],
                    ],
                    tr('Providers') => [
                        [
                            'url'  => '/admin/providers/providers.html',
                            'icon' => ''
                        ],
                    ],
                    tr('Companies') => [
                        'icon'    => '',
                        'submenu' => [
                            tr('Companies') => [
                                'url'  => '/companies/companies.html',
                                'icon' => ''
                            ],
                            tr('Branches') => [
                                'url'  => '/companies/branches.html',
                                'icon' => ''
                            ],
                            tr('Departments') => [
                                'url'  => '/companies/departments.html',
                                'icon' => ''
                            ],
                            tr('Employees') => [
                                'url'  => '/companies/employees.html',
                                'icon' => ''
                            ],
                            tr('Inventory') => [
                                'url'  => '/companies/inventory/inventory.html',
                                'icon' => ''
                            ]
                        ]
                    ]
                ]
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