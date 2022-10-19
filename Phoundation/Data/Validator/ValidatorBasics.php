<?php

namespace Phoundation\Data\Validator;

use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Exception\KeyAlreadySelectedException;
use Phoundation\Data\Exception\NoKeySelectedException;
use Phoundation\Data\Exception\ValidatorException;
use Phoundation\Exception\Exceptions;
use Phoundation\Exception\OutOfBoundsException;



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
    protected mixed $selected_optional = null;

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
    protected mixed $process_value = null;

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
     * Internal $_GET array until validation has been completed
     *
     * @var array|null
     */
    protected static ?array $get = null;

    /**
     * Internal $_POST array until validation has been completed
     *
     * @var array|null
     */
    protected static ?array $post = null;

    /**
     * The maximum string size that this validator will touch
     *
     * @var int $max_string_size
     */
    protected int $max_string_size = 1073741824;


    /**
     * Validator constructor.
     *
     * @note Keys that do not exist in $data that are validated will automatically be created
     * @note Keys in $data that are not validated will automatically be removed
     * @param array|null $source The data array that must be validated.
     * @param Validator|null $parent If specified, this is actually a child validator to the specified parent
     */
    public function __construct(?array &$source = [], ?Validator $parent = null) {
        // Ensure the source is an array
        if ($source === null) {
            $source = [];
        }

        $this->source = &$source;
        $this->parent = $parent;
    }



    /**
     * Returns a new array validator
     *
     * @param array $source
     * @return Validator
     */
    public static function array(array &$source): Validator
    {
        return new Validator($source);
    }



    /**
     * Returns a new GET array validator
     *
     * @return Validator
     */
    public static function get(): Validator
    {
        // Clear the $_REQUEST array, it should never be used
        $_REQUEST = [];

        return new Validator($_GET);
    }



    /**
     * Returns a new POST array validator
     *
     * @return Validator
     */
    public static function post(): Validator
    {
        // Clear the $_REQUEST array, it should never be used
        $_REQUEST = [];

        return new Validator($_POST);
    }


    /**
     * Returns a new file validator
     *
     * @param string $file
     * @return FileValidator
     */
    public static function file(string $file): FileValidator
    {
        return new FileValidator($file);
    }



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
     * Selects the specified key within the array that we are validating
     *
     * @param int|string $field The array key (or HTML form field) that needs to be validated / sanitized
     * @return Validator
     */
    public function select(int|string $field): Validator
    {
        if (!$field) {
            throw new OutOfBoundsException(tr('No field specified'));
        }

        if (in_array($field, $this->selected_fields)) {
            throw new KeyAlreadySelectedException(tr('The specified key ":key" has already been selected before', [':key' => $field]));
        }

        if ($this->source === null) {
            throw new OutOfBoundsException(tr('Cannot select field ":field", no source array specified', [':field' => $field]));
        }

        // Does the field exist in the source? If not, initialize it with NULL to be able to process it
        if (!array_key_exists($field, $this->source)) {
            $this->source[$field] = null;
        }

        // Select the field. Unset process_values first to ensure the byref link is broken
        unset($this->process_values);

        $this->selected_field    = $field;
        $this->selected_fields[] = $field;
        $this->selected_value    = $this->source[$field];
        $this->process_values    = [null => &$this->selected_value];
        $this->selected_optional = null;

//show('SELECTED ' . ($this->parent_field ? $this->parent_field . ' > ' : '') . $field);
        return $this;
    }



    /**
     * This method will make the selected field optional and use the specified $default instead
     *
     * This means that either it may not exist, or it's contents may be NULL
     *
     * @see Validator::xor()
     * @param bool|int|float|string|array $default
     * @return Validator
     */
    public function isOptional(bool|int|float|string|array $default): Validator
    {
        $this->selected_optional = $default;
        return $this;
    }



    /**
     * This method will make sure that either this field OR the other specified field will have a value
     *
     * @see Validator::isOptional()
     * @param string $field
     * @return Validator
     */
    public function xor(string $field): Validator
    {
        if ($this->selected_field === $field) {
            throw new ValidatorException(tr('Cannot validate XOR field ":field" with itself', [':field' => $field]));
        }

        if (isset_get($this->source[$this->selected_field])) {
            // The currently selected field exists, the specified field cannot exist
            if (isset_get($this->source[$field])) {
                $this->addFailure(tr('Both fields ":field" and ":selected_field" were set, where only either one of them are allowed', [':field' => $field, ':selected_field' => $this->selected_field]));
            }
        } else {
            // The currently selected field does not exists, the specified field MUST exist
            if (!isset_get($this->source[$field])) {
                $this->addFailure(tr('Neither fields ":field" and ":selected_field" were set, where either one of them must be set', [':field' => $field, ':selected_field' => $this->selected_field]));
            }
        }

        return $this;
    }



    /**
     * Will validate that the value of this field matches the value for the specified field
     *
     * @param string $field
     * @param bool $strict If true will execute a strict comparison where the datatype must match as well (so 1 would
     *                     not be the same as "1") for example
     * @return Validator
     * @see Validator::isOptional()
     */
    public function isEqualTo(string $field, bool $strict = false): Validator
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
     * @return Validator
     */
    public function recurse(): Validator
    {
        $this->ensureSelected();

        // Create a new Validator object from the current value. If the current value is not an array (oopsie) then just
        // send in an empty array so that the Validation chain won't break
        if (!is_array($this->selected_value)) {
            $array = [];
            $child = new Validator($array, $this);
        } else {
            $child = new Validator($this->selected_value, $this);
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
     * @return Validator
     */
    public function validate(): Validator
    {
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
            Log::warning(tr('Array validation ended with the following failures'), 3);
            Log::warning($this->failures, 2);

            throw Exceptions::ValidationFailedException(tr('Validation of the specified source array failed with the registered failures'), $this->failures)->makeWarning();
        }

        return $this;
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
        $this->process_value   = null;
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

        if ($value === null) {
            if ($this->selected_optional === null) {
                // At this point we know we MUST have a value, so we're bad here
                $this->addFailure(tr('is required'));
                return false;
            }

            // If value is set or not doesn't matter, its okay
            $value = $this->selected_optional;
            return true;
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
            $failure = tr('The ":field" ', [':parent' => $this->parent_field, ':field' => $this->selected_field]) . $failure;
        }

        // Generate key to store this failure
        if (!$field){
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
     * Link $_GET and $_POST data to internal arrays to ensure developers cannot access them until validation has been
     * completed
     *
     * @param array $get
     * @param array $post
     * @return void
     */
    public function hideGetPost(array &$get, array &$post): void
    {
        global $_GET, $_POST;

        $this->get  = &$get;
        $this->post = &$post;

        unset($_REQUEST);
    }



    /**
     * Gives free and full access to $_GET and $_POST data, now that it has been validated
     *
     * @return void
     */
    protected static function liberateGetPost(): void
    {
        global $_GET, $_POST;

        $_GET = &self::$get;
        $_POST = &self::$post;

//        unset(self::$get);
//        unset(self::$post);
    }



    /**
     * Ensure that a key has been selected
     *
     * @return void
     */
    protected function ensureSelected(): void
    {
        if (empty($this->selected_field)) {
            throw new NoKeySelectedException(tr('The specified key ":key" has already been selected before', [':key' => $this->selected_field]));
        }
    }
}