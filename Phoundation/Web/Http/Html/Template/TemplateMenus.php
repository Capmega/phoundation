<?php

namespace Phoundation\Web\Http\Html\Template;



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
    /**
     * Returns the default sidebar menu
     *
     * @return array|null
     */
    public function getSidebarMenu(): ?array
    {
        return null;
    }



    /**
     * Returns the default top navbar top menu
     *
     * @return array|null
     */
    public function getNavigationMenu(): ?array
    {
        return [
            tr('System') => [
                tr('Accounts') => [
                    tr('Accounts') => '/accounts/users',
                    tr('Roles') => '/accounts/roles',
                    tr('Rights') => '/accounts/rights',
                    tr('Groups') => '/accounts/groups',
                    tr('Switch user') => '/accounts/switch'
                ],
                tr('Security') => [
                    tr('Authentications log') => '/security/log/authentications',
                    tr('Activity log') => '/security/log/activity'
                ],
                tr('Libraries') => '/libraries',
                tr('Key / Values store') => '/system/key-values',
                tr('Storage system') => [
                    tr('Collections') => '/storage/collections',
                    tr('Documents') => '/storage/documents',
                    tr('Resources') => '/storage/resources',
                ],
                tr('Servers') => [
                    tr('Servers') => '/servers/servers',
                    tr('Forwardings') => '/servers/forwardings',
                    tr('SSH accounts') => '/servers/ssh-accounts',
                    tr('Databases') => '/servers/databases',
                    tr('Database accounts') => '/servers/database-accounts',
                ],
                tr('Hardware') => [
                    tr('devices') => '/hardware/devices',
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



    /**
     * Returns the default menu for the profile image
     *
     * @return array|null
     */
    public function getProfileImageMenu(): ?array
    {
        return [
            tr('Profile') => '/profile',
            tr('Sign out') => '/sign-out',
        ];
    }
}