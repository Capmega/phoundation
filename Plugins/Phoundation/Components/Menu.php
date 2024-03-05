<?php

declare(strict_types=1);

namespace Plugins\Phoundation\Components;


/**
 * Class Menu
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Menu extends \Phoundation\Web\Html\Components\Widgets\Menus\Menu
{
    /**
     * Menu class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setSource([
            tr('System') => [
                'icon' => '',
            ],
            tr('Dashboard') => [
                'rights' => 'admin',
                'url'    => '/',
                'icon'   => 'fas fa-tachometer-alt',
            ],
            tr('Accounts') => [
                'rights' => 'admin,accounts',
                'icon'   => 'fas fa-users',
                'menu'   => [
                    tr('Users') => [
                        'url'    => '/accounts/users.html',
                        'icon'   => 'fas fa-users'
                    ],
                    tr('Roles') => [
                        'url'    => '/accounts/roles.html',
                        'icon'   => 'fas fa-users'
                    ],
                    tr('Rights') => [
                        'url'    => '/accounts/rights.html',
                        'icon'   => 'fas fa-lock'
                    ],
                    tr('Groups') => [
                        'url'    => '/accounts/groups.html',
                        'icon'   => 'fas fa-users'
                    ],
                    tr('Switch user') => [
                        'rights' => 'user-switch',
                        'url'    => '/accounts/switch',
                        'icon'   => 'fas fa-user'
                    ]
                ]
            ],
            tr('Security') => [
                'rights' => 'admin,security',
                'icon' => 'fas fa-lock',
                'menu' => [
                    tr('Authentications log') => [
                        'rights' => 'logs',
                        'url'    => '/security/authentications.html',
                        'icon'   => 'fas fa-key'
                    ],
                    tr('Incidents log') => [
                        'rights' => 'logs',
                        'url'    => '/security/incidents.html',
                        'icon'   => 'fas fa-key'
                    ],
                    tr('Non HTTP-200 URL\'s') => [
                        'rights' => 'logs',
                        'url'    => '/security/non-200-urls.html',
                        'icon'   => 'fas fa-tasks'
                    ],
                    tr('Activity log') => [
                        'rights' => 'logs',
                        'url'    => '/security/activity',
                        'icon'   => 'fas fa-tasks'
                    ],
                ],
            ],
            tr('Development') => [
                'rights' => 'admin,development',
                'icon' => 'fas fa-lock',
                'menu' => [
                    tr('Plugins') => [
                        'rights' => 'plugins',
                        'url'    => '/development/plugins.html',
                        'icon'   => 'fas fa-key'
                    ],
                    tr('Developer incidents') => [
                        'rights' => 'incidents',
                        'url'    => '/development/incidents.html',
                        'icon'   => 'fas fa-key'
                    ],
                    tr('Slow webpage log') => [
                        'rights' => 'logs',
                        'url'    => '/development/slow.html',
                        'icon'   => 'fas fa-key'
                    ]
                ],
            ],
            tr('Key / Values store') => [
                'rights' => 'admin,key-values',
                'url'  => '/system/key-values.html',
                'icon' => 'fas fa-database'
            ],
            tr('Storage system') => [
                'rights' => 'admin,storage',
                'icon' => 'fas fa-database',
                'menu' => [
                    tr('Collections') => [
                        'rights' => 'collections',
                        'url'  => '/storage/collections.html',
                        'icon' => 'fas fa-paperclip'
                    ],
                    tr('Documents')  => [
                        'rights' => 'documents',
                        'url'  => '/storage/documents.html',
                        'icon' => 'fas fa-book'
                    ],
                    tr('Pages')  => [
                        'rights' => 'pages',
                        'url'  => '/storage/documents.html',
                        'icon' => 'fas fa-book'
                    ],
                    tr('Resources') => [
                        'rights' => 'resources',
                        'url'  => '/storage/resources.html',
                        'icon' => 'fas fa-list-ul'
                    ]
                ]
            ],
            tr('Servers') => [
                'rights' => 'admin,servers',
                'icon' => 'fas fa-server',
                'menu' => [
                    tr('Servers') => [
                        'url'  => '/servers/servers.html',
                        'icon' => 'fas fa-server'
                    ],
                    tr('Forwards') => [
                        'url'  => '/servers/forwards.html',
                        'icon' => 'fas fa-arrow-right'
                    ],
                    tr('SSH accounts') => [
                        'rights' => 'ssh,accounts',
                        'url'  => '/servers/ssh-accounts.html',
                        'icon' => 'fas fa-gear'
                    ],
                    tr('Databases') => [
                        'rights' => 'databases',
                        'url'    => '/servers/databases.html',
                        'icon'   => 'fas fa-database'
                    ],
                    tr('Database accounts') => [
                        'rights' => 'databases',
                        'url'  => '/servers/database-accounts.html',
                        'icon' => 'fas fa-users'
                    ],
                ]
            ],
            tr('Hardware') => [
                'rights' => 'admin,hardware',
                'icon' => 'fas fa-camera',
                'menu' => [
                    tr('Devices') => [
                        'rights' => 'devices',
                        'url'    => '/hardware/devices.html',
                        'icon'   => 'fas fa-camera'
                    ],
                    tr('Scanners') => [
                        'rights' => 'scanners',
                        'icon'   => 'fas fa-print',
                        'menu'   => [
                            tr('Document') => [
                                'icon' => 'fas fa-book',
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
                        'url'    => '/phoundation/plugins/plugins.html',
                        'icon'   => ''
                    ],
                    tr('Templates') => [
                        'rights' => 'templates',
                        'url'    => '/phoundation/templates.html',
                        'icon'   => ''
                    ],
                    tr('Filesystems') => [
                        'rights' => 'file-system',
                        'icon' => 'fas fa-lock',
                        'menu' => [
                            tr('Auto mounts') => [
                                'rights' => 'mounts',
                                'url'    => '/phoundation/file-system/auto-mounts/mounts.html',
                                'icon'   => 'fas fa-key'
                            ],
                            tr('Requirements') => [
                                'rights' => 'requirements',
                                'url'    => '/phoundation/file-system/requirements/requirements.html',
                                'icon'   => 'fas fa-key'
                            ],
                        ],
                    ],
                    tr('Databases') => [
                        'rights' => 'databases',
                        'icon' => 'fas fa-lock',
                        'menu' => [
                            tr('Connectors') => [
                                'rights' => 'mounts',
                                'url'    => '/phoundation/databases/connectors/connectors.html',
                                'icon'   => 'fas fa-key'
                            ],
                        ],
                    ],
                ],
            ],
            tr('About') => [
                'rights' => 'admin',
                'url'  => '/about.html',
                'icon' => ''
            ],
            tr('Productivity') => [
                'icon' => '',
            ],
            tr('Customers') => [
                'rights' => 'admin,customers',
                'url'  => '/business/customers.html',
                'icon' => 'fas fa-users'
            ],
            tr('Providers') => [
                'rights' => 'admin,providers',
                'url'    => '/business/providers.html',
                'icon'   => 'fas fa-users'
            ],
            tr('Businesses') => [
                'rights' => 'admin,businesses',
                'icon'   => 'fas fa-building',
                'menu'   => [
                    tr('Companies') => [
                        'rights' => 'companies',
                        'url'    => '/companies/companies.html',
                        'icon'   => 'fas fa-building'
                    ],
                    tr('Branches') => [
                        'rights' => 'branches',
                        'url'    => '/companies/branches.html',
                        'icon'   => 'fas fa-building'
                    ],
                    tr('Departments') => [
                        'rights' => 'departments',
                        'url'    => '/companies/departments.html',
                        'icon'   => 'fas fa-sitemap'
                    ],
                    tr('Employees') => [
                        'rights' => 'employees',
                        'url'    => '/companies/employees.html',
                        'icon'   => 'fas fa-users'
                    ],
                    tr('Inventory') => [
                        'rights' => 'employees',
                        'url'    => '/companies/inventory/inventory.html',
                        'icon'   => 'fas fa-shopping-cart'
                    ]
                ]
            ]
        ]);
    }
}