<?php

declare(strict_types=1);

namespace Phoundation\Core\Plugins;

use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Libraries\Library;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryPath;
use Phoundation\Data\DataEntry\Traits\DataEntryPriority;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;


/**
 * Class Plugin
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
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
     * Plugin class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name  = 'plugin';
        $this->table         = 'core_plugins';
        $this->unique_field  = 'name';

        parent::__construct($identifier);
    }


    /**
     * @return void
     */
    // TODO Use hooks after startup!
    abstract public static function start(): void;


    /**
     * Returns if this plugin is enabled or not
     *
     * @return bool
     */
    public function getEnabled(): bool
    {
        if ($this->getName() === 'Phoundation') {
            return true;
        }

        return (bool) $this->getDataValue('enabled');
    }


    /**
     * Sets if this plugin is enabled or not
     *
     * @param int|bool $enabled
     * @return static
     */
    public function setEnabled(int|bool $enabled): static
    {
        $enabled = (bool) $enabled;

        if ($this->getName() === 'Phoundation') {
            if (!$enabled) {
                throw new CoreException(tr('Cannot disable the "Phoundation" plugin, it is always enabled'));
            }
        }

        return $this->setDataValue('enabled', $enabled);
    }


    /**
     * Returns if this plugin is disabled or not
     *
     * @return bool
     */
    public function getDisabled(): bool
    {
        return !$this->getEnabled();
    }


    /**
     * Sets if this plugin is disabled or not
     *
     * @param bool $disabled
     * @return static
     */
    public function setDisabled(bool $disabled): static
    {
        return $this->setEnabled(!$disabled);
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
    public static function register(): void
    {
        $plugin = static::new();
        $plugin
            ->setName($plugin->getName())
            ->setPath($plugin->getPath())
            ->setClass($plugin->getClass())
            ->setEnabled(false)
            ->setPriority($plugin->getPriority())
            ->setDescription($plugin->getDescription())
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
     * Validates the provider record with the specified validator object
     *
     * @param ArgvValidator|PostValidator|GetValidator $validator
     * @param bool $no_arguments_left
     * @param bool $modify
     * @return array
     */
    protected function validate(ArgvValidator|PostValidator|GetValidator $validator, bool $no_arguments_left = false, bool $modify = false): array
    {
        $data = $validator
            ->select($this->getAlternateValidationField('name'), true)->hasMaxCharacters()->isName()
            ->select($this->getAlternateValidationField('priority'), true)->isOptional(0)->isBetween(0, 9)
            ->select($this->getAlternateValidationField('enabled'), true)->isBoolean()
            ->select($this->getAlternateValidationField('file'), true)->isPath()
            ->select($this->getAlternateValidationField('class'), true)->hasMaxCharacters(2048)->matchesRegex('/Plugins\\[a-z0-9]+\\Plugin/')
            ->select($this->getAlternateValidationField('description'), true)->isOptional()->hasMaxCharacters(65_530)->isPrintable()
            ->noArgumentsLeft($no_arguments_left)
            ->validate();

        // Ensure the name doesn't exist yet as it is a unique identifier
        if ($data['name']) {
            static::notExists($data['name'], $this->getId(), true);
        }

        return $data;
    }


    /**
     * Sets the available data keys for the User class
     *
     * @return array
     */
    protected static function getFieldDefinitions(): array
    {
       return [
            'disabled' => [
                'virtual' => true,
                'cli'     => '-d,--disable',
            ],
           'seo_name' => [
               'visible' => false,
           ],
            'name' => [
                'required'  => true,
                'readonly'  => true,
                'complete'  => false,
                'label'     => tr('Name'),
                'size'      => 4,
                'maxlength' => 64,
                'help'      => tr('The name of this plugin'),
            ],
            'priority' => [
                'type'     => 'numeric',
                'cli'      => '-p,--priority PRIORITY (1 - 10)',
                'db_null'  => false,
                'min'     => 1,
                'default' => 5,
                'max'     => 100,
                'size'     => 4,
                'label'    => tr('Priority'),
                'help'     => tr('Sets the priority'),
            ],
           'enabled' => [
               'complete' => false,
               'type'     => 'checkbox',
               'cli'      => '-e,--enable',
               'size'     => 4,
               'label'    => tr('Start'),
               'help'     => tr('If specified, this plugin is enabled and will automatically start upon each page load or script execution'),
               'default'  => true,
           ],
            'path' => [
                'readonly' => true,
                'size'     => 6,
                'label'    => tr('Path'),
            ],
            'class' => [
                'readonly' => true,
                'size'     => 6,
                'label'    => tr('Class'),
            ],
            'description' => [
                'readonly' => true,
                'size'     => 12,
                'label'    => tr('Description')
            ],
        ];
    }
}