<?php

/**
 * Class FsMount
 *
 *
 * @note      On Ubuntu requires packages nfs-utils cifs-utils psmisc
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Mounts;

use Phoundation\Core\Config\Exception\ConfigPathDoesNotExistsException;
use Phoundation\Core\Core;
use Phoundation\Core\Hooks\Hook;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryNameDescription;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryOptions;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryTimeout;
use Phoundation\Data\Traits\TraitDataRestrictions;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Exception\NotExistsException;
use Phoundation\Filesystem\Interfaces\PhoMountInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Filesystem\Mounts\Exception\MountsException;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoPath;
use Phoundation\Os\Processes\Commands\Mount;
use Phoundation\Os\Processes\Commands\UnMount;
use Phoundation\Web\Html\Enums\EnumInputType;

class PhoMount extends DataEntry implements PhoMountInterface
{
    use TraitDataEntryNameDescription;
    use TraitDataRestrictions;
    use TraitDataEntryOptions;
    use TraitDataEntryTimeout;

    /**
     * FsMount class constructor
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     * @param PhoRestrictionsInterface|null             $restrictions
     */
    public function __construct(IdentifierInterface|array|string|int|false|null $identifier = null, ?PhoRestrictionsInterface $restrictions = null)
    {
        parent::__construct($identifier);

        $this->restrictions = $this->ensureRestrictions($restrictions);
    }


    /**
     * @inheritDoc
     */
    public static function getTable(): ?string
    {
        return 'filesystem_mounts';
    }


    /**
     * @inheritDoc
     */
    public static function getEntryName(): string
    {
        return tr('FsMount');
    }


    /**
     * @inheritDoc
     */
    public static function getUniqueColumn(): ?string
    {
        return 'name';
    }


    /**
     * Returns the configuration path for this DataEntry object, if it has one, or NULL instead
     *
     * @return string|null
     */
    public static function getConfigurationPath(): ?string
    {
        return 'filesystem.mounts';
    }


    /**
     * Returns the mount object for the path that is mounted as close as possible to the specified path
     *
     * @param PhoPath|string           $path
     * @param PhoRestrictionsInterface $restrictions
     *
     * @return static|null
     */
    public static function getForPath(PhoPath|string $path, PhoRestrictionsInterface $restrictions): ?static
    {
        if (sql()->getDatabase()) {
            $paths = sql()->query('SELECT   `id`, `target_path` 
                                         FROM     `filesystem_mounts` 
                                         ORDER BY LENGTH(`target_path`)');

            while ($mount_path = $paths->fetch()) {
                $mount_path['target_path'] = PhoPath::absolutePath($mount_path['target_path'], must_exist: false);

                if (str_starts_with($path, $mount_path['target_path'])) {
                    return static::new($mount_path['id'], 'id')->setRestrictions($restrictions);
                }
            }
        }

        return null;
    }


    /**
     * @inheritDoc
     */
    public function load(IdentifierInterface|array|string|int|null $identifier = null): static
    {
        $column = static::determineColumn($identifier);

        try {
            return parent::load($identifier);

        } catch (DataEntryNotExistsException $e) {
            // FsMount was not found in the database. Get it from configuration instead but that DOES require the name
            // column
            switch ($column) {
                case 'name':
                    try {
                        $mount = config()->getArray('filesystem.mounts.' . str_replace(' ', '-', $this->identifier['name']));

                        return static::newFromSource($mount)->setMetaEnabled($this->meta_enabled)
                                     ->setIgnoreDeleted($this>$this->ignore_deleted);

                    } catch (ConfigPathDoesNotExistsException) {
                        // Yeah, this identifier does not exist
                        throw $e;
                    }

                    break;

                case 'source_path':
                    // This is a mount that SHOULD already exist on the system
                    $mount = PhoMounts::getMountSources(new PhoDirectory($this->identifier['source_path']));

                    return static::new($mount)->setMetaEnabled($this>$this->meta_enabled)
                                              ->setIgnoreDeleted($this>$this->ignore_deleted);

                case 'target_path':
                    // This is a mount that SHOULD already exist on the system
                    $mount = PhoMounts::getMountTargets(new PhoDirectory($this->identifier['target_path']));

                    return static::new($mount)->setMetaEnabled($this>$this->meta_enabled)
                                              ->setIgnoreDeleted($this>$this->ignore_deleted);
            }

            throw $e;
        }
    }


    /**
     * Sets the filesystem for this object
     *
     * @param string|null $filesystem
     *
     * @return static
     */
    public function setFilesystem(?string $filesystem): static
    {
        return $this->set($filesystem, 'filesystem');
    }


    /**
     * Sets the auto_mount for this object
     *
     * @param int|bool|null $auto_mount
     *
     * @return static
     */
    public function setAutoMount(int|bool|null $auto_mount): static
    {
        return $this->set((bool) $auto_mount, 'auto_mount');
    }


    /**
     * Sets the auto_unmount for this object
     *
     * @param int|bool|null $auto_unmount
     *
     * @return static
     */
    public function setAutoUnmount(int|bool|null $auto_unmount): static
    {
        return $this->set((bool) $auto_unmount, 'auto_unmount');
    }


    /**
     * Returns the absolute $source_path for this object
     *
     * @return string|null
     */
    public function getAbsoluteSourcePath(): ?string
    {
        return PhoPath::absolutePath($this->getSourcePath(), must_exist: false);
    }


    /**
     * Returns the source_path for this object
     *
     * @return string|null
     */
    public function getSourcePath(): ?string
    {
        return $this->getTypesafe('string', 'source_path');
    }


    /**
     * Sets the source_path for this object
     *
     * @param string|null $source_path
     *
     * @return static
     */
    public function setSourcePath(?string $source_path): static
    {
        return $this->set($source_path, 'source_path');
    }


    /**
     * Sets the $target_path for this object
     *
     * @param string|null $target_path
     *
     * @return static
     */
    public function setTargetPath(?string $target_path): static
    {
        return $this->set($target_path, 'target_path');
    }


    /**
     * Will automatically mount this target if it isn't mounted yet and configured for auto mount
     *
     * Returns null if the target path is mounted correctly
     *
     * Returns false if the target path is not mounted but not configured for auto mount
     *
     * Returns true if the target path is not mounted and was automatically mounted
     *
     * Throws an exception if the target path is mounted on a different path
     *
     * @return bool|null
     */
    public function autoMount(): ?bool
    {
        if (config()->getBoolean('filesystem.automounts.enabled', false)) {
            return false;
        }
        // This path is inside a mount
        if ($this->isMounted()) {
            if ($this->getCurrentSource() !== $this->load('source_path')) {
                throw new MountsException(tr('The target path ":target" should be mounted on ":source" but is currently mounted on ":current"', [
                    ':target'  => $this->getTargetPath(),
                    ':source'  => $this->getSourcePath(),
                    ':current' => $this->getCurrentSource(),
                ]));
            }

            // The Path is mounted already on the correct location, all is fine
            return null;
        }

        // The target path mount part isn't mounted!
        if (!$this->getAutoMount()) {
            return false;
        }

        if ($this->getAutoUnmount()) {
            // This mount must be removed once the process finishes!
            Core::addShutdownCallback('unmount-' . $this->getSeoName(), function () {
                Log::action(ts('Automatically unmounting ":source" from ":target"', [
                    ':source' => $this->getSourcePath(),
                    ':target' => $this->getTargetPath(),
                ]));
                $this->unmount();
            });
        }

        // FsMount and try again!
        Log::action(ts('Automatically mounting ":source" to ":target"', [
            ':source' => $this->getSourcePath(),
            ':target' => $this->getTargetPath(),
        ]));

        Hook::new('phoundation')
            ->execute('file-system/auto-mount', [
                'source'      => $this->getSourcePath(),
                'target'      => $this->getTargetPath(),
                'file-system' => $this->getFilesystem(),
                'options'     => $this->getOptions(),
                'timeout'     => $this->getTimeout(),
            ]);

        $this->mount();
        return true;
    }


    /**
     * Returns true if the target path is mounted at the correct path
     *
     * Returns false if the target path is not mounted
     *
     * Throws an exception if the target path is mounted at a different path
     *
     * @return bool
     */
    public function isMounted(): bool
    {
        try {
            foreach (PhoMounts::getMountSources($this->getAbsoluteTargetPath(), $this->restrictions)
                              ->getAllRowsSingleColumn('source_path') as $source_path) {
                if ($this->getSourcePath() !== $source_path) {
                    throw new MountsException(tr('The target path ":target" should be mounted from ":source" but is mounted from ":current"', [
                        ':source'  => $this->getTargetPath(),
                        ':target'  => $this->getSourcePath(),
                        ':current' => $source_path,
                    ]));
                }
            }

            return true;

        } catch (NotExistsException) {
            return false;
        }
    }


    /**
     * Returns the absolute $target_path for this object
     *
     * @return string|null
     */
    public function getAbsoluteTargetPath(): ?string
    {
        return PhoPath::absolutePath($this->getTargetPath(), must_exist: false);
    }


    /**
     * Returns the $target_path for this object
     *
     * @return string|null
     */
    public function getTargetPath(): ?string
    {
        return $this->getTypesafe('string', 'target_path');
    }


    /**
     * Returns the current source for the mount on the target_path
     *
     * Returns null if the target path is not mounted
     *
     * @return string|null
     */
    public function getCurrentSource(): string|null
    {
        try {
            $mounts = PhoMounts::getMountSources($this->getAbsoluteTargetPath(), $this->restrictions)
                               ->getAllRowsSingleColumn('source_path');

            return end($mounts);

        } catch (NotExistsException) {
            return null;
        }
    }


    /**
     * Returns the auto_mount for this object
     *
     * @return bool|null
     */
    public function getAutoMount(): ?bool
    {
        return $this->getTypesafe('bool', 'auto_mount');
    }


    /**
     * Returns the auto_unmount for this object
     *
     * @return bool|null
     */
    public function getAutoUnmount(): ?bool
    {
        return $this->getTypesafe('bool', 'auto_unmount');
    }


    /**
     * Unmounts this mount point
     *
     * @return static
     */
    public function unmount(): static
    {
        UnMount::new($this->restrictions)
               ->unmount($this->getAbsoluteTargetPath());

        return $this;
    }


    /**
     * Returns the filesystem for this object
     *
     * @return string|null
     */
    public function getFilesystem(): ?string
    {
        return $this->getTypesafe('string', 'filesystem');
    }


    /**
     * FsMounts this mount point
     *
     * @return static
     */
    public function mount(): static
    {
        Mount::new($this->restrictions)->mount(
            $this->getSourcePath(),
            $this->getAbsoluteTargetPath(),
            $this->getFilesystem(),
            $this->getOptions(),
            $this->getTimeout()
        );

        return $this;
    }


    /**
     * @inheritDoc
     */
    protected function setDefinitions(DefinitionsInterface $definitions): static
    {
        $definitions->add(DefinitionFactory::newName()
                                           ->setInputType(EnumInputType::name)
                                           ->setSize(12)
                                           ->setMaxlength(64)
                                           ->setLabel(tr('Name'))
                                           ->setHelpText(tr('The unique identifier name for this mount'))
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isUnique();
                                           }))

                    ->add(DefinitionFactory::newSeoName())

                    ->add(Definition::new('source_path')
                                    ->setInputType(EnumInputType::name)
                                    ->setSize(4)
                                    ->setMaxlength(255)
                                    ->setLabel(tr('Source'))
                                    ->setHelpText(tr('The source file for this mount'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isFile([PhoDirectory::newFilesystemRootObject(), PhoDirectory::newDomainObject('*')]);
                                    }))

                    ->add(Definition::new('target_path')
                                    ->setInputType(EnumInputType::name)
                                    ->setSize(4)
                                    ->setMaxlength(255)
                                    ->setLabel(tr('Target'))
                                    ->setHelpText(tr('The target file for this mount'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isDirectory(PhoDirectory::newFilesystemRootObject());
                                    }))

                    ->add(Definition::new('filesystem')
                                    ->setOptional(true)
                                    ->setSize(4)
                                    ->setDataSource([
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
                                        'iso9660:1999' => 'ISO 9660:1999',
                                    ])
                                    ->setLabel(tr('Filesystem'))
                                    ->setHelpText(tr('The filesystem with which to mount this source')))

                    ->add(Definition::new('options')
                                    ->setOptional(true)
                                    ->setSize(6)
                                    ->setDefault('defaults')
                                    ->setMaxlength(508)
                                    ->setLabel(tr('Options'))
                                    ->setHelpText(tr('The options for this mount')))

                    ->add(DefinitionFactory::newBoolean('auto_mount')
                                           ->setSize(2)
                                           ->setHelpText(tr('If checked, this mount will automatically be mounted by the process using a path in this mount'))
                                           ->setLabel(tr('Auto mount')))

                    ->add(DefinitionFactory::newBoolean('auto_unmount')
                                           ->setSize(2)
                                           ->setHelpText(tr('If checked, this mount will automatically be unmounted after use when the process using a path in this mount terminates'))
                                           ->setLabel(tr('Auto unmount')))

                    ->add(DefinitionFactory::newNumber('timeout')
                                           ->setOptional(true, 3)
                                           ->setSize(2)
                                           ->setHelpText(tr('If specified, the mount attempt for this filesystem will be aborted after this number of seconds'))
                                           ->setLabel(tr('Timeout')))

                    ->add(DefinitionFactory::newDescription()
                                           ->setHelpText(tr('The description for this mount')));

        return $this;
    }
}
