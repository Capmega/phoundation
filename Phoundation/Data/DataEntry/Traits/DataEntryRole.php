<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Accounts\Roles\Interfaces\RoleInterface;
use Phoundation\Accounts\Roles\Role;


/**
 * Trait DataEntryRole
 *
 * This trait contains methods for DataEntry objects that require a role
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryRole
{
    /**
     * @var RoleInterface|null $role
     */
    protected ?RoleInterface $role;

    /**
     * Returns the roles_id for this object
     *
     * @return int|null
     */
    public function getRolesId(): ?int
    {
        return $this->getSourceColumnValue('int', 'roles_id');

    }


    /**
     * Sets the roles_id for this object
     *
     * @param int|null $roles_id
     * @return static
     */
    public function setRolesId(?int $roles_id): static
    {
        unset($this->role);
        return $this->setSourceValue('roles_id', $roles_id);
    }


    /**
     * Returns the RoleInterface object for this object
     *
     * @return RoleInterface|null
     */
    public function getRole(): ?RoleInterface
    {
        if (!isset($this->role)) {
            $this->role = Role::getOrNull($this->getRolesId());
        }

        return $this->role;
    }


    /**
     * Sets the RoleInterface object for this object
     *
     * @param RoleInterface|null $role
     * @return static
     */
    public function setRole(?RoleInterface $role): static
    {
        if ($role) {
            $this->role = $role;
            return $this->setSourceValue('roles_id', $role->getId());
        }

        return $this->setRolesId(null);
    }


    /**
     * Returns the role name for this object
     *
     * @return string|null
     */
    public function getRolesName(): ?string
    {
        return $this->getRole()?->getname();
    }


    /**
     * Sets the role name for this object
     *
     * @param string|null $name
     * @return static
     */
    public function setRolesName(?string $name): static
    {
        return $this->setRole(Role::get($name, 'name'));
    }
}
