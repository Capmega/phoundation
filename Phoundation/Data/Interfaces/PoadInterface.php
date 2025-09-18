<?php

declare(strict_types=1);

namespace Phoundation\Data\Interfaces;


use PDOStatement;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;

interface PoadInterface
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
    public function getPoadArray(): array;

    /**
     * Returns the POAD array in JSON string format
     *
     * @return string
     */
    public function getPoadString(): string;
}
