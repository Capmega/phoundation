<?php

declare(strict_types=1);

namespace Phoundation\Developer\Incidents;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryData;
use Phoundation\Data\DataEntry\Traits\DataEntryDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryException;
use Phoundation\Data\DataEntry\Traits\DataEntryType;
use Phoundation\Data\DataEntry\Traits\DataEntryUrl;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Web\Html\Enums\InputElement;


/**
 * Incident class
 *
 *
 *
 * @see DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Incident extends DataEntry
{
    use DataEntryDescription;
    use DataEntryException;
    use DataEntryType;
    use DataEntryData;
    use DataEntryUrl;


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
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
     * @return void
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(Definition::new($this, 'type')
                ->setReadonly(true)
                ->setLabel('Type')
                ->setSize(6)
                ->setMaxlength(255)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isName(16);
                }))
            ->addDefinition(DefinitionFactory::getTitle($this)
                ->setSize(6))
            ->addDefinition(Definition::new($this, 'url')
                ->setOptional(true)
                ->setReadonly(true)
                ->setLabel('URL')
                ->setSize(12)
                ->setMaxlength(2048))
            ->addDefinition(DefinitionFactory::getDescription($this))
            ->addDefinition(Definition::new($this, 'exception')
                ->setOptional(true)
                ->setReadonly(true)
                ->setLabel('Exception')
                ->setSize(12)
                ->setMaxlength(16_777_200)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isPrintable();
                }))
            ->addDefinition(Definition::new($this, 'data')
                ->setOptional(true)
                ->setReadonly(true)
                ->setElement(InputElement::textarea)
                ->setLabel('Data')
                ->setSize(12)
                ->setMaxlength(16_777_200));
    }
}
