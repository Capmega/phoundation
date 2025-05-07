<?php

/**
 * Class DataEntryTesting
 *
 * This class will allow testing of DataEntry objects
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Tests;

use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryName;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryParent;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Web\Html\Enums\EnumInputType;

class TestDataEntry extends DataEntry
{
    use TraitDataEntryName;
    use TraitDataEntryDescription;
    use TraitDataEntryParent;


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'test_dataentries';
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'seo_name';
    }


    /**
     * Returns the test-column field for this TestDataEntry object
     *
     * @return string|null
     */
    public function getTestColumn(): ?string
    {
        return $this->getTypesafe('string', 'test_column');
    }


    /**
     * Sets the test-column field for this TestDataEntry object
     *
     * @param string|null $value
     *
     * @return TestDataEntry
     */
    public function setTestColumn(?string $value): static
    {
        return $this->set($value, 'test_column');
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $definitions): static
    {
        $definitions->add(DefinitionFactory::newName()
                                           ->setOptional(false)
                                           ->setInputType(EnumInputType::name)
                                           ->setSize(12)
                                           ->setMaxlength(64)
                                           ->setHelpText(tr('The name for this TestDataEntry'))
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isUnique();
                                           }))

                    ->add(DefinitionFactory::newSeoName())

                    ->add(DefinitionFactory::newCode('test_column'))

                    ->add(DefinitionFactory::newParentsId())

                    ->add(DefinitionFactory::newDescription()
                                           ->setHelpText(tr('The description for this TestDataEntry')));

        return $this;
    }
}
