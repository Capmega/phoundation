<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Rights;

use Phoundation\Accounts\Interfaces\InterfaceRight;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataEntryFieldDefinition;
use Phoundation\Data\DataEntry\DataEntryFieldDefinitions;
use Phoundation\Data\DataEntry\Interfaces\DataEntryFieldDefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\Interfaces\InterfaceDataEntry;


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
class Right extends DataEntry implements InterfaceRight
{
    use DataEntryNameDescription;


    /**
     * Right class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name = 'right';

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
     * @param DataEntryFieldDefinitionsInterface $field_definitions
     */
    protected function initFieldDefinitions(DataEntryFieldDefinitionsInterface $field_definitions): void
    {
        $field_definitions
            ->add(DataEntryFieldDefinition::new('name')
                ->setLabel(tr('Name'))
                ->setSize(12)
                ->setMaxlength(64)
                ->setHelpText(tr('The name for this right')))
            ->add(DataEntryFieldDefinition::new('seo_name')
                ->setVisible(true)
                ->setReadonly(true))
            ->add(DataEntryFieldDefinition::new('description')
                ->setOptional(true)
                ->setLabel(tr('Description'))
                ->setSize(12)
                ->setMaxlength(65_535)
                ->setHelpText(tr('The description for this right')));
    }
}