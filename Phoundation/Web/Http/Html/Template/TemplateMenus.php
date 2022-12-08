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
    public static function getSidebarMenu(): ?array
    {
        return null;
    }



    /**
     * Returns the default top navbar top menu
     *
     * @return array|null
     */
    public static function getNavigationMenu(): ?array
    {
        return [
            tr('System') => [
                tr('Accounts') => [
                    tr('Accounts') => '/accounts/users.html',
                    tr('Roles') => '/accounts/roles.html',
                    tr('Rights') => '/accounts/rights.html',
                    tr('Groups') => '/accounts/groups.html',
                    tr('Switch user') => '/accounts/switch'
                ],
                tr('Security') => [
                    tr('Authentications log') => '/security/log/authentications.html',
                    tr('Activity log') => '/security/log/activity'
                ],
                tr('Libraries') => '/libraries.html',
                tr('Key / Values store') => '/system/key-values.html',
                tr('Storage system') => [
                    tr('Collections') => '/storage/collections.html',
                    tr('Documents') => '/storage/documents.html',
                    tr('Resources') => '/storage/resources.html',
                ],
                tr('Servers') => [
                    tr('Servers') => '/servers/servers.html',
                    tr('Forwardings') => '/servers/forwardings.html',
                    tr('SSH accounts') => '/servers/ssh-accounts.html',
                    tr('Databases') => '/servers/databases.html',
                    tr('Database accounts') => '/servers/database-accounts.html',
                ],
                tr('Hardware') => [
                    tr('devices') => '/hardware/devices.html',
                    tr('Scanners') => [
                        tr('Document') => [
                            tr('Drivers') => '/hardware/scanners/document/drivers.html',
                            tr('Devices') => '/hardware/scanners/document/devices.html',
                        ],
                        tr('Finger print') => '/hardware/scanners/finger-print.html',
                    ]
                ],
            ],
            tr('Admin') => [
                tr('Customers') => '/admin/customers/customers.html',
                tr('Providers') => '/admin/providers/providers.html',
                tr('Companies') => [
                    tr('Companies') => '/companies/companies.html',
                    tr('Branches') => '/companies/branches.html',
                    tr('Departments') => '/companies/departments.html',
                    tr('Employees') => '/companies/employees.html',
                    tr('Inventory') => '/companies/inventory/inventory.html',
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
    public static function getProfileImageMenu(): ?array
    {
        return [
            tr('Profile') => '/profile.html',
            tr('Sign out') => '/sign-out.html',
        ];
    }
}