<?php

namespace Phoundation\Data\Validator;



use Phoundation\Core\Arrays;
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
        show('START VALIDATE VALUES "' . $this->selected_field . '" (' . ($this->process_value_failed ? 'FAILED' : 'NOT FAILED') . ')');
        show($this->process_values);

        if ($this->process_value_failed) {
            show('NOT VALIDATING, ALREADY FAILED');
            // In the span of multiple tests on one value, one test failed, don't execute the rest of the tests
            return $this;
        }

        foreach ($this->process_values as &$value) {
            show('VALUE:' . Strings::force($value));
            // Process all process_values
            $this->process_value_failed = false;
            $this->process_value = &$value;
            $this->process_value = $function($this->process_value);
        }

        unset($value);

        return $this;
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a boolean
     *
     * @return Validator
     */
    public function isBoolean(): Validator
    {
        return $this->validateValues(function(mixed &$value) {
            if ($this->checkIsOptional($value)) {
                if (Strings::getBoolean($value, false) === null) {
                    $this->addFailure(tr('must have a boolean value'));
                }
            }

            return $value;
        });
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
            if ($this->checkIsOptional($value)) {
                if (!is_integer($value)) {
                    $this->addFailure(tr('must have an integer value'));
                }
            }

            return $value;
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is an float
     *
     * @return Validator
     */
    public function isFloat(): Validator
    {
        return $this->validateValues(function(mixed &$value) {
            if ($this->checkIsOptional($value)) {
                if (!is_float($value)) {
                    $this->addFailure(tr('must have a float value'));
                }
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
            if ($this->checkIsOptional($value)) {
                if (!is_numeric($value)) {
                    $this->addFailure(tr('must have a numeric value'));
                }
            }

            return $value;
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is positive
     *
     * @param bool $allow_zero
     * @return Validator
     */
    public function isPositive(bool $allow_zero = false): Validator
    {
        return $this->validateValues(function(mixed &$value) use ($allow_zero) {
            $this->isNumeric();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if ($value < ($allow_zero ? 0 : 1)) {
                $this->addFailure(tr('must have a positive value', [':field' => $this->selected_field]));
            }

            return $value;
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is positive
     *
     * @param int|float $amount
     * @return Validator
     */
    public function isMoreThan(int|float $amount): Validator
    {
        return $this->validateValues(function(mixed &$value) use ($amount) {
            $this->isNumeric();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if ($value <= $amount) {
                $this->addFailure(tr('must be more than than ":amount"', [':amount' => $amount]));
            }

            return $value;
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is positive
     *
     * @param int|float $amount
     * @return Validator
     */
    public function isLessThan(int|float $amount): Validator
    {
        return $this->validateValues(function(mixed &$value) use ($amount) {
            $this->isNumeric();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if ($value >= $amount) {
                $this->addFailure(tr('must be less than ":amount"', [':amount' => $amount]));
            }

            return $value;
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is between the two specified amounts
     *
     * @param int|float $minimum
     * @param int|float $maximum
     * @return Validator
     */
    public function isBetween(int|float $minimum, int|float $maximum): Validator
    {
        return $this->validateValues(function(mixed &$value) use ($minimum, $maximum) {
            $this->isNumeric();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (($value <= $minimum) or ($value >= $maximum)) {
                $this->addFailure(tr('must be between ":amount" and ":maximum"', [':minimum' => $minimum, ':maximum' => $maximum]));
            }

            return $value;
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is negative
     *
     * @param bool $allow_zero
     * @return Validator
     */
    public function isNegative(bool $allow_zero = false): Validator
    {
        return $this->validateValues(function(mixed &$value) use ($allow_zero) {
            $this->isNumeric();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if ($value > ($allow_zero ? 0 : 1)) {
                $this->addFailure(tr('must have a negative value'));
            }

            return $value;
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a scalar value
     *
     * @return Validator
     */
    public function isScalar(): Validator
    {
        return $this->validateValues(function(mixed &$value) {
            if ($this->checkIsOptional($value)) {
                if (!is_scalar($value)) {
                    show($value);
                    $this->addFailure(tr('must have a scalar value', [':field' => $this->selected_field]));
                }
            }

            return $value;
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a scalar value
     *
     * @param array $array
     * @return Validator
     */
    public function inArray(array $array) : Validator
    {
        return $this->validateValues(function(mixed &$value) use ($array) {
            // This value must be scalar, and not too long. What is too long? Longer than the longest allowed item
            $this->isScalar();
            $this->hasMaxSize(Arrays::getLongestString($array));

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (!in_array($value, $array)) {
                $this->addFailure(tr('must be one of ":list"', [':list' => $array]));
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
            if ($this->checkIsOptional($value)) {
                if (!is_string($value)) {
                    $this->addFailure(tr('must have a string value', [':field' => $this->selected_field]));
                }
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
    public function hasMinSize(int $characters): Validator
    {
        return $this->validateValues(function(mixed $value) use ($characters) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (strlen($value) < $characters) {
                $this->addFailure(tr('must have ":count" characters or more', [':count' => $characters]));
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
    public function hasMaxSize(int $characters): Validator
    {
        return $this->validateValues(function(mixed $value) use ($characters) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (strlen($value) > $characters) {
                $this->addFailure(tr('must have ":count" characters or less', [':count' => $characters]));
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
            if ($this->checkIsOptional($value)) {
                if (!is_array($value)) {
                    $this->addFailure(tr('must have an array value'));
                }
            }

            return $value;
        });
    }



    /**
     * Validates that the selected field array has a minimal amount of elements
     *
     * @param int $count
     * @return Validator
     */
    public function hasMinimumElements(int $count): Validator
    {
        return $this->validateValues(function(mixed $value) use ($count) {
            $this->isArray();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (count($value) < $count) {
                $this->addFailure(tr('must have ":count" elements or more', [':count' => $count]));
            }

            return $value;
        });
    }



    /**
     * Validates that the selected field array has a maximum amount of elements
     *
     * @param int $count
     * @return Validator
     */
    public function hasMaximumElements(int $count): Validator
    {
        return $this->validateValues(function(mixed $value) use ($count) {
            $this->isArray();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (count($value) > $count) {
                $this->addFailure(tr('must have ":count" elements or less', [':count' => $count]));
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
show('TEST IS EMAIL');
        return $this->validateValues(function(mixed $value) {
show('EMAIL FUNCTION');
            $this->hasMinSize(3);
            $this->hasMaxSize(128);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->addFailure(tr('must contain a valid email'));
            }

            return $value;
        });
   }
}