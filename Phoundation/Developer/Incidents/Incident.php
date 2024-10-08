<?php

declare(strict_types=1);

namespace Phoundation\Developer\Incidents;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryData;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryException;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryType;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryUrl;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Web\Html\Enums\EnumElement;

/**
 * Incident class
 *
 *
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */
class Incident extends DataEntry
{
    use TraitDataEntryDescription;
    use TraitDataEntryException;
    use TraitDataEntryType;
    use TraitDataEntryData;
    use TraitDataEntryUrl;

    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): ?string
    {
        return 'developer_incidents';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return 'incident';
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'code';
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     *
     * @return void
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(Definition::new($this, 'type')
                                    ->setReadonly(true)
                                    ->setLabel('Type')
                                    ->setSize(6)
                                    ->setMaxlength(255)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isName(16);
                                    }))
                    ->add(DefinitionFactory::getTitle($this)
                                           ->setSize(6))
                    ->add(Definition::new($this, 'url')
                                    ->setOptional(true)
                                    ->setReadonly(true)
                                    ->setLabel('URL')
                                    ->setSize(12)
                                    ->setMaxlength(2048))
                    ->add(DefinitionFactory::getDescription($this))
                    ->add(Definition::new($this, 'exception')
                                    ->setOptional(true)
                                    ->setReadonly(true)
                                    ->setLabel('Exception')
                                    ->setSize(12)
                                    ->setMaxlength(16_777_200)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isPrintable();
                                    }))
                    ->add(Definition::new($this, 'data')
                                    ->setOptional(true)
                                    ->setReadonly(true)
                                    ->setElement(EnumElement::textarea)
                                    ->setLabel('Data')
                                    ->setSize(12)
                                    ->setMaxlength(16_777_200));
    }
}
