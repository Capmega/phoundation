<?php

declare(strict_types=1);

namespace Phoundation\Data\Interfaces;


use PDOStatement;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;

interface ArraySourceMethodsInterface
{
    /**
     * Returns a new DataEntry object from the specified array source
     *
     * @param ArraySourceInterface|array|string|null $source
     *
     * @return ArraySourceMethodsInterface|null
     */
    public static function newFromSourceOrNull(ArraySourceInterface|array|string|null $source): ?static;


    /**
     * Returns a new DataEntry object from the specified array source
     *
     * @param DataEntryInterface|IteratorInterface|PDOStatement|array|string|null $source
     *
     * @return static
     */
    public static function newFromSource(DataEntryInterface|IteratorInterface|PDOStatement|array|string|null $source = null): static;

    /**
     * Returns the source
     *
     * @return array
     */
    public function getSource(): array;

    /**
     * Returns a list of all internal definition keys
     *
     * @return mixed
     */
    public function getSourceKeys(): array;
}
