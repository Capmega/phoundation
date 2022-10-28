<?php

namespace Phoundation\Libraries;

use Phoundation\Cache\Cache;
use Phoundation\Core\Config;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Core\Tmp;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Developer\Debug;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\Exceptions;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Notifications\Notification;



/**
 * Libraries class
 *
 * This library can initialize all other libraries
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Libraries
 */
class Libraries
{
    /**
     * The constant indicating the path for Phoundation libraries
     */
    const CLASS_PATH_SYSTEM  = ROOT . 'Phoundation';

    /**
     * The constant indicating the path for PLugin libraries
     */
    const CLASS_PATH_PLUGINS = ROOT . 'Plugins';

    /**
     * If true, this system is in initialization mode
     *
     * @var bool
     */
    protected static bool $initializing = false;



    /**
     * Execute a complete systems initialization
     *
     * @param bool $system
     * @param bool $plugins
     * @param string|null $comments
     * @param string|null $library
     * @return void
     */
    public static function initialize(bool $system = true, bool $plugins = true, ?string $comments = null, ?string $library = null): void
    {
        self::$initializing = true;

        if (FORCE) {
            self::force();
        }

        if ($library) {
            // Init only the specified library
            $library = self::findLibrary($library);
            $library->init();

        } else {
            // Wipe all temporary data
            Tmp::clear();

            // Wipe all cache data
            Cache::clear();

            // Ensure the system database exists
            self::ensureSystemsDatabase();

            // Go over all system libraries and initialize them, then do the same for the plugins
            self::initializeLibraries($system, $plugins);
        }

        // Initialization done!
        self::$initializing = false;

        if (Debug::production()) {
            // Notification developers
            Notification::create()
                ->setGroups('developers')
                ->setTitle(tr('System initialization'))
                ->setMessage(tr('The system ran an initialization'))
                ->setDetails([
                    'system'  => $system,
                    'plugins' => $plugins,
                    'library' => $library,
                    'comment' => $comments
                ])
                ->send();
        }
    }



    /**
     * Returns a list with all libraries
     *
     * @param bool $system
     * @param bool $plugins
     * @return array
     */
    public static function listLibraries(bool $system = true, bool $plugins = true): array
    {
        if (!$system and !$plugins) {
            throw new OutOfBoundsException(tr('Both system and plugin library paths are filtered out'));
        }

        $return = [];

        // List system libraries
        if ($system) {
            $return = array_merge($return, self::listLibraryPaths(self::CLASS_PATH_SYSTEM));
        }

        // List plugin libraries
        if ($plugins) {
            try {
                $return = array_merge($return, self::listLibraryPaths(self::CLASS_PATH_PLUGINS));

            } catch (NotExistsException $e) {
                // The plugins path does not exist. No biggie, note it in the logs and create it for next time.
                mkdir(self::CLASS_PATH_PLUGINS, Config::get('filesystem.mode.default.directory', 0750));
            }
        }

        return $return;
    }



    /**
     * Returns true if the system is initializing
     *
     * @return bool
     */
    public static function isInitializing(): bool
    {
        return self::$initializing;
    }



    /**
     * Ensure that the systems database exists
     *
     * @return void
     */
    protected static function ensureSystemsDatabase(): void
    {
        if (!sql('system', false)->schema()->database()->exists()) {
            sql('system', false)->schema()->database()->create();
        }

        sql('system', false)->use();
    }



    /**
     * Returns a list with all libraries for the specified path
     *
     * @param string $path
     * @return array
     */
    protected static function listLibraryPaths(string $path): array
    {
        $return  = [];
        $path    = Strings::endsWith($path, '/');

        if (!file_exists($path)) {
            throw new NotExistsException(tr('The specified libraray base path ":path" does not exist', [
                ':path' => $path
            ]));
        }

        $libraries = scandir($path);

        foreach ($libraries as $library) {
            // Skip hidden files, current and parent directory
            if ($library[0] === '.') {
                continue;
            }

            $file = $path . $library . '/';

            // Library paths MUST be directories
            if (is_dir($file)) {
                $return[$file] = new Library($file);
            }
        }

        return $return;
    }



