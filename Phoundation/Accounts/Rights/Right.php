<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Rights;

use Phoundation\Accounts\Interfaces\RightInterface;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Web\Http\Html\Enums\InputTypeExtended;


/**
 * Class Right
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Right extends DataEntry implements RightInterface
{
    use DataEntryNameDescription;


    /**
     * Right class constructor
     *
     * @param DataEntry|string|int|null $identifier
     */
    public function __construct(DataEntry|string|int|null $identifier = null)
    {
        $this->entry_name   = 'right';

        parent::__construct($identifier);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'accounts_rights';
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $field_definitions
     */
    protected function initFieldDefinitions(DefinitionsInterface $field_definitions): void
    {
        $field_definitions
            ->add(Definition::new('name')
                ->setLabel(tr('Name'))
                ->setSize(12)
                ->setMaxlength(64)
                ->setHelpText(tr('The name for this right')))
            ->add(Definition::new('seo_name')
                ->setVisible(true)
                ->setReadonly(true))
            ->add(Definition::new('description')
                ->setOptional(true)
                ->setInputType(InputTypeExtended::description)
                ->setLabel(tr('Description'))
                ->setSize(12)
                ->setMaxlength(65_535)
                ->setHelpText(tr('The description for this right')));
    }
}