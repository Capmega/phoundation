<?php

namespace Phoundation\Core\Plugins;

use Phoundation\Core\Libraries\Library;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryPath;
use Phoundation\Data\DataEntry\Traits\DataEntryPriority;
use Phoundation\Exception\OutOfBoundsException;
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
    use DataEntryNameDescription;
    use DataEntryPath;
    use DataEntryPriority {
        setPriority as setTraitPriority;
    }



    /**
     * Customer class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        static::$entry_name  = 'plugin';
        $this->table         = 'core_plugins';
        $this->unique_column = 'name';

        parent::__construct($identifier);
    }



    /**
     * @return void
     */
    // TODO Use hooks after startup!
    abstract public static function start(): void;



    /**
     * Returns if this plugin should autostart or not
     *
     * @return bool
     */
    public function getStart(): bool
    {
        return (bool) $this->getDataValue('start');
    }



    /**
     * Sets if this plugin should autostart or not
     *
     * @param bool $start
     * @return static
     */
    public function setStart(bool $start): static
    {
        if ($this->getName() === 'Phoundation') {
            $start = true;
        }

        return $this->setDataValue('start', $start);
    }



    /**
     * Returns the plugin path for this plugin
     *
     * @return string
     */
    public function getClass(): string
    {
        return Library::getClassPath($this->getPath() . 'Plugin.php');
    }


    /**
     * Sets the main class for this plugin
     *
     * @param string $class
     * @return static
     */
    public function setClass(string $class): static
    {
        return $this->setDataValue('class', $class);
    }


    /**
     * Sets the priority for this plugin
     *
     * @param int|null $priority
     * @return static
     */
    public function setPriority(int|null $priority): static
    {
        if ($this->getName() === 'Phoundation') {
            $priority = 0;
        } else {
            if (!$priority) {
                // Default to mid level
                $priority = 50;
            } elseif (($priority < 1) or ($priority > 100)) {
                throw new OutOfBoundsException(tr('Specified priority ":priority" is invalid, it should be between 1 - 100', [
                    ':priority' => $priority
                ]));
            }
        }

        return $this->setTraitPriority($priority);
    }


    /**
     * Returns the plugin path for this plugin
     *
     * @return string
     */
    public function getPath(): string
    {
        return dirname(Library::getClassFile($this)) . '/';
    }


    /**
     * Returns the plugin name
     *
     * @return string
     */
    public function getName(): string
    {
        return basename(dirname(Library::getClassFile($this)));
    }


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
     * Register this plugin in the database
     *
     * @return void
     */
    public function register(): void
    {
        $this
            ->setName($this->getName())
            ->setPath($this->getPath())
            ->setClass($this->getClass())
            ->setStart($this->getStart())
            ->setPriority($this->getPriority())
            ->setDescription($this->getDescription())
            ->save();
    }


    /**
     * Delete the plugin from the plugin registry
     *
     * @param string|null $comments
     * @return void
     */
    public function unregister(?string $comments = null): void
    {
        self::unlinkScripts();
        sql()->delete('core_plugins', [':seo_name' => $this->getName()], $comments);
    }



    /**
     * Enable this plugin
     *
     * @param string|null $comments
     * @return void
     */
    public function enable(?string $comments = null): void
    {
        self::linkScripts();
        sql()->setStatus(null, 'core_plugins', ['seo_name' => $this->getSeoName()], $comments);
    }


    /**
     * Disable this plugin
     *
     * @param string|null $comments
     * @return void
     */
    public function disable(?string $comments = null): void
    {
        self::unlinkScripts();
        sql()->setStatus('disabled', 'core_plugins', ['seo_name' => $this->getSeoName()], $comments);
    }


    /**
     * Link the scripts for this plugin to the PATH_ROOT/scripts directory
     *
     * @return void
     */
    protected function linkScripts(): void
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
    protected function unlinkScripts(): void
    {
        $plugin = strtolower(dirname(__DIR__));
        $file   = PATH_ROOT . 'scripts/' . $plugin;

        if (file_exists($file)) {
            File::new(PATH_ROOT . 'scripts/' . $plugin)->delete();
        }
    }



    /**
     * Defines the form keys for this object
     *
     * @return void
     */
    protected function setKeys(): void
    {
       $this->keys = [
           'start' => [
               'default' => true,
               'type'    => 'checkbox',
               'label'   => tr('Start')
           ],
           'priority' => [
               'type'    => 'numeric',
               'db_null' => false,
               'min'     => 1,
               'max'     => 100,
               'label'   => tr('Priority')
           ],
           'name' => [
               'disabled' => true,
               'label' => tr('Name')
           ],
           'seo_name' => [
               'visible' => false,
           ],
            'path' => [
                'disabled' => true,
                'label' => tr('Path')
            ],
            'class' => [
                'disabled' => true,
                'label' => tr('Class')
            ],
            'description' => [
                'disabled' => true,
                'label' => tr('Description')
            ],
        ];

        $this->keys_display = [
            'name'        => 4,
            'priority'    => 4,
            'start'       => 4,
            'path'        => 6,
            'class'       => 6,
            'description' => 12,
        ];

        parent::setKeys();
    }
}