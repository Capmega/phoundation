<?php

/**
 * Class Configurations
 *
 * This class can manage various user configuration entries.
 *
 * @see       https://www.hanselman.com/blog/how-to-detect-if-the-users-os-prefers-dark-mode-and-change-your-site-with-css-and-js
 * @see       \Phoundation\Accounts\Users\User
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users\Configuration;

use Phoundation\Accounts\Users\Configuration\Interfaces\ConfigurationsInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Definitions;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\IteratorCore;
use Phoundation\Data\Traits\TraitDataDefinitions;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Data\Validator\Validator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Html\Components\Forms\DataEntryForm;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Http\Url;


class Configurations extends IteratorCore implements ConfigurationsInterface
{
    use TraitDataDefinitions;


    /**
     * Configurations class constructor
     *
     * @param UserInterface $user
     */
    public function __construct(UserInterface $user) {
        $this->setParentObject($user)
             ->setDefinitionsObject($this->initDefinitionsObject())
             ->buildSource();

        parent::__construct();
    }


    /**
     * Returns a new DataIterator type object
     *
     * @param UserInterface $user
     *
     * @return static
     */
    public static function new(UserInterface $user): static
    {
        return new static($user);
    }


    /**
     * Builds the source for this Configurations object
     *
     * @return static
     */
    protected function buildSource(): static
    {
        $source = [];

        foreach ($this->_definitions as $column => $_definition) {
            if ($_definition->getVirtual()) {
                continue;
            }

            $source[$column] = $this->getConfiguredValue($_definition);
        }

        return $this->setSource($source);
    }


    /**
     * Returns the configured value for the specified column
     *
     * @param DefinitionInterface $_definition
     * @param bool                $allow_user_configuration
     *
     * @return mixed
     */
    protected function getConfiguredValue(DefinitionInterface $_definition, bool $allow_user_configuration = true): mixed
    {
        $method = $_definition->getProperty('configuration_method');

        if ($method) {
            return config()->$method($_definition->getProperty('configuration_path'), $_definition->getDefault(), $allow_user_configuration, false);
        }

        throw new OutOfBoundsException(tr('Cannot get configured value for column ":column", the column has no method property specified', [
            ':column' => $_definition->getColumn(),
        ]));
    }


    /**
     * Applies user changes
     *
     * @param bool                          $require_clean_source
     * @param array|ValidatorInterface|null $source
     *
     * @return static
     */
    public function apply(bool $require_clean_source = true, array|ValidatorInterface|null &$source = null): static
    {
        $_validator = Validator::pick($source);

        foreach ($this->_definitions as $_definition) {
            $_definition->validate($_validator);
        }

        $this->source = array_merge($this->source, $_validator->validate($require_clean_source));
        return $this;
    }


    /**
     * Save the configurations for this user
     *
     * @param bool        $force
     * @param bool        $skip_validation
     * @param string|null $comments
     *
     * @return static
     */
    public function save(bool $force = false, bool $skip_validation = false, ?string $comments = null): static
    {
        foreach ($this->_definitions as $column => $_definition) {
            if ($_definition->getVirtual()) {
                continue;
            }

            $this->saveColumn(array_get_safe($this->source, $column), $column, $_definition);
        }

        return $this;
    }


    /**
     * Save the specified column
     *
     * @param mixed               $value
     * @param string              $column
     * @param DefinitionInterface $_definition
     *
     * @return static
     */
    protected function saveColumn(mixed $value, string $column, DefinitionInterface $_definition): static
    {
        // Store display data in the SESSION object
        switch ($column) {
            case 'display_mode':
                // no break

            case 'compact_mode':
                Session::set($value, 'display', $column);
                break;
        }

        if ($value == $this->getConfiguredValue($_definition, false)) {
            config()->deleteUserPath($_definition->getProperty('configuration_path'));

        } else {
            config()->updateUserPath($value, $_definition->getProperty('configuration_path'));
        }

        return $this;
    }


    /**
     * Creates and returns an HTML for the phones
     *
     * @param string $name
     * @param bool   $meta_visible
     *
     * @return DataEntryFormInterface
     */
    public function getHtmlFormObject(string $name = 'configuration', bool $meta_visible = false): DataEntryFormInterface
    {
        return DataEntryForm::new()
                            ->setSource(Arrays::toStringFromBoolean($this->source, true: 'on', false: 'off'))
                            ->setReadonly($this->readonly)
                            ->setDisabled($this->disabled)
                            ->setDefinitionsObject($this->getDefinitionsObject());
    }


    /**
     * Returns the definitions for this object
     *
     * @return DefinitionsInterface
     */
    protected function initDefinitionsObject(): DefinitionsInterface
    {
        return Definitions::new()->add(Definition::new('timezones_name')
// TODO Implement with new GEO library
                                                 ->setRender(false)
                                                 ->setOptional(true, 'auto')
                                                 ->setSize(6)
                                                 ->setElement(EnumElement::select)
                                                 ->addProperty('getString'                    , 'configuration_method')
                                                 ->addProperty('locale.timezones.default.name', 'configuration_path')
                                                 ->setLabel(tr('Timezone'))
                                                 ->setHelpGroup(tr('Location information'))
                                                 ->setHelpText(tr('The timezone name where this user resides'))
                                                 ->setSource([
                                                 ])
                                                 ->addValidationFunction(function (ValidatorInterface $_validator) {
                                                     $_validator->existsInDatabase(tr('The specified timezone does not exist'), table: 'geo_timezones');
                                                 }))

                                 ->add(Definition::new('auto_signout')
                                                 ->setOptional(true, 0)
                                                 ->setInputType(EnumInputType::select)
                                                 ->setSize(6)
                                                 ->addProperty('getInteger'                               , 'configuration_method')
                                                 ->addProperty('security.web.sessions.auto.sign-out.value', 'configuration_path')
                                                 ->setLabel(tr('Automatically sign-out'))
                                                 ->setHelpText(tr('Here you can specify if you wish the system to sign you out automatically after X amount of seconds. Specify 0 to never automatically sign out'))
                                                 ->setSource(Arrays::convertToTimeDifference(config()->getArray('security.web.sessions.auto.sign-out.list', [
                                                     0, 60, 300, 600, 900, 1800, 3600, 7200, 14400, 28800, 43200, 86400,
                                                 ]), tr('Off'))))

                                 ->add(Definition::new('display_mode')
                                                 ->setOptional(true, 'light')
                                                 ->setInputType(EnumInputType::select)
                                                 ->setSize(6)
                                                 ->addProperty('getString'       , 'configuration_method')
                                                 ->addProperty('platforms.web.display.mode', 'configuration_path')
                                                 ->setLabel(tr('Display mode'))
                                                 ->setHelpText(tr('Here you can specify if you wish the user interface to be in dark mode, light mode, or use whatever your system uses'))
                                                 ->setSource([
                                                     'dark'  => tr('Dark'),
//                                                     'auto'  => tr('System'),
                                                     'light' => tr('Light'),
                                                 ]))

                                 ->add(Definition::new('compact_mode')
                                                 ->setOptional(true, false)
                                                 ->setInputType(EnumInputType::select)
                                                 ->setSize(6)
                                                 ->addProperty('getBoolean'         , 'configuration_method')
                                                 ->addProperty('platforms.web.display.compact', 'configuration_path')
                                                 ->setLabel(tr('Compact mode'))
                                                 ->setHelpText(tr('Here you can specify if you wish the user interface to be more compact, or not. If the user interface is more compact, you will scroll less, but it may be harder to click correctly'))
                                                 ->setSource([
                                                     'on'   => tr('On'),
                                                     'off'  => tr('Off'),
                                                 ])
                                                 ->addValidationFunction(function (ValidatorInterface $_validator) {
                                                     $_validator->isBoolean();
                                                 }))

                                 ->add(Definition::new('menu_open')
                                                 ->setOptional(true, false)
                                                 ->setInputType(EnumInputType::select)
                                                 ->setSize(6)
                                                 ->addProperty('getBoolean'                  , 'configuration_method')
                                                 ->addProperty('platforms.web.interface.user.menu.open', 'configuration_path')
                                                 ->setLabel(tr('Open menu after sign-in'))
                                                 ->setHelpText(tr('Here you can specify if you wish the user menu to be open or not when you sign in'))
                                                 ->setSource([
                                                     'on'   => tr('On'),
                                                     'off'  => tr('Off'),
                                                                 ])
                                                 ->addValidationFunction(function (ValidatorInterface $_validator) {
                                                     $_validator->isBoolean();
                                                 }))

                                 ->add(Definition::new('accordion_open')
                                                 ->setOptional(true, false)
                                                 ->setInputType(EnumInputType::select)
                                                 ->setSize(6)
                                                 ->addProperty('getBoolean'                       , 'configuration_method')
                                                 ->addProperty('platforms.web.interface.accordion.auto.open', 'configuration_path')
                                                 ->setLabel(tr('Auto open roster accordion'))
                                                 ->setHelpText(tr('Here you can specify if you wish the patient roster accordion to automatically open the first entry, or not'))
                                                 ->setSource([
                                                     'on'   => tr('On'),
                                                     'off'  => tr('Off'),
                                                                 ])
                                                 ->addValidationFunction(function (ValidatorInterface $_validator) {
                                                     $_validator->isBoolean();
                                                 }))

                                 ->add(DefinitionFactory::newDivider())

                                 ->add(DefinitionFactory::newUrl('default_page')
                                                        ->setDisabled(true)
                                                        ->setClearButton(true)
                                                        ->setOptional(true, '')
                                                        ->setSize(12)
                                                        ->addProperty('getString'        , 'configuration_method')
                                                        ->addProperty('platforms.web.pages.default', 'configuration_path')
                                                        ->setLabel(tr('Default page'))
                                                        ->setHelpText(tr('Here you can specify the default page you wish to see when you sign in'))
                                                        ->addScriptObjectCallback(function () {
                                                            return Script::new('$(".trailing.clear").on("click", function (e) {
                                                                                   e.preventDefault();
                                                                                   var $self = $(this);
                                                                                   $.post("' . Url::new('my/profile/default-url/clear.json')->makeAjax() . '")
                                                                                   .done(function (response) {
                                                                                       $self.siblings("input.form-control").val("");
                                                                                       $self.remove();
                                                                                   });                                                                    
                                                                                   return false;
                                                                                });');
                                                        }));
    }
}
