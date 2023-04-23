<?php

namespace Phoundation\Core\Plugins;

use Phoundation\Core\Libraries\Library;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Path;
use Throwable;

/**
 * Class Plugin
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Plugins extends DataList
{
    /**
     * A cached list of enabled plugins
     *
     * @var array|null $enabled
     */
    protected ?array $enabled = null;

    /**
     * Providers class constructor
     *
     * @param Plugin|null $parent
     * @param string|null $id_column
     */
    public function __construct(?Plugin $parent = null, ?string $id_column = null)
    {
        $this->entry_class = Plugin::class;
        $this->table_name  = 'core_plugins';

        $this->setHtmlQuery('SELECT   `id`, `name`, COALESCE(`status`, "' . tr('Enabled') . '") AS `status`, IF(`start`, "' . tr('Yes') . '", "' . tr('No') . '") AS `start`, `priority`, `description` 
                                   FROM     `core_plugins` 
                                   ORDER BY `name`');
        parent::__construct($parent, $id_column);
    }


    /**
     * @return void
     */
    public static function setup(): void
    {
        self::clear();
        self::scan();
    }


    /**
     * Scans the Plugins/ directory for available plugins
     *
     * This method ensures all available plugins are registered in the database and that any plugin registration
     * that no longer exists is removed from the database
     *
     * @return int
     */
    public static function scan(): int
    {
        $count = 0;

        foreach (self::getPlugins() as $name => $class) {
            try {
                $plugin = $class::new($name);

                if (!$plugin->getId()) {
                    $plugin->register();
                    $count++;
                }
            } catch (Throwable $e) {
                Log::warning(tr('Failed to read plugin ":plugin" because of the following exception. Ignoring it.', [
                    ':plugin' => $name
                ]));

                Log::error($e);
            }
        }

        return $count;
    }


    /**
     * Starts all enabled plugins
     *
     * @return void
     */
    public static function start(): void
    {
        foreach (self::getEnabled() as $name => $plugin) {
            if ($plugin['enabled']) {
                Log::action(tr('Starting plugin ":plugin"', [':plugin' => $name]), 3);
                include_once($plugin['path'] . 'Plugin.php');
                $plugin['class']::start();
            }
        }
    }


//    /**
//     * Starts CLI all enabled plugins
//     *
//     * @return void
//     */
//    public static function startCli(): void
//    {
//        foreach (self::getEnabled() as $name => $plugin) {
//            Log::action(tr('Starting CLI on plugin ":plugin"', [':plugin' => $name]), 3);
//            $plugin['class']::startCli();
//        }
//    }
//
//
//
//    /**
//     * Starts HTTP for all enabled plugins
//     *
//     * @return void
//     */
//    public static function startHttp(): void
//    {
//        foreach (self::getEnabled() as $name => $plugin) {
//            Log::action(tr('Starting HTTP on plugin ":plugin"', [':plugin' => $name]));
//            $plugin['class']::startHttp();
//        }
//    }


    /**
     * Loads all plugins from the database and returns them in an array
     *
     * @return array
     */
    public static function getAvailable(): array
    {
        return sql()->list('SELECT   `name`, `status`, `priority`, `path`, `class` 
                                  FROM     `core_plugins` 
                                  ORDER BY `priority`');
    }


    /**
     * Returns an array with all enabled plugins from the database
     *
     * @return array
     */
    public static function getEnabled(): array
    {
        $return = sql()->list('SELECT   `name` AS `id`, `name`, `path`, `class`, `enabled` 
                                     FROM     `core_plugins` 
                                     WHERE    `status` IS NULL 
                                     ORDER BY `priority` ASC');

        if (!$return) {
            // Phoundation plugin is ALWAYS enabled
            return [
                'phoundation' => [
                    'path'    => PATH_ROOT . '/Plugins/Phoundation/',
                    'class'   => 'Plugins\Phoundation\Plugin',
                    'enabled' => true
                ]
            ];
        }

        return $return;
    }


    /**
     * Clears all plugins from only the database
     *
     * @return void
     */
    public static function clear(): void
    {
        sql()->query('DELETE FROM `core_plugins`');
    }


    /**
     * Purges all plugins from both database and the PATH_ROOT/Plugins path
     *
     * @return void
     */
    public static function purge(): void
    {
        $path = PATH_ROOT . 'Plugins/';

        self::clear();
        File::new($path)->delete();
        Path::new($path)->ensure();
    }


    /**
     * Returns all available plugins in the Plugins/ path
     *
     * @return array
     */
    protected static function getPlugins(): array
    {
        $path    = PATH_ROOT . 'Plugins/';
        $return  = [];
        $plugins = scandir($path);

        foreach ($plugins as $id => $plugin) {
            // Filter . .. and hidden files
            if (str_starts_with($plugin, '.')) {
                continue;
            }

            $file = $path . $plugin . '/Plugin.php';

            // Are these valid plugins? Valid plugins must have name uppercase first letter and upper/lowercase rest,
            // must have Plugin.php file available that is subclass of \Phoundation\Core\Plugin
            if (!preg_match('/^[A-Z][a-zA-Z]+$/', $plugin)) {
                Log::warning(tr('Ignoring possible plugin ":plugin", the name is invalid. It should have a valid CamelCase type name', [
                    ':plugin' => $plugin
                ]));

                continue;
            }

            if (!file_exists($file)) {
                Log::warning(tr('Ignoring possible plugin ":plugin", it has no required Plugin.php file', [
                    ':plugin' => $plugin
                ]));

                continue;
            }

            $class = Library::getClassPath($file);
            include_once($file);

            if (!is_subclass_of($class, Plugin::class)) {
                Log::warning(tr('Ignoring possible plugin ":plugin", the Plugin.php file contains a class that is not a subclass of ":class"', [
                    ':plugin' => $plugin,
                    ':class'  => Plugin::class
                ]));

                continue;
            }

            $return[$plugin] = $class;
        }

        return $return;
    }


    /**
     * @inheritDoc
     */
    protected function load(string|int|null $id_column = null): static
    {
        // TODO: Implement load() method.
    }


    /**
     * @inheritDoc
     */
    protected function loadDetails(array|string|null $columns, array $filters = []): array
    {
        // TODO: Implement loadDetails() method.
    }


    /**
     * @inheritDoc
     */
    public function save(): static
    {
        // TODO: Implement save() method.
    }
}