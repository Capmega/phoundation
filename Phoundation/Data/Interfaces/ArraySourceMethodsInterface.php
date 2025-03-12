<?php

declare(strict_types=1);

namespace Phoundation\Data\Interfaces;


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
     * @param ArraySourceInterface|array|string $source
     *
     * @return static
     */
    public static function newFromSource(ArraySourceInterface|array|string $source): static;

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
