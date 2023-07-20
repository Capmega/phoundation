<?php

declare(strict_types=1);

namespace Phoundation\Core\Plugins;

use PDOStatement;
use Phoundation\Core\Libraries\Library;
use Phoundation\Core\Locale\Language\Language;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Path;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;
use Throwable;


/**
 * Class Plugin
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     */
    public function __construct()
    {
        $this->setQuery('SELECT   `id`, `name`, IFNULL(`status`, "' . tr('Ok') . '") AS `status`, IF(`enabled`, "' . tr('Enabled') . '", "' . tr('Disabled') . '") AS `enabled`, `priority`, `description` 
                               FROM     `core_plugins` 
                               ORDER BY `name`');

        parent::__construct();
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'core_plugins';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryClass(): string
    {
        return Plugin::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return 'name';
    }


    /**
     * @return void
     */
    public static function setup(): void
    {
        static::clear();
        static::scan();
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

        foreach (static::getPlugins() as $name => $class) {
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
        foreach (static::getEnabled() as $plugin) {
            if ($plugin['enabled']) {
                Log::action(tr('Starting plugin ":plugin"', [':plugin' => $plugin['name']]), 9);
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
//        foreach (static::getEnabled() as $name => $plugin) {
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
//        foreach (static::getEnabled() as $name => $plugin) {
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
        $return = sql()->list('SELECT   `id`, 
                                              `name`, 
                                              IF(`status` IS NULL, "' . tr('Ok') . '"     , "' . tr('Failed') . '")   AS `status`, 
                                              IF(`enabled` = 1   , "' . tr('Enabled') . '", "' . tr('Disabled') . '") AS `enabled`, 
                                              `priority`, 
                                              `class`, 
                                              `path`
                                     FROM     `core_plugins`
                                     WHERE    `name` != "Phoundation" 
                                     ORDER BY `priority` ASC');

        if (!$return) {
            // Phoundation plugin is ALWAYS enabled
            return [static::getPhoundationPluginEntry()];
        }

        // Push Phoundation plugin to the front of the list
        array_unshift($return, static::getPhoundationPluginEntry());

        return $return;
    }


    /**
     * Returns an array with all enabled plugins from the database
     *
     * @return array
     */
    public static function getEnabled(): array
    {
        $return = sql()->list('SELECT   `id`, 
                                              `name`, 
                                              IF(`status` IS NULL, "' . tr('Ok') . '"     , "' . tr('Failed') . '")   AS `status`, 
                                              IF(`enabled` = 1   , "' . tr('Enabled') . '", "' . tr('Disabled') . '") AS `enabled`, 
                                              `priority`, 
                                              `class`, 
                                              `path`
                                     FROM     `core_plugins` 
                                     WHERE    `name`    != "Phoundation"
                                     AND      `status`  IS NULL 
                                       AND    `enabled` != 0  
                                     ORDER BY `priority` ASC');

        if (!$return) {
            // Phoundation plugin is ALWAYS enabled
            return [static::getPhoundationPluginEntry()];
        }

        // Push Phoundation plugin to the front of the list
        array_unshift($return, static::getPhoundationPluginEntry());

        return $return;
    }


    /**
     * Returns the phoundation plugin entry
     *
     * @return array[]
     */
    protected static function getPhoundationPluginEntry(): array
    {
        return [
            'name'     => 'Phoundation',
            'status'   => tr('Ok'),
            'enabled'  => tr('Enabled'),
            'priority' => 0,
            'class'    => 'Plugins\Phoundation\Plugin',
            'path'     => PATH_ROOT . '/Plugins/Phoundation/',
        ];
    }


    /**
     * Purges all plugins from both database and the PATH_ROOT/Plugins path
     *
     * @return static
     */
    public function clear(): static
    {
        $path = PATH_ROOT . 'Plugins/';

        File::new($path)->delete();
        Path::new($path)->ensure();

        return parent::clear();
    }


    /**
     * Purges all plugins from both database and the PATH_ROOT/Plugins path
     *
     * @return void
     */
    public function purge(): void
    {
        $path = PATH_ROOT . 'Plugins/';

        static::clear();
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
    public function load(string|int|null $id_column = null): static
    {
        $this->source = sql()->list('SELECT   `core_plugins`.`id`, `core_plugins`.`name` 
                                     FROM     `core_plugins` 
                                     WHERE    `core_plugins`.`status` IS NULL
                                     ORDER BY `core_plugins`.`name`' . sql()->getLimit());

        // The keys contain the ids...
        $this->source = array_flip($this->source);
        return $this;
    }


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @return SelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', string $key_column = 'id'): SelectInterface
    {
        return parent::getHtmlSelect($value_column, $key_column)
            ->setName('plugins_id')
            ->setNone(tr('Select a plugin'))
            ->setEmpty(tr('No plugins available'));
    }
}
