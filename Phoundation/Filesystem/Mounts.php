<?php

namespace Phoundation\Filesystem;

use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Exception\Exception;
use Phoundation\Exception\NotExistsException;
use Phoundation\Filesystem\Exception\DirectoryNotMountedException;
use Phoundation\Os\Processes\Commands\Mount;
use Stringable;


/**
 * Class Mounts
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */
class Mounts extends DataList
{
    /**
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'filesystem_mounts';
    }


    public static function getEntryClass(): string
    {
        return \Phoundation\Filesystem\Mount::class;
    }


    public static function getUniqueField(): ?string
    {
        return 'name';
    }


    /**
     * Mounts the specified source to the specified target
     *
     * @param Stringable|string $source
     * @param Stringable|string $target
     * @param array|null $options
     * @param string|null $filesystem
     * @return void
     */
    public static function mount(Stringable|string $source, Stringable|string $target, ?array $options = null, ?string $filesystem = null): void
    {
        Mount::new()->mount($source, $target, $options, $filesystem);
    }


    /**
     * Unmounts the specified target
     *
     * @param Stringable|string $target
     * @return void
     */
    public static function unmount(Stringable|string $target): void
    {
        Mount::new()->unmount($target);
    }


    /**
     * Returns a list of all devices as keys with value information about where they are mounted with what options
     *
     * @param Stringable|string $directory
     * @return array
     */
    public static function getDirectoryMountInformation(Stringable|string $directory): array
    {
        $mounts = static::listMountTargets();

        if (array_key_exists($directory, $mounts)) {
            return $mounts[$directory];
        }

        throw new DirectoryNotMountedException(tr('The specified directory ":directory" is not mounted', [
            ':directory' => $directory
        ]));
    }


    /**
     * Returns a list of all devices as keys with value information about where they are mounted with what options
     *
     * @return array
     */
    public static function listMountSources(): array
    {
        return static::loadMounts('source');
    }


    /**
     * Returns a list of all directories as keys with value information about where they are mounted from with what
     * options
     *
     * @return array
     */
    public static function listMountTargets(): array
    {
        return static::loadMounts('target');
    }


    /**
     * Returns a list of all devices as keys with value information about where they are mounted with what options
     *
     * @param string $source
     * @return array
     */
    public static function getMountSource(string $source): array
    {
        $mounts = static::loadMounts('source');

        if (array_key_exists($source, $mounts)) {
            return $mounts[$source];
        }

        throw new NotExistsException(tr('The specified mount source ":source" does not exist', [
            ':source' => $source
        ]));
    }


    /**
     * Returns a list of all directories as keys with value information about where they are mounted from with what
     * options
     *
     * @param string $target
     * @return array|null
     */
    public static function getMountTarget(string $target): ?array
    {
        $mounts = static::loadMounts('target');

        if (array_key_exists($target, $mounts)) {
            return $mounts[$target];
        }

        throw new NotExistsException(tr('The specified mount target ":target" does not exist', [
            ':target' => $target
        ]));
    }


    /**
     * Loads all mounts data into an array and returns it with the specified key
     *
     * @param string $key Should ONLY be one of "source", or "target"
     * @return array
     */
    protected static function loadMounts(string $key): array
    {
        $return = [];
        $mounts = File::new('/proc/mounts')->getContentsAsArray();

        foreach ($mounts as $mount) {
            $mount = explode(' ', $mount);
            $mount = [
                'source'     => $mount[0],
                'target'     => $mount[1],
                'filesystem' => $mount[2],
                'options'    => $mount[3],
                'fs_freq'    => $mount[4],
                'fs_passno'  => $mount[5],
            ];

            $return[$mount[$key]] = $mount;
        }

        return $return;
    }
}