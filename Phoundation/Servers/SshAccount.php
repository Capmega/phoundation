<?php

declare(strict_types=1);

namespace Phoundation\Servers;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataEntryFieldDefinitions;
use Phoundation\Data\DataEntry\Interfaces\DataEntryFieldDefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryUsername;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Filesystem\Traits\DataRestrictions;

/**
 * SshAccount class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Servers
 */
class SshAccount extends DataEntry
{
    use DataRestrictions;
    use DataEntryNameDescription;
    use DataEntryUsername;

    /**
     * User class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name = 'SSH account';

        parent::__construct($identifier);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'ssh_accounts';
    }


    /**
     * Returns the ssh_key for this object
     *
     * @return string|null
     */
    public function getSshKey(): ?string
    {
        return $this->getDataValue('string', 'ssh_key');
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
     * Sets the available data keys for this entry
     *
     * @return DataEntryFieldDefinitionsInterface
     */
    protected static function setFieldDefinitions(): DataEntryFieldDefinitionsInterface
    {
        return DataEntryFieldDefinitions::new(static::getTable());

        return [
            'name' => [
                'required'   => true,
                'complete'   => true,
                'cli'        => '-n,--name NAME',
                'size'       => 6,
                'maxlength'  => 64,
                'label'      => tr('Name'),
                'help_group' => tr('Identification'),
                'help'       => tr('The name for this account'),
            ],
            'seo_name' => [
                'visible'  => false,
                'readonly' => false,
            ],
            'username' => [
                'required'   => true,
                'complete'   => true,
                'cli'        => '-u,--username NAME',
                'size'       => 6,
                'maxlength'  => 64,
                'label'      => tr('Username'),
                'help_group' => tr(''),
                'help'       => tr('The username on the server for this account'),
            ],
            'description' => [
                'element'    => 'text',
                'complete'   => true,
                'cli'        => '-d,--description DESCRIPTION',
                'size'       => 12,
                'maxlength'  => 65_535,
                'label'      => tr('Description'),
                'help_group' => tr(''),
                'help'       => tr('The description for this account'),
            ],
            'ssh_key' => [
                'required'   => true,
                'complete'   => true,
                'cli'        => '-i,--ssh-key-file FILE',
                'element'    => 'text',
                'size'       => 12,
                'maxlength'  => 65_535,
                'label'      => tr('SSH Key'),
                'help_group' => tr(''),
                'help'       => tr('The SSH private key associated with this username'),
            ],
       ];

//        $data = $validator
//            ->select('name', true)->hasMaxCharacters(64)->isName()
//            ->select('username', true)->hasMaxCharacters(64)->isVariable()
//            ->select('ssh_key', true)->xor('ssh_key_file')->hasMaxCharacters(255)->isFile()
//            ->select('ssh_key_file', true)->xor('ssh_key')->hasMaxCharacters(65_535)->matchesRegex('-----BEGIN .+? PRIVATE KEY-----.+?-----END .+? PRIVATE KEY-----')
//            ->select('description', true)->isOptional()->hasMaxCharacters(65_535)->isDescription()
//            ->noArgumentsLeft()
//            ->validate();
//
//        // Ensure the hostname doesn't exist yet as it is a unique identifier
//        if ($data['name']) {
//            Server::notExists($data['name'], $this->getId(), true);
//        }
//
//        return $data;
    }
}