<?php

namespace Phoundation\Filesystem\Mounts;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryOptions;
use Phoundation\Data\DataEntry\Traits\DataEntrySourcePath;
use Phoundation\Data\DataEntry\Traits\DataEntryTargetString;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Filesystem\Interfaces\MountInterface;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Traits\DataRestrictions;
use Phoundation\Os\Processes\Commands\UnMount;
use Phoundation\Utils\Config;
use Phoundation\Web\Html\Enums\InputType;
use Phoundation\Web\Html\Enums\InputTypeExtended;


/**
 * Class Mount
 *
 *
 * @note On Ubuntu requires packages nfs-utils cifs-utils psmisc
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */
class Mount extends DataEntry implements MountInterface
{
    use DataEntryNameDescription;
    use DataRestrictions;
    use DataEntryOptions;


    /**
     * Mount class constructor
     *
     * @param int|string|DataEntryInterface|null $identifier
     * @param string|null $column
     * @param bool|null $meta_enabled
     * @param RestrictionsInterface|null $restrictions
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, ?string $column = null, ?bool $meta_enabled = null, ?RestrictionsInterface $restrictions = null)
    {
        parent::__construct($identifier, $column, $meta_enabled);
        $this->restrictions = $this->ensureRestrictions($restrictions);
    }


    /**
     * @inheritDoc
     */
    public static function getTable(): string
    {
        return 'filesystem_mounts';
    }


    /**
     * @inheritDoc
     */
    public static function getDataEntryName(): string
    {
        return tr('Mount');
    }


    /**
     * @inheritDoc
     */
    public static function getUniqueField(): ?string
    {
        return 'name';
    }


    /**
     * @inheritDoc
     */
    public static function get(DataEntryInterface|string|int|null $identifier, ?string $column = null, bool $meta_enabled = false, bool $force = false): ?static
    {
        try {
            return parent::get($identifier, $column, $meta_enabled, $force);

        } catch (DataEntryNotExistsException $e) {
            // Mount was not found in the database. Get it from configuration instead but that DOES require the name
            // column
            switch ($column) {
                case 'name':
                    $mount = Config::getArray('filesystem.mounts.' . $identifier);
                    return static::fromSource($mount, $meta_enabled);

                case 'source_path':
                    // This is a mount that SHOULD already exist on the system
                    $mount = Mounts::getMountSources($identifier);
                    return static::fromSource($mount, $meta_enabled);

                case 'target_path':
                    // This is a mount that SHOULD already exist on the system
                    $mount = Mounts::getMountTargets($identifier);
                    return static::fromSource($mount, $meta_enabled);
            }

            throw $e;
        }
    }


    /**
     * Returns the filesystem for this object
     *
     * @return string|null
     */
    public function getFilesystem(): ?string
    {
        return $this->getSourceFieldValue('string', 'filesystem');
    }


    /**
     * Sets the filesystem for this object
     *
     * @param string|null $filesystem
     * @return static
     */
    public function setFilesystem(?string $filesystem): static
    {
        return $this->setSourceValue('filesystem', $filesystem);
    }


    /**
     * Returns the auto_mount for this object
     *
     * @return bool|null
     */
    public function getAutoMount(): ?bool
    {
        return $this->getSourceFieldValue('bool', 'auto_mount');
    }


    /**
     * Sets the auto_mount for this object
     *
     * @param int|bool|null $auto_mount
     * @return static
     */
    public function setAutoMount(int|bool|null $auto_mount): static
    {
        return $this->setSourceValue('auto_mount', (bool) $auto_mount);
    }


    /**
     * Returns the auto_unmount for this object
     *
     * @return bool|null
     */
    public function getAutoUnmount(): ?bool
    {
        return $this->getSourceFieldValue('bool', 'auto_unmount');
    }


    /**
     * Sets the auto_unmount for this object
     *
     * @param int|bool|null $auto_unmount
     * @return static
     */
    public function setAutoUnmount(int|bool|null $auto_unmount): static
    {
        return $this->setSourceValue('auto_unmount', (bool) $auto_unmount);
    }


    /**
     * Returns the source_path for this object
     *
     * @return string|null
     */
    public function getSourcePath(): ?string
    {
        return $this->getSourceFieldValue('string', 'source_path');
    }


    /**
     * Sets the source_path for this object
     *
     * @param string|null $source_path
     * @return static
     */
    public function setSourcePath(?string $source_path): static
    {
        return $this->setSourceValue('source_path', $source_path);
    }


    /**
     * Returns the $target_path for this object
     *
     * @return string|null
     */
    public function getTargetPath(): ?string
    {
        return $this->getSourceFieldValue('string', 'target_path');
    }


    /**
     * Sets the $target_path for this object
     *
     * @param string|null $target_path
     * @return static
     */
    public function setTargetPath(?string $target_path): static
    {
        return $this->setSourceValue('target_path', $target_path);
    }


    /**
     * Mounts this mount point
     *
     * @return $this
     */
    public function mount(): static
    {
        \Phoundation\Os\Processes\Commands\Mount::new($this->restrictions)
            ->mount($this->getSource(), $this->getTargetString());

        return $this;
    }


    /**
     * Unmounts this mount point
     *
     * @return $this
     */
    public function unmount(): static
    {
        UnMount::new($this->restrictions)
            ->unmount($this->getTargetString());

        return $this;
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
                ->setMaxlength(64)
                ->setLabel(tr('Name'))
                ->setHelpText(tr('The unique identifier name for this mount'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isUnique(tr('value ":name" already exists', [':name' => $validator->getSelectedValue()]));
                }))
            ->addDefinition(DefinitionFactory::getSeoName($this))
            ->addDefinition(Definition::new($this, 'source_path')
                ->setInputType(InputTypeExtended::name)
                ->setSize(4)
                ->setMaxlength(255)
                ->setLabel(tr('Source'))
                ->setHelpText(tr('The source file for this mount'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isFile();
                }))
            ->addDefinition(Definition::new($this, 'target_path')
                ->setInputType(InputTypeExtended::name)
                ->setSize(4)
                ->setMaxlength(255)
                ->setLabel(tr('Target'))
                ->setHelpText(tr('The target file for this mount'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isFile();
                }))
            ->addDefinition(Definition::new($this, 'filesystem')
                ->setSize(4)
                ->setSource([
                    ''             => tr('Auto detect'),
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
                    'iso9660:1999' => tr('ISO 9660:1999')
                ])
                ->setLabel(tr('Filesystem'))
                ->setHelpText(tr('The filesystem with which to mount this source')))
            ->addDefinition(Definition::new($this, 'options')
                ->setSize(8)
                ->setDefault('defaults')
                ->setMaxlength(508)
                ->setLabel(tr('Options'))
                ->setHelpText(tr('The options for this mount'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isFile();
                }))
            ->addDefinition(DefinitionFactory::getBoolean($this, 'auto_mount')
                ->setSize(2)
                ->setHelpText(tr('If checked, this mount will automatically be mounted by the process using a path in this mount'))
                ->setLabel(tr('Auto mount')))
            ->addDefinition(DefinitionFactory::getBoolean($this, 'auto_unmount')
                ->setSize(2)
                ->setHelpText(tr('If checked, this mount will automatically be unmounted after use when the process using a path in this mount terminates'))
                ->setLabel(tr('Auto unmount')))
            ->addDefinition(DefinitionFactory::getDescription($this)
                ->setHelpText(tr('The description for this mount')));
    }
}
