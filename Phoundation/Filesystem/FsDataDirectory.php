<?php

/**
 * FsDataDirectory class
 *
 * This class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Stringable;

class FsDataDirectory extends FsDirectory
{
    /**
     * FsDataDirectory class constructor
     *
     * @todo IMPLEMENT
     *
     * @param Stringable|string                 $source
     * @param bool|FsRestrictionsInterface|null $restrictions
     * @param Stringable|bool|string|null       $absolute_prefix
     */
    public function __construct(Stringable|string $source, bool|FsRestrictionsInterface|null $restrictions = null, Stringable|bool|string|null $absolute_prefix = false)
    {
        parent::__construct($source, $restrictions, $absolute_prefix);









        //    /**
//     * ???
//     *
//     * @param string $section
//     * @param bool $writable
//     * @return string
//     */
//    public static function getGlobalDataDirectory(string $section = '', bool $writable = true): string
//    {
//        // First find the global data path.
//        // For now, either the same height as this project, OR one up the filesystem tree
//        $directories = [
//            '/var/lib/data/',
//            '/var/www/data/',
//            DIRECTORY_ROOT . '../data/',
//            DIRECTORY_ROOT . '../../data/'
//        ];
//
//        if (!empty($_SERVER['HOME'])) {
//            // Also check the users home directory
//            $directories[] = $_SERVER['HOME'] . '/projects/data/';
//            $directories[] = $_SERVER['HOME'] . '/data/';
//        }
//
//        $found = false;
//
//        foreach ($directories as $directory) {
//            if (file_exists($directory)) {
//                $found = $directory;
//                break;
//            }
//        }
//
//        if ($found) {
//            // Cleanup path. If realpath fails, we know something is amiss
//            if (!$found = realpath($found)) {
//                throw new CoreException(tr('Found directory ":directory" failed realpath() check', [
//                    ':directory' => $directory
//                ]));
//            }
//        }
//
//        if (!$found) {
//            if (!PLATFORM_CLI) {
//                throw new CoreException('Global data path not found');
//            }
//
//            try {
//                Log::warning(tr('Warning: Global data path not found. Normally this path should exist either 1 directory up, 2 directories up, in /var/lib/data, /var/www/data, $USER_HOME/projects/data, or $USER_HOME/data'));
//                Log::warning(tr('Warning: If you are sure this simply does not exist yet, it can be created now automatically. If it should exist already, then abort this script and check the location!'));
//
//                // TODO Do this better, this is crap
//                $directory = Process::newCliScript('base/init_global_data_path')->executeReturnArray();
//
//                if (!file_exists($directory)) {
//                    // Something went wrong and it was not created anyway
//                    throw new CoreException(tr('Configured directory ":directory" was created but it could not be found', [
//                        ':directory' => $directory
//                    ]));
//                }
//
//                // Its now created! Strip "data/"
//                $directory = Strings::slash($directory);
//
//            } catch (Exception $e) {
//                throw new CoreException('get_global_data_path(): Global data path not found, or init_global_data_path failed / aborted', $e);
//            }
//        }
//
//        // Now check if the specified section exists
//        if ($section and !file_exists($directory . $section)) {
//            Directory::ensure($directory . $section);
//        }
//
//        if ($writable and !is_writable($directory . $section)) {
//            throw new CoreException(tr('The global directory ":directory" is not writable', [
//                ':directory' => $directory . $section
//            ]));
//        }
//
//        if (!$global_path = realpath($directory . $section)) {
//            // Curious, the path exists, but realpath failed and returned false. This should never happen since we
//            // ensured the path above! This is just an extra check in case of.. Weird problems :)
//            throw new CoreException(tr('The found global data directory ":directory" is invalid (realpath returned false)', [
//                ':directory' => $directory
//            ]));
//        }
//
//        return Strings::slash($global_path);
//    }




    }
}
