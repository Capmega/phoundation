<?php

/**
 * Trait TraitDataEntrySshAccount
 *
 * This trait contains methods for DataEntry objects that require an SSH account
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Servers
 */


declare(strict_types=1);

namespace Phoundation\Servers\Traits;

use Phoundation\Servers\Interfaces\SshAccountInterface;
use Phoundation\Servers\SshAccount;


trait TraitDataEntrySshAccount
{
    /**
     * @var SshAccountInterface|null
     */
    protected ?SshAccountInterface $ssh_account = null;


    /**
     * Returns the ssh_accounts_id for this object
     *
     * @return int|null
     */
    public function getSshAccountsId(): ?int
    {
        return $this->getTypesafe('int', 'ssh_accounts_id');
    }


    /**
     * Sets the ssh_accounts_id for this object
     *
     * @param int|null $ssh_accounts_id
     *
     * @return static
     */
    public function setSshAccountsId(?int $ssh_accounts_id): static
    {
        if ($ssh_accounts_id) {
            $this->ssh_account = new SshAccount($ssh_accounts_id);

        } else {
            $this->ssh_account = null;
        }

        return $this->set($ssh_accounts_id, 'ssh_accounts_id');
    }


    /**
     * Returns the ssh_accounts_id for this user
     *
     * @return SshAccount|null
     */
    public function getSshAccount(): ?SshAccountInterface
    {
        return $this->ssh_account;
    }


    /**
     * Sets the ssh_accounts_name for this user
     *
     * @param SshAccountInterface|null $account
     *
     * @return static
     */
    public function setSshAccount(SshAccountInterface|null $account): static
    {
        $this->ssh_account = $account;

        return $this->set($account?->getId(), 'ssh_accounts_id');
    }


    /**
     * Returns the ssh_accounts_name for this user
     *
     * @return string|null
     */
    public function getSshAccountsName(): ?string
    {
        return $this->getTypesafe('string', 'ssh_accounts_name');
    }


    /**
     * Sets the ssh_accounts_name for this object
     *
     * @param string|null $ssh_accounts_name
     *
     * @return static
     */
    public function setSshAccountsName(?string $ssh_accounts_name): static
    {
        if ($ssh_accounts_name) {
            $this->ssh_account = SshAccount::load($ssh_accounts_name);

            return $this->set($this->ssh_account->getId(), 'ssh_accounts_id');

        }
        $this->ssh_account = null;

        return $this->set(null, 'ssh_accounts_id');
    }
}
