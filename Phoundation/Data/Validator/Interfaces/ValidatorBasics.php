<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator\Interfaces;


/**
 * Interface ValidatorBasics
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
interface ValidatorBasics
{
    /**
     * Returns the maximum string size that this Validator will touch
     *
     * @return int|null
     */
    function getMaximumStringSize(): ?int;

    /**
     * Returns the maximum string size that this Validator will touch
     *
     * @param int|null $max_string_size
     * @return void
     */
    function setMaximumStringSize(?int $max_string_size): void;

    /**
     * Returns the parent field with the specified name
     *
     * @return string|null
     */
    function getParentField(): ?string;

    /**
     * Sets the parent field with the specified name
     *
     * @param string|null $field
     * @return void
     */
    function setParentField(?string $field): void;

    /**
     * This method will make the selected field optional and use the specified $default instead
     *
     * This means that either it may not exist, or it's contents may be NULL
     *
     * @param array|string|float|int|bool|null $default
     * @return static
     *
     * @see Validator::xor()
     * @see Validator::or()
     */
    function isOptional(array|string|float|int|bool|null $default = null): static;

    /**
     * Renames the current field to the specified field name
     *
     * @param string $field_name
     * @return $this
     */
    function rename(string $field_name): static;

    /**
     * This method will make sure that either this field OR the other specified field will have a value
     *
     * @param string $field
     * @param bool $rename
     * @return static
     *
     * @see Validator::isOptional()
     * @see Validator::or()
     */
    function xor(string $field, bool $rename = false): static;

    /**
     * This method will make sure that either this field OR the other specified field optionally will have a value
     *
     * @param string $field
     * @param mixed $default
     * @return static
     *
     * @see Validator::isOptional()
     * @see Validator::xor()
     */
    function or(string $field, mixed $default = null): static;

    /**
     * Will validate that the value of this field matches the value for the specified field
     *
     * @param string $field
     * @param bool $strict If true will execute a strict comparison where the datatype must match as well (so 1 would
     *                     not be the same as "1") for example
     * @return static
     * @see Validator::isOptional()
     */
    function isEqualTo(string $field, bool $strict = false): static;

    /**
     * Recurse into a sub array and return another validator object for that sub array
     *
     * @return static
     */
    function recurse(): static;

    /**
     * Called at the end of defining all validation rules.
     *
     * This method will check the failures array and if any failures were registered, it will throw an exception
     *
     * @return array
     */
    function validate(): array;

    /**
     * Resets the class for a new validation
     *
     * @return void
     */
    function clear(): void;

    /**
     * Add the specified failure message to the failures list
     *
     * @param string $failure
     * @param string|null $field
     * @return void
     */
    function addFailure(string $failure, ?string $field = null): void;

    /**
     * Returns the list of failures found during validation
     *
     * @return array
     */
    function getFailures(): array;
}