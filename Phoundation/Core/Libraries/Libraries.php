<?php

/**
 * Libraries class
 *
 * This library can initialize all other libraries
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Core\Libraries;

use Phoundation\Accounts\Config\Exception\ConfigPathDoesNotExistsException;
use Phoundation\Cache\Cache;
use Phoundation\Core\Core;
use Phoundation\Core\Libraries\Exception\LibraryMultipleVendorsException;
use Phoundation\Core\Libraries\Exception\LibraryNotFoundException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Tmp;
use Phoundation\Databases\Sql\Exception\DatabasesConnectorException;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Notifications\Notification;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Tables\HtmlTable;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlTableInterface;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Http\Url;
use Throwable;


class Libraries
{
    /**
     * The constant indicating the path for Phoundation libraries
     */
    public const string CLASS_DIRECTORY_SYSTEM = DIRECTORY_ROOT . 'Phoundation/';

    /**
     * The constant indicating the path for Plugin libraries
     */
    public const string CLASS_DIRECTORY_PLUGINS = DIRECTORY_ROOT . 'Plugins/';

    /**
     * The constant indicating the path for Template libraries
     */
    public const string CLASS_DIRECTORY_TEMPLATES = DIRECTORY_ROOT . 'Templates/';


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
     * Tracks if the command cache has been cleared within this process
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
        Core::enableInitState();

        Log::warning('Executing system reset, dropping all init enabled databases!');
        Log::warning('Check your configuration to see which databases are configured with the init flag!');

        $connectors = config()->getArray('databases.connectors');

        foreach ($connectors as $connector => $configuration) {
            switch (array_get_safe($configuration, 'driver')) {
                case 'sql':
                    // no break

                case 'mysql':
                    if (($connector === 'system') or array_get_safe($configuration, 'init')) {
                        sql($connector, false)
                            ->getSchemaObject(false)
                            ->getDatabaseObject(use: false)
                            ->drop();
                    }

                    break;

                case 'memcached':
                    try {
                        mc($connector)->clear();

                    } catch (ConfigPathDoesNotExistsException $e) {
                        Log::warning(ts('Cannot flush memcached because the current driver is not properly configured, see exception information'));
                        Log::warning($e);
                    }

                    break;

                case 'mongo':
                    // no break

                case 'redis':
                    // no break

                case 'elasticsearch':
                    Log::error(ts('Ignoring "reset" for connector ":connector", support for required driver ":driver" is under construction', [
                        ':driver'    => $configuration['driver'],
                        ':connector' => $connector,
                    ]));

                    break;

                case '':
                    throw new DatabasesConnectorException(tr('No driver specified for connector ":connector"', [
                        ':connector' => $connector,
                    ]));

                default:
                    throw new DatabasesConnectorException(tr('Unknown driver ":driver" specified for connector ":connector"', [
                        ':driver'    => $configuration['driver'],
                        ':connector' => $connector,
                    ]));
            }
        }
    }


    /**
     * Execute a complete systems initialization
     *
     * @see https://kedar.nitty-witty.com/blog/a-unique-foreign-key-issue-in-mysql-8-4
     *
     * @param bool        $system
     * @param bool        $plugins
     * @param bool        $templates
     * @param string|null $comments
     * @param array|null  $libraries
     *
     * @return void
     */
    public static function initialize(bool $system = true, bool $plugins = true, bool $templates = true, ?string $comments = null, ?array $libraries = null): void
    {
        static::$initializing = true;

        if (FORCE) {
            static::force();
        }

        // Wipe all temporary data and set the core in INIT mode
        Log::setVerbose(true);
        Core::enableInitState();
        Tmp::clear();

        // Ensure the system database exists
        static::ensureSystemsDatabaseAccessible();

        // Go over all system libraries and initialize them, then do the same for the plugins
        static::initializeLibraries($system, $plugins, $templates, $comments, $libraries);

        // Initialization done!
        static::$initializing = false;

        if (Core::isProductionEnvironment()) {
            // Notification developers
            Notification::new()
                        ->setUrl(Url::new('/system/information.html')->makeWww())
                        ->setMode(EnumDisplayMode::info)
                        ->setRoles('developer')
                        ->setTitle(tr('System initialization'))
                        ->setMessage(tr('The system ran an initialization'))
                        ->setDetails([
                                         'system'    => $system,
                                         'plugins'   => $plugins,
                                         'templates' => $templates,
                                         'comment'   => $comments,
                                     ])
                        ->send();
        }

        try {
            // Wipe all cache data
            Cache::clearAll();

        } catch (ConfigPathDoesNotExistsException $e) {
            Log::warning($e->getMessage());
        }

        Log::setVerbose(VERBOSE);
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

        sql()
            ->getSchemaObject()
            ->getDatabaseObject()
            ->drop()
            ->create();

        sql()->use();
    }


    /**
     * Ensure that the systems database exists and is accessible
     *
     * @return void
     */
    protected static function ensureSystemsDatabaseAccessible(): void
    {
        $exists = sql('system', false)->getSchemaObject(false)
                                                          ->getDatabaseObject(use: false)
                                                              ->exists();

        if (!$exists) {
            sql('system', false)
                ->getSchemaObject(false)
                ->getDatabaseObject(use: false)
                ->create();
        }

        // Use the new database, and reset the schema object
        sql('system')->use(true);
        sql('system', false)->resetSchema();
    }


    /**
     * Libraries all libraries for system and plugins
     *
     * @param bool        $system
     * @param bool        $plugins
     * @param bool        $templates
     * @param string|null $comments
     * @param array|null  $filter_libraries
     *
     * @return int
     */
    protected static function initializeLibraries(bool $system = true, bool $plugins = true, bool $templates = true, ?string $comments = null, ?array $filter_libraries = null): int
    {
        // Get a list of all available libraries and their versions
        $libraries      = static::listLibraries($system, $plugins, $templates);
        $post_libraries = $libraries;
        $library_count  = count($libraries);
        $update_count   = 0;

        // First, ensure all libraries have the correct structure
        static::verifyLibraries($libraries);
        Log::action(ts('Initializing libraries'));

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
                Log::success(ts('Finished updates for library ":library"', [
                    ':library' => $library->getName(),
                ]));

                unset($libraries[$directory]);
            }
        }

        // Post initialize all libraries
        // Go over the list of libraries and try to update each one
        if (TEST) {
            Log::warning('Not executing post init files due to test mode');

        } else {
            Log::action(ts('Executing post init updates'));

            foreach ($post_libraries as $library) {
                // Execute the update inits for this library and update the library information and start over
                if ($library->initPost($comments)) {
                    // Library has been post initialized. Break so that we can check which library should be updated next.
                    $update_count++;
                    Log::success(ts('Finished post updates for library ":library"', [
                        ':library' => $library->getName(),
                    ]));
                }
            }
        }

        // Rebuild the command cache
        static::rebuildCommandsCache();

        if (!$update_count) {
            // No libraries were updated
            Log::success(ts('Finished initialization, no libraries were updated'));

        } else {
            Log::success(ts('Finished initialization, executed ":count" updates in ":libraries" libraries', [
                ':count'     => $update_count,
                ':libraries' => $library_count,
            ]));
        }

        return $update_count;
    }


    /**
     * Returns a list with all libraries
     *
     * @param bool $system
     * @param bool $plugins
     * @param bool $templates
     *
     * @return array
     */
    public static function listLibraries(bool $system = true, bool $plugins = true, bool $templates = true): array
    {
        if (!$system and !$plugins and !$templates) {
            throw new OutOfBoundsException(tr('All system, plugins, and templates library paths are filtered out'));
        }

        $return = [];

        Log::action(ts('Scanning libraries'), 3);

        // List system libraries
        if ($system) {
            $return = array_merge($return, static::listLibraryDirectories(static::CLASS_DIRECTORY_SYSTEM));
        }

        // List plugin libraries
        if ($plugins) {
            try {
                $return = array_merge($return, static::listLibraryDirectories(static::CLASS_DIRECTORY_PLUGINS, true));

            } catch (NotExistsException $e) {
                if (PhoDirectory::newPluginsObject()->exists()) {
                    $o_directory = PhoDirectory::new($e->getDataKey('directory'), PhoRestrictions::newPluginsObject());

                    if ($o_directory->isLink()) {
                        Log::warning(ts('Failed to read target link ":link" for plugins vendor ":vendor", check plugins directory ":directory"', [
                            ':link'      => $o_directory->getLinkTarget(),
                            ':vendor'    => $o_directory->getBasename(),
                            ':directory' => PhoDirectory::newPluginsObject(),
                        ]));

                    } else {
                        Log::warning(ts('Failed to read plugins directory for vendor ":vendor", check plugins directory ":directory"', [
                            ':link'      => $o_directory->getLinkTarget(),
                            ':vendor'    => $o_directory->getBasename(),
                            ':directory' => PhoDirectory::newPluginsObject(),
                        ]));
                    }

                } else {
                    // The plugins path does not exist. No biggie, note it in the logs and create it for next time.
                    mkdir(static::CLASS_DIRECTORY_PLUGINS, config()->get('filesystem.mode.default.directory', 0750));
                }
            }
        }

        // List templates libraries
        if ($templates) {
            try {
                $return = array_merge($return, static::listLibraryDirectories(static::CLASS_DIRECTORY_TEMPLATES));

            } catch (NotExistsException $e) {
                // The templates path does not exist. No biggie, note it in the logs and create it for next time.
                mkdir(static::CLASS_DIRECTORY_TEMPLATES, config()->get('filesystem.mode.default.directory', 0750));
            }
        }

        return $return;
    }


    /**
     * Returns a list with all libraries for the specified path
     *
     * @param string $directory
     * @param bool   $has_vendors
     *
     * @todo Should receive an PhoDirectory object instead of a string
     * @return array
     */
    protected static function listLibraryDirectories(string $directory, bool $has_vendors = false): array
    {
        $return    = [];
        $directory = Strings::ensureEndsWith($directory, '/');

        if (!file_exists($directory)) {
            throw NotExistsException::new(tr('The specified library base directory ":directory" does not exist', [
                ':directory' => $directory,
            ]))->addData([
                'directory' => $directory
            ]);
        }

        if ($has_vendors) {
            // Return the libraries for each vendor
            $vendors   = scandir($directory);
            $libraries = [];

            foreach ($vendors as $vendor) {
                if ($vendor[0] === '.') {
                    continue;
                }

                $libraries = array_merge($libraries, static::listLibraryDirectories($directory . $vendor . '/'));
            }

            return $libraries;
        }

        // Build a library list and return it
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
                $return[$file] = new Library(new PhoDirectory($file, PhoRestrictions::newReadonlyObject(DIRECTORY_ROOT)));
            }
        }

        return $return;
    }


    /**
     * Ensure that all libraries have the
     *
     * @param array $libraries
     *
     * @return void
     */
    protected static function verifyLibraries(array $libraries): void
    {
        Log::action(ts('Verifying libraries'));

        foreach ($libraries as $library) {
            $library->verify();
        }
    }


    /**
     * Order the libraries by next_init_version first
     *
     * @param array      $libraries
     * @param array|null $filter_libraries
     *
     * @return void
     */
    protected static function orderAndFilterLibraries(array &$libraries, ?array $filter_libraries = null): void
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
     * Rebuilds the PHO command cache
     *
     * @return void
     */
    public static function rebuildCommandsCache(): void
    {
        static::clearCommandsCache();
        Log::action(ts('Rebuilding command cache'), 4);

        // Get temporary directory to build cache and the current cache directory
        $o_temporary = PhoDirectory::newTemporaryObject();
        $o_cache     = PhoDirectory::new(DIRECTORY_COMMANDS, PhoRestrictions::newWritableObject([
            DIRECTORY_COMMANDS,
            DIRECTORY_TMP,
            DIRECTORY_ROOT . 'commands/'
        ]));

        if ($o_cache->exists()) {
            // Replace the temporary directory with the cache directory contents
            $o_temporary = $o_temporary->delete()->getParentDirectoryObject()->ensure();
            $o_cache->copy($o_temporary);
        }

        foreach (static::listLibraries() as $library) {
            $library->rebuildCommandsCache($o_cache, $o_temporary);
        }

        $o_target = PhoFile::new(DIRECTORY_ROOT . 'commands', PhoRestrictions::newRootObject(true))->delete();

        // Move the old out of the way, push the new in and ensure we have a root directory link
        $o_cache->replaceWithPath($o_temporary)
                ->symlinkTargetFromThis($o_target);

        static::$cache_has_been_rebuilt = true;

        Log::success(ts('Finished rebuilding command cache'));
    }


    /**
     * Deletes the PHO commands cache
     *
     * @return void
     */
    public static function clearCommandsCache(): void
    {
        Log::action(ts('Clearing commands caches (symlinks only)'), 3);

        PhoDirectory::new(DIRECTORY_COMMANDS, PhoRestrictions::newFilesystemRootObject(true))
                    ->clearTreeSymlinks(true)
                    ->ensure();

        static::$cache_has_been_cleared = true;
    }


    /**
     * Rebuilds the PHO hook cache
     *
     * @return void
     */
    public static function rebuildHooksCache(): void
    {
        static::clearHooksCache();

        Log::action(ts('Rebuilding hook cache'), 4);

        // Get temporary directory to build cache and the current cache directory
        $o_temporary = PhoDirectory::newTemporaryObject();
        $o_cache     = PhoDirectory::new(DIRECTORY_HOOKS, PhoRestrictions::newWritableObject([
            DIRECTORY_HOOKS,
            DIRECTORY_TMP,
            DIRECTORY_ROOT . 'hooks/'
        ]));

        if ($o_cache->exists()) {
            // Replace the temporary directory with the cache directory contents
            $o_temporary = $o_temporary->delete()->getParentDirectoryObject()->ensure();
            $o_cache->copy($o_temporary);
        }

        foreach (static::listLibraries() as $library) {
            $library->rebuildHooksCache($o_cache, $o_temporary);
        }

        $o_target = PhoFile::new(DIRECTORY_ROOT . 'hooks', PhoRestrictions::newRootObject(true))->delete();

        // Move the old out of the way, push the new in and ensure we have a root directory link
        $o_cache->replaceWithPath($o_temporary)
                ->symlinkTargetFromThis($o_target);

        static::$cache_has_been_rebuilt = true;

        Log::success(ts('Finished rebuilding hook cache'));
    }


    /**
     * Deletes the PHO hooks cache
     *
     * @return void
     */
    public static function clearHooksCache(): void
    {
        Log::action(ts('Clearing hooks caches (symlinks only)'), 3);

        PhoDirectory::new(DIRECTORY_HOOKS, PhoRestrictions::newFilesystemRootObject(true))
                   ->clearTreeSymlinks(true)
                   ->ensure();

        static::$cache_has_been_cleared = true;
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
     *
     * @param string $library
     * @param bool   $system
     * @param bool   $plugin
     * @param bool   $template
     *
     * @return Library
     */
    public static function findLibrary(string $library, bool $system = true, bool $plugin = true, bool $template = true): Library
    {
        $return      = null;
        $directories = [];

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
        foreach ($directories as $directory) {
            $directory = Strings::slash($directory);

            // Library must exist and be a directory
            if (file_exists($directory . $library)) {
                if (is_dir($directory . $library)) {
                    if ($return) {
                        throw new OutOfBoundsException(tr('The specified library ":library" is both a system library and a plugin', [
                            ':library' => $library,
                        ]));
                    }

                    $return = new Library(new PhoDirectory($directory . $library, PhoRestrictions::newReadonlyObject($directory)));
                }
            }
        }

        if ($return) {
            return $return;
        }

        throw NotExistsException::new(tr('The specified library ":library" does not exist', [
            ':library' => $library,
        ]))->makeWarning();
    }


    /**
     * Returns the PhpStatistics object for this library
     *
     * @param bool $system
     * @param bool $plugin
     * @param bool $template
     *
     * @todo Fix PhoRestrictions!!!!! Are now for filesystem root, which negates any protections!
     * @return array
     */
    public static function getPhpStatistics(bool $system = true, bool $plugin = true, bool $template = true): array
    {
        $return = ['totals' => []];

        if ($system) {
            // Get statistics for all system libraries
            $return['system'] = PhoDirectory::new(LIBRARIES::CLASS_DIRECTORY_SYSTEM, PhoRestrictions::newFilesystemRootObject())->getPhpStatistics(true);
            $return['totals'] = Arrays::addValues($return['totals'], $return['system']);
        }

        if ($plugin) {
            // Get statistics for all plugin libraries
            $return['plugins'] = PhoDirectory::new(LIBRARIES::CLASS_DIRECTORY_PLUGINS, PhoRestrictions::newFilesystemRootObject())->getPhpStatistics(true);
            $return['totals']  = Arrays::addValues($return['totals'], $return['plugins']);
        }

        if ($template) {
            // Get statistics for all template libraries
            $return['templates'] = PhoDirectory::new(LIBRARIES::CLASS_DIRECTORY_TEMPLATES, PhoRestrictions::newFilesystemRootObject())->getPhpStatistics(true);
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

        $table->getHeaders()
              ->setSource([
                  tr('Library'),
                  tr('Version'),
                  tr('Description'),
              ]);

        return $table;
    }


    /**
     * Rebuilds the web pages cache
     *
     * @return void
     */
    public static function rebuildWebCache(): void
    {
        static::clearWebCache();

        Log::action(ts('Rebuilding web cache'), 4);

        // Get temporary directory to build cache and the current cache directory
        $o_temporary = PhoDirectory::newTemporaryObject();
        $o_cache     = PhoDirectory::new(DIRECTORY_WEB, PhoRestrictions::newWritableObject([
            DIRECTORY_WEB,
            DIRECTORY_TMP,
            DIRECTORY_ROOT . 'web/'
        ]));

        if ($o_cache->exists()) {
            // Replace the temporary directory with the cache directory contents
            $o_temporary = $o_temporary->delete()->getParentDirectoryObject()->ensure();
            $o_cache->copy($o_temporary);
        }

        foreach (static::listLibraries() as $library) {
            $library->rebuildWebCache($o_cache, $o_temporary);
        }

        $o_target = PhoFile::new(DIRECTORY_ROOT . 'web', PhoRestrictions::newRootObject(true))->delete();

        // Move the old out of the way, push the new in and ensure we have a root directory link
        $o_cache->replaceWithPath($o_temporary)
                ->symlinkTargetFromThis($o_target);

        Log::success(ts('Finished rebuilding web cache'));
    }


    /**
     * Deletes the web pages cache
     *
     * @return void
     */
    public static function clearWebCache(): void
    {
        Log::action(ts('Clearing web caches (symlinks only)'), 3);

        PhoDirectory::new(DIRECTORY_WEB, PhoRestrictions::newFilesystemRootObject(true))
                    ->clearTreeSymlinks(true)
                    ->ensure();
    }


    /**
     * Rebuilds the data cache
     *
     * @return void
     * @todo Properly implement, symlink only certain parts of data/ as data/ itself CONTAINS the symlinks in data/system/cache. Also data/log etc should not be symlinked!
     */
    public static function rebuildDataCache(): void
    {
return;
        static::clearDataCache();

        Log::action(ts('Rebuilding data cache'), 4);

        // Get temporary directory to build cache and the current cache directory
        $o_temporary = PhoDirectory::newTemporaryObject();
        $o_cache     = PhoDirectory::newDataObject(true);

        if ($o_cache->exists()) {
            // Replace the temporary directory with the cache directory contents
            $o_temporary = $o_temporary->delete()->getParentDirectoryObject()->ensure();
            $o_cache->copy($o_temporary);
        }

        foreach (static::listLibraries() as $library) {
            $library->rebuildDataCache($o_cache, $o_temporary);
        }

        $o_target = PhoFile::new(DIRECTORY_ROOT . 'data', PhoRestrictions::newRootObject(true))->delete();

        // Move the old out of the way, push the new in and ensure we have a root directory link
        $o_cache->replaceWithPath($o_temporary)
                ->symlinkTargetFromThis($o_target);

        Log::success(ts('Finished rebuilding data cache'));
    }


    /**
     * Deletes the data cache
     *
     * @return void
     */
    public static function clearDataCache(): void
    {
        Log::action(ts('Clearing data caches (symlinks only)'), 3);

        PhoDirectory::new(DIRECTORY_WEB, PhoRestrictions::newFilesystemRootObject(true))
                    ->clearTreeSymlinks(true)
                    ->ensure();
    }


    /**
     * Rebuilds the config cache
     *
     * @return void
     * @todo Properly implement, requires moving config directory as well, probably
     */
    public static function rebuildConfigCache(): void
    {
return;
        static::clearConfigCache();

        Log::action(ts('Rebuilding config cache'), 4);

        // Get temporary directory to build cache and the current cache directory
        $o_temporary = PhoDirectory::newTemporaryObject();
        $o_cache     = PhoDirectory::new(DIRECTORY_WEB, PhoRestrictions::newWritableObject([
            DIRECTORY_WEB,
            DIRECTORY_TMP,
            DIRECTORY_ROOT . 'config/'
        ]));

        if ($o_cache->exists()) {
            // Replace the temporary directory with the cache directory contents
            $o_temporary = $o_temporary->delete()->getParentDirectoryObject()->ensure();
            $o_cache->copy($o_temporary);
        }

        foreach (static::listLibraries() as $library) {
            $library->rebuildConfigCache($o_cache, $o_temporary);
        }

        $o_target = PhoFile::new(DIRECTORY_ROOT . 'config', PhoRestrictions::newRootObject(true))->delete();

        // Move the old out of the way, push the new in and ensure we have a root directory link
        $o_cache->replaceWithPath($o_temporary)
                ->symlinkTargetFromThis($o_target);

        Log::success(ts('Finished rebuilding config cache'));
    }


    /**
     * Deletes the config cache
     *
     * @return void
     */
    public static function clearConfigCache(): void
    {
        Log::action(ts('Clearing config caches (symlinks only)'), 3);

        PhoDirectory::new(DIRECTORY_WEB, PhoRestrictions::newFilesystemRootObject(true))
                    ->clearTreeSymlinks(true)
                    ->ensure();
    }


    /**
     * Rebuilds the cron pages cache
     *
     * @return void
     */
    public static function rebuildCronCache(): void
    {
        static::clearCronCache();

        Log::action(ts('Rebuilding cron cache'), 4);

        // Get temporary directory to build cache and the current cache directory
        $o_temporary = PhoDirectory::newTemporaryObject();
        $o_cache     = PhoDirectory::new(DIRECTORY_CRON, PhoRestrictions::newWritableObject([
            DIRECTORY_CRON,
            DIRECTORY_TMP,
            DIRECTORY_ROOT . 'cron/'
        ]));

        if ($o_cache->exists()) {
            // Replace the temporary directory with the cache directory contents
            $o_temporary = $o_temporary->delete()->getParentDirectoryObject()->ensure();
            $o_cache->copy($o_temporary);
        }

        foreach (static::listLibraries() as $library) {
            $library->rebuildCronCache($o_cache, $o_temporary);
        }

        $o_target = PhoFile::new(DIRECTORY_ROOT . 'cron', PhoRestrictions::newRootObject(true))->delete();

        // Move the old out of the way, push the new in and ensure we have a root directory link
        $o_cache->replaceWithPath($o_temporary)
                ->symlinkTargetFromThis($o_target);

        Log::success(ts('Finished rebuilding cron cache'));
    }


    /**
     * Deletes the cron pages cache
     *
     * @return void
     */
    public static function clearCronCache(): void
    {
        Log::action(ts('Clearing cron caches (symlinks only)'), 3);

        PhoDirectory::new(DIRECTORY_CRON, PhoRestrictions::newFilesystemRootObject(true))
                    ->clearTreeSymlinks(true)
                    ->ensure();
    }


    /**
     * Rebuilds the Tests cache
     *
     * @return void
     */
    public static function rebuildTestsCache(): void
    {
        static::clearTestsCache();

        Log::action(ts('Rebuilding Tests cache'), 4);

        // Get temporary directory to build cache and the current cache directory
        $o_temporary = PhoDirectory::newTemporaryObject();
        $o_cache     = PhoDirectory::new(DIRECTORY_SYSTEM . 'cache/system/Tests', PhoRestrictions::newWritableObject([
            DIRECTORY_SYSTEM . 'cache/system/Tests',
            DIRECTORY_TMP,
            DIRECTORY_ROOT . 'Tests/'
        ]));

        if ($o_cache->exists()) {
            // Replace the temporary directory with the cache directory contents
            $o_temporary = $o_temporary->delete()->getParentDirectoryObject()->ensure();
            $o_cache->copy($o_temporary);
        }

        foreach (static::listLibraries() as $library) {
            $library->rebuildTestsCache($o_cache, $o_temporary);
        }

        $o_target = PhoFile::new(DIRECTORY_ROOT . 'Tests', PhoRestrictions::newRootObject(true))->delete();

        // Move the old out of the way, push the new in and ensure we have a root directory link
        $o_cache->replaceWithPath($o_temporary)
                ->symlinkTargetFromThis($o_target);

        Log::success(ts('Finished rebuilding Tests cache'));
    }


    /**
     * Deletes the Tests cache
     *
     * @return void
     */
    public static function clearTestsCache(): void
    {
        Log::action(ts('Clearing test caches (symlinks only)'), 3);

        PhoDirectory::new(DIRECTORY_TESTS, PhoRestrictions::newFilesystemRootObject(true))
                    ->clearTreeSymlinks(true)
                    ->ensure();
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

        Log::action(ts('Pre-loading all library classes into memory'));

        Find::new(PhoDirectory::new(
            $path,
            PhoRestrictions::newReadonlyObject($path)
        ))->setType('f')
          ->setName('*.php')
          ->setCallback(function ($file) {
                $test  = strtolower($file);
                $tests = [
                    'Tests/bootstrap.php',
                ];

                // Don't loads file in the LIBRARY/Library path
                if (str_contains($test, '/library/')) {
                    return;
                }

                // Don't load the following specific files
                if (str_ends_with($test, 'Tests/bootstrap.php')) {
                    return;
                }

                try {
                    Log::action(ts('Attempting to pre-loading library file ":file"', [
                        ':file' => Strings::from($file, DIRECTORY_ROOT),
                    ]), 2);

                    require_once($file);

                } catch (Throwable $e) {
                    Log::warning(ts('Pre-loading system library file ":file" caused exception ":message" at ":file@:line", ignoring', [
                        ':file'    => Strings::from($e->getFile(), DIRECTORY_ROOT),
                        ':message' => $e->getMessage(),
                        ':line'    => $e->getLine(),
                    ]), 4);
                }
            })
            ->executeNoReturn();
    }


    /**
     * Loads all Phoundation Plugins and Templates classes into memory
     *
     * @return void
     */
    public static function loadAllPluginClassesIntoMemory(): void
    {
        $paths = [
            DIRECTORY_ROOT . 'Plugins/'   => tr('Plugins'),
            DIRECTORY_ROOT . 'Templates/' => tr('Templates'),
        ];

        foreach ($paths as $path => $type) {
            Log::action(ts('Pre-loading all ":type" classes into memory', [
                ':type' => $type,
            ]));

            Find::new(PhoDirectory::new(
                $path,
                PhoRestrictions::newReadonlyObject($path)
            ))->setType('f')
              ->setName('*.php')
              ->setCallback(function ($file) {
                    $test  = strtolower($file);
                    $tests = [
                        'Tests/bootstrap.php',
                    ];

                    // Don't loads file in the LIBRARY/Library path
                    if (str_contains($test, '/library/')) {
                        return;
                    }

                    // Don't load the following specific files
                    if (str_ends_with($test, 'Tests/bootstrap.php')) {
                        return;
                    }

                    try {
                        Log::action(ts('Attempting to pre-loading library file ":file"', [
                            ':file' => Strings::from($file, DIRECTORY_ROOT),
                        ]), 2);

                        require_once($file);

                    } catch (Throwable $e) {
                        Log::warning(ts('Pre-loading plugin library file ":file" caused exception ":message" at ":file@:line", ignoring', [
                            ':file'    => Strings::from($file, DIRECTORY_ROOT),
                            ':message' => $e->getMessage(),
                            ':line'    => $e->getLine(),
                        ]), 4);
                    }
                })
                ->executeNoReturn();
        }
    }


    /**
     * Detects the vendor for the specified library
     *
     * @note Will throw a LibraryNotFoundException if the specified library does not exist
     *
     * @note Will throw a LibraryMultipleVendorsException if multiple vendors have a library with the specified name
     *
     * @param string $library
     *
     * @return string
     */
    public static function detectVendor(string $library): string
    {
        $libraries = Libraries::listLibraries();
        $library   = strtolower($library);

        foreach ($libraries as $library_name => $o_library) {
            $library_name = Strings::untilReverse($library_name, '/');
            $library_name = Strings::fromReverse($library_name, '/');
            $library_name = strtolower($library_name);

            if ($library === $library_name) {
                if (empty($vendor)) {
                    $vendor = $o_library->getVendor();
                    continue;
                }

                throw LibraryMultipleVendorsException::new(tr('Failed to find vendor for library ":library", multiple vendors have a library with that name', [
                    ':library' => $library,
                ]))->setData([
                    'vendors' => [
                        $vendor,
                        $o_library->getVendor()
                    ]
                ]);
            }
        }

        if (empty($vendor)) {
            throw new LibraryNotFoundException(tr('Failed to find vendor for library ":library", the library could not be found', [
                ':library' => $library,
            ]));
        }

        return $vendor;
    }


    /**
     * Returns true when the system has initialized to the point that core_versions supports vendors
     *
     * @param bool $force
     *
     * @return bool
     */
    public static function supportsVendors(bool $force = false): bool
    {
        static $true = false;

        if ($true) {
            return true;
        }

        if ($force) {
            // Some outside function just told us that as of now, vendors are supported!
            $true = true;

        } else {
            $version = (int) sql()->getColumn('SELECT MAX(`version`) AS `version` 
                                               FROM   `core_versions` 
                                               WHERE  `library` = "core"');

            if ($version >= 6000) {
                // Once core_versions supports vendors, it will ALWAYS support vendors, we're done!
                $true = true;
            }
        }

        return $true;

    }


    /**
     * Returns true when the system has initialized to the point that core_versions supports the project_version column
     * and the phoundation_version column
     *
     * @param bool $force
     *
     * @return bool
     */
    public static function supportsPhoundationVersions(bool $force = false): bool
    {
        static $true = false;

        if ($true) {
            return true;
        }

        if ($force) {
            // Some outside function just told us that as of now, vendors are supported!
            $true = true;

        } else {
            $version = (int) sql()->getColumn('SELECT MAX(`version`) AS `version` 
                                               FROM   `core_versions` 
                                               WHERE  `library` = "core"');

            if ($version >= 9000) {
                // Once core_versions supports vendors (0.9.0 and up), it will ALWAYS support vendors, we're done!
                $true = true;
            }
        }

        return $true;
    }
}
