<?php

/**
 * Class TestDataIterator
 * This class will be used for unit tests related to the DataIterator and DataIteratorCore classes
 *
 * @see       DataIterator
 * @author    Harrison Macey <harrison@medinet.ca>
 * @author    Sven Olaf Oostenbrink <sven@medinet.ca>
 * @license   This plugin is developed by Medinet and may only be used by others with explicit written authorization
 * @copyright Copyright © 2025 Medinet <copyright@medinet.ca>
 * @package   Plugins\Medinet\Wards
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Tests;

use Phoundation\Data\DataEntries\DataIterator;


class TestDataIterator extends DataIterator
{
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
     * Returns the class for a single DataEntry in this Iterator object
     *
     * @return string|null
     */
    public static function getDefaultContentDataType(): ?string
    {
        return TestDataEntry::class;
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
}
