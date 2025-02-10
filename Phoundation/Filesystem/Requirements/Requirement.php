<?php

/**
 * Class Requirement
 *
 * This class can check if the specified path conforms to specific requirements like "must be a directory" or "must be
 * filesystem type X"
 *
 * @note      On Ubuntu requires packages nfs-utils cifs-utils psmisc
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Requirements;

use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryNameDescription;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryPathObject;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Web\Html\Enums\EnumInputType;


class Requirement extends DataEntry
{
    use TraitDataEntryNameDescription;
    use TraitDataEntryPathObject;

    /**
     * @inheritDoc
     */
    public static function getTable(): ?string
    {
        return 'filesystem_requirements';
    }


    /**
     * @inheritDoc
     */
    public static function getEntryName(): string
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
     *
     * @return static
     */
    public function check(string $path): static
    {
        if ($this->getPathObject()) {

        }
    }


    /**
     * @inheritDoc
     */
    protected function setDefinitions(DefinitionsInterface $definitions): static
    {
        $definitions->add(DefinitionFactory::newName()
                                           ->setInputType(EnumInputType::name)
                                           ->setSize(12)
                                           ->setMaxlength(128)
                                           ->setLabel(tr('Name'))
                                           ->setHelpText(tr('The unique identifier name for this requirement'))
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isUnique();
                                           }))
                    ->add(DefinitionFactory::newSeoName())
                    ->add(Definition::new('path')
                                    ->setInputType(EnumInputType::name)
                                    ->setSize(6)
                                    ->setMaxlength(255)
                                    ->setLabel(tr('Path'))
                                    ->setHelpText(tr('The path that these requirements apply to'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isFile();
                                    }))
                    ->add(Definition::new('filesystem')
                                    ->setInputType(EnumInputType::select)
                                    ->setDataSource([
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
                                        'iso9660:1999' => 'ISO 9660:1999',
                                    ])
                                    ->setSize(3)
                                    ->setMaxlength(16)
                                    ->setLabel(tr('Filesystem'))
                                    ->setHelpText(tr('The filesystem this should use')))
                    ->add(Definition::new('file_type')
                                    ->setInputType(EnumInputType::select)
                                    ->setDataSource([
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
                    ->add(DefinitionFactory::newDescription()
                                           ->setHelpText(tr('The description for this mount')));
    }
}
