<?php

namespace Phoundation\Filesystem;

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
class Mounts
{
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
        $mounts = static::listTargets();

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
    public static function listSources(): array
    {
        return static::loadMounts('source');
    }


    /**
     * Returns a list of all directories as keys with value information about where they are mounted from with what
     * options
     *
     * @return array
     */
    public static function listTargets(): array
    {
        return static::loadMounts('target');
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