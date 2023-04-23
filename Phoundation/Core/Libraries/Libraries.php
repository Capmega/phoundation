<?php

namespace Phoundation\Core\Libraries;

use Phoundation\Cache\Cache;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Exception\ConfigurationDoesNotExistsException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Core\Tmp;
use Phoundation\Developer\Debug;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Path;
use Phoundation\Notifications\Notification;
use Phoundation\Web\Http\Html\Components\Table;
use Phoundation\Web\Http\Html\Enums\DisplayMode;


/**
 * Libraries class
 *
 * This library can initialize all other libraries
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Libraries
{
    /**
     * The constant indicating the path for Phoundation libraries
     */
    const CLASS_PATH_SYSTEM  = PATH_ROOT . 'Phoundation/';

    /**
     * The constant indicating the path for Plugin libraries
     */
    const CLASS_PATH_PLUGINS = PATH_ROOT . 'Plugins/';

    /**
     * The constant indicating the path for Template libraries
     */
    const CLASS_PATH_TEMPLATES = PATH_ROOT . 'Templates/';

    /**
     * If true, this system is in initialization mode
     *
     * @var bool
     */
    protected static bool $initializing = false;



    /**
     * Resets the entire system by wiping all databases
     *
     * @return void
     */
    public static function reset(): void
    {
        Log::warning('Executing system reset, dropping all databases!');
        $databases = Config::get('databases');

        foreach ($databases as $driver => $data) {
            switch ($driver) {
                case 'sql':
                    foreach ($data['instances'] as $instance => $configuration) {
                        sql($instance, false)->schema()->database()->drop();
                    }

                    break;

                case 'memcached':
                    try {
                        mc()->flush();

                    } catch (ConfigurationDoesNotExistsException $e) {
                        Log::warning(tr('Cannot flush memcached because the current driver is not properly configured, see exception information'));
                        Log::warning($e);
                    }

                    break;

                case 'mongo':
                case 'redis':
                case 'elasticsearch':
                    Log::error(tr('Support for resetting driver ":driver" is under construction', [':driver' => $driver]));
                    break;

                default:
                    // Ignore, only process drivers
            }
        }
    }



    /**
     * Execute a complete systems initialization
     *
     * @param bool $system
     * @param bool $plugins
     * @param bool $templates
     * @param string|null $comments
     * @param string|null $library
     * @return void
     */
    public static function initialize(bool $system = true, bool $plugins = true, bool $templates = true, ?string $comments = null, ?string $library = null): void
    {
        static::$initializing = true;

        if (FORCE) {
            static::force();
        }

        if ($library) {
            // Init only the specified library
            $library = static::findLibrary($library);
            $library->init($comments);

        } else {
            // Wipe all temporary data
            Tmp::clear();

            try {
                // Wipe all cache data
                Cache::clear();
            } catch (ConfigurationDoesNotExistsException $e) {
                Log::warning($e->getMessage());
            }

            // Ensure the system database exists
            static::ensureSystemsDatabase();

            // Go over all system libraries and initialize them, then do the same for the plugins
            static::initializeLibraries($system, $plugins, $templates);
        }

        // Initialization done!
        static::$initializing = false;

        if (Debug::production()) {
            // Notification developers
            Notification::new()
                ->setMode(DisplayMode::info)
                ->setRoles('developers')->setTitle(tr('System initialization'))
                ->setMessage(tr('The system ran an initialization'))
                ->setDetails([
                    'system'    => $system,
                    'plugins'   => $plugins,
                    'templates' => $templates,
                    'library'   => $library,
                    'comment'   => $comments
                ])
                ->send();
        }
    }



    /**
     * Returns a list with all libraries
     *
     * @param bool $system
     * @param bool $plugins
     * @param bool $templates
     * @return array
     */
    public static function listLibraries(bool $system = true, bool $plugins = true, bool $templates = true): array
    {
        if (!$system and !$plugins and !$templates) {
            throw new OutOfBoundsException(tr('All system, plugins, and templates library paths are filtered out'));
        }

        $return = [];

        // List system libraries
        if ($system) {
            $return = array_merge($return, static::listLibraryPaths(static::CLASS_PATH_SYSTEM));
        }

        // List plugin libraries
        if ($plugins) {
            try {
                $return = array_merge($return, static::listLibraryPaths(static::CLASS_PATH_PLUGINS));

            } catch (NotExistsException $e) {
                // The plugins path does not exist. No biggie, note it in the logs and create it for next time.
                mkdir(static::CLASS_PATH_PLUGINS, Config::get('filesystem.mode.default.directory', 0750));
            }
        }

        // List templates libraries
        if ($templates) {
            try {
                $return = array_merge($return, static::listLibraryPaths(static::CLASS_PATH_TEMPLATES));

            } catch (NotExistsException $e) {
                // The templates path does not exist. No biggie, note it in the logs and create it for next time.
                mkdir(static::CLASS_PATH_TEMPLATES, Config::get('filesystem.mode.default.directory', 0750));
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
        return static::$initializing;
    }



    /**
     * Returns the path for the specified library
     *
     * @note If the specified library exists both as a system library and a plugin, an OutOfBoundsException exception
     *       will be thrown
     * @param string $library
     * @param bool $system
     * @param bool $plugin
     * @param bool $template
     * @return Library
     */
    public static function findLibrary(string $library, bool $system = true, bool $plugin = true, bool $template = true): Library
    {
        $return = null;
        $paths  = [];

        if ($system) {
            $paths[] = static::CLASS_PATH_SYSTEM;
        }

        if ($plugin) {
            $paths[] = static::CLASS_PATH_PLUGINS;
        }

        if ($template) {
            $paths[] = static::CLASS_PATH_TEMPLATES;
        }

        if (empty($paths)) {
            throw new OutOfBoundsException(tr('Neither system not plugin nor template paths specified to search'));
        }

        $directory = Strings::capitalize($library);

        // Library must exist in either SYSTEM or PLUGINS paths
        foreach($paths as $path) {
            $path = Strings::slash($path);

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

        throw NotExistsException::new(tr('The specified library does not exist'))->makeWarning();
    }



    /**
     * Returns the PhpStatistics object for this library
     *
     * @param bool $system
     * @param bool $plugin
     * @param bool $template
     * @return array
     */
    public static function getPhpStatistics(bool $system = true, bool $plugin = true, bool $template = true): array
    {
        $return = ['totals' => []];

        if ($system) {
            // Get statistics for all system libraries
            $return['system'] = Path::new(LIBRARIES::CLASS_PATH_SYSTEM, [LIBRARIES::CLASS_PATH_SYSTEM])->getPhpStatistics(true);
            $return['totals'] = Arrays::addValues($return['totals'], $return['system']);
        }

        if ($plugin) {
            // Get statistics for all plugin libraries
            $return['plugins'] = Path::new(LIBRARIES::CLASS_PATH_PLUGINS, [LIBRARIES::CLASS_PATH_PLUGINS])->getPhpStatistics(true);
            $return['totals']  = Arrays::addValues($return['totals'], $return['plugins']);
        }

        if ($template) {
            // Get statistics for all template libraries
            $return['templates'] = Path::new(LIBRARIES::CLASS_PATH_TEMPLATES, [LIBRARIES::CLASS_PATH_TEMPLATES])->getPhpStatistics(true);
            $return['totals']    = Arrays::addValues($return['totals'], $return['templates']);
        }

        return $return;
    }



    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @return Table
     */
    public static function getHtmlTable(): Table
    {
        // Create and return the table
        return Table::new()
            ->setColumnHeaders([tr('Library'), tr('Version'), tr('Description')])
            ->setSourceArray(static::listLibraries());
    }



    /**
     * Ensure that the systems database exists
     *
     * @return void
     */
    protected static function ensureSystemsDatabase(): void
    {
        if (!sql('system', false)->schema(false)->database()->exists()) {
            sql('system', false)->schema(false)->database()->create();
        }

        // Use the new database, and reset the schema object
        sql('system')->use();
        sql('system', false)->resetSchema();
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
            throw new NotExistsException(tr('The specified library base path ":path" does not exist', [
                ':path' => $path
            ]));
        }

        $libraries = scandir($path);

        foreach ($libraries as $library) {
            // Skip hidden files, current and parent directory
            if ($library[0] === '.') {
                continue;
            }

            // Skip the "disabled" directory
            if ($library === 'disabled') {
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
     * @param bool $templates
     * @param string|null $comments
     * @return int
     */
    protected static function initializeLibraries(bool $system = true, bool $plugins = true, bool $templates = true, ?string $comments = null): int
    {
        // Get a list of all available libraries and their versions
        $libraries     = static::listLibraries($system, $plugins, $templates);
        $library_count = count($libraries);
        $update_count  = 0;

        // Keep initializing libraries until none of them have inits available anymore
        while ($libraries) {
            // Order to have the nearest next init version first
            static::orderLibraries($libraries);

            // Go over the libraries list and try to update each one
            foreach ($libraries as $path => $library) {
                // Execute the update inits for this library and update the library information and start over
                if ($library->init($comments)) {
                    // Library has been initialized. Break so that we can check which library should be updated next.
                    $update_count++;
                    break;
                } else {
                    // This library has nothing more to initialize, remove it from the list
                    Log::success(tr('Finished updates for library ":library"', [
                        ':library' => $library->getName()
                    ]));

                    unset($libraries[$path]);
                }
            }
        }

        if (!$update_count) {
            // No libraries were updated
            Log::success(tr('Finished initialization, no libraries were updated'));
        } else {
            Log::success(tr('Finished initialization, executed ":count" updates in ":libraries" libraries', [
                ':count'     => $update_count,
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
     * Execute a forced initialization.
     *
     * This will drop the system database and initialize the sytem from scratch
     *
     * @return void
     */
    protected static function force(): void
    {
        if (Debug::production()) {
            throw new AccessDeniedException(tr('For safety reasons, init or setup force is NOT allowed on production environment!'));
        }

        sql()->schema()->database()->drop();
        sql()->schema()->database()->create();
        sql()->use();
    }
}