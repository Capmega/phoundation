<?php

/**
 * Class Mounts
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Mounts;

use Phoundation\Data\DataEntry\DataIterator;
use Phoundation\Exception\NotExistsException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\Exception\DirectoryNotMountedException;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Filesystem\Mounts\Interfaces\MountsInterface;
use Phoundation\Os\Processes\Commands\Mount;
use Phoundation\Os\Processes\Commands\UnMount;
use Stringable;


class Mounts extends DataIterator implements MountsInterface
{
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
    public static function getDefaultContentDataTypes(): ?string
    {
        return FsMount::class;
    }


    /**
     * @inheritDoc
     */
    public static function getUniqueColumn(): ?string
    {
        return 'name';
    }


    /**
     * Mounts the specified source to the specified target
     *
     * @param Stringable|string $source
     * @param Stringable|string $target
     * @param string|null       $filesystem
     * @param array|null        $options
     *
     * @return void
     */
    public static function mount(Stringable|string $source, Stringable|string $target, ?string $filesystem = null, ?array $options = null): void
    {
        Mount::new()->mount($source, $target, $filesystem, $options);
    }


    /**
     * Unmounts the specified target
     *
     * @param Stringable|string $target
     *
     * @return void
     */
    public static function unmount(Stringable|string $target): void
    {
        UnMount::new()->unmount($target);
    }


    /**
     * Returns a list of all devices as keys with value information about where they are mounted with what options
     *
     * @param Stringable|string $directory
     *
     * @return array
     */
    public static function getDirectoryMountInformation(Stringable|string $directory): array
    {
        $mounts = static::listMountTargets();

        if (array_key_exists($directory, $mounts)) {
            return $mounts[$directory];
        }

        throw new DirectoryNotMountedException(tr('The specified directory ":directory" is not mounted', [
            ':directory' => $directory,
        ]));
    }


    /**
     * Returns a list of all directories as keys with value information about where they are mounted from with what
     * options
     *
     * @return static
     */
    public static function listMountTargets(): static
    {
        return static::listMounts('target_path');
    }


    /**
     * Loads all mounts data into an array and returns it with the specified key
     *
     * @param string $key Should ONLY be one of "source", or "target"
     *
     * @return static
     */
    protected static function listMounts(string $key): static
    {
        $return = static::new();
        $mounts = FsFile::new('/proc/mounts', FsRestrictions::getReadonly('/proc/', 'FsMounts::ListMounts()'))
                        ->getContentsAsArray();

        foreach ($mounts as $mount) {
            $mount = explode(' ', $mount);
            $mount = [
                'source_path' => $mount[0],
                'target_path' => $mount[1],
                'filesystem'  => $mount[2],
                'options'     => $mount[3],
                'fs_freq'     => $mount[4],
                'fs_passno'   => $mount[5],
            ];

            $return->add(FsMount::newFromSource($mount), $mount[$key]);
        }

        return $return;
    }


    /**
     * Returns a list of all devices as keys with value information about where they are mounted with what options
     *
     * @return static
     */
    public static function listMountSources(): static
    {
        return static::listMounts('source_path');
    }


    /**
     * Returns a list of all source devices / paths as keys for the specified target path and with what options
     *
     * @param Stringable|string       $target
     * @param FsRestrictionsInterface $restrictions
     *
     * @return static
     */
    public static function getMountSources(Stringable|string $target, FsRestrictionsInterface $restrictions): static
    {
        $target = FsDirectory::new($target, $restrictions)->getSource(true);
        $mounts = static::listMounts('target_path');
        $return = static::new();

        foreach ($mounts as $path => $source) {
            if ($path === $target) {
                $return->add($source, $path);
            }
        }

        if ($return->isEmpty()) {
            throw new NotExistsException(tr('The specified mount target ":target" does not exist', [
                ':target' => $target,
            ]));
        }

        return $return;
    }


    /**
     * Returns a list of all target directories as keys with value information about where they are mounted from and
     * with what options
     *
     * @param string $source
     *
     * @return static
     */
    public static function getMountTargets(string $source): static
    {
        $mounts = static::listMounts('source_path');
        $return = static::new();

        foreach ($mounts as $path => $target) {
            if ($path === $source) {
                $return->add($target, $path);
            }
        }

        if ($return->isEmpty()) {
            throw new NotExistsException(tr('The specified mount source ":source" does not exist', [
                ':source' => $source,
            ]));
        }

        return $return;
    }
}
