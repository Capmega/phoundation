<?php

namespace Phoundation\Data\Traits;

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
        return Poad::generateArray($this->getSource(false, false), static::class, EnumPoadTypes::object);
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
        return Poad::generateString($this->getSource(false, false), static::class, EnumPoadTypes::object, null, $force_pretty_print);
    }
}
