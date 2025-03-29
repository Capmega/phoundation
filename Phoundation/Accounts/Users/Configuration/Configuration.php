<?php

/**
 * Class User
 *
 * This class manages single user configuration entries
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users\Configuration;

use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\Traits\TraitDataStringPath;
use Phoundation\Data\Traits\TraitDataStringValue;
use Phoundation\Data\Traits\TraitDataUserObject;


class Configuration extends DataEntry
{
    use TraitDataUserObject;
    use TraitDataStringPath;
    use TraitDataStringValue;


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'accounts_configurations';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
    {
        return tr('Account configuration');
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
     *
     * @return static
     */
    protected function setDefinitions(DefinitionsInterface $definitions): static
    {
        $definitions->add(DefinitionFactory::newUsersId())

                    ->add(DefinitionFactory::newVariable('path')
                                           ->setMaxlength(255))

                    ->add(DefinitionFactory::newVariable('value')
                                           ->setMaxlength(65_535));

        return $this;
    }
}
