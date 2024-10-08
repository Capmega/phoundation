<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Mounts;

use Phoundation\Core\Core;
use Phoundation\Core\Hooks\Hook;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryOptions;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryTimeout;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Exception\NotExistsException;
use Phoundation\Filesystem\Interfaces\MountInterface;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Mounts\Exception\MountsException;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Traits\TraitDataRestrictions;
use Phoundation\Os\Processes\Commands\UnMount;
use Phoundation\Utils\Config;
use Phoundation\Web\Html\Enums\EnumInputType;

/**
 * Class Mount
 *
 *
 * @note      On Ubuntu requires packages nfs-utils cifs-utils psmisc
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */
class Mount extends DataEntry implements MountInterface
{
    use TraitDataEntryNameDescription;
    use TraitDataRestrictions;
    use TraitDataEntryOptions;
    use TraitDataEntryTimeout;

    /**
     * Mount class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null                        $column
     * @param bool|null                          $meta_enabled
     * @param RestrictionsInterface|null         $restrictions
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, ?string $column = null, ?bool $meta_enabled = null, ?RestrictionsInterface $restrictions = null)
    {
        parent::__construct($identifier, $column, $meta_enabled, $init);
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
    public static function getDataEntryName(): string
    {
        return tr('Mount');
    }


    /**
     * @inheritDoc
     */
    public static function getUniqueColumn(): ?string
    {
        return 'name';
    }


    /**
     * Returns the mount object for the path that is mounted as close as possible to the specified path
     *
     * @param Path|string           $path
     * @param RestrictionsInterface $restrictions
     *
     * @return static|null
     */
    public static function getForPath(Path|string $path, RestrictionsInterface $restrictions): ?static
    {
        if (sql()->getDatabase()) {
            $paths = sql()->query('SELECT   `id`, `target_path` 
                                 FROM     `filesystem_mounts` 
                                 ORDER BY LENGTH(`target_path`)');
            while ($mount_path = $paths->fetch()) {
                $mount_path['target_path'] = Path::absolutePath($mount_path['target_path'], must_exist: false);
                if (str_starts_with($path, $mount_path['target_path'])) {
                    return static::new($mount_path['id'], 'id')
                                 ->setRestrictions($restrictions);
                }
            }
        }

        return null;
    }


    /**
     * @inheritDoc
     */
    public static function load(DataEntryInterface|string|int|null $identifier, ?string $column = null, bool $meta_enabled = false, bool $force = false): static
    {
        try {
            return parent::load($identifier, $column, $meta_enabled, $force);

        } catch (DataEntryNotExistsException $e) {
            // Mount was not found in the database. Get it from configuration instead but that DOES require the name
            // column
            switch ($column) {
                case 'name':
                    $mount = Config::getArray('filesystem.mounts.' . $identifier);

                    return static::newFromSource($mount, $meta_enabled);
                case 'source_path':
                    // This is a mount that SHOULD already exist on the system
                    $mount = Mounts::getMountSources($identifier);

                    return static::newFromSource($mount, $meta_enabled);
                case 'target_path':
                    // This is a mount that SHOULD already exist on the system
                    $mount = Mounts::getMountTargets($identifier);

                    return static::newFromSource($mount, $meta_enabled);
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
        return Path::absolutePath($this->getSourcePath(), must_exist: false);
    }


    /**
     * Returns the source_path for this object
     *
     * @return string|null
     */
    public function getSourcePath(): ?string
    {
        return $this->getValueTypesafe('string', 'source_path');
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
        if (Config::getBoolean('filesystem.automounts.enabled', false)) {
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
                Log::action(tr('Automatically unmounting ":source" from ":target"', [
                    ':source' => $this->getSourcePath(),
                    ':target' => $this->getTargetPath(),
                ]));
                $this->unmount();
            });
        }
        // Mount and try again!
        Log::action(tr('Automatically mounting ":source" to ":target"', [
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
            foreach (Mounts::getMountSources($this->getAbsoluteTargetPath(), $this->restrictions)
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
        return Path::absolutePath($this->getTargetPath(), must_exist: false);
    }


    /**
     * Returns the $target_path for this object
     *
     * @return string|null
     */
    public function getTargetPath(): ?string
    {
        return $this->getValueTypesafe('string', 'target_path');
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
            $mounts = Mounts::getMountSources($this->getAbsoluteTargetPath(), $this->restrictions)
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
        return $this->getValueTypesafe('bool', 'auto_mount');
    }


    /**
     * Returns the auto_unmount for this object
     *
     * @return bool|null
     */
    public function getAutoUnmount(): ?bool
    {
        return $this->getValueTypesafe('bool', 'auto_unmount');
    }


    /**
     * Unmounts this mount point
     *
     * @return $this
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
        return $this->getValueTypesafe('string', 'filesystem');
    }


    /**
     * Mounts this mount point
     *
     * @return $this
     */
    public function mount(): static
    {
        \Phoundation\Os\Processes\Commands\Mount::new($this->restrictions)
                                                ->mount($this->getSourcePath(), $this->getAbsoluteTargetPath(), $this->getFilesystem(), $this->getOptions(), $this->getTimeout());

        return $this;
    }


    /**
     * @inheritDoc
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(DefinitionFactory::getName($this)
                                           ->setInputType(EnumInputType::name)
                                           ->setSize(12)
                                           ->setMaxlength(64)
                                           ->setLabel(tr('Name'))
                                           ->setHelpText(tr('The unique identifier name for this mount'))
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isUnique(tr('value ":name" already exists', [':name' => $validator->getSelectedValue()]));
                                           }))
                    ->add(DefinitionFactory::getSeoName($this))
                    ->add(Definition::new($this, 'source_path')
                                    ->setInputType(EnumInputType::name)
                                    ->setSize(4)
                                    ->setMaxlength(255)
                                    ->setLabel(tr('Source'))
                                    ->setHelpText(tr('The source file for this mount'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isFile();
                                    }))
                    ->add(Definition::new($this, 'target_path')
                                    ->setInputType(EnumInputType::name)
                                    ->setSize(4)
                                    ->setMaxlength(255)
                                    ->setLabel(tr('Target'))
                                    ->setHelpText(tr('The target file for this mount'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isFile();
                                    }))
                    ->add(Definition::new($this, 'filesystem')
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
                    ->add(Definition::new($this, 'options')
                                    ->setOptional(true)
                                    ->setSize(6)
                                    ->setDefault('defaults')
                                    ->setMaxlength(508)
                                    ->setLabel(tr('Options'))
                                    ->setHelpText(tr('The options for this mount'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isFile();
                                    }))
                    ->add(DefinitionFactory::getBoolean($this, 'auto_mount')
                                           ->setSize(2)
                                           ->setHelpText(tr('If checked, this mount will automatically be mounted by the process using a path in this mount'))
                                           ->setLabel(tr('Auto mount')))
                    ->add(DefinitionFactory::getBoolean($this, 'auto_unmount')
                                           ->setSize(2)
                                           ->setHelpText(tr('If checked, this mount will automatically be unmounted after use when the process using a path in this mount terminates'))
                                           ->setLabel(tr('Auto unmount')))
                    ->add(DefinitionFactory::getNumber($this, 'timeout')
                                           ->setOptional(true, 3)
                                           ->setSize(2)
                                           ->setHelpText(tr('If specified, the mount attempt for this filesystem will be aborted after this number of seconds'))
                                           ->setLabel(tr('Timeout')))
                    ->add(DefinitionFactory::getDescription($this)
                                           ->setHelpText(tr('The description for this mount')));
    }
}
