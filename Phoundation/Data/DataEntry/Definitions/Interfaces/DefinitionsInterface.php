<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Definitions\Interfaces;

use PDOStatement;
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
     * Iterator class constructor
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null $execute
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null);

    /**
     * Returns a new Iterator object
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null $execute
     * @return static
     */
    public static function new(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static;

    /**
     * Returns the table
     *
     * @return string|null
     */
    public function getTable(): ?string;

    /**
     * Sets the table
     *
     * @param string|null $table
     * @return static
     */
    public function setTable(?string $table): static;

    /**
     * Returns the field prefix string
     *
     * @return ?string
     */
    public function getFieldPrefix(): ?string;

    /**
     * Sets the field prefix string
     *
     * @param string|null $prefix
     * @return $this
     */
    public function setFieldPrefix(?string $prefix): static;

    /**
     * Adds the specified Definition to the fields list
     *
     * @param DefinitionInterface $field
     * @return static
     */
    public function add(DefinitionInterface $field): static;

    /**
     * Returns the current Definition object
     *
     * @return DefinitionInterface
     */
    public function current(): DefinitionInterface;

    /**
     * Progresses the internal pointer to the next Definition object
     *
     * @return static
     */
    public function next(): static;

    /**
     * Returns the current key for the current menu
     *
     * @return string|float|int
     */
    public function key(): string|float|int;

    /**
     * Returns if the current pointer is valid or not
     *
     * @todo Is this really really required? Since we're using internal array pointers anyway, it always SHOULD be valid
     * @return bool
     */
    public function valid(): bool;

    /**
     * Rewinds the internal pointer
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function rewind(): static;

    /**
     * Returns the Definitions fields array in a JSON string
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Returns the Definitions array
     *
     * @return array
     */
    public function __toArray(): array;

    /**
     * Returns the specified field
     *
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return DefinitionInterface
     */
    public function get(Stringable|string|float|int $key, bool $exception = false): DefinitionInterface;

    /**
     * Returns if the specified Definition exists or not
     *
     * @param Stringable|string|float|int $key
     * @return bool
     */
    function exists(Stringable|string|float|int $key): bool;

    /**
     * Returns the Definitions array
     *
     * @return array
     */
    public function getSource(): array;

    /**
     * Returns the amount of Definitions
     *
     * @return int
     */
    public function getCount(): int;

    /**
     * Returns true if the list is empty and has no Definition objects
     *
     * @return bool
     */
    public function isEmpty(): bool;

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

    /**
     * Clears the Definitions list
     *
     * @return $this
     */
    public function clear(): static;

    /**
     * Deletes the Definitions with the specified key
     *
     * @return $this
     */
    public function delete(string|float|int $key): static;
}