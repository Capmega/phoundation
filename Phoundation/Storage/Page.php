<?php

declare(strict_types=1);

namespace Phoundation\Storage;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataEntryFieldDefinitions;
use Phoundation\Data\DataEntry\Interfaces\DataEntryFieldDefinitionsInterface;


/**
 * Class Page
 *
 * This class is a page in the Phoundation storage system
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Storage
 */
class Page extends DataEntry
{
    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'storage_pages';
    }


    /**
     * Sets the available data keys for this entry
     *
     * @return DataEntryFieldDefinitionsInterface
     */
    protected static function setFieldDefinitions(): DataEntryFieldDefinitionsInterface
    {
        return DataEntryFieldDefinitions::new(static::getTable());
    }
}