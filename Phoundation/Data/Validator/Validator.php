<?php

namespace Phoundation\Data\Validator;



use Phoundation\Core\Strings;

/**
 * Validator class
 *
 * This class validates data from untrusted arrays
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Utils
 */
class Validator
{
    use ValidatorBasics;



    /**
     * Allow the validator to check each element in a list of values.
     *
     * Basically each method will expect to process a list always and ->select() will put the selected value in an
     * artificial array because of this. ->each() actually will have a list of values, so puts that list directly into
     * $this->process_values
     *
     * @see Validator::self()
     * @see Validator::select()
     * @return Validator
     */
    public function each(): Validator
    {
        // This obviously only works on arrays
        $this->isArray();

        // Unset process_values first to ensure the byref link is broken
        unset($this->process_values);
        $this->process_values = &$this->selected_value;
show($this->process_values);
show('each');
        return $this;
    }



    /**
     * Will let the validator treat the value as a single variable
     *
     * Basically each method will expect to process a list always and ->select() will put the selected value in an
     * artificial array because of this. ->each() actually will have a list of values, so puts that list directly into
     * $this->process_values
     *
     * @see Validator::each()
     * @see Validator::select()
     * @return Validator
     */
    public function single(): Validator
    {
        $this->process_values = [&$this->selected_value];

        return $this;
    }



    /**
     * Apply the specified anonymous function on a single or all of the process_values for the selected field
     *
     * @param callable $function
     * @return Validator
     */
    protected function validateValues(callable $function): Validator
    {
        $this->ensureSelected();

        if ($this->process_value_failed) {
            // In the span of multiple tests on one value, one test failed, don't execute the rest of the tests
            return $this;
        }

        if ($this->process_value) {
            // Process only one single process_value
            $this->process_value = $function($this->process_value);

        } else {
            foreach ($this->process_values as &$value) {
                // Process all process_values
                $this->process_value_failed = false;
                $this->process_value = &$value;
                $this->process_value = $function($this->process_value);
            }
        }

        return $this;
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is an integer
     *
     * @return Validator
     */
    public function isInteger(): Validator
    {
        return $this->validateValues(function(mixed &$value) {
            if (!is_integer($value)) {
                $this->addFailure($this->selected_label, tr('must have an integer value'));
            }

            return $value;
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is numeric
     *
     * @return Validator
     */
    public function isNumeric(): Validator
    {
        return $this->validateValues(function(mixed &$value) {
            if (!is_numeric($value)) {
                $this->addFailure($this->selected_label, tr('must have a numeric value'));
            }

            return $value;
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a string
     *
     * @return Validator
     */
    public function isString(): Validator
    {
        return $this->validateValues(function(mixed &$value) {
            if (!is_string($value)) {
show($value);
                $this->addFailure($this->selected_label, tr('must have a string value', [':field' => $this->selected_label]));
            }

            return $value;
        });
    }



    /**
     * Validates that the selected field is equal or larger than the specified amount of characters
     *
     * @param int $characters
     * @return Validator
     */
    public function isMinSize(int $characters): Validator
    {
        return $this->validateValues(function(mixed $value) use ($characters) {
            $this->isString();

            if (strlen($value) < $characters) {
                $this->addFailure($this->selected_label, tr('must have ":count" characters or more', [':count' => $characters]));
            }

            return $value;
        });
    }



    /**
     * Validates that the selected field is equal or shorter than the specified amount of characters
     *
     * @param int $characters
     * @return Validator
     */
    public function isMaxSize(int $characters): Validator
    {
        return $this->validateValues(function(mixed $value) use ($characters) {
            $this->isString();

            if (strlen($value) > $characters) {
                $this->addFailure($this->selected_label, tr('must have ":count" characters or less', [':count' => $characters]));
            }

            return $value;
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is an array
     *
     * @return Validator
     */
    public function isArray(): Validator
    {
        return $this->validateValues(function(mixed $value) {
            if (!is_array($value)) {
                $this->addFailure($this->selected_label, tr('must have an array value'));
            }

            return $value;
        });
    }



    /**
     * Validates if the selected field is a valid email address
     *
     * This method ensures that the specified array key is an array
     *
     * @return Validator
     */
    public function isEmail(): Validator
    {
        return $this->validateValues(function(mixed $value) {
            $this->isMinSize(3);
            $this->isMaxSize(128);

            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->addFailure($this->selected_label, tr('must contain a valid email'));
            }

            return $value;
        });
   }
}