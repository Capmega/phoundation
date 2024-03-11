<?php

declare(strict_types=1);

namespace Phoundation\Core\Libraries;

use Phoundation\Cache\Cache;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Tmp;
use Phoundation\Databases\Sql\Exception\DatabasesConnectorException;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Notifications\Notification;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Exception\ConfigPathDoesNotExistsException;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Tables\HtmlTable;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlTableInterface;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Throwable;


/**
 * Libraries class
 *
 * This library can initialize all other libraries
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Libraries
{
    /**
     * The constant indicating the path for Phoundation libraries
     */
    const CLASS_DIRECTORY_SYSTEM  = DIRECTORY_ROOT . 'Phoundation/';

    /**
     * The constant indicating the path for Plugin libraries
     */
    const CLASS_DIRECTORY_PLUGINS = DIRECTORY_ROOT . 'Plugins/';

    /**
     * The constant indicating the path for Template libraries
     */
    const CLASS_DIRECTORY_TEMPLATES = DIRECTORY_ROOT . 'Templates/';

    /**
     * If true, this system is in initialization mode
     *
     * @var bool
     */
    protected static bool $initializing = false;

    /**
     * Tracks if the commands cache has been rebuilt within this process
     *
     * @var bool $cache_has_been_rebuilt
     */
    protected static bool $cache_has_been_rebuilt = false;

    /**
     * Tracjs if the command cache has been cleared within this process
     *
     * @var bool $cache_has_been_cleared
     */
    protected static bool $cache_has_been_cleared = false;


    /**
     * Resets the entire system by wiping all databases
     *
     * @return void
     */
    public static function reset(): void
    {
        Log::warning('Executing system reset, dropping all databases!');
        $connectors = Config::getArray('databases.connectors');

        foreach ($connectors as $connector => $configuration) {
            switch (isset_get($configuration['driver'])) {
                case 'sql':
                    // no break
                case 'mysql':
                    if (($connector === 'system') or isset_get($configuration['init'])) {
                        sql($connector, false)->schema(false)->database()->drop();
                    }

                    break;

                case 'memcached':
                    try {
                        mc($connector)->flush();

                    } catch (ConfigPathDoesNotExistsException $e) {
                        Log::warning(tr('Cannot flush memcached because the current driver is not properly configured, see exception information'));
                        Log::warning($e);
                    }

                    break;

                case 'mongo':
                case 'redis':
                case 'elasticsearch':
                    Log::error(tr('Ignoring connector ":driver", support for required driver ":driver" is under construction', [
                        ':driver'    => $configuration['driver'],
                        ':connector' => $connector
                    ]));
                    break;

                case '':
                    throw new DatabasesConnectorException(tr('No driver specified for connector ":connector"', [
                        ':connector' => $connector
                    ]));

                default:
                    throw new DatabasesConnectorException(tr('Unknown driver ":driver" specified for connector ":connector"', [
                        ':driver'    => $configuration['driver'],
                        ':connector' => $connector
                    ]));
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
     * @param array|null $libraries
     * @return void
     */
    public static function initialize(bool $system = true, bool $plugins = true, bool $templates = true, ?string $comments = null, ?array $libraries = null): void
    {
        static::$initializing = true;

        if (FORCE) {
            static::force();
        }

        // Wipe all temporary data and set the core in INIT mode
        Tmp::clear();
        Core::enableInitState();

        try {
            // Wipe all cache data
            Cache::clear();

        } catch (ConfigPathDoesNotExistsException $e) {
            Log::warning($e->getMessage());
        }

        // Ensure the system database exists
        static::ensureSystemsDatabaseAccessible();

        // Go over all system libraries and initialize them, then do the same for the plugins
        static::initializeLibraries($system, $plugins, $templates, $comments, $libraries);

        // Initialization done!
        static::$initializing = false;

        if (Core::isProductionEnvironment()) {
            // Notification developers
            Notification::new()
                ->setUrl('/system/information.html')
                ->setMode(EnumDisplayMode::info)
                ->setRoles('developer')->setTitle(tr('System initialization'))
                ->setMessage(tr('The system ran an initialization'))
                ->setDetails([
                    'system'    => $system,
                    'plugins'   => $plugins,
                    'templates' => $templates,
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

        Log::action(tr('Scanning libraries'), 3);

        // List system libraries
        if ($system) {
            $return = array_merge($return, static::listLibraryDirectories(static::CLASS_DIRECTORY_SYSTEM));
        }

        // List plugin libraries
        if ($plugins) {
            try {
                $return = array_merge($return, static::listLibraryDirectories(static::CLASS_DIRECTORY_PLUGINS));

            } catch (NotExistsException $e) {
                // The plugins path does not exist. No biggie, note it in the logs and create it for next time.
                mkdir(static::CLASS_DIRECTORY_PLUGINS, Config::get('filesystem.mode.default.directory', 0750));
            }
        }

        // List templates libraries
        if ($templates) {
            try {
                $return = array_merge($return, static::listLibraryDirectories(static::CLASS_DIRECTORY_TEMPLATES));

            } catch (NotExistsException $e) {
                // The templates path does not exist. No biggie, note it in the logs and create it for next time.
                mkdir(static::CLASS_DIRECTORY_TEMPLATES, Config::get('filesystem.mode.default.directory', 0750));
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
        $directories  = [];

        if ($system) {
            $directories[] = static::CLASS_DIRECTORY_SYSTEM;
        }

        if ($plugin) {
            $directories[] = static::CLASS_DIRECTORY_PLUGINS;
        }

        if ($template) {
            $directories[] = static::CLASS_DIRECTORY_TEMPLATES;
        }

        if (empty($directories)) {
            throw new OutOfBoundsException(tr('Neither system not plugin nor template paths specified to search'));
        }

        $library = Strings::capitalize($library);

        // Library must exist in either SYSTEM or PLUGINS paths
        foreach($directories as $directory) {
            $directory = Strings::slash($directory);

            // Library must exist and be a directory
            if (file_exists($directory . $library)) {
                if (is_dir($directory . $library)) {
                    if ($return) {
                        throw new OutOfBoundsException(tr('The specified library ":library" is both a system library and a plugin', [
                            ':library' => $library
                        ]));
                    }

                    $return = new Library($directory . $library);
                }
            }
        }

        if ($return) {
            return $return;
        }

        throw NotExistsException::new(tr('The specified library ":library" does not exist', [
            ':library' => $library
        ]))->makeWarning();
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
            $return['system'] = Directory::new(LIBRARIES::CLASS_DIRECTORY_SYSTEM, [LIBRARIES::CLASS_DIRECTORY_SYSTEM])->getPhpStatistics(true);
            $return['totals'] = Arrays::addValues($return['totals'], $return['system']);
        }

        if ($plugin) {
            // Get statistics for all plugin libraries
            $return['plugins'] = Directory::new(LIBRARIES::CLASS_DIRECTORY_PLUGINS, [LIBRARIES::CLASS_DIRECTORY_PLUGINS])->getPhpStatistics(true);
            $return['totals']  = Arrays::addValues($return['totals'], $return['plugins']);
        }

        if ($template) {
            // Get statistics for all template libraries
            $return['templates'] = Directory::new(LIBRARIES::CLASS_DIRECTORY_TEMPLATES, [LIBRARIES::CLASS_DIRECTORY_TEMPLATES])->getPhpStatistics(true);
            $return['totals']    = Arrays::addValues($return['totals'], $return['templates']);
        }

        return $return;
    }


    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @return HtmlTableInterface
     */
    public static function getHtmlTable(): HtmlTableInterface
    {
        // Create and return the table
        $table = HtmlTable::new()->setSource(static::listLibraries());
        $table->getHeaders()->setSource([
            tr('Library'),
            tr('Version'),
            tr('Description')
        ]);

        return $table;
    }


    /**
     * Ensure that the systems database exists and is accessible
     *
     * @return void
     */
    protected static function ensureSystemsDatabaseAccessible(): void
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
     * @param string $directory
     * @return array
     */
    protected static function listLibraryDirectories(string $directory): array
    {
        $return  = [];
        $directory    = Strings::endsWith($directory, '/');

        if (!file_exists($directory)) {
            throw new NotExistsException(tr('The specified library base directory ":directory" does not exist', [
                ':directory' => $directory
            ]));
        }

        $libraries = scandir($directory);

        foreach ($libraries as $library) {
            // Skip hidden files, current and parent directory
            if ($library[0] === '.') {
                continue;
            }

            // Skip the "disabled" directory
            if ($library === 'disabled') {
                continue;
            }

            $file = $directory . $library . '/';

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
     * @param array|null $filter_libraries
     * @return int
     */
    protected static function initializeLibraries(bool $system = true, bool $plugins = true, bool $templates = true, ?string $comments = null, array $filter_libraries = null): int
    {
        // Get a list of all available libraries and their versions
        $libraries      = static::listLibraries($system, $plugins, $templates);
        $post_libraries = $libraries;
        $library_count  = count($libraries);
        $update_count   = 0;

        // First, ensure all libraries have the correct structure
        static::verifyLibraries($libraries);

        Log::action(tr('Initializing libraries'));

        // Keep initializing libraries until none of them have inits available anymore
        while ($libraries) {
            // Order to have the nearest next init version first
            static::orderAndFilterLibraries($libraries, $filter_libraries);

            // Go over the list of libraries and try to update each one
            foreach ($libraries as $directory => $library) {
                // Execute the update inits for this library and update the library information and start over
                if ($library->init($comments)) {
                    // The library has been initialized. Break so that we can check which library should be updated next.
                    $update_count++;
                    break;
                }

                // This library has nothing more to initialize, remove it from the list
                Log::success(tr('Finished updates for library ":library"', [
                    ':library' => $library->getName()
                ]));

                unset($libraries[$directory]);
            }
        }

        // Post initialize all libraries
        // Go over the list of libraries and try to update each one
        if (TEST) {
            Log::warning('Not executing post init files due to test mode');

        } else {
            Log::action(tr('Executing post init updates'));

            foreach ($post_libraries as $library) {
                // Execute the update inits for this library and update the library information and start over
                if ($library->initPost($comments)) {
                    // Library has been post initialized. Break so that we can check which library should be updated next.
                    $update_count++;

                    Log::success(tr('Finished post updates for library ":library"', [
                        ':library' => $library->getName()
                    ]));
                }
            }
        }

        // Rebuild the commands cache
        static::rebuildCommandCache();

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
     * Ensure that all libraries have the
     *
     * @param array $libraries
     * @return void
     */
    protected static function verifyLibraries(array $libraries): void
    {
        Log::action(tr('Verifying libraries'));

        foreach ($libraries as $library) {
            $library->verify();
        }
    }


    /**
     * Deletes the PHO commands cache
     *
     * @return void
     */
    public static function clearCommandsCache(): void
    {
        Log::action(tr('Clearing commands caches'), 3);
        $cache = Directory::new(DIRECTORY_COMMANDS, Restrictions::writable(DIRECTORY_COMMANDS, 'Libraries::clearCommandsCache()'))->clearTreeSymlinks(true);

        if (!$cache->exists()) {
            Path::new(DIRECTORY_ROOT . '/commands', Restrictions::writable(DIRECTORY_ROOT, 'Libraries::clearWebCache()'))->delete();
        }

        static::$cache_has_been_cleared = true;
    }


    /**
     * Deletes the web pages cache
     *
     * @return void
     */
    public static function clearWebCache(): void
    {
        Log::action(tr('Clearing web caches'), 3);
        $cache = Directory::new(DIRECTORY_WEB, Restrictions::writable(DIRECTORY_WEB, 'Libraries::clearWebCache()'))->clearTreeSymlinks(true);

        if (!$cache->exists()) {
            Path::new(DIRECTORY_ROOT . 'web', Restrictions::writable(DIRECTORY_ROOT, 'Libraries::clearWebCache()'))->delete();
        }
    }


    /**
     * Deletes the tests cache
     *
     * @return void
     */
    public static function clearTestsCache(): void
    {
        Log::action(tr('Clearing test caches'), 3);
        $cache = Directory::new(DIRECTORY_DATA . 'system/cache/tests', Restrictions::writable(DIRECTORY_DATA . 'system/cache/tests', 'Libraries::clearTestsCache()'))->clearTreeSymlinks(true);

        if (!$cache->exists()) {
            Path::new(DIRECTORY_ROOT . '/tests', Restrictions::writable(DIRECTORY_ROOT, 'Libraries::clearWebCache()'))->delete();
        }
    }


    /**
     * Rebuilds the PHO commands cache
     *
     * @return void
     */
    public static function rebuildCommandCache(): void
    {
        static::clearCommandsCache();

        Log::action(tr('Rebuilding command cache'), 4);

        // Get temporary directory to build cache and the current cache directory
        $temporary = Directory::getTemporary();
        $cache     = Directory::new(DIRECTORY_COMMANDS, Restrictions::writable(DIRECTORY_COMMANDS, tr('Commands cache rebuild')));

        if ($cache->exists()) {
            // Replace the temporary directory with the cache directory contents
            $temporary = $temporary->delete();
            $cache->copy($temporary);
        }

        foreach (static::listLibraries() as $library) {
            $library->rebuildCommandCache($cache, $temporary);
        }

        // Move the old out of the way, push the new in and ensure we have a root directory link
        $cache->replaceWithPath($temporary)->symlinkTargetFromThis(Path::new(DIRECTORY_ROOT . 'commands', Restrictions::writable(DIRECTORY_ROOT . 'commands', tr('Commands cache rebuild')))->delete());

        static::$cache_has_been_rebuilt = true;
        Log::success(tr('Finished rebuilding command cache'));
    }


    /**
     * Rebuilds the web pages cache
     *
     * @return void
     */
    public static function rebuildWebCache(): void
    {
        static::clearWebCache();

        Log::action(tr('Rebuilding web cache'), 4);

        // Get temporary directory to build cache and the current cache directory
        $temporary = Directory::getTemporary();
        $cache     = Directory::new(DIRECTORY_WEB, Restrictions::writable(DIRECTORY_WEB, tr('Commands web rebuild')));

        if ($cache->exists()) {
            // Replace the temporary directory with the cache directory contents
            $temporary = $temporary->delete();
            $cache->copy($temporary);
        }

        foreach (static::listLibraries() as $library) {
            $library->rebuildWebCache($cache, $temporary);
        }

        // Move the old out of the way, push the new in and ensure we have a root directory link
        $cache
            ->replaceWithPath($temporary)
                ->symlinkTargetFromThis(Path::new(DIRECTORY_ROOT . 'web', Restrictions::writable(DIRECTORY_ROOT . 'web', tr('Web cache rebuild')))->delete());

        Log::success(tr('Finished rebuilding web cache'));
    }


    /**
     * Rebuilds the tests cache
     *
     * @return void
     */
    public static function rebuildTestsCache(): void
    {
        static::clearTestsCache();

        Log::action(tr('Rebuilding tests cache'), 4);

        // Get temporary directory to build cache and the current cache directory
        $temporary = Directory::getTemporary();
        $cache     = Directory::new(DIRECTORY_DATA . 'system/cache/tests', Restrictions::writable(DIRECTORY_DATA . 'system/cache/tests', tr('Commands tests rebuild')));

        if ($cache->exists()) {
            // Replace the temporary directory with the cache directory contents
            $temporary = $temporary->delete();
            $cache->copy($temporary);
        }

        foreach (static::listLibraries() as $library) {
            $library->rebuildTestsCache($cache, $temporary);
        }

        // Move the old out of the way, push the new in and ensure we have a root directory link
        $cache
            ->replaceWithPath($temporary)
            ->symlinkTargetFromThis(Path::new(DIRECTORY_ROOT . 'tests', Restrictions::writable(DIRECTORY_ROOT . 'tests', tr('Tests cache rebuild')))->delete());

        Log::success(tr('Finished rebuilding tests cache'));
    }


    /**
     * Returns true if in this process the commands cache has been cleared
     *
     * @return bool
     */
    public static function cacheHasBeenCleared(): bool
    {
        return static::$cache_has_been_cleared;
    }


    /**
     * Returns true if in this process the commands cache has been rebuilt
     *
     * @return bool
     */
    public static function cacheHasBeenRebuilt(): bool
    {
        return static::$cache_has_been_rebuilt;
    }


    /**
     * Order the libraries by next_init_version first
     *
     * @param array $libraries
     * @param array|null $filter_libraries
     * @return void
     */
    protected static function orderAndFilterLibraries(array &$libraries, array $filter_libraries = null): void
    {
        // Prepare libraries filter if specified
        if ($filter_libraries) {
            $filter_libraries = Arrays::lowercaseValues($filter_libraries);
            $filter_libraries = array_flip($filter_libraries);
        }

        // Remove libraries that have nothing to execute anymore
        foreach ($libraries as $directory => $library) {
            if ($filter_libraries) {
                if (!array_key_exists(strtolower($library->getName()), $filter_libraries)) {
                    // This library should not be initialized
                    unset($libraries[$directory]);
                    continue;
                }
            }

            if ($library->getNextInitVersion() === null) {
                unset($libraries[$directory]);
            }
        }

        // Order the libraries
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
        if (Core::isProductionEnvironment()) {
            throw new AccessDeniedException(tr('For safety reasons, init or setup force is NOT allowed on production environment!'));
        }

        sql()->schema()->database()->drop();
        sql()->schema()->database()->create();
        sql()->use();
    }


    /**
     * Returns the highest registered version number
     *
     * @return int
     */
    public static function getMaximumVersion(): int
    {
        return sql()->getColumn('SELECT MAX(`version`) AS `max` FROM `core_versions`');
    }


    /**
     * Loads all Phoundation classes into memory
     *
     * This is useful, for example, when executing an upgrade and you want to avoid a class from a newer version being
     * needed AFTER the update, where that class could be of a different -newer and incompatible- version that would
     * cause the update method to crash
     *
     * @return void
     */
    public static function loadAllPhoundationClassesIntoMemory(): void
    {
        $path = DIRECTORY_ROOT . 'Phoundation/';

        Log::action(tr('Pre-loading all library classes into memory for compatibility'), 5);

        Find::new(Restrictions::readonly($path))
            ->setPath($path)
            ->setType('f')
            ->setName('*.php')
            ->setCallback(function($file) {
                $test  = strtolower($file);
                $tests = [
                    'Tests/bootstrap.php'
                ];

                // Don't loads file in the LIBRARY/Library path
                if (str_contains($test, '/library/')) {
                    return;
                }

                // Don't load the following specific files
                if (str_ends_with($test, 'tests/bootstrap.php')) {
                    return;
                }

                try {
                    Log::action(tr('Attempting to pre-loading library file ":file"', [
                        ':file' => Strings::from($file, DIRECTORY_ROOT)
                    ]), 2);

                    require_once($file);

                } catch (Throwable $e) {
                    Log::warning(tr('Pre-loading library file ":file" caused exception ":message", ignoring', [
                        ':file'    => Strings::from($file, DIRECTORY_ROOT),
                        ':message' => $e->getMessage()
                    ]), 4);
                }
            })
            ->executeNoReturn();
    }
}
