<?php

declare(strict_types=1);

namespace Phoundation\Developer\Incidents;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionDefaults;
use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryDetails;
use Phoundation\Data\DataEntry\Traits\DataEntryException;
use Phoundation\Data\DataEntry\Traits\DataEntryTitle;
use Phoundation\Data\DataEntry\Traits\DataEntryType;
use Phoundation\Data\DataEntry\Traits\DataEntryUrl;


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
     * @param DataEntryInterface|string|int|null $identifier
     * @param bool $init
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, bool $init = false)
    {
        $this->table       = 'developer_incidents';
        $this->entry_name  = 'incident';

        parent::__construct($identifier, $init);
    }


    /**
     * Sets the available data keys for this entry
     *
     * @return Definitions
     */
    protected function initDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->add(Definition::new('type')
                ->setReadonly(true)
                ->setLabel('Type')
                ->setSize(6)
                ->setMaxlength(255)
                ->addValidationFunction(function ($validator) {
                    $validator->isName(16);
                }))
            ->add(DefinitionDefaults::getTitle()
                ->setSize(6))
            ->add(Definition::new('url')
                ->setReadonly(true)
                ->setLabel('URL')
                ->setSize(12)
                ->setMaxlength(2048)
                ->addValidationFunction(function ($validator) {
                    $validator->isUrl();
                }))
            ->add(DefinitionDefaults::getDescription())
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
