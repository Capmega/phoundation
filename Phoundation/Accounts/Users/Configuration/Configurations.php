<?php

/**
 * Class Configurations
 *
 * This class can manage various user configuration entries.
 *
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
use Phoundation\Data\DataEntries\DataIteratorCore;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Definitions;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Html\Components\Forms\DataEntryForm;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Http\Url;


class Configurations extends DataIteratorCore implements ConfigurationsInterface
{
    /**
     * Configurations class constructor
     *
     * @param UserInterface $user
     */
    public function __construct(UserInterface $user) {
        $this->setAcceptedDataTypes(static::getDefaultContentDataType());
        $this->setParentObject($user);

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
     * Returns the class for a single DataEntry in this Iterator object
     *
     * @return string|null
     */
    public static function getDefaultContentDataType(): ?string
    {
        return Configuration::class;
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
        $source = [
            'timezones_name' => sessionconfig()->getString('locale.timezones.default.name'    , 'PST'),
            'dark_mode'      => sessionconfig()->getBoolean('web.interface.user.modes.dark'   , false),
            'compact_mode'   => sessionconfig()->getBoolean('web.interface.user.modes.compact', false),
            'open_menu'      => sessionconfig()->getBoolean('web.interface.user.menu.open'    , false),
            'default_page'   => sessionconfig()->getString('default_page'                     , ''),
        ];

        $source      = Arrays::toStringFromBoolean($source);
        $definitions = Definitions::new()->add(DefinitionFactory::newTimezonesName()
                                                                ->setSize(6)
                                                                ->setHelpGroup(tr('Location information'))
                                                                ->setHelpText(tr('The timezone name where this user resides')))

                                         ->add(Definition::new('dark_mode')
                                                         ->setInputType(EnumInputType::select)
                                                         ->setSize(6)
                                                         ->setLabel(tr('Dark mode'))
                                                         ->setHelpText(tr('Here you can specify if you wish the user interface to be in dark mode, light mode, or use whatever your system uses'))
                                                         ->setDataSource([
                                                              1      => tr('On'),
                                                              'auto' => tr('System'),
                                                              0      => tr('Off'),
                                                         ]))

                                         ->add(Definition::new('compact_mode')
                                                         ->setInputType(EnumInputType::select)
                                                         ->setSize(6)
                                                         ->setLabel(tr('Compact mode'))
                                                         ->setHelpText(tr('Here you can specify if you wish the user interface to be more compact, or not. If the user interface is more compact, you will scroll less, but it may be harder to click correctly'))
                                                         ->setDataSource([
                                                             'on'   => tr('On'),
                                                             'off'  => tr('Off'),
                                                         ]))

                                         ->add(Definition::new('open_menu')
                                                        ->setInputType(EnumInputType::select)
                                                         ->setSize(6)
                                                         ->setLabel(tr('Open menu'))
                                                         ->setHelpText(tr('Here you can specify if you wish the user menu to be open or not when you sign in'))
                                                         ->setDataSource([
                                                             'on'   => tr('On'),
                                                             'off'  => tr('Off'),
                                                         ]))

                                        ->add(DefinitionFactory::newUrl('default_page')
                                                        ->setSize(6)
                                                        ->setLabel(tr('Default page'))
                                                        ->setHelpText(tr('Here you can specify the default page you wish to see when you sign in')));

        return DataEntryForm::new()
                            ->setSource($source)
                            ->setReadonly($this->readonly)
                            ->setDisabled($this->disabled)
                            ->setDefinitionsObject($definitions);
    }
}
