<?php

declare(strict_types=1);

namespace Phoundation\Servers\Traits;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Servers\SshAccount;


/**
 * Trait DataEntrySshAccount
 *
 * This trait contains methods for DataEntry objects that require an SSH account
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Servers
 */
trait DataEntrySshAccount
{
    /**
     * Returns the ssh_accounts_id for this object
     *
     * @return int|null
     */
    public function getSshAccountsId(): ?int
    {
        return $this->getDataValue('int', 'ssh_accounts_id');
    }


    /**
     * Sets the ssh_accounts_id for this object
     *
     * @param int|null $ssh_accounts_id
     * @return static
     */
    public function setSshAccountsId(?int $ssh_accounts_id): static
    {
        return $this->setSourceValue('ssh_accounts_id', $ssh_accounts_id);
    }


    /**
     * Returns the ssh_accounts_id for this user
     *
     * @return SshAccount|null
     */
    public function getSshAccount(): ?SshAccount
    {
        $ssh_accounts_id = $this->getDataValue('int', 'ssh_accounts_id');

        if ($ssh_accounts_id) {
            return new SshAccount($ssh_accounts_id);
        }

        return null;
    }


    /**
     * Returns the ssh_accounts_name for this user
     *
     * @return string|null
     */
    public function getSshAccountsName(): ?string
    {
        return $this->getDataValue('string', 'ssh_accounts_name');
    }


    /**
     * Sets the ssh_accounts_name for this user
     *
     * @param string|null $ssh_accounts_name
     * @return static
     */
    public function setSshAccount(?string $ssh_accounts_name): static
    {
        return $this->setSourceValue('ssh_accounts_name', $ssh_accounts_name);
    }
}