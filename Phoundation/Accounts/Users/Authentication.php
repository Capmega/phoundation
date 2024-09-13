<?php

/**
 * Class Authentication
 *
 *
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;


class Authentication extends DataEntry
{
    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'accounts_authentications';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return tr('Account authentication');
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return null;
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(DefinitionFactory::getBoolean($this, 'captcha_required')
                                    ->setLabel(tr('Required CAPTCHA'))
                                    ->setDisabled(true)
                                    ->setOptional(true, false)
                                    ->setSize(2))

                    ->add(Definition::new($this, 'failed_reason')
                                    ->setLabel(tr('Reason why failed'))
                                    ->setDisabled(true)
                                    ->setOptional(true)
                                    ->setSize(10))

                    ->add(DefinitionFactory::getUsersId($this)
                        ->setOptional(true)
                        ->setSize(6))

                    ->add(DefinitionFactory::getVariable($this, 'username')
                        ->setLabel(tr('Used user account'))
                        ->setDisabled(true)
                        ->setSize(6))

                    ->add(DefinitionFactory::getIpAddress($this, 'ip')
                        ->setLabel(tr('IP address'))
                        ->setDisabled(true)
                        ->setOptional(true)
                        ->setSize(4))

                    ->add(Definition::new($this, 'action')
                        ->setLabel(tr('Action'))
                        ->setDisabled(true)
                        ->setOptional(true)
                        ->setSize(4))

                    ->add(Definition::new($this, 'method')
                        ->setLabel(tr('Method'))
                        ->setDisabled(true)
                        ->setOptional(true)
                        ->setSize(4));
    }
}
