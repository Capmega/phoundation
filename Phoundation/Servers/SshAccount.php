<?php

namespace Phoundation\Servers;

use Phoundation\Accounts\Users\Users;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryUsername;
use Phoundation\Filesystem\Traits\Restrictions;


/**
 * SshAccount class
 *
 * This class manages the localhost server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Servers
 */
class SshAccount extends DataEntry
{
    use Restrictions;
    use DataEntryNameDescription;
    use DataEntryUsername;


    /**
     * User class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        static::$entry_name  = 'SSH account';
        $this->table         = 'ssh_accounts';
        $this->unique_column = 'seo_name';

        parent::__construct($identifier);
    }



    /**
     * Returns the ssh_key for this object
     *
     * @return string|null
     */
    public function getSshKey(): ?string
    {
        return $this->getDataValue('ssh_key');
    }



    /**
     * Sets the ssh_key for this object
     *
     * @param string|null $ssh_key
     * @return static
     */
    public function setSshKey(?string $ssh_key): static
    {
        return $this->setDataValue('ssh_key', $ssh_key);
    }



    /**
     * @inheritDoc
     */
    protected function setKeys(): void
    {
        $this->keys = [
            'name' => [
                'maxlength' => 64,
                'label'     => tr('Name')
            ],
            'seo_name' => [
                'visible' => false,
            ],
            'username' => [
                'maxlength' => 64,
                'label'     => tr('Username')
            ],
            'description' => [
                'element'   => 'text',
                'maxlength' => 2047,
                'label'     => tr('Description')
            ],
            'ssh_key' => [
                'element'   => 'text',
                'maxlength' => 65535,
                'label'     => tr('SSH Key')
            ],
       ];

        $this->keys_display = [
            'name'        => 6,
            'username'    => 6,
            'description' => 12,
            'ssh_key'     => 12,
        ] ;

        parent::setKeys();
    }
}