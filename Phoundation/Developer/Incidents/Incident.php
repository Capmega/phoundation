<?php

declare(strict_types=1);

namespace Phoundation\Developer\Incidents;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Data\DataEntry\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryDetails;
use Phoundation\Data\DataEntry\Traits\DataEntryException;
use Phoundation\Data\DataEntry\Traits\DataEntryTitle;
use Phoundation\Data\DataEntry\Traits\DataEntryType;
use Phoundation\Data\DataEntry\Traits\DataEntryUrl;
use Phoundation\Data\Interfaces\InterfaceDataEntry;


/**
 * Incident class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Incident extends DataEntry
{
    use DataEntryDescription;
    use DataEntryDetails;
    use DataEntryException;
    use DataEntryTitle;
    use DataEntryType;
    use DataEntryUrl;


    /**
     * Plugin class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name  = 'incident';

        parent::__construct($identifier);
    }


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
     * Sets the available data keys for this entry
     *
     * @return Definitions
     */
    protected function initFieldDefinitions(DefinitionsInterface $field_definitions): void
    {
        $field_definitions
            ->add(Definition::new('type')
                ->setReadonly(true)
                ->setLabel('Type')
                ->setSize(6)
                ->setMaxlength(255)
                ->addValidationFunction(function ($validator) {
                    $validator->isName(16);
                }))
            ->add(Definition::new('title')
                ->setReadonly(true)
                ->setLabel('Title')
                ->setSize(6)
                ->setMaxlength(255)
                ->addValidationFunction(function ($validator) {
                    $validator->hasMaxCharacters(255)->isPrintable();
                }))
            ->add(Definition::new('url')
                ->setReadonly(true)
                ->setLabel('URL')
                ->setSize(12)
                ->setMaxlength(2048)
                ->addValidationFunction(function ($validator) {
                    $validator->isUrl();
                }))
            ->add(Definition::new('description')
                ->setOptional(true)
                ->setLabel('Description')
                ->setSize(12)
                ->setMaxlength(255)
                ->addValidationFunction(function ($validator) {
                    $validator->isDescription();
                }))
            ->add(Definition::new('exception')
                ->setReadonly(true)
                ->setLabel('Exception')
                ->setSize(12)
                ->setMaxlength(16_777_200)
                ->addValidationFunction(function ($validator) {
                    $validator->isPrintable();
                }))
            ->add(Definition::new('data')
                ->setReadonly(true)
                ->setElement('text')
                ->setLabel('Data')
                ->setSize(12)
                ->setMaxlength(16_777_200));
    }
}
