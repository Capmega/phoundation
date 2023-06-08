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
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * @param string|int|null $ssh_accounts_id
     * @return static
     */
    public function setSshAccountsId(string|int|null $ssh_accounts_id): static
    {
        if ($ssh_accounts_id and !is_natural($ssh_accounts_id)) {
            throw new OutOfBoundsException(tr('Specified ssh_accounts_id ":id" is not a natural number', [
                ':id' => $ssh_accounts_id
            ]));
        }

        return $this->setDataValue('ssh_accounts_id', get_null(isset_get_typed('integer', $ssh_accounts_id)));
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
     * Sets the ssh_accounts_id for this user
     *
     * @param SshAccount|string|int|null $ssh_account
     * @return static
     */
    public function setSshAccount(SshAccount|string|int|null $ssh_account): static
    {
        if ($ssh_account) {
            if (!is_numeric($ssh_account)) {
                $ssh_account = SshAccount::get($ssh_account);
            }

            if (is_object($ssh_account)) {
                $ssh_account = $ssh_account->getId();
            }
        }

        return $this->setSshAccountsId(get_null($ssh_account));
    }
}