<?php

namespace Phoundation\Data\DataEntry\Definitions\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Stringable;


/**
 * Class Definitions
 *
 * Contains a collection of Definition objects for a DataEntry class and can validate the values
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
interface DefinitionsInterface extends IteratorInterface
{
    /**
     * Returns the field prefix
     *
     * @return string|null
     */
    public function getFieldPrefix(): ?string;

    /**
     * Sets the field prefix
     *
     * @param string|null $prefix
     * @return static
     */
    public function setFieldPrefix(?string $prefix): static;

    /**
     * Returns the data entry
     *
     * @return DataEntryInterface
     */
    public function getDataEntry(): DataEntryInterface;

    /**
     * Sets the data entry
     *
     * @param DataEntryInterface $data_entry
     * @return static
     */
    public function setDataEntry(DataEntryInterface $data_entry): static;

    /**
     * Adds the specified Definition to the fields list
     *
     * @param DefinitionInterface $field
     * @return static
     */
    public function addDefinition(DefinitionInterface $field): static;

    /**
     * Returns the current Definition object
     *
     * @return DefinitionInterface
     */
    public function current(): DefinitionInterface;

    /**
     * Returns the current key for the current button
     *
     * @return string|float|int|null
     */
    public function key(): string|float|int|null;

    /**
     * Returns the specified field
     *
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return DefinitionInterface
     */
    public function get(Stringable|string|float|int $key, bool $exception = false): DefinitionInterface;

    /**
     * Returns the first Definition entry
     *
     * @return DefinitionInterface
     */
    public function getFirst(): DefinitionInterface;

    /**
     * Returns the last Definition entry
     *
     * @return DefinitionInterface
     */
    public function getLast(): DefinitionInterface;
}