    /**
     * Libraries all libraries for system and plugins
     *
     * @param bool $system
     * @param bool $plugins
     * @return int
     */
    protected static function initializeLibraries(bool $system = true, bool $plugins = true, ?string $comments = null): int
    {
        // Get a list of all available libraries and their versions
        $libraries     = self::listLibraries($system, $plugins);
        $library_count = count($libraries);
        $update_count  = 0;

        // Keep initializing libraries until none of them have inits available anymore
        while ($libraries) {
            // Order to have the nearest next init version first
            self::orderLibraries($libraries);

            // Go over the libraries list and try to update each one
            foreach ($libraries as $path => $library) {
                if (!$library->getNextInitVersion()) {
                    // This library has nothing more to initialize, remove it from the list
                    Log::success(tr('Finished updates for library ":library"', [':library' => $library->getName()]));
                    unset($libraries[$path]);
                    break;
                }

                // Execute the update inits for this library and update the library information and start over
                $library->init($comments);
                $update_count++;
                break;
            }
        }

        if (!$update_count) {
            // No libraries were updated
            Log::success(tr('Finished initialization, no libraries were updated'));
        } else {
            Log::success(tr('Finished initialization, executed ":count" updates in ":libraries" libraries', [
                ':count' => $update_count,
                ':libraries' => $library_count
            ]));
        }

        return $update_count;
    }



    /**
     * Order the libraries by next_init_version first
     *
     * @param array $libraries
     * @return void
     */
    protected static function orderLibraries(array &$libraries): void
    {
        // Remove libraries that have nothing to execute anymore
        foreach ($libraries as $path => $library) {
            if ($library->getNextInitVersion() === null) {
                unset($libraries[$path]);
            }
        }

        // Order
        uasort($libraries, function ($a, $b) {
            return version_compare($a->getNextInitVersion(), $b->getNextInitVersion());
        });
    }



    /**
     * Returns the path for the specified library
     *
     * @note If the specified library exists both as a system library and a plugin, an OutOfBoundsException exception
     *       will be thrown
     * @throws OutOfBoundsException|NotExistsException
     * @param string $library
     * @param bool $system
     * @param bool $plugin
     * @return Library
     */
    protected static function findLibrary(string $library, bool $system = true, bool $plugin = true): Library
    {
        $return = null;
        $paths  = [];

        if ($system) {
            $paths[] = self::CLASS_PATH_SYSTEM;
        }

        if ($plugin) {
            $paths[] = self::CLASS_PATH_PLUGINS;
        }

        if (empty($paths)) {
            throw new OutOfBoundsException(tr('Neither system not plugin paths specified to search'));
        }

        $directory = Strings::capitalize($library);

        // Library must exist in either SYSTEM or PLUGINS paths
        foreach($paths as $path) {
            // Library must exist and be a directory
            if (file_exists($path . $directory)) {
                if (is_dir($path . $directory)) {
                    if ($return) {
                        throw new OutOfBoundsException(tr('The specified library ":library" is both a system library and a plugin', [
                            ':library' => $library
                        ]));
                    }

                    $return = new Library($path . $directory);
                }
            }
        }

        if ($return) {
            return $return;
        }

        throw Exceptions::NotExistsException(tr('The specified library does not exist'))->makeWarning();
    }


    /**
     * Execute a forced initialization.
     *
     * This will drop the system database and initialize the sytem from scratch
     *
     * @return void
     */
    protected static function force(): void
    {
        if (Debug::production()) {
            throw new AccessDeniedException(tr('For safety reasons, init force is NOT allowed on production environment!'));
        }

        sql()->schema()->database()->drop();
        sql()->schema()->database()->create();
        sql()->use();
    }
}