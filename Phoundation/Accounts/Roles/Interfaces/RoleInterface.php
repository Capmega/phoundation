<?php

namespace Phoundation\Accounts\Roles\Interfaces;

use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Users\Interfaces\UsersInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Web\Http\Html\Components\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Http\Html\Components\Interfaces\EntryInterface;
use Phoundation\Web\Http\Html\Components\Interfaces\FormInterface;


/**
 * Interface RoleInterface
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
interface RoleInterface extends DataEntryInterface
{
    /**
     * Add the specified rights to this role
     *
     * @return RightsInterface
     */
    public function getRights(): RightsInterface;

    /**
     * Returns the users that are linked to this role
     *
     * @return UsersInterface
     */
    public function getUsers(): UsersInterface;

    /**
     * * Creates and returns an HTML DataEntry form
     *
     * @param string $name
     * @return DataEntryFormInterface
     */
    public function getRightsHtmlDataEntryForm(string $name = 'roles_id[]'): DataEntryFormInterface;
}
