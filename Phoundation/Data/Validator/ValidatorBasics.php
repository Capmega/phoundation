<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator;

use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Validator\Exception\NoKeySelectedException;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Exception\ValidatorException;
use ReflectionProperty;


/**
 * ValidatorBasics trait
 *
 * This class contains the basic controls for Validator classes
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
trait ValidatorBasics
{
    /**
     * The source data that we will be validating
     *
     * @var array|string|null $source
     */
    protected array|string|null $source = null;

    /**
     * Register for the failures occurred during validations
     *
     * @var array $failures
     */
    protected array $failures = [];

    /**
     * The current field that is being validated
     *
     * @var string|int|null $selected_field
     */
    protected string|int|null $selected_field = null;

    /**
     * The keys that have been selected to be validated. All keys found in the $source array that are not in this array
     * will be removed
     *
     * @var array $selected_fields
     */
    protected array $selected_fields = [];

    /**
     * The value that is selected for testing
     *
     * @var mixed|null $selected_value
     */
    protected mixed $selected_value = null;

    /**
     * If not NULL, the currently selected field may be non-existent or NULL, it will receive this default value
     *
     * @var mixed $selected_optional
     */
    protected mixed $selected_optional;

    /**
     * The value(s) that actually will be tested. This most of the time will be an array with a single reference to
     * $selected_value, but when ->each() validates a list of values, this will reference that list directly
     *
     * @var array|null $process_values
     */
    protected ?array $process_values = null;

    /**
     * The single key that actually will be tested.
     *
     * @var int|string $process_key
     */
    protected mixed $process_key = null;

    /**
     * The single value that actually will be tested.
     *
     * @var mixed $process_value
     */
    protected mixed $process_value;

    /**
     * Registers when the single value being tested failed during multiple tests or not
     *
     * @var bool $process_value_failed
     */
    protected bool $process_value_failed = false;

    /**
     * If specified, this is a child element to a parent.
     *
     * The ->validate() call will NOT cause an exception but instead will send the failures list to the parent and then
     * return the parent element
     *
     * @var Validator|null
     */
    protected ?Validator $parent = null;

    /**
     * If set, all field failure keys will show the parent field as well
     *
     * @var string|null
     */
    protected ?string $parent_field = null;

    /**
     * Child Validator object for sub array elements. When validating the final result, the results from all the child
     * validators will be added to the result as well
     *
     * @var array $children
     */
    protected array $children = [];

    /**
     * The maximum string size that this validator will touch
     *
     * @var int $max_string_size
     */
    protected int $max_string_size = 1073741824;

    /**
     * Required to test if selected_optional property is initialized or not
     *
     * @var ReflectionProperty $reflection_selected_optional
     */
    protected ReflectionProperty $reflection_selected_optional;

    /**
     * Required to test if process_value property is initialized or not
     *
     * @var ReflectionProperty $reflection_process_value
     */
    protected ReflectionProperty $reflection_process_value;

    /**
     * If true, all validations will ALWAYS pass
     *
     * @note Still, ONLY validated variables will be available after Validate::validate() has been executed!
     * @var bool $disabled
     */
    protected static bool $disabled = false;

    /**
     * If true, ONLY password validations will ALWAYS pass
     *
     * @note Still, ONLY validated variables will be available after Validate::validate() has been executed!
     * @var bool $password_disabled
     */
    protected static bool $password_disabled = false;


    /**
     * Returns the maximum string size that this Validator will touch
     *
     * @return string|null
     */
    public function getMaximumStringSize(): ?string
    {
        return $this->max_string_size;
    }


    /**
     * Returns the maximum string size that this Validator will touch
     *
     * @param string|null $max_string_size
     * @return void
     */
    public function setMaximumStringSize(?string $max_string_size): void
    {
        $this->max_string_size = $max_string_size;
    }


    /**
     * Returns the parent field with the specified name
     *
     * @return string|null
     */
    public function getParentField(): ?string
    {
        return $this->parent_field;
    }


    /**
     * Sets the parent field with the specified name
     *
     * @param string|null $field
     * @return void
     */
    public function setParentField(?string $field): void
    {
        $this->parent_field = $field;
    }


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
    public function isOptional(array|string|float|int|bool|null $default = null): static
    {
        $this->selected_optional = $default;
        return $this;
    }


    /**
     * Renames the current field to the specified field name
     *
     * @param string $field_name
     * @return $this
     */
    public function rename(string $field_name): static
    {
        $this->source[$field_name] = $this->source[$this->selected_field];
        unset($this->source[$this->selected_field]);
        $this->selected_field = $field_name;

        return $this;
    }


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
    public function xor(string $field, bool $rename = false): static
    {
        if ($this->selected_field === $field) {
            throw new ValidatorException(tr('Cannot validate XOR field ":field" with itself', [':field' => $field]));
        }

        if (isset_get($this->source[$this->selected_field])) {
            // The currently selected field exists, the specified field cannot exist
            if (isset_get($this->source[$field])) {
                $this->addFailure(tr('Both fields ":field" and ":selected_field" were set, where only either one of them are allowed', [
                    ':field' => $field,
                    ':selected_field' => $this->selected_field
                ]));
            }

            if ($rename) {
                // Rename this field to the specified field
                $this->rename($field);
            }
        } else {
            // The currently selected field does not exist, the specified field MUST exist
            if (!isset_get($this->source[$field])) {
                $this->addFailure(tr('Neither fields ":field" and ":selected_field" were set, where either one of them must be set', [
                    ':field' => $field,
                    ':selected_field' => $this->selected_field
                ]));

            } else {
                // Yay, the alternate field exists, so this one can be made optional.
                $this->isOptional();
            }
        }

        return $this;
    }


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
    public function or(string $field, mixed $default = null): static
    {
        if ($this->selected_field === $field) {
            throw new ValidatorException(tr('Cannot validate OR field ":field" with itself', [':field' => $field]));
        }

        if (isset_get($this->source[$this->selected_field])) {
            // The currently selected field exists, the specified field cannot exist
            if (isset_get($this->source[$field])) {
                $this->addFailure(tr('Both fields ":field" and ":selected_field" were set, where only either one of them are allowed', [':field' => $field, ':selected_field' => $this->selected_field]));
            }
        } else {
            // The currently selected field does not exist, so we default
            $this->isOptional($default);
        }

        return $this;
    }


    /**
     * Will validate that the value of this field matches the value for the specified field
     *
     * @param string $field
     * @param bool $strict If true will execute a strict comparison where the datatype must match as well (so 1 would
     *                     not be the same as "1") for example
     * @return static
     * @see Validator::isOptional()
     */
    public function isEqualTo(string $field, bool $strict = false): static
    {
        return $this->validateValues(function($value) use ($field, $strict) {
            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if ($strict) {
                if ($value !== $this->source[$field]) {
                    $this->addFailure(tr('must contain exactly the same value as the field ":field"', [':field' => $field]));
                }
            } else {
                if ($value != $this->source[$field]) {
                    $this->addFailure(tr('must contain the same value as the field ":field"', [':field' => $field]));
                }
            }

            return $value;
        });
    }


    /**
     * Recurse into a sub array and return another validator object for that sub array
     *
     * @return static
     */
    public function recurse(): static
    {
        $this->ensureSelected();

        // Create a new Validator object from the current value. If the current value is not an array (oopsie) then just
        // send in an empty array so that the Validation chain won't break
        if (!is_array($this->selected_value)) {
            $array = [];
            $child = new static($array, $this);
        } else {
            $child = new static($this->selected_value, $this);
        }

        $child->setParentField(($this->parent_field ? $this->parent_field . ' > ' : '') . $this->selected_field);

        $this->children[$this->selected_field] = $child;
        return $child;
    }


    /**
     * Called at the end of defining all validation rules.
     *
     * This method will check the failures array and if any failures were registered, it will throw an exception
     *
     * @return array
     */
    public function validate(): array
    {
        // Remove all unselected and all failed fields
        foreach ($this->source as $field => $value) {
            // Unprocessed fields
            if (!in_array($field, $this->selected_fields)) {
                unset($this->source[$field]);
                continue;
            }

            // Failed fields
            if (array_key_exists($field, $this->failures)) {
                unset($this->source[$field]);
            }
        }

        if ($this->parent) {
            // Copy failures from the child to the parent and return the parent to continue
            foreach ($this->failures as $field => $failure) {
                $this->parent->addFailure($failure, $field);
            }

            // Clear the contents of this object to avoid stuck references
            $this->clear();
            return $this->parent;
        }

        if ($this->failures) {
            throw ValidationFailedException::new(tr('Data validation failed with the following issues:'), $this->failures)->makeWarning();
        }

        return $this->source;
    }


    /**
     * Resets the class for a new validation
     *
     * @return void
     */
    public function clear(): void
    {
        unset($this->selected_fields);
        unset($this->selected_value);
        unset($this->process_values);
        unset($this->process_value);
        unset($this->source);

        $this->selected_fields = [];
        $this->selected_value  = null;
        $this->process_values  = null;
        $this->source          = null;
    }


    /**
     * Return if this field is optional or not
     *
     * @param mixed $value The value to test
     * @return bool
     */
    protected function checkIsOptional(mixed &$value): bool
    {
        if ($this->process_value_failed) {
            // Value processing already failed anyway, so always fail
            return false;
        }

// DEBUG CODE: In case of errors with validation, its very useful to have these debugged here
//        show($this->selected_field);
//        show($value);

        if (!$value) {
            if (($value !== 0) and ($value !== "0")) {
                if (!$this->reflection_selected_optional->isInitialized($this)){
                    // At this point we know we MUST have a value, so we're bad here
                    $this->addFailure(tr('is required'));
                    return false;
                }

                // If value is set or not doesn't matter, it's okay
                $value = $this->selected_optional;
                $this->process_value_failed = true;
                return false;
            }
        }

        // Field has a value, we're okay
        return true;
    }


    /**
     * Add the specified failure message to the failures list
     *
     * @param string $failure
     * @param string|null $field
     * @return void
     */
    public function addFailure(string $failure, ?string $field = null): void
    {
        if (static::$disabled) {
            return;
        }

//show('FAILURE (' . $this->parent_field . ' / ' . $this->selected_field . ' / ' . $this->process_key . '): ' . $failure);
        // Build up the failure string
        if (is_numeric($this->process_key)) {
            if (is_numeric($this->selected_field)) {
                if ($this->parent_field) {
                    $failure = tr('The ":key" field in ":field" in ":parent" ', [':key' => Strings::ordinalIndicator($this->process_key), ':field' => Strings::ordinalIndicator($this->selected_field), ':parent' => $this->parent_field]) . $failure;
                } else {
                    $failure = tr('The ":key" field in ":field" ', [':key' => Strings::ordinalIndicator($this->process_key), ':field' => Strings::ordinalIndicator($this->selected_field)]) . $failure;
                }
            } else if ($this->parent_field) {
                $failure = tr('The ":key" field in ":field" in ":parent" ', [':key' => Strings::ordinalIndicator($this->process_key), ':field' => $this->selected_field, ':parent' => $this->parent_field]) . $failure;
            } else {
                $failure = tr('The ":key" field in ":field" ', [':key' => Strings::ordinalIndicator($this->process_key), ':field' => $this->selected_field]) . $failure;
            }
        } elseif (is_numeric($this->selected_field)) {
            if ($this->parent_field) {
                $failure = tr('The ":key" field in ":parent" ', [':count' => Strings::ordinalIndicator($this->selected_field), ':parent' => $this->parent_field]) . $failure;
            } else {
                $failure = tr('The ":key" field ', [':count' => Strings::ordinalIndicator($this->selected_field)]) . $failure;
            }
        } elseif ($this->parent_field) {
            $failure = tr('The ":field" field in ":parent" ', [':parent' => $this->parent_field, ':field' => $this->selected_field]) . $failure;
        } else {
            $failure = tr('The ":field" field ', [':field' => $this->selected_field]) . $failure;
        }

        // Generate key to store this failure
        if (!$field) {
            if ($this->parent_field) {
                $field = $this->parent_field . ':' . $this->selected_field;
            } else {
                $field = $this->selected_field;
            }

            if ($this->process_key !== null) {
                $field .= ':' . $this->process_key;
            }
        }

        // Store the failure
        $this->process_value_failed = true;
        $this->failures[$field] = $failure;
    }


    /**
     * Returns the list of failures found during validation
     *
     * @return array
     */
    public function getFailures(): array
    {
        return $this->failures;
    }


    /**
     * Ensure that a key has been selected
     *
     * @return void
     */
    protected function ensureSelected(): void
    {
        if (empty($this->selected_field)) {
            throw new NoKeySelectedException(tr('The specified key ":key" has already been selected before', [
                ':key' => $this->selected_field
            ]));
        }
    }
}