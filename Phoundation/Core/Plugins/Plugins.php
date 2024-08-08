<?php

/**
 * Class Plugin
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */

declare(strict_types=1);

namespace Phoundation\Core\Plugins;

use Phoundation\Core\Libraries\Library;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Plugins\Interfaces\PluginsInterface;
use Phoundation\Data\DataEntry\DataIterator;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Developer\Debug;
use Phoundation\Exception\Exception;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Throwable;

class Plugins extends DataIterator implements PluginsInterface
{
    /**
     * A cached list of enabled plugins
     *
     * @var array|null $enabled
     */
    protected ?array $enabled = null;


    /**
     * Providers class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT   `id`, 
                                        `vendor`, 
                                        `name`, 
                                        `status`, 
                                        `priority`, 
                                        `description` 
                               FROM     `core_plugins` 
                               ORDER BY `name`');

        parent::__construct();
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'core_plugins';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string|null
     */
    public static function getEntryClass(): ?string
    {
        return Plugin::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'name';
    }


    /**
     * @return void
     */
    public static function setup(): void
    {
        static::new()
              ->erase()
              ->scan();
    }


    /**
     * Scans the Plugins/ directory for available plugins
     *
     * This method ensures all available plugins are registered in the database and that any plugin registration
     * that no longer exists is removed from the database
     *
     * @return PluginsInterface
     */
    public static function scan(): PluginsInterface
    {
        foreach (static::scanPluginsPath() as $name => $class) {
            try {
                $plugin = $class::new($name, 'name');

                if ($plugin->isNew()) {
                    $plugin->register();
                }

            } catch (Throwable $e) {
                Log::warning(tr('Failed to read plugin ":plugin" because of the following exception. Ignoring it.', [
                    ':plugin' => $name,
                ]));
                Log::error($e);
            }
        }

        return static::new()->load();
    }


    /**
     * Returns all available plugins in the Plugins/ directory
     *
     * @return array
     */
    protected static function scanPluginsPath(): array
    {
        $directory = DIRECTORY_ROOT . 'Plugins/';
        $return    = [];
        $vendors   = scandir($directory);

        foreach ($vendors as $vendor) {
            // Filter . .. and hidden files
            if (str_starts_with($vendor, '.')) {
                continue;
            }

            $plugins = scandir($directory . $vendor);

            foreach ($plugins as $id => $plugin) {
                // Filter . .. and hidden files
                if (str_starts_with($plugin, '.')) {
                    continue;
                }

                $file = $directory . $vendor . '/' . $plugin . '/Library/Plugin.php';

                if ($plugin === 'disabled') {
                    // The "disabled" directory is for disabled plugins, ignore it completely
                    continue;
                }

                // Are these valid plugins? Valid plugins must have name uppercase first letter and upper/lowercase rest,
                // must have Plugin.php file available that is subclass of \Phoundation\Core\Plugin
                if (!preg_match('/^[A-Z][a-zA-Z]+$/', $plugin)) {
                    Log::warning(tr('Ignoring plugin ":vendor/:plugin", the name is invalid. It should have a valid CamelCase type name', [
                        ':vendor' => $vendor,
                        ':plugin' => $plugin,
                    ]), 9);
                    continue;
                }

                if (!file_exists($file)) {
                    Log::warning(tr('Ignoring plugin ":vendor/:plugin", it has no required Plugin.php file in the Library/ directory', [
                        ':vendor' => $vendor,
                        ':plugin' => $plugin,
                    ]), 3);
                    continue;
                }

                $class = Library::getClassPath($file);
                include_once($file);

                // Ensure that the class directory matches the file directory
                if (!static::classPathMatchesFilePath($class, $file)) {
                    Log::warning(tr('Ignoring plugin ":vendor/:plugin", the Plugin.php file has class directory ":class" which does not match its file directory ":file"', [
                        ':vendor' => $vendor,
                        ':plugin' => $plugin,
                        ':file'   => Strings::from($file, DIRECTORY_ROOT),
                        ':class'  => $class,
                    ]));

                    continue;
                }

                if (!is_subclass_of($class, Plugin::class)) {
                    Log::warning(tr('Ignoring plugin ":vendor/:plugin", the Plugin.php file contains a class that is not a subclass of ":class"', [
                        ':vendor' => $vendor,
                        ':plugin' => $plugin,
                        ':class'  => Plugin::class,
                    ]));

                    continue;
                }

                $return[$plugin] = $class;
            }
        }

        return $return;
    }


    /**
     * Returns true if the specified class directory matches the file directory
     *
     * @param string $class
     * @param string $file
     *
     * @return bool
     */
    protected static function classPathMatchesFilePath(string $class, string $file): bool
    {
        $class = str_replace('\\', '/', $class);
        $file  = Strings::cut($file, DIRECTORY_ROOT, '.php');

        return $class === $file;
    }


