<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator;

use Phoundation\Core\Arrays;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Validator\Exception\NoKeySelectedException;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Exception\ValidatorException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use ReflectionProperty;


/**
 * ValidatorBasics trait
 *
 * This class contains the basic controls for Validator classes
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * The prefix for field selection
     *
     * @var string|null $field_prefix
     */
    protected ?string $field_prefix = null;

    /**
     * The table that contains the data
     *
     * @var string|null $table
     */
    protected ?string $table = null;

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
     * @var array|string|float|int|bool|null $selected_optional
     */
    protected array|string|float|int|bool|null $selected_optional = null;

    /**
     * If true, the value is optional
     *
     * @var bool $selected_is_optional
     */
    protected bool $selected_is_optional = false;

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
     * If true, then the current field has the default value
     *
     * @var bool $selected_is_default
     */
    protected bool $selected_is_default = false;

    /**
     * If specified, this is a child element to a parent.
     *
     * The ->validate() call will NOT cause an exception but instead will send the failures list to the parent and then
     * return the parent element
     *
     * @var ValidatorInterface|null
     */
    protected ?ValidatorInterface $parent = null;

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
     * @todo Check if we can get rid of this reflectionproperty stuff, its very hacky
     * @var ReflectionProperty $reflection_selected_optional
     */
    protected ReflectionProperty $reflection_selected_optional;

    /**
     * Required to test if process_value property is initialized or not
     *
     * @todo Check if we can get rid of this reflectionproperty stuff, its very hacky
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
     * If true, failed fields will be cleared on validation
     *
     * @var bool $clear_failed_fields
     */
    protected bool $clear_failed_fields = false;


    /**
     * Returns the entire source for this validator object
     *
     * @return array|null
     */
    public function getSource(): ?array
    {
        return $this->source;
    }


    /**
     * Returns the value for the specified key, or null if not
     *
     * @return array
     */
    public function getSourceKey(string $key): mixed
    {
        return array_get_safe($this->source, $key);
    }


    /**
     * Returns true if the specified key exists
     *
     * @param string $key
     * @return bool
     */
    public function sourceKeyExists(string $key): bool
    {
        return array_key_exists($key, $this->source);
    }


    /**
     * Manually set one of the internal fields to the specified value
     *
     * @param string $key
     * @param array|string|int|float|bool|null $value
     * @return static
     */
    public function setField(string $key, array|string|int|float|bool|null $value): static
    {
        $this->source[$key] = $value;
        return $this;
    }


    /**
     * Returns if failed fields will be cleared on validation
     *
     * @return bool
     */
    public function getClearFailedFields(): bool
    {
        return $this->clear_failed_fields;
    }


    /**
     * Sets if failed fields will be cleared on validation
     *
     * @param bool $clear_failed_fields
     * @return static
     */
    public function setClearFailedFields(bool $clear_failed_fields): static
    {
        $this->clear_failed_fields = $clear_failed_fields;
        return $this;
    }


    /**
     * Returns the maximum string size that this Validator will touch
     *
     * @return int|null
     */
    public function getMaximumStringSize(): ?int
    {
        return $this->max_string_size;
    }


    /**
     * Returns the maximum string size that this Validator will touch
     *
     * @param int|null $max_string_size
     * @return void
     */
    public function setMaximumStringSize(?int $max_string_size): void
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
     * @return ValidatorInterface
     *
     * @see Validator::xor()
     * @see Validator::or()
     */
    public function isOptional(array|string|float|int|bool|null $default = null): static
    {
        $this->selected_is_optional = true;
        $this->selected_optional    = $default;
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
     * @return ValidatorInterface
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
                $this->addFailure(tr('nor ":field" were set, where either one of them must be set', [
                    ':field' => $field
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
     * @return ValidatorInterface
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
     * @return ValidatorInterface
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
     * @return ValidatorInterface
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
     * @param bool $clean_source
     * @return array
     */
    public function validate(bool $clean_source = true): array
    {
        // Remove all unselected and all failed fields
        foreach ($this->source as $field => $value) {
            // Unprocessed fields
            if ($clean_source) {
                if (!in_array($field, $this->selected_fields)) {
                    $unclean[$field] = tr('The field ":field" is unknown', [':field' => $field]);
                    unset($this->source[$field]);
                    continue;
                }
            }

            // Failed fields
            if (array_key_exists($field, $this->failures)) {
                if ($this->clear_failed_fields) {
show('CLEAR ' . $field);
                    unset($this->source[$field]);
                }
            }
        }

        if ($this->parent) {
            // Copy failures from the child to the parent and return the parent to continue
            foreach ($this->failures as $field => $failure) {
                $this->parent->addFailure($failure, $field);
            }

            // Clear the contents of this object to avoid stuck references
            $this->clear();
            // TODO Fix parent support
            return $this->parent;
        }

        if ($this->failures) {
            throw ValidationFailedException::new(tr('Data validation failed with the following issues:'))
                ->setData($this->failures)
                ->makeWarning();
        }

        if (isset($unclean)) {
            throw ValidationFailedException::new(tr('Data validation failed because of the following unknown fields'))
                ->setData($unclean)
                ->makeWarning();
        }

        return Arrays::extract($this->source, $this->selected_fields);
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
     * Return true if this field was empty and now has the specified optional value and does not require validation
     *
     * @note This process will set the self::process_value_failed to true when the optional value is applied to stop
     *       further testing.
     * @param mixed $value The value to test
     * @return bool
     */
    protected function checkIsOptional(mixed &$value): bool
    {
        if ($this->process_value_failed) {
            // Value processing already failed anyway, so always fail
            return true;
        }

// DEBUG CODE: In case of errors with validation, its very useful to have these debugged here
//        show($this->selected_field);
//        show($value);

        if (!$value) {
            if (!$this->selected_is_optional) {
                // At this point we know we MUST have a value, so we're bad here
                $this->addFailure(tr('is required'));
                return true;
            }

            // If value is set or not doesn't matter, it's okay
            $value = $this->selected_optional;
            $this->selected_is_default  = true;
            $this->process_value_failed = true;
            return true;
        }

        // Field has a value, we're okay
        return false;
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

        // Detect field name to store this failure
        if ($field) {
            $selected_field = $field;

        } else {
            $selected_field = $this->selected_field;

            if ($this->parent_field) {
                $field = $this->parent_field . ':' . $selected_field;
            } else {
                if (!$selected_field) {
                    throw OutOfBoundsException::new(tr('No field specified or selected for validation failure ":failure"', [
                        ':failure' => $failure
                    ]));
                }

                $field = $selected_field;
            }

            if ($this->process_key !== null) {
                $field .= ':' . $this->process_key;
            }
        }

        Log::warning(tr('Validation failed on field ":field" with value ":value" because ":failure"', [
            ':field'   => ($this->parent_field ?? '-') . ' / ' . $selected_field . ' / ' . ($this->process_key ?? '-'),
            ':failure' => $failure,
            ':value'   => $this->source[$selected_field],
        ]));

        if (Debug::enabled()) {
            Log::backtrace();
        }

        // Build up the failure string
        if (is_numeric($this->process_key)) {
            if (is_numeric($selected_field)) {
                if ($this->parent_field) {
                    $failure = tr('The ":key" field in ":field" in ":parent" ', [':key' => Strings::ordinalIndicator($this->process_key), ':field' => Strings::ordinalIndicator($selected_field), ':parent' => $this->parent_field]) . $failure;
                } else {
                    $failure = tr('The ":key" field in ":field" ', [':key' => Strings::ordinalIndicator($this->process_key), ':field' => Strings::ordinalIndicator($selected_field)]) . $failure;
                }
            } else if ($this->parent_field) {
                $failure = tr('The ":key" field in ":field" in ":parent" ', [':key' => Strings::ordinalIndicator($this->process_key), ':field' => $selected_field, ':parent' => $this->parent_field]) . $failure;
            } else {
                $failure = tr('The ":key" field in ":field" ', [':key' => Strings::ordinalIndicator($this->process_key), ':field' => $selected_field]) . $failure;
            }
        } elseif (is_numeric($selected_field)) {
            if ($this->parent_field) {
                $failure = tr('The ":key" field in ":parent" ', [':count' => Strings::ordinalIndicator($selected_field), ':parent' => $this->parent_field]) . $failure;
            } else {
                $failure = tr('The ":key" field ', [':count' => Strings::ordinalIndicator($selected_field)]) . $failure;
            }
        } elseif ($this->parent_field) {
            $failure = tr('The ":field" field in ":parent" ', [':parent' => $this->parent_field, ':field' => $selected_field]) . $failure;
        } else {
            $failure = tr('The ":field" field ', [':field' => $selected_field]) . $failure;
        }

        // Store the failure
        $this->process_value_failed = true;
        $this->failures[$field]     = $failure;
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
     * Returns if the currently selected field failed or not
     *
     * @return bool
     */
    public function getSelectedFieldHasFailed(): bool
    {
        return $this->fieldHasFailed($this->selected_field);
    }


    /**
     * Returns true if the specified field has failed
     *
     * @param string $field
     * @return bool
     */
    public function fieldHasFailed(string $field): bool
    {
        if (!array_key_exists($field, $this->source)) {
            throw new OutOfBoundsException(tr('The specified field ":field" does not exist', [
                ':field' => $field
            ]));
        }

        return array_key_exists($field, $this->failures);
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
