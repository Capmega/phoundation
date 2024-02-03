<?php

namespace Phoundation\Filesystem\Requirements;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryPath;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Web\Html\Enums\InputType;
use Phoundation\Web\Html\Enums\InputTypeExtended;


/**
 * Class Requirement
 *
 *
 * @note On Ubuntu requires packages nfs-utils cifs-utils psmisc
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */
class Requirement extends DataEntry
{
    use DataEntryNameDescription;
    use DataEntryPath;


    /**
     * @inheritDoc
     */
    public static function getTable(): string
    {
        return 'filesystem_requirements';
    }


    /**
     * @inheritDoc
     */
    public static function getDataEntryName(): string
    {
        return tr('Path requirement');
    }


    /**
     * @inheritDoc
     */
    public static function getUniqueColumn(): ?string
    {
        return 'name';
    }


    /**
     * @param string $path
     * @return $this
     */
    public function check(string $path): static
    {
        if ($this->getPath()) {

        }
    }


    /**
     * @inheritDoc
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(DefinitionFactory::getName($this)
                ->setInputType(InputTypeExtended::name)
                ->setSize(12)
                ->setMaxlength(128)
                ->setLabel(tr('Name'))
                ->setHelpText(tr('The unique identifier name for this requirement'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isUnique(tr('value ":name" already exists', [':name' => $validator->getSelectedValue()]));
                }))
            ->addDefinition(DefinitionFactory::getSeoName($this))
            ->addDefinition(Definition::new($this, 'path')
                ->setInputType(InputTypeExtended::name)
                ->setSize(6)
                ->setMaxlength(255)
                ->setLabel(tr('Path'))
                ->setHelpText(tr('The path that these requirements apply to'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isFile();
                }))
            ->addDefinition(Definition::new($this, 'filesystem')
                ->setInputType(InputType::select)
                ->setSource([
                    ''             => tr('No requirements'),
                    'ext2'         => tr('EXT2'),
                    'ext3'         => tr('EXT3'),
                    'ext4'         => tr('EXT4'),
                    'reiserfs4'    => tr('ReiserFS 4'),
                    'xfs'          => tr('XFS'),
                    'btrfs'        => tr('BTRFS'),
                    'zfs'          => tr('ZFS'),
                    'nfs'          => tr('NFS'),
                    'smb'          => tr('SMB'),
                    'cifs'         => tr('CIFS'),
                    'ipfs'         => tr('IPFS'),
                    'sysfs'        => tr('SysFS'),
                    'tmpfs'        => tr('TmpFS'),
                    'dev'          => tr('DevFS'),
                    'proc'         => tr('Proc'),
                    'loop'         => tr('Loop device'),
                    'luks'         => tr('LUKS'),
                    'hfs'          => tr('HFS'),
                    'vfat'         => tr('vfat'),
                    'ntfs'         => tr('NTFS'),
                    'iso9660:1999' => 'ISO 9660:1999'
                ])
                ->setSize(3)
                ->setMaxlength(16)
                ->setLabel(tr('Filesystem'))
                ->setHelpText(tr('The filesystem this should use')))
            ->addDefinition(Definition::new($this, 'file_type')
                ->setInputType(InputType::select)
                ->setSource([
                    ''                 => tr('No requirements'),
                    'directory'        => tr('Directory'),
                    'fifo device'      => tr('Fifo device'),
                    'character device' => tr('Character device'),
                    'block device'     => tr('Block device'),
                    'reg file'         => tr('Reg file'),
                    'socket file'      => tr('Socket file'),
                ])
                ->setSize(3)
                ->setMaxlength(16)
                ->setLabel(tr('File type'))
                ->setHelpText(tr('The type of file this should be')))
           ->addDefinition(DefinitionFactory::getDescription($this)
                ->setHelpText(tr('The description for this mount')));
    }
}
