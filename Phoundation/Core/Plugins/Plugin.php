<?php

declare(strict_types=1);

namespace Phoundation\Core\Plugins;

use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Libraries\Library;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Plugins\Interfaces\PluginInterface;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryPath;
use Phoundation\Data\DataEntry\Traits\DataEntryPriority;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\InputType;
use Phoundation\Web\Html\Enums\InputTypeExtended;


/**
 * Class Plugin
 *
 *
 *
 * @see DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Plugin extends DataEntry implements PluginInterface
{
    use DataEntryNameDescription;
    use DataEntryPath;
    use DataEntryPriority {
        setPriority as setTraitPriority;
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
    public static function getDataEntryName(): string
    {
        return tr('Plugin');
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

        return $this->getSourceFieldValue('bool', 'enabled', false);
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

        return $this->setSourceValue('enabled', (bool) $enabled);
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
     * @param int|bool|null $disabled
     * @return static
     */
    public function setDisabled(int|bool|null $disabled): static
    {
        return $this->setEnabled(!$disabled);
    }


    /**
     * Returns the plugin path for this plugin
     *
     * @return string|null
     */
    public function getClass(): ?string
    {
        $directory = $this->getPath();

        if ($directory) {
            return Library::getClassPath(DIRECTORY_ROOT . $directory . 'Plugin.php');
        }

        return null;
    }


    /**
     * Sets the main class for this plugin
     *
     * @param string|null $class
     * @return static
     */
    public function setClass(?string $class): static
    {
        return $this->setSourceValue('class', $class);
    }


    /**
     * Sets the priority for this plugin
     *
     * @param int|null $priority
     * @return static
     */
    public function setPriority(?int $priority): static
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
        return Strings::from(dirname(Library::getClassFile($this)) . '/', DIRECTORY_ROOT);
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
        static::disable();
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

        if (static::exists($name, 'name')) {
            // This plugin is already registered
            Log::warning(tr('Not registering plugin ":plugin", it is already registered', [
                ':plugin' => $name
            ]), 3);

            return;
        }

        // Only the Phoundation plugin is ALWAYS enabled
        $enabled = ($name === 'Phoundation');

        Log::action(tr('Registering new plugin ":plugin"', [':plugin' => $name]));

        // Register the plugin
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
        static::unlinkScripts();
        sql()->dataEntryDelete('core_plugins', [':seo_name' => $this->getName()], $comments);
    }


    /**
     * Enable this plugin
     *
     * @param string|null $comments
     * @return void
     */
    public function enable(?string $comments = null): void
    {
        static::linkScripts();
        sql()->dataEntrySetStatus(null, 'core_plugins', ['seo_name' => $this->getSeoName()], $comments);
    }


    /**
     * Disable this plugin
     *
     * @param string|null $comments
     * @return void
     */
    public function disable(?string $comments = null): void
    {
        static::unlinkScripts();
        sql()->dataEntrySetStatus('disabled', 'core_plugins', ['seo_name' => $this->getSeoName()], $comments);
    }


    /**
     * Link the scripts for this plugin to the DIRECTORY_ROOT/scripts directory
     *
     * @return void
     */
    protected function linkScripts(): void
    {
        $plugin = strtolower(dirname(__DIR__));
        $file   = __DIR__ . '/scripts';

        if (file_exists($file)) {
            link ($file, DIRECTORY_ROOT . 'scripts/' . $plugin);
        }
    }


    /**
     * Link the scripts for this plugin to the DIRECTORY_ROOT/scripts directory
     *
     * @return void
     */
    protected function unlinkScripts(): void
    {
        $plugin = strtolower(dirname(__DIR__));
        $file   = DIRECTORY_ROOT . 'scripts/' . $plugin;

        if (file_exists($file)) {
            File::new(DIRECTORY_ROOT . 'scripts/' . $plugin)->delete();
        }
    }


    /**
     * Sets the available data keys for the User class
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(Definition::new($this, 'disabled')
                ->setInputType(InputTypeExtended::boolean)
                ->setOptional(true)
                ->setVirtual(true)
                ->setVisible(false)
                ->setCliField('-d,--disable'))
            ->addDefinition(DefinitionFactory::getName($this, 'seo_name')
                ->setVisible(false))
            ->addDefinition(DefinitionFactory::getName($this)
                ->setSize(6)
                ->setHelpText(tr('The name of this plugin')))
            ->addDefinition(Definition::new($this, 'priority')
                ->setOptional(true)
                ->setInputType(InputType::number)
                ->setNullDb(false, 5)
                ->setSize(3)
                ->setCliField('--priority')
                ->setCliAutoComplete(true)
                ->setLabel(tr('Priority'))
                ->setMin(0)
                ->setMax(100)
                ->setHelpText(tr('The priority for this plugin, between 1 and 9'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isInteger();
                }))
            ->addDefinition(Definition::new($this, 'enabled')
                ->setOptional(true)
                ->setInputType(InputType::checkbox)
                ->setSize(3)
                ->setCliField('-e,--enabled')
                ->setLabel(tr('Enabled'))
                ->setDefault(true)
                ->setHelpText(tr('If enabled, this plugin will automatically start upon each page load or script execution'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isBoolean();
                }))
            ->addDefinition(Definition::new($this, 'class')
                ->setLabel(tr('Class'))
                ->setInputType(InputType::text)
                ->setMaxlength(255)
                ->setSize(6)
                ->setHelpText(tr('The base class path of this plugin'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->hasMaxCharacters(1024)->matchesRegex('/Plugins\\\[\\\A-Za-z0-9]+\\\Plugin/');
                }))
            ->addDefinition(Definition::new($this, 'path')
                ->setLabel(tr('Directory'))
                ->setInputType(InputTypeExtended::path)
                ->setMaxlength(128)
                ->setSize(6)
                ->setHelpText(tr('The filesystem path where this plugin is located')))
            ->addDefinition(DefinitionFactory::getDescription($this));
    }
}
