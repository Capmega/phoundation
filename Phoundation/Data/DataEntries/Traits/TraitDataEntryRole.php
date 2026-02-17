<?php

/**
 * Trait TraitDataEntryRole
 *
 * This trait contains methods for DataEntry objects that require an role
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Accounts\Roles\Interfaces\RoleInterface;
use Phoundation\Accounts\Roles\Role;


trait TraitDataEntryRole
{
    /**
     * Setup virtual configuration for Roles
     *
     * @return static
     */
    protected function addVirtualConfigurationRoles(): static
    {
        return $this->addVirtualConfiguration('roles', Role::class, [
            'id',
            'name'
        ]);
    }


    /**
     * Returns the roles_id column
     *
     * @return int|null
     */
    public function getRolesId(): ?int
    {
        return $this->getVirtualData('roles', 'int', 'id');
    }


    /**
     * Sets the roles_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setRolesId(?int $id): static
    {
        return $this->setVirtualData('roles', $id, 'id');
    }


    /**
     * Returns the roles_name column
     *
     * @return string|null
     */
    public function getRolesName(): ?string
    {
        return $this->getVirtualData('roles', 'string', 'name');
    }


    /**
     * Sets the roles_name column
     *
     * @param string|null $name
     * @return static
     */
    public function setRolesName(?string $name): static
    {
        return $this->setVirtualData('roles', $name, 'name');
    }


    /**
     * Returns the Role Object
     *
     * @return RoleInterface|null
     */
    public function getRoleObject(): ?RoleInterface
    {
        return $this->getVirtualObject('roles');
    }


    /**
     * Returns the roles_id for this user
     *
     * @param RoleInterface|null $_object
     *
     * @return static
     */
    public function setRoleObject(?RoleInterface $_object): static
    {
        return $this->setVirtualObject('roles', $_object);
    }
}
