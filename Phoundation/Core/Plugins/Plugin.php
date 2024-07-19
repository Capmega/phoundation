<?php

/**
 * Class Plugin
 *
 *
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */

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
use Phoundation\Data\DataEntry\Traits\TraitDataEntryDirectory;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryPriority;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\EnumInputType;

class Plugin extends DataEntry implements PluginInterface
{
    use TraitDataEntryNameDescription;
    use TraitDataEntryDirectory {
        setDirectory as protected __setDirectory;
    }
    use TraitDataEntryPriority {
        setPriority as protected __setPriority;
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
    public static function start(): void {}


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
     *       returned class will be Plugins\Phoundation\Phoundation\Library\Plugin, instead of
     *       Phoundation\Core\Plugins\Plugin
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null                        $column
     * @param bool                               $meta_enabled
     * @param bool                               $force
     *
     * @return static
     */
    public static function load(DataEntryInterface|string|int|null $identifier, ?string $column = null, bool $meta_enabled = false, bool $force = false): static
    {
        $plugin = parent::load($identifier, $column, $meta_enabled, $force);
        $file   = DIRECTORY_ROOT . $plugin->getDirectory() . 'Library/Plugin.php';
        $class  = Library::getClassPath($file);
        $class  = Library::includeClassFile($class);

        return $class::newFromSource($plugin->getSource());
    }


    /**
     * Returns the plugin directory for this plugin
     *
     * @return FsDirectoryInterface
     */
    public function getDirectory(): FsDirectoryInterface
    {
        $directory = $this->getTypesafe(FsDirectoryInterface::class, 'directory');

        if (!$directory) {
            // Path hasn't been set yet? It should always be set UNLESS it's new.
            if (!$this->isNew()) {
                throw new PluginsException(tr('Plugin ":plugin" from vendor ":vendor" does not have a class directory set', [
                    ':vendor' => get_class($this),
                    ':plugin' => get_class($this),
                ]));
            }

            // New object, detect the directory automatically
            $directory = dirname(Strings::from(dirname(Library::getClassFile($this)) . '/', DIRECTORY_ROOT));
        }

        return new FsDirectory($directory, FsRestrictions::getReadonly(DIRECTORY_ROOT . 'Plugins'));
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
     * Returns if this plugin is enabled or not
     *
     * @return bool
     */
    public function getEnabled(): bool
    {
        if ($this->getName() === 'Phoundation') {
            return true;
        }

        return $this->getTypesafe('string', 'status') === null;
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
     * Sets if this plugin is disabled or not
     *
     * @param int|bool|null $disabled
     *
     * @return static
     */
    public function setDisabled(int|bool|null $disabled): static
    {
        return $this->setEnabled(!$disabled);
    }


    /**
     * Sets if this plugin is enabled or not
     *
     * @param int|bool|null $enabled
     *
     * @return static
     */
    public function setEnabled(int|bool|null $enabled): static
    {
        if ($this->getName() === 'Phoundation') {
            if (!$enabled) {
                throw new CoreException(tr('Cannot disable the "Phoundation" plugin, it is always enabled'));
            }
        }

        return $this->set($enabled ? null : 'disabled', 'status');
    }


    /**
     * Returns the menu_enabled for this object
     *
     * @return bool|null
     */
    public function getMenuEnabled(): ?bool
    {
        return $this->getTypesafe('int', 'menu_enabled', 50);
    }


    /**
     * Sets the menu_enabled for this object
     *
     * @param int|bool|null $menu_enabled
     *
     * @return static
     */
    public function setMenuEnabled(int|bool|null $menu_enabled): static
    {
        return $this->set((bool) $menu_enabled, 'menu_enabled');
    }


    /**
     * Returns the menu_priority for this object
     *
     * @return int|null
     */
    public function getMenuPriority(): ?int
    {
        return $this->getTypesafe('int', 'menu_priority', 50);
    }


    /**
     * Sets the menu_priority for this object
     *
     * @param int|null $menu_priority
     *
     * @return static
     */
    public function setMenuPriority(?int $menu_priority): static
    {
        if (is_numeric($menu_priority) and (($menu_priority < 0) or ($menu_priority > 100))) {
            throw new OutOfBoundsException(tr('Specified menu_priority ":menu_priority" is invalid, it should be a number from 0 to 100', [
                ':menu_priority' => $menu_priority,
            ]));
        }

        return $this->set($menu_priority, 'menu_priority');
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
     * Disable this plugin
     *
     * @param string|null $comments
     *
     * @return void
     */
    public function disable(?string $comments = null): void
    {
        $this->setStatus('disabled', $comments);
    }


    /**
     * Enable this plugin
     *
     * @param string|null $comments
     *
     * @return void
     */
    public function enable(?string $comments = null): void
    {
        $this->setStatus(null, $comments);
    }


    /**
     * Register this plugin in the database
     *
     * @return void
     */
    public function register(): void
    {
        if (!$this->isNew()) {
            Log::warning(tr('Not registering plugin ":vendor/:plugin", it is already registered', [
                ':vendor' => $this->getVendor(),
                ':plugin' => $this->getName(),
            ]), 3);
        }

        if (static::exists($this->getName(), 'name')) {
            // This plugin is already registered
            Log::warning(tr('Not registering plugin ":vendor/:plugin", it is already registered', [
                ':vendor' => $this->getVendor(),
                ':plugin' => $this->getName(),
            ]), 3);

            return;
        }

        // Only the Phoundation plugin is ALWAYS enabled
        $enabled = ($this->getName() === 'Phoundation');

        Log::action(tr('Registering new plugin ":vendor/:plugin"', [
            ':vendor' => $this->getVendor(),
            ':plugin' => $this->getName(),
        ]));

        // Register the plugin
        $this->setDirectory($this->getDirectory())
             ->setVendor($this->getVendor())
             ->setClass($this->getClass())
             ->setEnabled($enabled)
             ->setPriority($this->getPriority())
             ->setDescription($this->getDescription())
             ->save();
    }


    /**
     * Returns the vendor name for this plugin
     *
     * @return string|null
     */
    public function getVendor(): ?string
    {
        $vendor = $this->getTypesafe('string', 'vendor');

        if ($vendor === null) {
            $directory = $this->getDirectory();

            if ($directory) {
                return Strings::cut($directory, 'Plugins/', '/');
            }

            return null;
        }

        return $vendor;
    }


    /**
     * Sets the priority for this plugin
     *
     * @param int|null $priority
     *
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

            } elseif (($priority < 0) or ($priority > 100)) {
                throw new OutOfBoundsException(tr('Specified priority ":priority" is invalid, it should be between 0 - 100', [
                    ':priority' => $priority,
                ]));
            }
        }

        return $this->__setPriority($priority);
    }


    /**
     * Sets the main class for this plugin
     *
     * @param string|null $class
     *
     * @return static
     */
    public function setClass(?string $class): static
    {
        return $this->set($class, 'class');
    }


    /**
     * Sets the main vendor for this plugin
     *
     * @param string|null $vendor
     *
     * @return static
     */
    public function setVendor(?string $vendor): static
    {
        return $this->set($vendor, 'vendor');
    }


    /**
     * Returns the class path for this plugin
     *
     * @return string|null
     */
    public function getClass(): ?string
    {
        $directory = $this->getDirectory();

        if ($directory) {
            return Library::getClassPath(DIRECTORY_ROOT . $directory . 'Library/Plugin.php');
        }

        return null;
    }


    /**
     * Returns if the commands of this plugin are enabled or not
     *
     * @return bool
     */
    public function getCommandsEnabled(): bool
    {
        return $this->getTypesafe('bool', 'commands_enabled', false);
    }


    /**
     * Sets if the commands of this plugin are enabled or not
     *
     * @param int|bool|null $commands_enabled
     *
     * @return static
     */
    public function setCommandsEnabled(int|bool|null $commands_enabled): static
    {
        return $this->set((bool) $commands_enabled, 'commands_enabled');
    }


    /**
     * Returns if the web pages of this plugin are enabled or not
     *
     * @return bool
     */
    public function getWebEnabled(): bool
    {
        return $this->getTypesafe('bool', 'web_enabled', false);
    }


    /**
     * Sets if the web pages of this plugin are enabled or not
     *
     * @param int|bool|null $web_enabled
     *
     * @return static
     */
    public function setWebEnabled(int|bool|null $web_enabled): static
    {
        return $this->set((bool) $web_enabled, 'web_enabled');
    }


    /**
     * Delete the plugin from the plugin registry
     *
     * @param string|null $comments
     *
     * @return void
     */
    public function unregister(?string $comments = null): void
    {
        static::unlinkScripts();
        sql()->delete('core_plugins', [':seo_name' => $this->getName()], $comments);
    }


    /**
     * Sets the path for this object
     *
     * @param FsDirectoryInterface|string|null $directory
     * @param FsRestrictionsInterface|null     $restrictions
     *
     * @return static
     */
    public function setDirectory(FsDirectoryInterface|string|null $directory, ?FsRestrictionsInterface $restrictions = null): static
    {
        $restrictions = $restrictions ?? FsRestrictions::getReadonly(DIRECTORY_ROOT . 'Plugins');

        return $this->set(is_string($directory) ? new FsDirectory($directory, $restrictions) : $directory, 'directory');
    }


    /**
     * Sets the available data keys for the User class
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(Definition::new($this, 'disabled')
                                    ->setInputType(EnumInputType::boolean)
                                    ->setOptional(true)
                                    ->setVirtual(true)
                                    ->setRender(false)
                                    ->setCliColumn('-d,--disable'))

                    ->add(Definition::new($this, 'vendor')
                                    ->setLabel(tr('Vendor'))
                                    ->setInputType(EnumInputType::text)
                                    ->setMaxlength(128)
                                    ->setSize(6)
                                    ->setHelpText(tr('The vendor that manages this plugin')))

                    ->add(DefinitionFactory::getName($this, 'seo_name')
                                           ->setRender(false))

                    ->add(DefinitionFactory::getName($this)
                                           ->setSize(6)
                                           ->setHelpText(tr('The name of this plugin')))

                    ->add(Definition::new($this, 'priority')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::number)
                                    ->setDbNullValue(false, 50)
                                    ->setSize(3)
                                    ->setCliColumn('--priority')
                                    ->setCliAutoComplete(true)
                                    ->setLabel(tr('Priority'))
                                    ->setMin(0)
                                    ->setMax(100)
                                    ->setHelpText(tr('The priority for loading this plugin, between 0 and 100. A lower value will load the plugin before others'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isInteger();
                                    }))

                    ->add(Definition::new($this, 'menu_priority')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::number)
                                    ->setDbNullValue(false, 50)
                                    ->setSize(3)
                                    ->setCliColumn('--menu-priority')
                                    ->setCliAutoComplete(true)
                                    ->setLabel(tr('Menu priority'))
                                    ->setMin(0)
                                    ->setMax(100)
                                    ->setHelpText(tr('The priority for where to display the menu of this plugin, between 0 and 100. A lower value will display the menu before others'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isInteger();
                                    }))

                    ->add(Definition::new($this, 'menu_enabled')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::checkbox)
                                    ->setDbNullValue(false, true)
                                    ->setSize(2)
                                    ->setCliColumn('--menu-enabled')
                                    ->setCliAutoComplete(true)
                                    ->setLabel(tr('Menu enabled'))
                                    ->setHelpText(tr('Sets if the menu of this plugin will be available and visible, or not')))

                    ->add(Definition::new($this, 'commands_enabled')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::checkbox)
                                    ->setDbNullValue(false, true)
                                    ->setSize(2)
                                    ->setCliColumn('--commands-enabled')
                                    ->setCliAutoComplete(true)
                                    ->setLabel(tr('Commands enabled'))
                                    ->setHelpText(tr('Sets if the command line commands of this plugin will be available, or not')))

                    ->add(Definition::new($this, 'web_enabled')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::checkbox)
                                    ->setSize(3)
                                    ->setCliColumn('-w,--web-enabled')
                                    ->setLabel(tr('Web enabled'))
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
                                        $validator->hasMaxCharacters(1024)
                                                  ->matchesRegex('/Plugins\\\[\\\A-Za-z0-9]+\\\Plugin/');
                                    }))

                    ->add(Definition::new($this, 'directory')
                                    ->setLabel(tr('Directory'))
                                    ->setInDirectories(new FsDirectory(DIRECTORY_ROOT . 'Plugins', FsRestrictions::getReadonly(DIRECTORY_ROOT . 'Plugins')))
                                    ->setInputType(EnumInputType::path)
                                    ->setMaxlength(128)
                                    ->setSize(6)
                                    ->setHelpText(tr('The filesystem directory where this plugin is located')))

                    ->add(DefinitionFactory::getDescription($this));
    }
}
