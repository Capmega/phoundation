<?php

declare(strict_types=1);


namespace Plugins\Phoundation\Components;


/**
 * TemplateMenus class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Menu extends \Phoundation\Web\Http\Html\Components\Menu
{
    public function __construct()
    {
        parent::__construct();

        $this->setSource([
            tr('Dashboard') => [
                'rights' => 'admin',
                'url'    => '/',
                'icon'   => 'fa-tachometer-alt',
            ],
            tr('Test') => [
                'rights' => 'admin',
                'url'    => '/test.html',
                'icon'   => 'fa-tachometer-alt',
            ],
            tr('System') => [
                'icon' => '',
            ],
            tr('Accounts') => [
                'rights' => 'admin,accounts',
                'icon'   => 'fa-users',
                'menu'   => [
                    tr('Users') => [
                        'url'    => '/accounts/users.html',
                        'icon'   => 'fa-users'
                    ],
                    tr('Roles') => [
                        'url'    => '/accounts/roles.html',
                        'icon'   => 'fa-users'
                    ],
                    tr('Rights') => [
                        'url'    => '/accounts/rights.html',
                        'icon'   => 'fa-lock'
                    ],
                    tr('Groups') => [
                        'url'    => '/accounts/groups.html',
                        'icon'   => 'fa-users'
                    ],
                    tr('Switch user') => [
                        'rights' => 'user-switch',
                        'url'    => '/accounts/switch',
                        'icon'   => 'fa-user'
                    ]
                ]
            ],
            tr('Security') => [
                'rights' => 'admin,security',
                'icon' => 'fa-lock',
                'menu' => [
                    tr('Authentications log') => [
                        'rights' => 'logs',
                        'url'    => '/security/authentications.html',
                        'icon'   => 'fa-key'
                    ],
                    tr('Incidents log') => [
                        'rights' => 'logs',
                        'url'    => '/security/incidents.html',
                        'icon'   => 'fa-key'
                    ],
                    tr('Activity log') => [
                        'rights' => 'logs',
                        'url'    => '/security/activity',
                        'icon'   => 'fa-tasks'
                    ],
                ],
            ],
            tr('Development') => [
                'rights' => 'admin,development',
                'icon' => 'fa-lock',
                'menu' => [
                    tr('Plugins') => [
                        'rights' => 'plugins',
                        'url'    => '/development/plugins.html',
                        'icon'   => 'fa-key'
                    ],
                    tr('Developer incidents') => [
                        'rights' => 'incidents',
                        'url'    => '/development/incidents.html',
                        'icon'   => 'fa-key'
                    ],
                    tr('Slow webpage log') => [
                        'rights' => 'logs',
                        'url'    => '/development/slow.html',
                        'icon'   => 'fa-key'
                    ]
                ],
            ],
            tr('Key / Values store') => [
                'rights' => 'admin,key-values',
                'url'  => '/system/key-values.html',
                'icon' => 'fa-database'
            ],
            tr('Storage system') => [
                'rights' => 'admin,storage',
                'icon' => 'fa-database',
                'menu' => [
                    tr('Collections') => [
                        'rights' => 'collections',
                        'url'  => '/storage/collections.html',
                        'icon' => 'fa-paperclip'
                    ],
                    tr('Documents')  => [
                        'rights' => 'documents',
                        'url'  => '/storage/documents.html',
                        'icon' => 'fa-book'
                    ],
                    tr('Pages')  => [
                        'rights' => 'pages',
                        'url'  => '/storage/documents.html',
                        'icon' => 'fa-book'
                    ],
                    tr('Resources') => [
                        'rights' => 'resources',
                        'url'  => '/storage/resources.html',
                        'icon' => 'fa-list-ul'
                    ]
                ]
            ],
            tr('Servers') => [
                'rights' => 'admin,servers',
                'icon' => 'fa-server',
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
                        'rights' => 'ssh,accounts',
                        'url'  => '/servers/ssh-accounts.html',
                        'icon' => 'fa-gear'
                    ],
                    tr('Databases') => [
                        'rights' => 'databases',
                        'url'    => '/servers/databases.html',
                        'icon'   => 'fa-database'
                    ],
                    tr('Database accounts') => [
                        'rights' => 'databases',
                        'url'  => '/servers/database-accounts.html',
                        'icon' => 'fa-users'
                    ],
                ]
            ],
            tr('Hardware') => [
                'rights' => 'admin,hardware',
                'icon' => 'fa-camera',
                'menu' => [
                    tr('Devices') => [
                        'rights' => 'devices',
                        'url'    => '/hardware/devices.html',
                        'icon'   => 'fa-camera'
                    ],
                    tr('Scanners') => [
                        'rights' => 'scanners',
                        'icon'   => 'fa-print',
                        'menu'   => [
                            tr('Document') => [
                                'icon' => 'fa-book',
                                'menu' => [
                                    tr('Drivers') => [
                                        'rights' => 'drivers',
                                        'url'    => '/hardware/scanners/document/drivers.html',
                                        'icon'   => ''
                                    ],
                                    tr('Devices') => [
                                        'rights' => 'devices',
                                        'url'    => '/hardware/scanners/document/devices.html',
                                        'icon'   => ''
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
            tr('Phoundation') => [
                'rights' => 'admin,phoundation',
                'url'    => '/phoundation.html',
                'icon'   => '',
                'menu'   => [
                    tr('Configuration') => [
                        'rights' => 'configuration',
                        'url'  => '/phoundation/configuration.html',
                        'icon' => ''
                    ],
                    tr('Routing') => [
                        'rights' => 'routing',
                        'url'    => '/phoundation/routing.html',
                        'icon'   => ''
                    ],
                    tr('Libraries') => [
                        'rights' => 'libraries',
                        'url'    => '/phoundation/libraries.html',
                        'icon'   => ''
                    ],
                    tr('Plugins') => [
                        'rights' => 'plugins',
                        'url'    => '/phoundation/plugins.html',
                        'icon'   => ''
                    ],
                    tr('Templates') => [
                        'rights' => 'templates',
                        'url'    => '/phoundation/templates.html',
                        'icon'   => ''
                    ],
                ]
            ],
            tr('Productivity') => [
                'icon' => '',
            ],
            tr('Customers') => [
                'rights' => 'admin,customers',
                'url'  => '/business/customers.html',
                'icon' => 'fa-users'
            ],
            tr('Providers') => [
                'rights' => 'admin,providers',
                'url'    => '/business/providers.html',
                'icon'   => 'fa-users'
            ],
            tr('Businesses') => [
                'rights' => 'admin,businesses',
                'icon'   => 'fa-building',
                'menu'   => [
                    tr('Companies') => [
                        'rights' => 'companies',
                        'url'    => '/companies/companies.html',
                        'icon'   => 'fa-building'
                    ],
                    tr('Branches') => [
                        'rights' => 'branches',
                        'url'    => '/companies/branches.html',
                        'icon'   => 'fa-building'
                    ],
                    tr('Departments') => [
                        'rights' => 'departments',
                        'url'    => '/companies/departments.html',
                        'icon'   => 'fa-sitemap'
                    ],
                    tr('Employees') => [
                        'rights' => 'employees',
                        'url'    => '/companies/employees.html',
                        'icon'   => 'fa-users'
                    ],
                    tr('Inventory') => [
                        'rights' => 'employees',
                        'url'    => '/companies/inventory/inventory.html',
                        'icon'   => 'fa-shopping-cart'
                    ]
                ]
            ],
            tr('Other') => [
                'icon' => ''
            ],
            tr('About') => [
                'rights' => 'admin',
                'url'  => '/about',
                'icon' => ''
            ]
        ]);
    }
}