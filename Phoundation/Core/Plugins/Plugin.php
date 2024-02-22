<?php

declare(strict_types=1);

namespace Phoundation\Core\Plugins;

use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Libraries\Library;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Plugins\Exception\PluginsException;
use Phoundation\Core\Plugins\Interfaces\PluginInterface;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryPath;
use Phoundation\Data\DataEntry\Traits\DataEntryPriority;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Html\Enums\EnumInputTypeExtended;


/**
 * Class Plugin
 *
 *
 *
 * @see DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
    public static function getUniqueColumn(): ?string
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

        return $this->getSourceValueTypesafe('bool', 'enabled', false);
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
     * Returns the class path for this plugin
     *
     * @return string|null
     */
    public function getClass(): ?string
    {
        $directory = $this->getPath();

        if ($directory) {
            return Library::getClassPath(DIRECTORY_ROOT . $directory . 'Library/Plugin.php');
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
        $path = $this->getSourceValueTypesafe('string', 'path');

        if (!$path) {
            // Path hasn't been set yet? That is weird as it should always be set UNLESS its new.
            if ($this->isNew()) {
                // New object, detect the path automatically
                return dirname(Strings::from(dirname(Library::getClassFile($this)) . '/', DIRECTORY_ROOT)) . '/';
            }

            throw new PluginsException(tr('Plugin ":plugin" does not have a class path set', [
                ':plugin' => get_class($this)
            ]));
        }

        return $path;
    }


    /**
     * Returns the plugin name
     *
     * @return string
     */
    public function getName(): string
    {
        return basename(dirname(dirname(Library::getClassFile($this))));
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
    public function register(): void
    {
        if (!$this->isNew()) {
            Log::warning(tr('Not registering plugin ":plugin", it is already registered', [
                ':plugin' => $this->getName()
            ]), 3);
        }

        if (static::exists($this->getName(), 'name')) {
            // This plugin is already registered
            Log::warning(tr('Not registering plugin ":plugin", it is already registered', [
                ':plugin' => $this->getName()
            ]), 3);

            return;
        }

        // Only the Phoundation plugin is ALWAYS enabled
        $enabled = ($this->getName() === 'Phoundation');

        Log::action(tr('Registering new plugin ":plugin"', [':plugin' => $this->getName()]));

        // Register the plugin
        $this->setPath($this->getPath())
             ->setClass($this->getClass())
             ->setEnabled($enabled)
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
        sql()->dataEntrySetStatus('disabled', 'core_plugins', ['seo_name' => $this->getSeoName()], $comments);
    }


    /**
     * Returns a DataEntry object matching the specified identifier that MUST exist in the database
     *
     * This method also accepts DataEntry objects of the same class, in which case it will simply return the specified
     * object, as long as it exists in the database.
     *
     * If the DataEntry does not exist in the database, then this method will check if perhaps it exists as a
     * configuration entry. This requires DataEntry::$config_path to be set. DataEntries from configuration will be in
     * readonly mode automatically as they cannot be stored in the database.
     *
     * DataEntries from the database will also have their status checked. If the status is "deleted", then a
     * DataEntryDeletedException will be thrown
     *
     * @note The test to see if a DataEntry object exists in the database can be either DataEntry::isNew() or
     *       DataEntry::getId(), which should return a valid database id
     *
     * @note IMPORTANT DETAIL: This Plugins::get() method overrides the DataEntry::get() method. It works the same, but
     *       will return the correct class for the plugin. If, for example, the Phoundation plugin was requested, the
     *       returned class will be Plugins\Phoundation\Library\Plugin, instead of Phoundation\Core\Plugins\Plugin
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null $column
     * @param bool $meta_enabled
     * @param bool $force
     * @param bool $no_identifier_exception
     * @return static
     */
    public static function get(DataEntryInterface|string|int|null $identifier, ?string $column = null, bool $meta_enabled = false, bool $force = false, bool $no_identifier_exception = true): static
    {
        $plugin = parent::get($identifier, $column, $meta_enabled, $force, $no_identifier_exception);
        $file   = DIRECTORY_ROOT . $plugin->getPath() . 'Library/Plugin.php';
        $class  = Library::getClassPath($file);
        $class  = Library::includeClassFile($class);

        return $class::fromSource($plugin->getSource());
    }


    /**
     * Sets the available data keys for the User class
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->add(Definition::new($this, 'disabled')
                ->setInputType(EnumInputTypeExtended::boolean)
                ->setOptional(true)
                ->setVirtual(true)
                ->setRender(false)
                ->setCliColumn('-d,--disable'))
            ->add(DefinitionFactory::getName($this, 'seo_name')
                ->setRender(false))
            ->add(DefinitionFactory::getName($this)
                ->setSize(6)
                ->setHelpText(tr('The name of this plugin')))
            ->add(Definition::new($this, 'priority')
                ->setOptional(true)
                ->setInputType(EnumInputType::number)
                ->setNullDb(false, 5)
                ->setSize(3)
                ->setCliColumn('--priority')
                ->setCliAutoComplete(true)
                ->setLabel(tr('Priority'))
                ->setMin(0)
                ->setMax(100)
                ->setHelpText(tr('The priority for this plugin, between 1 and 9'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isInteger();
                }))
            ->add(Definition::new($this, 'enabled')
                ->setOptional(true)
                ->setInputType(EnumInputType::checkbox)
                ->setSize(3)
                ->setCliColumn('-e,--enabled')
                ->setLabel(tr('Enabled'))
                ->setDefault(true)
                ->setHelpText(tr('If enabled, this plugin will automatically start upon each page load or command execution'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isBoolean();
                }))
            ->add(Definition::new($this, 'class')
                ->setLabel(tr('Class'))
                ->setInputType(EnumInputType::text)
                ->setMaxlength(255)
                ->setSize(6)
                ->setHelpText(tr('The base class path of this plugin'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->hasMaxCharacters(1024)->matchesRegex('/Plugins\\\[\\\A-Za-z0-9]+\\\Plugin/');
                }))
            ->add(Definition::new($this, 'path')
                ->setLabel(tr('Directory'))
                ->setInputType(EnumInputTypeExtended::path)
                ->setMaxlength(128)
                ->setSize(6)
                ->setHelpText(tr('The filesystem path where this plugin is located')))
            ->add(DefinitionFactory::getDescription($this));
    }
}
