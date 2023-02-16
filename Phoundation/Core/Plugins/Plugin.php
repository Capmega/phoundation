<?php

namespace Phoundation\Core\Plugins;

use Phoundation\Accounts\Users\Users;
use Phoundation\Core\Libraries\Library;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Filesystem\File;


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
abstract class Plugin extends DataEntry
{
    /**
     * Installs this plugin
     *
     * @return void
     */
    public function install(): void
    {
        self::enable();
    }


    /**
     * @return void
     */
    // TODO Use hooks after startup!
    abstract public static function start(): void;



    /**
     * Uninstalls this plugin
     *
     * @return void
     */
    public function uninstall(): void
    {
        self::disable();
    }



    /**
     * Disables this plugin
     *
     * @return void
     */
    public function disable(): void
    {
        // If this plugin has scripts, unlink them so that they are no longer available
        self::unlinkScripts();
    }



    /**
     * Enables this plugin
     *
     * @return void
     */
    public function enable(): void
    {
        // If this plugin has scripts, link them in the PATH_ROOT/scripts directory
        self::linkScripts();
    }



    /**
     * Returns a plugin object generated with data from the plugin directory in PATH_ROOT/Plugins/PLUGINNAME
     *
     * @param string $plugin_name
     * @return static
     */
    public static function read(string $plugin_name): static
    {
        $file   = self::getFile($plugin_name);
        $class  = Library::getClassPath($file);

        // Include the class file and create and return the object
        include_once($file);

        return new $class();
    }



    /**
     * Link the scripts for this plugin to the PATH_ROOT/scripts directory
     *
     * @return void
     */
    protected static function linkScripts(): void
    {
        $plugin = strtolower(dirname(__DIR__));
        $file   = __DIR__ . '/scripts';

        if (file_exists($file)) {
            link ($file, PATH_ROOT . 'scripts/' . $plugin);
        }
    }



    /**
     * Link the scripts for this plugin to the PATH_ROOT/scripts directory
     *
     * @return void
     */
    protected static function unlinkScripts(): void
    {
        $plugin = strtolower(dirname(__DIR__));
        $file   = PATH_ROOT . 'scripts/' . $plugin;

        if (file_exists($file)) {
            File::new(PATH_ROOT . 'scripts/' . $plugin)->delete();
        }
    }



    /**
     * Returns the plugin path for the specified plugin name
     *
     * @param string $plugin_name
     * @return string
     */
    protected static function getPath(string $plugin_name): string
    {
        $file = PATH_ROOT . 'Plugins/' . $plugin_name;
showdie($plugin_name);
        if (!file_exists($file)) {
            File::new($file)->ensureReadable();
        }

        return $file;
    }



    /**
     * Returns the Plugin.php file for the specified plugin name
     *
     * @param string $plugin_name
     * @return string
     */
    protected static function getFile(string $plugin_name): string
    {
        if (file_exists($plugin_name)) {
            return $plugin_name;
        }

        return self::getPath($plugin_name) . '/Plugin.php';
    }



    /**
     * Defines the form keys for this object
     *
     * @return void
     */
    protected function setKeys(): void
    {
        $this->keys = [
            'name' => [
                'disabled' => true,
                'label' => tr('Name')
            ],
            'path' => [
                'disabled' => true,
            ],
            'class' => [
                'disabled' => true,
            ],
            'description' => [
                'disabled' => true,
            ],
        ];

        $this->keys_display = [
            'name'        => 6,
            'path'        => 6,
            'class'       => 6,
            'description' => 6,
        ];

        parent::setKeys();
    }
}