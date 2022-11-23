<?php

namespace Phoundation\Data\Validator;

use DateTime;
use Phoundation\Core\Arrays;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Utils\Json;
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

        // Unset process_values first to ensure the byref link is broken
        unset($this->process_values);
        $this->process_values = &$this->selected_value;
//show($this->process_values);
//show('each');
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
        if ($this->process_value) {
            // A single value was selected, test only this value
            $this->process_value = $function($this->process_value);
        } else {
            $this->ensureSelected();
//show('START VALIDATE VALUES "' . $this->selected_field . '" (' . ($this->process_value_failed ? 'FAILED' : 'NOT FAILED') . ')');
//show($this->process_values);

            if ($this->process_value_failed) {
//show('NOT VALIDATING, ALREADY FAILED');
                // In the span of multiple tests on one value, one test failed, don't execute the rest of the tests
                return $this;
            }

            foreach ($this->process_values as $key => &$value) {
//show('KEY ' . $key.' / VALUE:' . Strings::force($value));
                // Process all process_values
                $this->process_key = $key;
                $this->process_value = &$value;
                $this->process_value_failed = false;

                $this->process_value = $function($this->process_value);
            }

            // Clear up work data
            unset($value);
            unset($this->process_key);
            unset($this->process_value);

            $this->process_key = null;
            $this->process_value = null;
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
        return $this->validateValues(function($value) {
            if ($this->checkIsOptional($value)) {
                if (Strings::getBoolean($value, false) === null) {
                    $this->addFailure(tr('must have a boolean value'));
                    $value = false;
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
     * @return static
     */
    public function isInteger(): static
    {
        return $this->validateValues(function($value) {
            if ($this->checkIsOptional($value)) {
                if (!is_integer($value)) {
                    $this->addFailure(tr('must have an integer value'));
                    $value = 0;
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
     * @return static
     */
    public function isFloat(): static
    {
        return $this->validateValues(function($value) {
            if ($this->checkIsOptional($value)) {
                if (!is_float($value)) {
                    $this->addFailure(tr('must have a float value'));
                    $value = 0.0;
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
     * @return static
     */
    public function isNumeric(): static
    {
        return $this->validateValues(function($value) {
            if ($this->checkIsOptional($value)) {
                if (!is_numeric($value)) {
                    $this->addFailure(tr('must have a numeric value'));
                    $value = 0;
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
     * @return static
     */
    public function isPositive(bool $allow_zero = false): static
    {
        return $this->validateValues(function($value) use ($allow_zero) {
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
     * This method ensures that the specified array key is a valid database id (integer, 1 and above)
     *
     * @return static
     */
    public function isId(): static
    {
        return $this->isPositive(false);
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
        return $this->validateValues(function($value) use ($amount) {
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
     * @return static
     */
    public function isLessThan(int|float $amount): static
    {
        return $this->validateValues(function($value) use ($amount) {
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
     * @return static
     */
    public function isBetween(int|float $minimum, int|float $maximum): static
    {
        return $this->validateValues(function($value) use ($minimum, $maximum) {
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
     * @return static
     */
    public function isNegative(bool $allow_zero = false): static
    {
        return $this->validateValues(function($value) use ($allow_zero) {
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
     * @return static
     */
    public function isScalar(): static
    {
        return $this->validateValues(function($value) {
            if ($this->checkIsOptional($value)) {
                if (!is_scalar($value)) {
//show($value);
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
     * @return static
     */
    public function inArray(array $array) : Validator
    {
        return $this->validateValues(function($value) use ($array) {
            // This value must be scalar, and not too long. What is too long? Longer than the longest allowed item
            $this->isScalar();
            $this->hasMaxCharacters(Arrays::getLongestString($array));

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
     * This method ensures that the specified array key contains the specified string
     *
     * @param string $string
     * @return static
     */
    public function contains(string $string) : Validator
    {
        return $this->validateValues(function($value) use ($string) {
            // This value must be scalar
            $this->isScalar();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (!str_contains($value, $string)) {
                $this->addFailure(tr('must contain ":value"', [':value' => $string]));
            }

            return $value;
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
        return $this->validateValues(function($value) {
            if ($this->checkIsOptional($value)) {
                if (!is_string($value)) {
                    $this->addFailure(tr('must have a string value', [':field' => $this->selected_field]));
                    $value = '';
                }
            }

            return $value;
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
        return $this->validateValues(function($value) use ($characters) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (strlen($value) != $characters) {
                $this->addFailure(tr('must have ":count" characters or more', [':count' => $characters]));
            }

            return $value;
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
        return $this->validateValues(function($value) use ($characters) {
            $this->isString();

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
     * @param int|null $characters
     * @return static
     */
    public function hasMaxCharacters(?int $characters = null): static
    {
        return $this->validateValues(function($value) use ($characters) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
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

            return $value;
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
        return $this->validateValues(function($value) use ($regex) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (!preg_match($regex, $value)) {
                $this->addFailure(tr('must match ":regex"', [':regex' => $regex]));
            }

            return $value;
        });
    }



    /**
     * Validates that the selected field contains only alphabet characters
     *
     * @return static
     */
    public function isAlpha(): static
    {
        return $this->validateValues(function($value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (!ctype_alpha($value)) {
                $this->addFailure(tr('must contain only letters'));
            }

            return $value;
        });
    }



    /**
     * Validates that the selected field contains only alphanumeric characters
     *
     * @return static
     */
    public function isAlphaNumeric(): static
    {
        return $this->validateValues(function($value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (!ctype_alnum($value)) {
                $this->addFailure(tr('must contain only letters and numbers'));
            }

            return $value;
        });
    }



    /**
     * Validates that the selected field contains only lowercase letters
     *
     * @return static
     */
    public function isLowercase(): static
    {
        return $this->validateValues(function($value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (!ctype_lower($value)) {
                $this->addFailure(tr('must contain only lowercase letters'));
            }

            return $value;
        });
    }



    /**
     * Validates that the selected field contains only uppercase letters
     *
     * @return static
     */
    public function isUppercase(): static
    {
        return $this->validateValues(function($value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (!ctype_upper($value)) {
                $this->addFailure(tr('must contain only uppercase letters'));
            }

            return $value;
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
        return $this->validateValues(function($value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (!ctype_punct($value)) {
                $this->addFailure(tr('must contain only uppercase letters'));
            }

            return $value;
        });
    }



    /**
     * Validates that the selected field contains only printable characters (including blanks)
     *
     * @return static
     */
    public function isPrintable(): static
    {
        return $this->validateValues(function($value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (!ctype_print($value)) {
                $this->addFailure(tr('must contain only printable characters'));
            }

            return $value;
        });
    }



    /**
     * Validates that the selected field contains only printable characters (NO blanks)
     *
     * @return static
     */
    public function isGraph(): static
    {
        return $this->validateValues(function($value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (!ctype_graph($value)) {
                $this->addFailure(tr('must contain only visible characters'));
            }

            return $value;
        });
    }



    /**
     * Validates that the selected field contains only whitespace characters
     *
     * @return static
     */
    public function isWhitespace(): static
    {
        return $this->validateValues(function($value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (!ctype_space($value)) {
                $this->addFailure(tr('must contain only whitespace characters'));
            }

            return $value;
        });
    }



    /**
     * Validates that the selected field contains only hexadecimal characters
     *
     * @return static
     */
    public function isHexadecimal(): static
    {
        return $this->validateValues(function($value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (!ctype_xdigit($value)) {
                $this->addFailure(tr('must contain only hexadecimal characters'));
            }

            return $value;
        });
    }



    /**
     * Validates that the selected field contains only octal numbers
     *
     * @return static
     */
    public function isOctal(): static
    {
        return $this->validateValues(function($value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (!preg_match('/^0-7*$/', $value)) {
                $this->addFailure(tr('must contain only octal numbers'));
            }

            return $value;
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
        return $this->validateValues(function($value) use ($validate_value, $strict, $secret) {
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
                    return '';
                }

                if ($value != $validate_value) {
                    if ($secret) {
                        $this->addFailure(tr('must be value ":value"', [':value' => $value]));
                    } else {
                        $this->addFailure(tr('has an incorrect value'));
                    }
                }
            }

            return $value;
        });
    }



    /**
     * Validates that the selected field is a date
     *
     * @return static
     */
    public function isDate(): static
    {
        return $this->validateValues(function($value) {
            $this->isString();
            $this->hasMaxCharacters(64); // Sort-of arbitrary max size, just to ensure Date class won't receive a 2MB string

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

// TODO Implement
//            if (!preg_match($regex, $value)) {
//                $this->addFailure(tr('must match ":regex"', [':regex' => $regex]));
//            }

            return $value;
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
        return $this->validateValues(function($value) use ($date_time) {
            $this->isDate();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

// TODO Implement
//            if (!preg_match($regex, $value)) {
//                $this->addFailure(tr('must match ":regex"', [':regex' => $regex]));
//            }

            return $value;
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
        return $this->validateValues(function($value) use ($date_time) {
            $this->isDate();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

// TODO Implement
//            if (!preg_match($regex, $value)) {
//                $this->addFailure(tr('must match ":regex"', [':regex' => $regex]));
//            }

            return $value;
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
        return $this->validateValues(function($value) {
            if ($this->checkIsOptional($value)) {
                if (!is_array($value)) {
                    $this->addFailure(tr('must have an array value'));
                    $value = [];
                }
            }

            return $value;
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
        return $this->validateValues(function($value) use ($count) {
            $this->isArray();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (count($value) != $count) {
                $this->addFailure(tr('must have exactly ":count" elements', [':count' => $count]));
            }

            return $value;
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
        return $this->validateValues(function($value) use ($count) {
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
     * @return static
     */
    public function hasMaximumElements(int $count): static
    {
        return $this->validateValues(function($value) use ($count) {
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
     * @return static
     */
    public function isHttpMethod(): static
    {
        return $this->validateValues(function($value) {
            $this->hasMinCharacters(3)->hasMaxCharacters(128);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            $value = mb_strtoupper($value);

            // Check against the HTTP methods that are considered valid
            switch ($value) {
                case 'GET':
                case 'HEAD':
                case 'POST':
                case 'PUT':
                case 'DELETE':
                case 'CONNECT':
                case 'OPTIONS':
                case 'TRACE':
                case 'PATCH':
                    break;

                default:
                    $this->addFailure(tr('must contain a valid HTTP method'));
            }

            return $value;
        });
    }



    /**
     * Validates if the selected field is a valid email address
     *
     * @return static
     */
    public function isEmail(): static
    {
        return $this->validateValues(function($value) {
            $this->hasMinCharacters(3)->hasMaxCharacters(128);

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



    /**
     * Validates if the selected field is a valid email address
     *
     * @return static
     */
    public function isUrl(): static
    {
        return $this->validateValues(function($value) {
            $this->hasMinCharacters(3)->hasMaxCharacters(128);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                $this->addFailure(tr('must contain a valid URL'));
            }

            return $value;
        });
    }



    /**
     * Validates if the selected field is a valid IP address
     *
     * @return static
     */
    public function isIp(): static
    {
        return $this->validateValues(function($value) {
            $this->hasMinCharacters(3)->hasMaxCharacters(48);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if (!filter_var($value, FILTER_VALIDATE_IP)) {
                $this->addFailure(tr('must contain a valid IP address'));
            }

            return $value;
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
        return $this->validateValues(function($value) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            // Try by regex. If that fails. try JSON decode
            if (!preg_match('/^(?2)({([ \n\r\t]*)(((?9)(?2):((?2)(?1)(?2)))(,(?2)(?4))*)?}|\[(?2)((?1)(?2)(,(?5))*)?\]|true|false|(\"([^"\\\p{Cc}]|\\(["\\\/bfnrt]|u[\da-fA-F]{4}))*\")|-?(0|[1-9]\d*)(\.\d+)?([eE][-+]?\d+)?|null)(?2)$/', $value)){
                json_decode($value);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->addFailure(tr('must contain a valid JSON string'));
                }
            }

            return $value;
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
        return $this->validateValues(function($value) use ($separator, $enclosure, $escape) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            try {
                str_getcsv($value, $separator, $enclosure, $escape);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid ":separator" separated string', [
                    ':separator' => $separator
                ]));
            }

            return $value;
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
        return $this->validateValues(function($value) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            try {
                unserialize($value);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid serialized string'));
            }

            return $value;
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
        return $this->validateValues(function($value) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            try {
                base58_decode($value);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid bas58 encoded string'));
            }

            return $value;
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
        return $this->validateValues(function($value) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            try {
                base64_decode($value);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid bas64 encoded string'));
            }

            return $value;
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
        return $this->validateValues(function($value) use ($characters) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
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
        return $this->validateValues(function($value) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            try {
                $value = mb_strtoupper($value);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid string'));
            }

            return $value;
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
        return $this->validateValues(function($value) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            try {
                $value = mb_strtolower($value);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid string'));
            }

            return $value;
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
        return $this->validateValues(function($value) use ($replace, $regex) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            if ($regex) {
                // Regex search / replace, each key will be treated as a regex instead of a normal string
                $value = preg_replace(array_keys($replace), array_values($replace), $value);

            } else {
                // Standard string search / replace
                $value = str_replace(array_keys($replace), array_values($replace), $value);
            }

            return $value;
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
     * @see self::sanitizeMakeString()
     */
    public function sanitizeDecodeJson(bool $array = true): static
    {
        return $this->validateValues(function($value) use ($array) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            try {
                $value = Json::decode($value);
            } catch (JsonException) {
                $this->addFailure(tr('must contain a valid JSON string'));
            }

            return $value;
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
     * @see self::sanitizeMakeString()
     */
    public function sanitizeDecodeCsv(string $separator = ',', string $enclosure = "\"", string $escape = "\\"): static
    {
        return $this->validateValues(function($value) use ($separator, $enclosure, $escape) {
            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            try {
                $value = str_getcsv($value, $separator, $enclosure, $escape);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid ":separator" separated string', [
                    ':separator' => $separator
                ]));
            }

            return $value;
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
     * @see self::sanitizeMakeString()
     */
    public function sanitizeDecodeSerialized(): static
    {
        return $this->validateValues(function($value) {
            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            try {
                $value = unserialize($value);
            } catch (Throwable $e) {
                $this->addFailure(tr('must contain a valid serialized string'));
            }

            return $value;
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
     * @see self::sanitizeMakeString()
     */
    public function sanitizeDecodeBase58(): static
    {
        return $this->validateValues(function($value) {
            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            try {
                $value = base58_decode($value);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid base58 encoded string'));
            }

            return $value;
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
     * @see self::sanitizeMakeString()
     */
    public function sanitizeDecodeBase64(): static
    {
        return $this->validateValues(function($value) {
            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            try {
                $value = base64_decode($value);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid base64 encoded string'));
            }

            return $value;
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
     * @see self::sanitizeMakeString()
     */
    public function sanitizeDecodeUrl(): static
    {
        return $this->validateValues(function($value) {
            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            try {
                $value = urldecode($value);
            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid url string'));
            }

            return $value;
        });
    }



    /**
     * Sanitize the selected value by making it a string
     *
     * @return static
     * @see self::sanitizeDecodeBase58()
     * @see self::sanitizeDecodeBase64()
     * @see self::sanitizeDecodeCsv()
     * @see self::sanitizeDecodeJson()
     * @see self::sanitizeDecodeSerialized()
     * @see self::sanitizeDecodeUrl()
     */
    public function sanitizeMakeString(): static
    {
        return $this->validateValues(function($value) {
            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return '';
            }

            try {
                $value = Strings::force($value);
            } catch (Throwable) {
                $this->addFailure(tr('cannot be processed'));
            }

            return $value;
        });
    }
}