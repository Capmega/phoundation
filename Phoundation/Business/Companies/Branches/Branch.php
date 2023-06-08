<?php

declare(strict_types=1);

namespace Phoundation\Business\Companies\Branches;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Interfaces\DataEntryFieldDefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Data\Validator\Interfaces\DataValidator;


/**
 * Class Branch
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Companies
 */
class Branch extends DataEntry
{
    use DataEntryNameDescription;


    /**
     * Department class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name = 'company branch';

        parent::__construct($identifier);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'business_branches';
    }


    /**
     * @inheritDoc
     */
    public function save(?string $comments = null): static
    {
        // TODO: Implement save() method.
    }


    /**
     * @inheritDoc
     */
    protected function validate(DataValidator $validator, bool $no_arguments_left, bool $modify): array
    {
        // TODO: Implement validate() method.
    }


    /**
     * Sets the available data keys for this entry
     *
     * @return DataEntryFieldDefinitionsInterface
     */
    protected static function setFieldDefinitions(): DataEntryFieldDefinitionsInterface
    {
        // TODO: Implement getFieldDefinitions() method.
        return [];
    }
}