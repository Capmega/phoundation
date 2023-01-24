<?php

namespace Phoundation\Data\Validator;

use DateTime;
use PDOStatement;
use Phoundation\Accounts\Passwords;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Arrays;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Validator\Exception\KeyAlreadySelectedException;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\Url;
use ReflectionProperty;
use Throwable;



/**
 * Validator class
 *
 * This class validates data from untrusted arrays
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
abstract class Validator
{
    use ValidatorBasics;



    /**
     * Returns if all validations are disabled or not
     *
     * @return bool
     */
    public static function disabled(): bool
    {
        return self::$disabled;
    }



    /**
     * Disable all validations
     *
     * @return void
     */
    public static function disable(): void
    {
        self::$disabled = true;
    }



    /**
     * Enable all validations
     *
     * @return void
     */
    public static function enable(): void
    {
        self::$disabled = false;
    }



    /**
     * Returns if all validations are disabled or not
     *
     * @return bool
     */
    public static function passwordsDisabled(): bool
    {
        return self::$password_disabled;
    }



    /**
     * Disable password validations
     *
     * @return void
     */
    public static function disablePasswords(): void
    {
        self::$password_disabled = true;
    }



    /**
     * Enable password validations
     *
     * @return void
     */
    public static function enablePasswords(): void
    {
        self::$password_disabled = false;
    }



    /**
     * Allow the validator to check each element in a list of values.
     *
     * Basically each method will expect to process a list always and ->select() will put the selected value in an
     * artificial array because of this. ->each() actually will have a list of values, so puts that list directly into
     * $this->process_values
     *
     * @see Validator::self()
     * @see Validator::select()
     * @return static
     */
    public function each(): static
    {
        // This obviously only works on arrays
        $this->isArray();

        if (!$this->process_value_failed) {
            // Unset process_values first to ensure the byref link is broken
            unset($this->process_values);
            $this->process_values = &$this->selected_value;
        }

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
     * @return static
     */
    public function single(): static
    {
        $this->process_values = [null => &$this->selected_value];

        return $this;
    }



    /**
     * Apply the specified anonymous function on a single or all of the process_values for the selected field
     *
     * @param callable $function
     * @return static
     */
    protected function validateValues(callable $function): static
    {
        if ($this->reflection_process_value->isInitialized($this)){
            // A single value was selected, test only this value
            $function($this->process_value);
        } else {
            $this->ensureSelected();

            if ($this->process_value_failed) {
                // In the span of multiple tests on one value, one test failed, don't execute the rest of the tests
                return $this;
            }

            foreach ($this->process_values as $key => &$value) {
                // Process all process_values
                $this->process_key          = $key;
                $this->process_value        = &$value;
                $this->process_value_failed = false;

                $function($this->process_value);
            }

            // Clear up work data
            unset($value);
            unset($this->process_value);
            $this->process_key = null;
        }

        return $this;
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a boolean
     *
     * @return static
     */
    public function isBoolean(): static
    {
        return $this->validateValues(function(&$value) {
            $this->checkIsOptional($value);

            if (Strings::getBoolean($value, false) === null) {
                if ($value !== null) {
                    $this->addFailure(tr('must have a boolean value'));
                }

                $value = false;
            }
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is an integer
     *
     * @return static
     */
    public function isInteger(): static
    {
        return $this->validateValues(function(&$value) {
            $this->checkIsOptional($value);

            if (!is_integer($value)) {
                if (is_string($value) and ((int) $value == $value)) {
                    // This integer value was specified as a numeric string
                    $value = (int) $value;
                } else {
                    if ($value !== null) {
                        $this->addFailure(tr('must have an integer value'));
                    }

                    $value = 0;
                }
            }
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is an float
     *
     * @return static
     */
    public function isFloat(): static
    {
        return $this->validateValues(function(&$value) {
            $this->checkIsOptional($value);

            if (!is_float($value)) {
                if (is_string($value) and ((float) $value == $value)) {
                    // This float value was specified as a numeric string
// TODO Test this! There may be slight inaccuracies here due to how floats work, so maybe we should check within a range?
                    $value = (float) $value;
                } else {
                    if ($value !== null) {
                        $this->addFailure(tr('must have a float value'));
                    }

                    $value = 0.0;
                }
            }
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is numeric
     *
     * @return static
     */
    public function isNumeric(): static
    {
        return $this->validateValues(function(&$value) {
            $this->checkIsOptional($value);

            if (!is_numeric($value)) {
                if ($value !== null) {
                    $this->addFailure(tr('must have a numeric value'));
                }

                $value = 0;
            }
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is positive
     *
     * @param bool $allow_zero
     * @return static
     */
    public function isPositive(bool $allow_zero = false): static
    {
        return $this->validateValues(function(&$value) use ($allow_zero) {
            $this->isNumeric();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if ($value < ($allow_zero ? 0 : 1)) {
                $this->addFailure(tr('must have a positive value'));
            }
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a valid natural number (integer, 1 and above)
     *
     * @param bool $allow_zero
     * @return static
     */
    public function isNatural(bool $allow_zero = true): static
    {
        return $this->isInteger()->isPositive($allow_zero);
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a valid latitude coordinate
     *
     * @return static
     */
    public function isLatitude(): static
    {
        return $this->isFloat()->isBetween(-90, 90);
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a valid longitude coordinate
     *
     * @return static
     */
    public function isLongitude(): static
    {
        return $this->isFloat()->isBetween(0, 180);
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a valid database id (integer, 1 and above)
     *
     * @param bool $allow_zero
     * @return static
     */
    public function isId(bool $allow_zero = false): static
    {
        return $this->isInteger()->isPositive($allow_zero);
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a valid code
     *
     * @param bool $allow_zero
     * @return static
     */
    public function isCode(bool $allow_zero = false): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(2)->hasMaxCharacters(16);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $this->isPrintable();
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is positive
     *
     * @param int|float $amount
     * @return static
     */
    public function isMoreThan(int|float $amount): static
    {
        return $this->validateValues(function(&$value) use ($amount) {
            $this->isNumeric();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if ($value <= $amount) {
                $this->addFailure(tr('must be more than than ":amount"', [':amount' => $amount]));
            }
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is positive
     *
     * @param int|float $amount
     * @return static
     */
    public function isLessThan(int|float $amount): static
    {
        return $this->validateValues(function(&$value) use ($amount) {
            $this->isNumeric();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if ($value >= $amount) {
                $this->addFailure(tr('must be less than ":amount"', [':amount' => $amount]));
            }
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is between the two specified amounts
     *
     * @param int|float $minimum
     * @param int|float $maximum
     * @return static
     */
    public function isBetween(int|float $minimum, int|float $maximum): static
    {
        return $this->validateValues(function(&$value) use ($minimum, $maximum) {
            $this->isNumeric();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (($value <= $minimum) or ($value >= $maximum)) {
                $this->addFailure(tr('must be between ":amount" and ":maximum"', [':minimum' => $minimum, ':maximum' => $maximum]));
            }
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is negative
     *
     * @param bool $allow_zero
     * @return static
     */
    public function isNegative(bool $allow_zero = false): static
    {
        return $this->validateValues(function(&$value) use ($allow_zero) {
            $this->isNumeric();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if ($value > ($allow_zero ? 0 : 1)) {
                $this->addFailure(tr('must have a negative value'));
            }
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a scalar value
     *
     * @return static
     */
    public function isScalar(): static
    {
        return $this->validateValues(function(&$value) {
            $this->checkIsOptional($value);

            if (!is_scalar($value)) {
                if ($value !== null) {
                    $this->addFailure(tr('must have a scalar value'));
                }

                $value = '';
            }
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a scalar value
     *
     * @param array $array
     * @return static
     */
    public function inArray(array $array) : Validator
    {
        return $this->validateValues(function(&$value) use ($array) {
            // This value must be scalar, and not too long. What is too long? Longer than the longest allowed item
            $this->isScalar();
            $this->hasMaxCharacters(Arrays::getLongestString($array));

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!in_array($value, $array)) {
                $this->addFailure(tr('must be one of ":list"', [':list' => $array]));
            }
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key contains the specified string
     *
     * @param string $string
     * @return static
     */
    public function contains(string $string) : Validator
    {
        return $this->validateValues(function(&$value) use ($string) {
            // This value must be scalar
            $this->isScalar();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!str_contains($value, $string)) {
                $this->addFailure(tr('must contain ":value"', [':value' => $string]));
            }
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified key is the same as the column value in the specified query
     *
     * @param PDOStatement|string $query
     * @param array|null $execute
     * @return static
     */
    public function isQueryColumn(PDOStatement|string $query, ?array $execute = null) : Validator
    {
        return $this->validateValues(function(&$value) use ($query, $execute) {
            // This value must be scalar, and not too long. What is too long? Longer than the longest allowed item
            $this->isScalar();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $execute = $this->applyExecuteVariables($execute);
            $column  = sql()->getColumn($query, $execute);

            $this->isValue($column);
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified key value contains the column value in the specified query
     *
     * @param PDOStatement|string $query
     * @param array|null $execute
     * @return static
     */
    public function containsQueryColumn(PDOStatement|string $query, ?array $execute = null) : Validator
    {
        return $this->validateValues(function(&$value) use ($query, $execute) {
            // This value must be scalar, and not too long. What is too long? Longer than the longest allowed item
            $this->isScalar();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $execute = $this->applyExecuteVariables($execute);
            $column  = sql()->getColumn($query, $execute);
            $this->contains($column);
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a scalar value
     *
     * @param PDOStatement|string $query
     * @param array|null $execute
     * @return static
     */
    public function inQueryColumns(PDOStatement|string $query, ?array $execute = null) : Validator
    {
        return $this->validateValues(function(&$value) use ($query, $execute) {
            // This value must be scalar, and not too long. What is too long? Longer than the longest allowed item
            $this->isScalar();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $execute = $this->applyExecuteVariables($execute);
            $results = sql()->list($query, $execute);
            $this->inArray($results);
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a string
     *
     * @return static
     */
    public function isString(): static
    {
        return $this->validateValues(function(&$value) {
            $this->checkIsOptional($value);

            if (!is_string($value)) {
                if ($value !== null) {
                    $this->addFailure(tr('must have a string value'));
                }

                $value = '';
            }
        });
    }



    /**
     * Validates that the selected field is equal or larger than the specified amount of characters
     *
     * @param int $characters
     * @return static
     */
    public function hasCharacters(int $characters): static
    {
        return $this->validateValues(function(&$value) use ($characters) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (strlen($value) != $characters) {
                $this->addFailure(tr('must have ":count" characters or more', [':count' => $characters]));
            }
        });
    }



    /**
     * Validates that the selected field is equal or larger than the specified amount of characters
     *
     * @param int $characters
     * @return static
     */
    public function hasMinCharacters(int $characters): static
    {
        return $this->validateValues(function(&$value) use ($characters) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (strlen($value) < $characters) {
                $this->addFailure(tr('must have ":count" characters or more', [':count' => $characters]));
            }
        });
    }



    /**
     * Validates that the selected field is equal or shorter than the specified amount of characters
     *
     * @param int|null $characters
     * @return static
     */
    public function hasMaxCharacters(?int $characters = null): static
    {
        return $this->validateValues(function(&$value) use ($characters) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            // Validate the maximum amount of characters
            if ($characters === null) {
                $characters = $this->max_string_size;
            } elseif ($characters > $this->max_string_size) {
                Log::warning(tr('The specified amount of maximum characters ":specified" surpasses the configured amount of ":configured". Forcing configured amount instead', [
                    ':specified'  => $characters,
                    ':configured' => $this->max_string_size
                ]));

                $characters = $this->max_string_size;
            }

            if (strlen($value) > $characters) {
                $this->addFailure(tr('must have ":count" characters or less', [':count' => $characters]));
            }
        });
    }



    /**
     * Validates that the selected field matches the specified regex
     *
     * @param string $regex
     * @return static
     */
    public function matchesRegex(string $regex): static
    {
        return $this->validateValues(function(&$value) use ($regex) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!preg_match($regex, $value)) {
                $this->addFailure(tr('must match ":regex"', [':regex' => $regex]));
            }
        });
    }



    /**
     * Validates that the selected field contains only alphabet characters
     *
     * @return static
     */
    public function isAlpha(): static
    {
        return $this->validateValues(function(&$value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!ctype_alpha($value)) {
                $this->addFailure(tr('must contain only letters'));
            }
        });
    }



    /**
     * Validates that the selected field contains only alphanumeric characters
     *
     * @return static
     */
    public function isAlphaNumeric(): static
    {
        return $this->validateValues(function(&$value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!ctype_alnum($value)) {
                $this->addFailure(tr('must contain only letters and numbers'));
            }
        });
    }



    /**
     * Validates that the selected field contains only lowercase letters
     *
     * @return static
     */
    public function isLowercase(): static
    {
        return $this->validateValues(function(&$value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!ctype_lower($value)) {
                $this->addFailure(tr('must contain only lowercase letters'));
            }
        });
    }



    /**
     * Validates that the selected field contains only uppercase letters
     *
     * @return static
     */
    public function isUppercase(): static
    {
        return $this->validateValues(function(&$value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!ctype_upper($value)) {
                $this->addFailure(tr('must contain only uppercase letters'));
            }
        });
    }



    /**
     * Validates that the selected field contains only characters that are printable, but neither letter, digit nor
     * blank
     *
     * @return static
     */
    public function isPunct(): static
    {
        return $this->validateValues(function(&$value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!ctype_punct($value)) {
                $this->addFailure(tr('must contain only uppercase letters'));
            }
        });
    }



    /**
     * Validates that the selected field contains only printable characters (including blanks)
     *
     * @return static
     */
    public function isPrintable(): static
    {
        return $this->validateValues(function(&$value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!ctype_print($value)) {
                $this->addFailure(tr('must contain only printable characters'));
            }
        });
    }



    /**
     * Validates that the selected field contains only printable characters (NO blanks)
     *
     * @return static
     */
    public function isGraph(): static
    {
        return $this->validateValues(function(&$value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!ctype_graph($value)) {
                $this->addFailure(tr('must contain only visible characters'));
            }
        });
    }



    /**
     * Validates that the selected field contains only whitespace characters
     *
     * @return static
     */
    public function isWhitespace(): static
    {
        return $this->validateValues(function(&$value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!ctype_space($value)) {
                $this->addFailure(tr('must contain only whitespace characters'));
            }
        });
    }



    /**
     * Validates that the selected field contains only hexadecimal characters
     *
     * @return static
     */
    public function isHexadecimal(): static
    {
        return $this->validateValues(function(&$value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!ctype_xdigit($value)) {
                $this->addFailure(tr('must contain only hexadecimal characters'));
            }
        });
    }



    /**
     * Validates that the selected field contains only octal numbers
     *
     * @return static
     */
    public function isOctal(): static
    {
        return $this->validateValues(function(&$value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!preg_match('/^0-7*$/', $value)) {
                $this->addFailure(tr('must contain only octal numbers'));
            }
        });
    }



    /**
     * Validates that the selected field is the specified value
     *
     * @param mixed $validate_value
     * @param bool $strict If true, will perform a strict check
     * @param bool $secret If specified the $validate_value will not be shown
     * @return static
     */
    public function isValue(mixed $validate_value, bool $strict = false, bool $secret = false): static
    {
        return $this->validateValues(function(&$value) use ($validate_value, $strict, $secret) {
            if ($strict) {
                // Strict validation
                if ($value !== $validate_value) {
                    if ($secret) {
                        $this->addFailure(tr('must be exactly value ":value"', [':value' => $value]));
                    } else {
                        $this->addFailure(tr('has an incorrect value'));
                    }
                }

            } else {
                $this->isString();

                if ($this->process_value_failed) {
                    // Validation already failed, don't test anything more
                    return;
                }

                if ($value != $validate_value) {
                    if ($secret) {
                        $this->addFailure(tr('must be value ":value"', [':value' => $value]));
                    } else {
                        $this->addFailure(tr('has an incorrect value'));
                    }
                }
            }
        });
    }



    /**
     * Validates that the selected field is a date
     *
     * @return static
     */
    public function isDate(): static
    {
        return $this->validateValues(function(&$value) {
            $this->isString();
            $this->hasMaxCharacters(64); // Sort-of arbitrary max size, just to ensure Date class won't receive a 2MB string

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

// TODO Implement
//            if (!preg_match($regex, $value)) {
//                $this->addFailure(tr('must match ":regex"', [':regex' => $regex]));
//            }
        });
    }



    /**
     * Validates that the selected field is a timezone
     *
     * @return static
     */
    public function isTimezone(): static
    {
        return $this->validateValues(function(&$value) {
            $this->isString();
            $this->hasMaxCharacters(32); // Sort-of arbitrary max size, just to ensure Date class won't receive a 2MB string

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

// TODO Implement
//            if (!preg_match($regex, $value)) {
//                $this->addFailure(tr('must match ":regex"', [':regex' => $regex]));
//            }
        });
    }



    /**
     * Validates that the selected date field is older than the specified date
     *
     * @param DateTime $date_time
     * @return static
     */
    public function isOlderThan(DateTime $date_time): static
    {
        return $this->validateValues(function(&$value) use ($date_time) {
            $this->isDate();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

// TODO Implement
//            if (!preg_match($regex, $value)) {
//                $this->addFailure(tr('must match ":regex"', [':regex' => $regex]));
//            }
        });
    }



    /**
     * Validates that the selected date field is younger than the specified date
     *
     * @param DateTime $date_time
     * @return static
     */
    public function isYoungerThan(DateTime $date_time): static
    {
        return $this->validateValues(function(&$value) use ($date_time) {
            $this->isDate();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

// TODO Implement
//            if (!preg_match($regex, $value)) {
//                $this->addFailure(tr('must match ":regex"', [':regex' => $regex]));
//            }
        });
    }



    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is an array
     *
     * @return static
     */
    public function isArray(): static
    {
        return $this->validateValues(function(&$value) {
            $this->checkIsOptional($value);

            if (!is_array($value)) {
                if ($value !== null) {
                    $this->addFailure(tr('must have an array value'));
                }

                $value = [];
            }
        });
    }



    /**
     * Validates that the selected field array has a minimal amount of elements
     *
     * @param int $count
     * @return static
     */
    public function hasElements(int $count): static
    {
        return $this->validateValues(function(&$value) use ($count) {
            $this->isArray();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (count($value) != $count) {
                $this->addFailure(tr('must have exactly ":count" elements', [':count' => $count]));
            }
        });
    }



    /**
     * Validates that the selected field array has a minimal amount of elements
     *
     * @param int $count
     * @return static
     */
    public function hasMinimumElements(int $count): static
    {
        return $this->validateValues(function(&$value) use ($count) {
            $this->isArray();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (count($value) < $count) {
                $this->addFailure(tr('must have ":count" elements or more', [':count' => $count]));
            }
        });
    }



    /**
     * Validates that the selected field array has a maximum amount of elements
     *
     * @param int $count
     * @return static
     */
    public function hasMaximumElements(int $count): static
    {
        return $this->validateValues(function(&$value) use ($count) {
            $this->isArray();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (count($value) > $count) {
                $this->addFailure(tr('must have ":count" elements or less', [':count' => $count]));
            }
        });
    }



    /**
     * Validates if the selected field is a valid email address
     *
     * @return static
     */
    public function isHttpMethod(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(3)->hasMaxCharacters(128);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $value = mb_strtoupper($value);

            // Check against the HTTP methods that are considered valid
            switch ($value) {
                case 'GET':
                    // no break
                case 'HEAD':
                    // no break
                case 'POST':
                    // no break
                case 'PUT':
                    // no break
                case 'DELETE':
                    // no break
                case 'CONNECT':
                    // no break
                case 'OPTIONS':
                    // no break
                case 'TRACE':
                    // no break
                case 'PATCH':
                    break;

                default:
                    $this->addFailure(tr('must contain a valid HTTP method'));
            }
        });
    }



    /**
     * Validates if the selected field is a valid phone number
     *
     * @return static
     */
    public function isPhone(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(10)->hasMaxCharacters(20);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $this->matchesRegex('/[0-9- ].+?/');
        });
    }



    /**
     * Validates if the selected field is a valid multiple phones field
     *
     * @return static
     */
    public function isPhones(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(10)->hasMaxCharacters(64);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $this->matchesRegex('/[0-9- ,].+?/');
        });
    }



    /**
     * Validates if the selected field is a valid gender
     *
     * @return static
     */
    public function isGender(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(2)->hasMaxCharacters(16);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $this->isPrintable();
        });
    }



    /**
     * Validates if the selected field is a valid name
     *
     * @return static
     */
    public function isName(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(2)->hasMaxCharacters(128);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $this->isPrintable();
        });
    }



    /**
     * Validates if the selected field is a valid word
     *
     * @return static
     */
    public function isWord(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(2)->hasMaxCharacters(32);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $this->matchesRegex('/^[a-z-]+$/i');
        });
    }



    /**
     * Validates if the selected field is a valid variable
     *
     * @return static
     */
    public function isVariable(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(2)->hasMaxCharacters(32);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $this->matchesRegex('/^[a-z_]+$/i');
        });
    }



    /**
     * Validates if the selected field is a valid name
     *
     * @param string|null $prefix
     * @return static
     */
    public function isFile(?string $prefix = null): static
    {
        return $this->validateValues(function(&$value) use($prefix) {
            $this->hasMinCharacters(1)->hasMaxCharacters(2048);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!str_starts_with($value, '/')) {
                $this->addFailure(tr('must be a file path'));
            }
        });
    }



    /**
     * Validates if the selected field is a valid name
     *
     * @return static
     */
    public function isExistingFile(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(1)->hasMaxCharacters(2048);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (file_exists($value)) {
                $this->addFailure(tr('must be an existing file'));
            }
        });
    }



    /**
     * Validates if the selected field is a valid description
     *
     * @return static
     */
    public function isDescription(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMaxCharacters(16777216);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $this->isPrintable();
        });
    }



    /**
     * Validates if the selected field is a valid password
     *
     * @return static
     */
    public function isPassword(): static
    {
        return $this->validateValues(function(&$value) {
            if (self::passwordsDisabled()) {
                // Don't test passwords
                return;
            }

            $this->hasMinCharacters(10)->hasMaxCharacters(128);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            try {
                Passwords::testSecurity($value);
            } catch (ValidationFailedException $e) {
                $this->addFailure(tr('failed because ":e"', [':e' => $e->getMessage()]));
            }
        });
    }



    /**
     * Validates if the selected field is a valid and strong enough password
     *
     * @return static
     */
    public function isStrongPassword(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(10)->hasMaxCharacters(128);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            // TODO Implement
        });
    }



    /**
     * Validates if the selected field is a valid email address
     *
     * @return static
     */
    public function isEmail(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(3)->hasMaxCharacters(128);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->addFailure(tr('must contain a valid email'));
            }
        });
    }



    /**
     * Validates if the selected field is a valid email address
     *
     * @return static
     */
    public function isUrl(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(3)->hasMaxCharacters(128);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!Url::isValid($value)) {
                $this->addFailure(tr('must contain a valid URL'));
            }
        });
    }



    /**
     * Validates if the selected field is a valid domain name
     *
     * @return static
     */
    public function isDomain(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(3)->hasMaxCharacters(128);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!filter_var($value, FILTER_VALIDATE_DOMAIN)) {
                $this->addFailure(tr('must contain a valid domain'));
            }
        });
    }



    /**
     * Validates if the selected field is a valid IP address
     *
     * @return static
     */
    public function isIp(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(3)->hasMaxCharacters(48);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!filter_var($value, FILTER_VALIDATE_IP)) {
                $this->addFailure(tr('must contain a valid IP address'));
            }
        });
    }



    /**
     * Validates if the selected field is a valid JSON string
     *
     * @copyright The used JSON regex validation taken from a twitter post by @Fish_CTO
     * @return static
     * @see self::isCsv()
     * @see self::isBase58()
     * @see self::isBase64()
     * @see self::isSerialized()
     * @see self::sanitizeDecodeJson()
     */
    public function isJson(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            // Try by regex. If that fails. try JSON decode
            if (!preg_match('/^(?2)({([ \n\r\t]*)(((?9)(?2):((?2)(?1)(?2)))(,(?2)(?4))*)?}|\[(?2)((?1)(?2)(,(?5))*)?\]|true|false|(\"([^"\\\p{Cc}]|\\(["\\\/bfnrt]|u[\da-fA-F]{4}))*\")|-?(0|[1-9]\d*)(\.\d+)?([eE][-+]?\d+)?|null)(?2)$/', $value)){
                json_decode($value);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->addFailure(tr('must contain a valid JSON string'));
                }
            }
        });
    }



    /**
     * Validates if the selected field is a valid CSV string
     *
     * @param string $separator The separation character, defaults to comma
     * @param string $enclosure
     * @param string $escape
     * @return static
     * @see self::isBase58()
     * @see self::isBase64()
     * @see self::isSerialized()
     * @see self::sanitizeDecodeCsv()
     */
    public function isCsv(string $separator = ',', string $enclosure = "\"", string $escape = "\\"): static
    {
        return $this->validateValues(function(&$value) use ($separator, $enclosure, $escape) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            try {
                str_getcsv($value, $separator, $enclosure, $escape);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid ":separator" separated string', [
                    ':separator' => $separator
                ]));
            }
        });
    }



    /**
     * Validates if the selected field is a serialized string
     *
     * @return static
     * @see self::isCsv()
     * @see self::isBase58()
     * @see self::isBase64()
     * @see self::isSerialized()
     * @see self::sanitizeDecodeSerialized()
     */
    public function isSerialized(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            try {
                unserialize($value);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid serialized string'));
            }
        });
    }



    /**
     * Validates if the selected field is a base58 string
     *
     * @return static
     * @see self::isCsv()
     * @see self::isBase64()
     * @see self::isSerialized()
     * @see self::sanitizeDecodeBase58()
     */
    public function isBase58(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            try {
                base58_decode($value);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid bas58 encoded string'));
            }
        });
    }



    /**
     * Validates if the selected field is a base64 string
     *
     * @return static
     * @see self::isCsv()
     * @see self::isBase58()
     * @see self::isSerialized()
     * @see self::sanitizeDecodeBase64()
     */
    public function isBase64(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            try {
                base64_decode($value);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid bas64 encoded string'));
            }
        });
    }



    /**
     * Sanitize the selected value by trimming whitespace
     *
     * @param string $characters
     * @return static
     * @see trim()
     */
    public function sanitizeTrim(string $characters = "\t\n\r\0\x0B"): static
    {
        return $this->validateValues(function(&$value) use ($characters) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            return trim($value, $characters);
        });
    }



    /**
     * Sanitize the selected value by making the entire string uppercase
     *
     * @return static
     * @see self::sanitizeTrim()
     * @see self::sanitizeLowercase()
     */
    public function sanitizeUppercase(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            try {
                $value = mb_strtoupper($value);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid string'));
            }
        });
    }



    /**
     * Sanitize the selected value by making the entire string lowercase
     *
     * @return static
     * @see self::sanitizeTrim()
     * @see self::sanitizeUppercase()
     */
    public function sanitizeLowercase(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            try {
                $value = mb_strtolower($value);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid string'));
            }
        });
    }



    /**
     * Sanitize the selected value with a search / replace
     *
     * @param array $replace A key => value map of all items that should be searched / replaced
     * @param bool $regex If true, all keys in the $replace array will be treated as a regex instead of a normal string
     *                    This is slower and more memory intensive, but more flexible as well.
     * @return static
     * @see trim()
     */
    public function sanitizeSearchReplace(array $replace, bool $regex = false): static
    {
        return $this->validateValues(function(&$value) use ($replace, $regex) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if ($regex) {
                // Regex search / replace, each key will be treated as a regex instead of a normal string
                $value = preg_replace(array_keys($replace), array_values($replace), $value);

            } else {
                // Standard string search / replace
                $value = str_replace(array_keys($replace), array_values($replace), $value);
            }
        });
    }



    /**
     * Sanitize the selected value by decoding the JSON
     *
     * @param bool $array If true, will return the data in associative arrays instead of generic objects
     * @return static
     * @see self::isJson()
     * @see self::sanitizeDecodeCsv()
     * @see self::sanitizeDecodeSerialized()
     * @see self::sanitizeForceString()
     */
    public function sanitizeDecodeJson(bool $array = true): static
    {
        return $this->validateValues(function(&$value) use ($array) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            try {
                $value = Json::decode($value);
            } catch (JsonException) {
                $this->addFailure(tr('must contain a valid JSON string'));
            }
        });
    }



    /**
     * Sanitize the selected value by decoding the specified CSV
     *
     * @param string $separator The separation character, defaults to comma
     * @param string $enclosure
     * @param string $escape
     * @return static
     * @see self::isCsv()
     * @see self::sanitizeDecodeBase58()
     * @see self::sanitizeDecodeBase64()
     * @see self::sanitizeDecodeJson()
     * @see self::sanitizeDecodeSerialized()
     * @see self::sanitizeDecodeUrl()
     * @see self::sanitizeForceString()
     */
    public function sanitizeDecodeCsv(string $separator = ',', string $enclosure = "\"", string $escape = "\\"): static
    {
        return $this->validateValues(function(&$value) use ($separator, $enclosure, $escape) {
            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            try {
                $value = str_getcsv($value, $separator, $enclosure, $escape);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid ":separator" separated string', [
                    ':separator' => $separator
                ]));
            }
        });
    }



    /**
     * Sanitize the selected value by decoding the specified CSV
     *
     * @return static
     * @see self::sanitizeDecodeBase58()
     * @see self::sanitizeDecodeBase64()
     * @see self::sanitizeDecodeCsv()
     * @see self::sanitizeDecodeJson()
     * @see self::sanitizeDecodeUrl()
     * @see self::sanitizeForceString()
     */
    public function sanitizeDecodeSerialized(): static
    {
        return $this->validateValues(function(&$value) {
            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            try {
                $value = unserialize($value);
            } catch (Throwable $e) {
                $this->addFailure(tr('must contain a valid serialized string'));
            }
        });
    }



    /**
     * Sanitize the selected value by converting it to an array
     *
     * @param string $characters
     * @return static
     * @see trim()
     * @see self::sanitizeForceString()
     */
    public function sanitizeForceArray(string $characters = ','): static
    {
        return $this->validateValues(function(&$value) use ($characters) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            try {
                $value = Arrays::force($value, $characters);

            } catch (Throwable) {
                $this->addFailure(tr('cannot be processed'));
            }
        });
    }



    /**
     * Sanitize the selected value by decoding the specified CSV
     *
     * @return static
     * @see self::sanitizeDecodeBase64()
     * @see self::sanitizeDecodeCsv()
     * @see self::sanitizeDecodeJson()
     * @see self::sanitizeDecodeSerialized()
     * @see self::sanitizeDecodeUrl()
     * @see self::sanitizeForceString()
     */
    public function sanitizeDecodeBase58(): static
    {
        return $this->validateValues(function(&$value) {
            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            try {
                $value = base58_decode($value);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid base58 encoded string'));
            }
        });
    }



    /**
     * Sanitize the selected value by decoding the specified CSV
     *
     * @return static
     * @see self::sanitizeDecodeBase58()
     * @see self::sanitizeDecodeCsv()
     * @see self::sanitizeDecodeJson()
     * @see self::sanitizeDecodeSerialized()
     * @see self::sanitizeDecodeUrl()
     * @see self::sanitizeForceString()
     */
    public function sanitizeDecodeBase64(): static
    {
        return $this->validateValues(function(&$value) {
            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            try {
                $value = base64_decode($value);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid base64 encoded string'));
            }
        });
    }



    /**
     * Sanitize the selected value by decoding the specified CSV
     *
     * @return static
     * @see self::sanitizeDecodeBase58()
     * @see self::sanitizeDecodeBase64()
     * @see self::sanitizeDecodeCsv()
     * @see self::sanitizeDecodeJson()
     * @see self::sanitizeDecodeSerialized()
     * @see self::sanitizeForceString()
     */
    public function sanitizeDecodeUrl(): static
    {
        return $this->validateValues(function(&$value) {
            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            try {
                $value = urldecode($value);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid url string'));
            }
        });
    }



    /**
     * Sanitize the selected value by making it a string
     *
     * @param string $characters
     * @return static
     * @see self::sanitizeDecodeBase58()
     * @see self::sanitizeDecodeBase64()
     * @see self::sanitizeDecodeCsv()
     * @see self::sanitizeDecodeJson()
     * @see self::sanitizeDecodeSerialized()
     * @see self::sanitizeDecodeUrl()
     * @see self::sanitizeForceArray()
     */
    public function sanitizeForceString(string $characters = ','): static
    {
        return $this->validateValues(function(&$value) use ($characters) {
            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            try {
                $value = Strings::force($value, $characters);

            } catch (Throwable) {
                $this->addFailure(tr('cannot be processed'));
            }
        });
    }



    /**
     * Constructor for all validator types
     *
     * @param Validator|null $parent
     * @param array|null $source
     * @return void
     */
    protected function construct(?Validator $parent = null, ?array &$source = []): void
    {
        // Ensure the source is an array
        if ($source === null) {
            $source = [];
        }

        $this->source = &$source;
        $this->parent = $parent;

        $this->reflection_selected_optional = new ReflectionProperty($this, 'selected_optional');
        $this->reflection_process_value     = new ReflectionProperty($this, 'process_value');
   }



    /**
     * Selects the specified key within the array that we are validating
     *
     * @param int|string $field The array key (or HTML form field) that needs to be validated / sanitized
     * @return static
     */
    public function standardSelect(int|string $field): static
    {
        // Unset various values first to ensure the byref link is broken
        unset($this->process_value);
        unset($this->process_values);
        unset($this->selected_value);

        $this->process_value_failed = false;

        if (!$field) {
            throw new OutOfBoundsException(tr('No field specified'));
        }

        if (in_array($field, $this->selected_fields)) {
            throw new KeyAlreadySelectedException(tr('The specified key ":key" has already been selected before', [
                ':key' => $field
            ]));
        }

        if ($this->source === null) {
            throw new OutOfBoundsException(tr('Cannot select field ":field", no source array specified', [
                ':field' => $field
            ]));
        }

        // Does the field exist in the source? If not, initialize it with NULL to be able to process it
        if (!array_key_exists($field, $this->source)) {
            $this->source[$field] = null;
        }

        // Select the field.
        $this->selected_field    = $field;
        $this->selected_fields[] = $field;
        $this->selected_value    = &$this->source[$field];
        $this->process_values    = [null => &$this->selected_value];

        unset($this->selected_optional);

        return $this;
    }



    /**
     * Go over the specified SQL execute array and apply any variable
     *
     * @param array|null $execute
     * @return array|null
     */
    protected function applyExecuteVariables(?array $execute): ?array
    {
        foreach ($execute as &$value) {
            if (is_string($value)) {
                if (str_starts_with($value, '$')) {
                    // Replace this value with key from the array
                    $value = isset_get($this->source[substr($value, 1)]);
                }
            }
        }

        unset($value);
        return $execute;
    }
}