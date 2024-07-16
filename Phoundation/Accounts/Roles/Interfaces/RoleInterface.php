<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Roles\Interfaces;

use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Users\Interfaces\UsersInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormInterface;

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
     *
     * @return DataEntryFormInterface
     */
    public function getRightsHtmlDataEntryForm(string $name = 'roles_id[]'): DataEntryFormInterface;
}
