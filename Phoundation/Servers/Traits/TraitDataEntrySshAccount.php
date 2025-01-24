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
     * SshAccount object cache
     *
     * @var SshAccountInterface|null $o_ssh_account
     */
    protected ?SshAccountInterface $o_ssh_account;


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'name';
    }


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
        $this->o_ssh_account = null;
        return $this->set($ssh_accounts_id, 'ssh_accounts_id');
    }


    /**
     * Returns the SshAccount for this object
     *
     * @return SshAccountInterface|null
     */
    public function getSshAccountObject(): ?SshAccountInterface
    {
        if (empty($this->o_ssh_account)) {
            $this->o_ssh_account = SshAccount::new($this->getTypesafe('int', 'ssh_accounts_id'))->loadOrNull();
        }

        return $this->o_ssh_account;
    }


    /**
     * Sets the SshAccount for this object
     *
     * @param SshAccountInterface|null $o_ssh_account
     * @return TraitDataEntrySshAccount
     */
    public function setSshAccountObject(?SshAccountInterface $o_ssh_account): static
    {
        $this->setSshAccountsId($o_ssh_account?->getId())
             ->o_ssh_account = $o_ssh_account;

        return $this;
    }


    /**
     * Returns the ssh_accounts_name for this object
     *
     * @return string|null
     */
    public function getSshAccountsName(): ?string
    {
        return $this->getSshAccountObject()->getName();
    }


    /**
     * Returns the ssh_accounts_name for this object
     *
     * @param string|null $ssh_accounts_name
     *
     * @return static
     */
    public function setSshAccountsName(?string $ssh_accounts_name): static
    {
        return $this->setSshAccountObject(SshAccount::new(['name' => $ssh_accounts_name])->loadOrNull());
    }
}
