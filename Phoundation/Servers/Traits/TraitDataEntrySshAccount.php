<?php

/**
 * Trait TraitDataEntrySshAccount
 *
 * This trait contains methods for DataEntry objects that require an SSH account
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Servers\Traits;

use Phoundation\Servers\Interfaces\SshAccountInterface;
use Phoundation\Servers\SshAccount;


trait TraitDataEntrySshAccount
{
    /**
     * Setup virtual configuration for SshAccounts
     *
     * @return static
     */
    protected function addVirtualConfigurationSshAccounts(): static
    {
        return $this->addVirtualConfiguration('ssh_accounts', SshAccount::class, [
            'id',
            'name'
        ]);
    }


    /**
     * Returns the ssh_accounts_id column
     *
     * @return int|null
     */
    public function getSshAccountsId(): ?int
    {
        return $this->getVirtualData('ssh_accounts', 'int', 'id');
    }


    /**
     * Sets the ssh_accounts_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setSshAccountsId(?int $id): static
    {
        return $this->setVirtualData('ssh_accounts', $id, 'id');
    }


    /**
     * Returns the ssh_accounts_name column
     *
     * @return string|null
     */
    public function getSshAccountsName(): ?string
    {
        return $this->getVirtualData('ssh_accounts', 'string', 'name');
    }


    /**
     * Sets the ssh_accounts_name column
     *
     * @param string|null $name
     * @return static
     */
    public function setSshAccountsName(?string $name): static
    {
        return $this->setVirtualData('ssh_accounts', $name, 'name');
    }


    /**
     * Returns the SshAccount Object
     *
     * @return SshAccountInterface|null
     */
    public function getSshAccountObject(): ?SshAccountInterface
    {
        return $this->getVirtualObject('ssh_accounts');
    }


    /**
     * Returns the ssh_accounts_id for this user
     *
     * @param SshAccountInterface|null $o_object
     *
     * @return static
     */
    public function setSshAccountObject(?SshAccountInterface $o_object): static
    {
        return $this->setVirtualObject('ssh_accounts', $o_object);
    }
}