    /**
     * Starts all enabled plugins
     *
     * @return void
     */
    public static function start(): void
    {
        foreach (static::getEnabled() as $id => $plugin) {
            try {
                if ($plugin['status'] === null) {
                    Log::action(tr('Starting plugin ":vendor/:plugin"', [
                        ':vendor' => $plugin['vendor'],
                        ':plugin' => $plugin['name'],
                    ]), 4);

                    include_once(DIRECTORY_ROOT . $plugin['directory'] . 'Library/Plugin.php');
                    $plugin['class']::start();
                }

            } catch (Throwable $e) {
                // Plugin failed to load, we MIGHT disable it automatically to avoid loads of errors in the log files
                // Do a LOT of logging here to ensure its clear what is happening and why
                Log::error(tr('Failed to start plugin ":vendor/:plugin" because of next exception', [
                    ':vendor' => $plugin['vendor'],
                    ':plugin' => $plugin['name'],
                ]));

                Log::error($e);

                if (Config::getBoolean('plugins.error.startup.disable', true)) {
                    if (!Debug::isEnabled()) {
                        Log::warning(tr('Disabling plugin ":vendor/:plugin" because it failed on startup', [
                            ':vendor' => $plugin['vendor'],
                            ':plugin' => $plugin['name'],
                        ]));

                        Plugin::new($id)->disable();

                    } else {
                        Log::warning(tr('NOT automatically disabling failed plugin ":vendor/:plugin" because the system is running in debug mode', [
                            ':vendor' => $plugin['vendor'],
                            ':plugin' => $plugin['name'],
                        ]));
                    }

                } else {
                    Log::warning(tr('NOT automatically disabling failed plugin ":vendor/:plugin" because the option to do so has been disabled with configuration path "plugins.error.startup.disable"', [
                        ':vendor' => $plugin['vendor'],
                        ':plugin' => $plugin['name'],
                    ]));
                }
            }
        }
    }


    /**
     * Returns an array with all enabled plugins from the database
     *
     * @return IteratorInterface
     */
    public static function getEnabled(): IteratorInterface
    {
        $return = sql()->list('SELECT   `id`, 
                                              `name`,
                                              `status`, 
                                              `priority`, 
                                              `vendor`, 
                                              `class`, 
                                              `directory`
                                     FROM     `core_plugins` 
                                     WHERE    `name`    != "Phoundation"
                                     AND      `status`  IS NULL 
                                     ORDER BY `priority` ASC');
        if (!$return) {
            // Phoundation plugin is ALWAYS enabled
            return new Iterator([static::getPhoundationPluginEntry()]);
        }
        // Push Phoundation plugin to the front of the list
        $return = array_replace([static::getPhoundationPluginEntry()], $return);

        return new Iterator($return);
    }


    /**
     * Returns the phoundation plugin entry
     *
     * @return array[]
     */
    protected static function getPhoundationPluginEntry(): array
    {
        return [
            'vendor'    => 'Phoundation',
            'name'      => 'Phoundation',
            'status'    => null,
            'priority'  => 0,
            'class'     => 'Plugins\Phoundation\Phoundation\Library\Plugin',
            'directory' => 'Plugins/Phoundation/Phoundation/',
        ];
    }


    /**
     * Loads all plugins from the database and returns them in an array
     *
     * @return IteratorInterface
     */
    public static function getAvailable(): IteratorInterface
    {
        $return = sql()->list('SELECT   `id`, 
                                              `name`, 
                                              `status`, 
                                              `priority`, 
                                              `vendor`, 
                                              `class`, 
                                              `directory`
                                     FROM     `core_plugins`
                                     WHERE    `name` != "Phoundation" 
                                     ORDER BY `priority` ASC');

        if (!$return) {
            // Phoundation plugin is ALWAYS enabled
            return new Iterator([static::getPhoundationPluginEntry()]);
        }

        // Push Phoundation plugin to the front of the list
        $return = array_replace([static::getPhoundationPluginEntry()], $return);

        return new Iterator($return);
    }


    /**
     * Purges all plugins from the DIRECTORY_ROOT/Plugins directory
     *
     * @return static
     */
    public function purge(): static
    {
        // Delete all plugins from disk
        $directory = DIRECTORY_ROOT . 'Plugins/';

        FsFile::new($directory)->delete();
        FsDirectory::new($directory)->ensure();

        return $this;
    }


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string      $value_column
     * @param string|null $key_column
     * @param string|null $order
     * @param array|null  $joins
     * @param array|null  $filters
     *
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', ?string $key_column = 'id', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface
    {
        return parent::getHtmlSelect($value_column, $key_column, $order, $joins, $filters)
                     ->setName('plugins_id')
                     ->setNotSelectedLabel(tr('Select a plugin'))
                     ->setComponentEmptyLabel(tr('No plugins available'));
    }
}
