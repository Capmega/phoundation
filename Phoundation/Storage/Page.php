<?php

declare(strict_types=1);

namespace Phoundation\Storage;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Interfaces\DefinitionsInterface;


/**
 * Class Page
 *
 * This class is a page in the Phoundation storage system
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * @param DefinitionsInterface $field_definitions
     */
    protected function initFieldDefinitions(DefinitionsInterface $field_definitions): void
    {
        $field_definitions;
    }
}