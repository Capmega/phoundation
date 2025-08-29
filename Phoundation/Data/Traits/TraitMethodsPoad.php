<?php

/**
 * Trait TraitMethodsPoad
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\Enums\EnumPoadTypes;
use Phoundation\Data\Poad\Poad;


trait TraitMethodsPoad
{
    /**
     * Returns the source data when cast to array in POA (Phoundation Object Array) format. This format allows any
     * object to be recreated from this array
     *
     * POA structures must have the following format
     * [
     *     "datatype" => The phoundation version that created this array
     *     "datatype" => "object"
     *     "class"    => The class name (static::class should suffice)
     *     "source"   => The object's source data
     * ]
     *
     * @return array
     */
    public function getPoadArray(): array
    {
        if ($this instanceof DataEntryInterface) {
            return Poad::generateArray($this->getSourceUnprocessed(), static::class, EnumPoadTypes::object);
        }

        return Poad::generateArray($this->getSource(), static::class, EnumPoadTypes::object);
    }


    /**
     * Returns the POAD array in JSON string format
     *
     * @param bool $force_pretty_print
     *
     * @return string
     */
    public function getPoadString(bool $force_pretty_print = false): string
    {
        if ($this instanceof DataEntryInterface) {
            return Poad::generateString($this->getSourceUnprocessed(), static::class, EnumPoadTypes::object, null, $force_pretty_print);
        }

        return Poad::generateString($this->getSource(), static::class, EnumPoadTypes::object, null, $force_pretty_print);
    }
}
