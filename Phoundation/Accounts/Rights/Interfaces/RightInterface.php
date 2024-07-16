<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Rights\Interfaces;

use Phoundation\Accounts\Roles\Interfaces\RolesInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;

interface RightInterface extends DataEntryInterface
{
    /**
     * Returns the roles that give this right
     *
     * @return RolesInterface
     */
    public function getRoles(): RolesInterface;
}
