<?php

declare(strict_types=1);

namespace Phoundation\Core\Plugins;

use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Libraries\Library;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryPath;
use Phoundation\Data\DataEntry\Traits\DataEntryPriority;
use Phoundation\Data\Interfaces\DataEntryInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Web\Http\Html\Enums\InputElement;
use Phoundation\Web\Http\Html\Enums\InputType;
use Phoundation\Web\Http\Html\Enums\InputTypeExtended;


/**
 * Class Plugin
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Plugin extends DataEntry
{
    use DataEntryNameDescription;
    use DataEntryPath;
    use DataEntryPriority {
        setPriority as setTraitPriority;
    }


    /**
     * Plugin class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null)
    {
        $this->entry_name  = 'plugin';
        $this->unique_field  = 'name';

        parent::__construct($identifier);
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
     * Execute the required code to start the plugin
     *
     * @return void
     */
    // TODO Use hooks after startup!
    public static function start(): void
    {
    }


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

        return $this->getDataValue('bool', 'enabled', false);
    }


    /**
     * Sets if this plugin is enabled or not
     *
     * @param int|bool|null $enabled
     * @return static
     */
    public function setEnabled(int|bool|null $enabled): static
    {
        if ($this->getName() === 'Phoundation') {
            if (!$enabled) {
                throw new CoreException(tr('Cannot disable the "Phoundation" plugin, it is always enabled'));
            }
        }

        return $this->setDataValue('enabled', (bool) $enabled);
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
        $name   = $plugin->getName();

        if (static::exists($name)) {
            // This plugin is already registered
            Log::warning(tr('Not registering plugin ":plugin", it is already registered', [
                ':plugin' => $name
            ]), 3);

            return;
        }

        Log::action(tr('Registering new plugin ":plugin"', [
            ':plugin' => $name
        ]));

        $enabled = ($name === 'Phoundation');

        $plugin
            ->setName($name)
            ->setPath($plugin->getPath())
            ->setClass($plugin->getClass())
            ->setEnabled($enabled)
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
     * Sets the available data keys for the User class
     *
     * @param DefinitionsInterface $field_definitions
     */
    protected function initFieldDefinitions(DefinitionsInterface $field_definitions): void
    {
        $field_definitions
            ->add(Definition::new('disabled')
                ->setOptional(true)
                ->setVirtual(true)
                ->setVisible(false)
                ->setCliField('-d,--disable'))
            ->add(Definition::new('name')
                ->setVisible(false))
            ->add(Definition::new('name')
                ->setLabel(tr('Name'))
                ->setInputType(InputTypeExtended::name)
                ->setMaxlength(64)
                ->setSize(6)
                ->setHelpText(tr('The name of this plugin')))
            ->add(Definition::new('priority')
                ->setOptional(true)
                ->setInputType(InputType::numeric)
                ->setNullDb(false, 5)
                ->setSize(3)
                ->setCliField('--priority')
                ->setAutoComplete(true)
                ->setLabel(tr('Priority'))
                ->setMin(1)
                ->setMax(9)
                ->setHelpText(tr('The priority for this plugin, between 1 and 9'))
                ->addValidationFunction(function ($validator) {
                    $validator->isInteger();
                }))
            ->add(Definition::new('enabled')
                ->setOptional(true)
                ->setInputType(InputType::checkbox)
                ->setSize(3)
                ->setCliField('-e,--enabled')
                ->setLabel(tr('Enabled'))
                ->setDefault(true)
                ->setHelpText(tr('If enabled, this plugin will automatically start upon each page load or script execution'))
                ->addValidationFunction(function ($validator) {
                    $validator->isInteger();
                }))
            ->add(Definition::new('class')
                ->setLabel(tr('Class'))
                ->setInputType(InputTypeExtended::name)
                ->setMaxlength(255)
                ->setSize(6)
                ->setHelpText(tr('The base class path of this plugin'))
                ->addValidationFunction(function ($validator) {
                    $validator->hasMaxCharacters(2048)->matchesRegex('/Plugins\\[a-z0-9]+\\Plugin/');
                }))
            ->add(Definition::new('path')
                ->setLabel(tr('Path'))
                ->setInputType(InputTypeExtended::path)
                ->setMaxlength(128)
                ->setSize(6)
                ->setHelpText(tr('The filesystem path where this plugin is located')))
            ->add(Definition::new('description')
                ->setOptional(true)
                ->setInputType(InputTypeExtended::description)
                ->setLabel('Description')
                ->setSize(12)
                ->setMaxlength(16_777_215)
                ->addValidationFunction(function ($validator) {
                    $validator->isDescription();
                }));
    }
}