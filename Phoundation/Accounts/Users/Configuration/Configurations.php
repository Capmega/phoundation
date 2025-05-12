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
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumInputType;


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

        foreach ($this->o_definitions as $column => $o_definition) {
            if ($o_definition->getVirtual()) {
                continue;
            }

            $source[$column] = $this->getConfiguredValue($o_definition);
        }

        return $this->setSource($source);
    }


    /**
     * Returns the configured value for the specified column
     *
     * @param DefinitionInterface $o_definition
     * @param bool                $allow_user_configuration
     *
     * @return mixed
     */
    protected function getConfiguredValue(DefinitionInterface $o_definition, bool $allow_user_configuration = true): mixed
    {
        $method = $o_definition->getProperty('configuration_method');

        if ($method) {
            return config()->$method($o_definition->getProperty('configuration_path'), $o_definition->getDefault(), $allow_user_configuration, false);
        }

        throw new OutOfBoundsException(tr('Cannot get configured value for column ":column", the column has no method property specified', [
            ':column' => $o_definition->getColumn(),
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
        $o_validator = Validator::pick($source);

        foreach ($this->o_definitions as $o_definition) {
            $o_definition->validate($o_validator);
        }

        $result       = $o_validator->validate($require_clean_source);
        $this->source = array_merge($this->source, $result);

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
        $source = Arrays::toBooleanFromString($this->source);

        foreach ($this->o_definitions as $column => $o_definition) {
            if ($o_definition->getVirtual()) {
                continue;
            }

            $this->saveColumn(array_get_safe($source, $column), $column, $o_definition);
        }

        return $this;
    }


    /**
     * Save the specified column
     *
     * @param mixed               $value
     * @param string              $column
     * @param DefinitionInterface $o_definition
     *
     * @return static
     */
    protected function saveColumn(mixed $value, string $column, DefinitionInterface $o_definition): static
    {
        // Store display data in the SESSION object
        switch ($column) {
            case 'dark_mode':
                // no break

            case 'compact_mode':
                Session::set($value, 'display', $column);
                break;
        }

        if ($value == $this->getConfiguredValue($o_definition, false)) {
            config()->deleteUserPath($o_definition->getProperty('configuration_path'));

        } else {
            config()->updateUserPath($value, $o_definition->getProperty('configuration_path'));
        }

        return $this;
    }


    /**
     * Returns the definitions for this object
     *
     * @return DefinitionsInterface
     */
    protected function initDefinitionsObject(): DefinitionsInterface
    {
        return Definitions::new()->add(Definition::new('timezones_name')
                                                 ->setOptional(true, 'auto')
                                                 ->setSize(6)
                                                 ->setElement(EnumElement::select)
                                                 ->addProperty('getString'                    , 'configuration_method')
                                                 ->addProperty('locale.timezones.default.name', 'configuration_path')
                                                 ->setLabel(tr('Timezone'))
                                                 ->setHelpGroup(tr('Location information'))
                                                 ->setHelpText(tr('The timezone name where this user resides'))
                                                 ->setDataSource([
                                                 ])
                                                 ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                                     $o_validator->columnExists(tr('The specified timezone does not exist'), table: 'geo_timezones');
                                                 }))

                                 ->add(Definition::new('auto_signout')
                                                 ->setOptional(true, 0)
                                                 ->setInputType(EnumInputType::select)
                                                 ->setSize(6)
                                                 ->addProperty('getInteger'                               , 'configuration_method')
                                                 ->addProperty('web.security.sessions.auto.sign-out.value', 'configuration_path')
                                                 ->setLabel(tr('Automatically sign-out'))
                                                 ->setHelpText(tr('Here you can specify if you wish the system to sign you out automatically after X amount of seconds. Specify 0 to never automatically sign out'))
                                                 ->setDataSource(Arrays::convertToTimeDifference(config()->getArray('web.security.sessions.auto.sign-out.list', [
                                                     0, 300, 600, 900, 1800, 3600, 7200, 14400, 28800, 43200, 86400,
                                                 ]), tr('Off'))))

                                 ->add(Definition::new('dark_mode')
                                                 ->setOptional(true, false)
                                                 ->setInputType(EnumInputType::select)
                                                 ->setSize(6)
                                                 ->addProperty('getBoolean'            , 'configuration_method')
                                                 ->addProperty('web.display.modes.dark', 'configuration_path')
                                                 ->setLabel(tr('Dark mode'))
                                                 ->setHelpText(tr('Here you can specify if you wish the user interface to be in dark mode, light mode, or use whatever your system uses'))
                                                 ->setDataSource([
                                                     'on'   => tr('On'),
//                                                     'auto' => tr('System'),
                                                     'off'  => tr('Off'),
                                                 ]))

                                 ->add(Definition::new('compact_mode')
                                                 ->setOptional(true, false)
                                                 ->setInputType(EnumInputType::select)
                                                 ->setSize(6)
                                                 ->addProperty('getBoolean'         , 'configuration_method')
                                                 ->addProperty('web.display.compact', 'configuration_path')
                                                 ->setLabel(tr('Compact mode'))
                                                 ->setHelpText(tr('Here you can specify if you wish the user interface to be more compact, or not. If the user interface is more compact, you will scroll less, but it may be harder to click correctly'))
                                                 ->setDataSource([
                                                     'on'   => tr('On'),
                                                     'off'  => tr('Off'),
                                                 ]))

                                 ->add(Definition::new('menu_open')
                                                 ->setOptional(true, false)
                                                 ->setInputType(EnumInputType::select)
                                                 ->setSize(6)
                                                 ->addProperty('getBoolean'                  , 'configuration_method')
                                                 ->addProperty('web.interface.user.menu.open', 'configuration_path')
                                                 ->setLabel(tr('Open menu after sign-in'))
                                                 ->setHelpText(tr('Here you can specify if you wish the user menu to be open or not when you sign in'))
                                                 ->setDataSource([
                                                     'on'   => tr('On'),
                                                     'off'  => tr('Off'),
                                                 ]))

                                 ->add(Definition::new('accordion_open')
                                                 ->setOptional(true, false)
                                                 ->setInputType(EnumInputType::select)
                                                 ->setSize(6)
                                                 ->addProperty('getBoolean'                       , 'configuration_method')
                                                 ->addProperty('web.interface.accordion.auto.open', 'configuration_path')
                                                 ->setLabel(tr('Auto open roster accordion'))
                                                 ->setHelpText(tr('Here you can specify if you wish the patient roster accordion to automatically open the first entry, or not'))
                                                 ->setDataSource([
                                                     'on'   => tr('On'),
                                                     'off'  => tr('Off'),
                                                 ]))

                                 ->add(DefinitionFactory::newDivider())

                                 ->add(DefinitionFactory::newUrl('default_page')
                                                        ->setDisabled(true)
                                                        ->setOptional(true, '')
                                                        ->setSize(12)
                                                        ->addProperty('getString'        , 'configuration_method')
                                                        ->addProperty('web.pages.default', 'configuration_path')
                                                        ->setLabel(tr('Default page'))
                                                        ->setHelpText(tr('Here you can specify the default page you wish to see when you sign in')));
    }


    /**
     * Creates and returns an HTML for the phones
     *
     * @param string $name
     * @param bool   $meta_visible
     *
     * @return DataEntryFormInterface
     */
    public function getHtmlDataEntryFormObject(string $name = 'configuration', bool $meta_visible = false): DataEntryFormInterface
    {
        return DataEntryForm::new()
                            ->setSource(Arrays::toStringFromBoolean($this->source, true: 'on', false: 'off'))
                            ->setReadonly($this->readonly)
                            ->setDisabled($this->disabled)
                            ->setDefinitionsObject($this->getDefinitionsObject());
    }
}
