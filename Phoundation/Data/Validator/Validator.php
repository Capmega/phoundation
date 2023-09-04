<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator;

use DateTime;
use PDOStatement;
use Phoundation\Accounts\Users\Password;
use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Data\Validator\Exception\KeyAlreadySelectedException;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Exception\ValidatorException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Enums\Interfaces\DisplayModeInterface;
use Phoundation\Web\Http\Url;
use ReflectionProperty;
use Stringable;
use Throwable;
use UnitEnum;


/**
 * Validator class
 *
 * This class validates data from untrusted arrays
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
abstract class Validator implements ValidatorInterface
{
    use ValidatorBasics;


    /**
     * Returns if all validations are disabled or not
     *
     * @return bool
     */
    public static function disabled(): bool
    {
        return static::$disabled;
    }


    /**
     * Disable all validations
     *
     * @return void
     */
    public static function disable(): void
    {
        static::$disabled = true;
    }


    /**
     * Enable all validations
     *
     * @return void
     */
    public static function enable(): void
    {
        static::$disabled = false;
    }


    /**
     * Returns if all validations are disabled or not
     *
     * @return bool
     */
    public static function passwordsDisabled(): bool
    {
        return static::$password_disabled;
    }


    /**
     * Disable password validations
     *
     * @return void
     */
    public static function disablePasswords(): void
    {
        static::$password_disabled = true;
    }


    /**
     * Enable password validations
     *
     * @return void
     */
    public static function enablePasswords(): void
    {
        static::$password_disabled = false;
    }


    /**
     * Forcibly set the specified key of this validator source to the specified value
     *
     * @param string|float|int $key
     * @param mixed $value
     * @return static
     */
    public function setSourceKey(string|float|int $key, mixed $value): static
    {
        $this->source[$key] = $value;
        return $this;
    }


    /**
     * Forcibly remove the specified source key
     *
     * @param string|float|int $key
     * @return static
     */
    public function removeSourceKey(string|float|int $key): static
    {
        unset($this->source[$key]);
        return $this;
    }


    /**
     * Returns the currently selected value
     *
     * @return mixed
     */
    public function getSourceValue(): mixed
    {
        return $this->selected_value;
    }


    /**
     * Allow the validator to check each element in a list of values.
     *
     * Basically each method will expect to process a list always and ->select() will put the selected value in an
     * artificial array because of this. ->each() actually will have a list of values, so puts that list directly into
     * $this->process_values
     *
     * @return static
     * @see DataValidator::select()
     * @see DataValidator::self()
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
     * @return static
     * @see DataValidator::select()
     * @see DataValidator::each()
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
        if ($this->reflection_process_value->isInitialized($this)) {
            // A single value was selected, test only this value
            $function($this->process_value);
        } else {
            $this->ensureSelected();

            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // In the span of multiple tests on one value, one test failed, don't execute the rest of the tests
                    return $this;
                }
            }

            foreach ($this->process_values as $key => &$value) {
                // Process all process_values
                $this->process_key          = $key;
                $this->process_value        = &$value;
// TODO TEST THIS! IF next line is enabled then multiple tests after each other will continue, even if the previous failed!!
//                $this->process_value_failed = false;
                $this->selected_is_default  = false;

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
        if ($this->selected_optional === true) {
            // A default TRUE for boolean values makes no sense as with this they will ALWAYS be TRUE.
            throw new OutOfBoundsException(tr('Cannot use "TRUE" as a default value for boolean field ":selected" as this means the field will ALWAYS be true', [
                ':selected' => $this->selected_field
            ]));
        }

        return $this->validateValues(function(&$value) {
            if (!$this->checkIsOptional($value)) {
                if (Strings::toBoolean($value, false) === null) {
                    if ($value !== null) {
                        $this->addFailure(tr('must have a boolean value'));
                    }

                    $value = false;
                }

                // Sanitize value, must be a boolean
                $value = Strings::toBoolean($value);
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
            if (!$this->checkIsOptional($value)) {
                if (!is_integer($value)) {
                    if (is_string($value) and (((int) $value) == $value)) {
                        // This integer value was specified as a numeric string
                        $value = (int) $value;

                    } else {
                        if ($value !== null) {
                            $this->addFailure(tr('must have an integer value'));
                        }

                        $value = 0;
                    }
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
            if (!$this->checkIsOptional($value)) {
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
            if (!$this->checkIsOptional($value)) {
                if (!is_numeric($value)) {
                    if ($value !== null) {
                        $this->addFailure(tr('must have a numeric value'));
                    }

                    $value = 0;
                }
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
        $this->isInteger();

        if ($this->process_value_failed) {
            // Validation already failed, don't test anything more
            return $this;
        }

        return $this->isPositive($allow_zero);
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
        $this->isFloat();

        if ($this->process_value_failed) {
            // Validation already failed, don't test anything more
            return $this;
        }

        return $this->isBetween(-90, 90);
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
        $this->isFloat();

        if ($this->process_value_failed) {
            // Validation already failed, don't test anything more
            return $this;
        }

        return $this->isBetween(0, 180);
    }


    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a valid database id (integer, 1 and above)
     *
     * @param bool $allow_zero
     * @return static
     */
    public function isDbId(bool $allow_zero = false): static
    {
        $this->isInteger();

        if ($this->process_value_failed) {
            // Validation already failed, don't test anything more
            return $this;
        }

        return $this->isPositive($allow_zero);
    }


    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a valid code
     *
     * @param string|null $until
     * @return static
     */
    public function isCode(?string $until = null): static
    {
        return $this->validateValues(function(&$value) use ($until) {
            if ($until) {
                // Truncate the code at one of the specified characters
                $value = Strings::until($value, $until);
                $value = trim($value);
            }

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
     * @param bool $equal If true, it is more than or equal to, instead of only more than
     * @return static
     */
    public function isMoreThan(int|float $amount, bool $equal = false): static
    {
        return $this->validateValues(function(&$value) use ($amount, $equal) {
            $this->isNumeric();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if ($equal) {
                if ($value < $amount) {
                    $this->addFailure(tr('must be more or equal than ":amount"', [':amount' => $amount]));
                }
            } else {
                if ($value <= $amount) {
                    $this->addFailure(tr('must be more than ":amount"', [':amount' => $amount]));
                }
            }

        });
    }


    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is positive
     *
     * @param int|float $amount
     * @param bool $equal If true, it is less than or equal to, instead of only less than
     * @return static
     */
    public function isLessThan(int|float $amount, bool $equal = false): static
    {
        return $this->validateValues(function(&$value) use ($amount, $equal) {
            $this->isNumeric();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if ($equal) {
                if ($value > $amount) {
                    $this->addFailure(tr('must be less or equal than ":amount"', [':amount' => $amount]));
                }
            } else {
                if ($value >= $amount) {
                    $this->addFailure(tr('must be less than ":amount"', [':amount' => $amount]));
                }
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

            if (($value < $minimum) or ($value > $maximum)) {
                $this->addFailure(tr('must be between ":minimum" and ":maximum"', [
                    ':minimum' => $minimum,
                    ':maximum' => $maximum
                ]));
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
     * This method ensures that the specified array key contains a currency value
     *
     * @return static
     */
    public function isCurrency(): static
    {
        return $this->validateValues(function(&$value) {
            $this->isFloat();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!preg_match('^[\$£¤€₠₱]?(((\d{1,3})(,?\d{1,3})*)|(\d+))(\.\d{2})?$', $value)) {
                if (!preg_match('^[\$£¤€₠₱]?(((\d{1,3})(\.?\d{1,3})*)|(\d+))(,\d{2})?$', $value)) {
                    $this->addFailure(tr('must have a currency value'));
                }
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
            if (!$this->checkIsOptional($value)) {
                if (!is_scalar($value)) {
                    if ($value !== null) {
                        $this->addFailure(tr('must have a scalar value'));
                    }

                    $value = '';
                }
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
    public function isInArray(array $array): static
    {
        return $this->validateValues(function(&$value) use ($array) {
            // This value must be scalar, and not too long. What is too long? Longer than the longest allowed item
            $this->isScalar();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $this->hasMaxCharacters(Arrays::getLongestValueSize($array));

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
     * This method ensures that the specified array key is a scalar value
     *
     * @param UnitEnum $enum
     * @return static
     */
    public function isInEnum(UnitEnum $enum): static
    {
        return $this->validateValues(function(&$value) use ($enum) {
            // This value must be scalar, and not too long. What is too long? Longer than the longest allowed item
            $this->isScalar();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!in_enum($value, $enum)) {
                $this->addFailure(tr('must be one of ":list"', [':list' => $enum]));
            }
        });
    }


    /**
     * Ensures that the value has the specified string
     *
     * This method ensures that the specified array key contains the specified string
     *
     * @param string $string
     * @param bool $regex
     * @return static
     */
    public function contains(string $string, bool $regex = false): static
    {
        return $this->validateValues(function(&$value) use ($string, $regex) {
            // This value must be scalar
            $this->isScalar();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if ($regex) {
                if (!preg_match($string, (string) $value)) {
                    $this->addFailure(tr('must match regex ":value"', [':value' => $string]));
                }
            } else {
                if (!str_contains((string) $value, $string)) {
                    $this->addFailure(tr('must contain ":value"', [':value' => $string]));
                }
            }
        });
    }


    /**
     * Ensures that the value has the specified string
     *
     * This method ensures that the specified array key contains the specified string
     *
     * @param string $string
     * @param bool $regex
     * @return static
     */
    public function containsNot(string $string, bool $regex = false): static
    {
        return $this->validateValues(function(&$value) use ($string, $regex) {
            // This value must be scalar
            $this->isScalar();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if ($regex) {
                if (preg_match($string, $value)) {
                    $this->addFailure(tr('must not contain ":value"', [':value' => $string]));
                }
            } else {
                if (str_contains($value, $string)) {
                    $this->addFailure(tr('must not contain ":value"', [':value' => $string]));
                }
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
     * @param bool $ignore_case
     * @return static
     */
    public function isQueryResult(PDOStatement|string $query, ?array $execute = null, bool $ignore_case = false): static
    {
        return $this->validateValues(function(&$value) use ($query, $execute, $ignore_case) {
            // This value must be scalar, and not too long. What is too long? Longer than the longest allowed item
            $this->isScalar();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $execute        = $this->applyExecuteVariables($execute);
            $validate_value = sql()->getColumn($query, $execute);

            if ($ignore_case) {
                $compare_value  = strtolower((string) $value);
                $validate_value = strtolower((string) $validate_value);
            } else {
                $compare_value  = $value;
            }

            if ($compare_value != $validate_value) {
                $this->addFailure(tr('does not exist'));
            }
        });
    }


    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified key is the same as the column value in the specified query
     *
     * @param string $column
     * @param PDOStatement|string $query
     * @param array|null $execute
     * @param bool $ignore_case
     * @param bool $fail_on_null = true
     * @return static
     */
    public function setColumnFromQuery(string $column, PDOStatement|string $query, ?array $execute = null, bool $ignore_case = false, bool $fail_on_null = true): static
    {
        return $this->validateValues(function(&$value) use ($column, $query, $execute, $ignore_case, $fail_on_null) {
            // This value must be scalar, and not too long. What is too long? Longer than the longest allowed item
            $this->isScalar();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $execute = $this->applyExecuteVariables($execute);
            $result  = sql()->getColumn($query, $execute);

            if (!$result and $fail_on_null) {
                $this->addFailure(Strings::plural(count($execute), tr('value ":values" does not exist', [':values' => implode(', ', $execute)]), tr('values ":values" do not exist', [':values' => implode(', ', $execute)])));
            }

            $this->source[$this->field_prefix . $column] = $result;
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
    public function containsQueryColumn(PDOStatement|string $query, ?array $execute = null): static
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
     * This method ensures that the value is in the results from the specified query
     *
     * @param PDOStatement|string $query
     * @param array|null $execute
     * @return static
     */
    public function inQueryResultArray(PDOStatement|string $query, ?array $execute = null): static
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

            $this->isInArray($results);
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
            if (!$this->checkIsOptional($value)) {
                if (!is_string($value)) {
                    if (!is_numeric($value)) {
                        if ($value !== null) {
                            $this->addFailure(tr('must have a string value'));
                        }

                        $value = '';

                    } else {
                        // A number is allowed to be interpreted as a string
                        $value = (string) $value;
                    }
                }
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
                $this->addFailure(tr('must have exactly ":count" characters', [':count' => $characters]));
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
            $characters = $this->getMaxStringSize($characters);

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
        return $this->contains($regex, true);
    }


    /**
     * Validates that the selected field NOT matches the specified regex
     *
     * @param string $regex
     * @return static
     */
    public function matchesNotRegex(string $regex): static
    {
        return $this->containsNot($regex, true);
    }


    /**
     * Validates that the selected field starts with the specified string
     *
     * @param string $string
     * @return static
     */
    public function startsWith(string $string): static
    {
        return $this->validateValues(function(&$value) use ($string) {
            // This value must be scalar
            $this->isScalar();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!str_starts_with((string) $value, $string)) {
                $this->addFailure(tr('must start with ":value"', [':value' => $string]));
            }
        });
    }


    /**
     * Validates that the selected field ends with the specified string
     *
     * @param string $string
     * @return static
     */
    public function endsWith(string $string): static
    {
        return $this->validateValues(function(&$value) use ($string) {
            // This value must be scalar
            $this->isScalar();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!str_ends_with((string) $value, $string)) {
                $this->addFailure(tr('must end with ":value"', [':value' => $string]));
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
     * Validates that the selected field is not a number
     *
     * @return static
     */
    public function isNotNumeric(): static
    {
        return $this->validateValues(function(&$value) {
            $this->isString();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (is_numeric($value)) {
                $this->addFailure(tr('cannot be a number'));
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

            if (!preg_match('/^[\p{L}\p{N}\p{P}\p{M}\p{S}\p{Z}\t\r\n]+$/u', $value)) {
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
     * @param bool $ignore_case
     * @return static
     * @todo Change these individual flag parameters to one bit flag parameter
     */
    public function isValue(mixed $validate_value, bool $strict = false, bool $secret = false, bool $ignore_case = true): static
    {
        return $this->validateValues(function(&$value) use ($validate_value, $strict, $secret, $ignore_case) {
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
                $this->isScalar();

                if ($this->process_value_failed) {
                    // Validation already failed, don't test anything more
                    return;
                }

                if ($ignore_case) {
                    $compare_value  = strtolower((string) $value);
                    $validate_value = strtolower((string) $validate_value);
                } else {
                    $compare_value  = $value;
                }

                if ($compare_value != $validate_value) {
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
     * @note Regex taken from https://code.oursky.com/regex-date-currency-and-time-accurate-data-extraction/
     * @return static
     */
    public function isDate(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMaxCharacters(32); // Sort-of arbitrary max size, just to ensure Date class won't receive a 2MB string

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            // Must match regex
            if (strtotime($value)) {
                // Must be able to create date object without failure
                try {
                    if (strtotime($value) !== false) {
                        return;
                    }
                    // Yeah, this is not a valid date

                } catch (Throwable) {
                    // Yeah, this is not a valid date
                }
            }

            $this->addFailure(tr('must be a valid date'));
        });
    }


    /**
     * Validates that the selected field is a date
     *
     * @note Regex taken from https://code.oursky.com/regex-date-currency-and-time-accurate-data-extraction/
     * @return static
     */
    public function isTime(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMaxCharacters(15); // 00:00:00.000000

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!is_object(DateTime::createFromFormat('h:i a', $value))){
                if (!is_object(DateTime::createFromFormat('H:i', $value))){
                    if (!is_object(DateTime::createFromFormat('h:i:s a', $value))){
                        if (!is_object(DateTime::createFromFormat('H:i:s', $value))){
                            $this->addFailure(tr('must be a valid time'));
                        }
                    }
                }
            }
        });
    }


    /**
     * Validates that the selected field is a date time field
     *
     * @note Regex taken from https://code.oursky.com/regex-date-currency-and-time-accurate-data-extraction/
     * @return static
     */
    public function isDateTime(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMaxCharacters(32); // Sort-of arbitrary max size, just to ensure Date class won't receive a 2MB string

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            // Must match regex
            if (strtotime($value)) {
                // Must be able to create date object without failure
                try {
                    if (strtotime($value) !== false) {
                        return;
                    }
                    // Yeah, this is not a valid date

                } catch (Throwable) {
                    // Yeah, this is not a valid date
                }
            }

            $this->addFailure(tr('must be a valid date time'));
        });
    }


    /**
     * Validates that the selected field is in the past
     *
     * @param DateTime|null $before
     * @return static
     */
    public function isBefore(?DateTime $before = null): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMaxCharacters(32); // Sort-of arbitrary max size, just to ensure Date class won't receive a 2MB string

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            throw new UnderConstructionException();


            $this->addFailure(tr('must be a valid date'));
        });
    }


    /**
     * Validates that the selected field is in the past
     *
     * @param DateTime|null $after
     * @return static
     */
    public function isAfter(?DateTime $after = null): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMaxCharacters(32); // Sort-of arbitrary max size, just to ensure Date class won't receive a 2MB string

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            throw new UnderConstructionException();


            $this->addFailure(tr('must be a valid date'));
        });
    }


    /**
     * Validates that the selected field is a credit card
     *
     *
     * @note Card regexes taken from https://code.oursky.com/regex-date-currency-and-time-accurate-data-extraction/
     * @note From the site: A huge disclaimer: Never depend your code on card regex. The reason behind is simple. Card
     *       issuers carry on adding new card number patterns or removing old ones. You are likely to end up with
     *       maintaining/debugging the regular expressions that way. It’s still fine to use them for visual effects,
     *       like for identifying the card type on the screen.
     * @return static
     */
    public function isCreditCard(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMaxCharacters(32); // Sort-of arbitrary max size, just to ensure regex won't receive a 2MB string

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $cards = [
                'Amex Card'          => '^3[47][0-9]{13}$',
                'BCGlobal'           => '^(6541|6556)[0-9]{12}$',
                'Carte Blanche Card' => '^389[0-9]{11}$',
                'Diners Club Card'   => '^3(?:0[0-5]|[68][0-9])[0-9]{11}$',
                'Discover Card'      => '^65[4-9][0-9]{13}|64[4-9][0-9]{13}|6011[0-9]{12}|(622(?:12[6-9]|1[3-9][0-9]|[2-8][0-9][0-9]|9[01][0-9]|92[0-5])[0-9]{10})$',
                'Insta Payment Card' => '^63[7-9][0-9]{13}$',
                'JCB Card'           => '^(?:2131|1800|35d{3})d{11}$',
                'KoreanLocalCard'    => '^9[0-9]{15}$',
                'Laser Card'         => '^(6304|6706|6709|6771)[0-9]{12,15}$',
                'Maestro Card'       => '^(5018|5020|5038|6304|6759|6761|6763)[0-9]{8,15}$',
                'Mastercard'         => '^(5[1-5][0-9]{14}|2(22[1-9][0-9]{12}|2[3-9][0-9]{13}|[3-6][0-9]{14}|7[0-1][0-9]{13}|720[0-9]{12}))$',
                'Solo Card'          => '^(6334|6767)[0-9]{12}|(6334|6767)[0-9]{14}|(6334|6767)[0-9]{15}$',
                'Switch Card'        => '^(4903|4905|4911|4936|6333|6759)[0-9]{12}|(4903|4905|4911|4936|6333|6759)[0-9]{14}|(4903|4905|4911|4936|6333|6759)[0-9]{15}|564182[0-9]{10}|564182[0-9]{12}|564182[0-9]{13}|633110[0-9]{10}|633110[0-9]{12}|633110[0-9]{13}$',
                'Union Pay Card'     => '^(62[0-9]{14,17})$',
                'Visa Card'          => '^4[0-9]{12}(?:[0-9]{3})?$',
                'Visa Master Card'   => '^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14})$'
            ];

            foreach ($cards as $regex) {
                if (preg_match($regex, $value)) {
                    return;
                }
            }

            $this->addFailure(tr('must be a valid credit card'));
        });
    }


    /**
     * Validates that the selected field is a valid display mode
     *
     * @return static
     */
    public function isDisplayMode(): static
    {
        return $this->validateValues(function(&$value) {
            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!($value instanceof DisplayModeInterface)) {
                if (is_string($value)) {
                    // Maybe a string representation of a backed enum?
                    $test = DisplayMode::tryFrom($value);

                    if ($test) {
                        $value = $test;

                    } else {
                        $this->addFailure(tr('must be a valid display mode'));
                    }
                }
            }
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
            $this->hasMaxCharacters(64); // Sort-of arbitrary max size, just to ensure Date class won't receive a 2MB string

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $this->isQueryResult('SELECT `id` FROM `geo_timezones` WHERE `name` = :name', [':name' => $value]);
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
            if (!$this->checkIsOptional($value)) {
                if (!is_array($value)) {
                    if ($value !== null) {
                        $this->addFailure(tr('must have an array value'));
                    }

                    $value = [];
                }
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
    public function isPhoneNumber(): static
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
     * @param string $separator
     * @return static
     */
    public function isPhoneNumbers(string $separator = ','): static
    {
        return $this->validateValues(function(&$value) use ($separator) {
            $this->hasMinCharacters(10)->hasMaxCharacters(64);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $separator = Strings::escapeForRegex($separator);
            $this->matchesRegex('/[0-9- ' . $separator . '].+?/');
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
     * @param int $characters
     * @return static
     */
    public function isName(int $characters = 128): static
    {
        return $this->validateValues(function(&$value) use ($characters) {
            $this->hasMinCharacters(1)->hasMaxCharacters($characters);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $this->isPrintable()->isNotNumeric();
        });
    }


    /**
     * Validates if the selected field is a valid name
     *
     * @param int $characters
     * @return static
     */
    public function isUsername(int $characters = 64): static
    {
        return $this->validateValues(function(&$value) use ($characters) {
            $this->hasMinCharacters(2)->hasMaxCharacters($characters);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            $this->isAlphaNumeric()->isNotNumeric();
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
     * Validates if the selected field is a valid file path
     *
     * @param Stringable|string|null $check_in_path
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param bool $exists
     * @return static
     */
    public function isPath(Stringable|string|null $check_in_path = null, RestrictionsInterface|array|string|null $restrictions = null, bool $exists = true): static
    {
        $restrictions = Restrictions::ensure($restrictions)->setLabel(tr('Validator'));

        return $this->validateValues(function(&$value) use($check_in_path, $restrictions, $exists) {
            $this->hasMinCharacters(1)->hasMaxCharacters(2048);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if ($check_in_path !== null) {
                if ($exists) {
                    // The specified directory must exist
                    $this->checkFile($value, $check_in_path, $restrictions, true);

                } else {
                    // The specified directory must NOT exist, but ensure the parent path is available so that we can
                    // create it
                    $this->checkFile(dirname($value), $check_in_path, $restrictions, true);

                    if (file_exists($value)) {
                        $this->addFailure(tr('is a path that already exists'));
                    }
                }
            }
        });
    }


    /**
     * Validates if the selected field is a valid directory
     *
     * @param Stringable|string|null $check_in_path
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param bool $exists
     * @return static
     */
    public function isDirectory(Stringable|string|null $check_in_path = null, RestrictionsInterface|array|string|null $restrictions = null, bool $exists = true): static
    {
        $restrictions = Restrictions::ensure($restrictions)->setLabel(tr('Validator'));

        return $this->validateValues(function(&$value) use($check_in_path, $restrictions, $exists) {
            $this->hasMinCharacters(1)->hasMaxCharacters(2048);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if ($check_in_path !== null) {
                if ($exists) {
                    // The specified directory must exist
                    $this->checkFile($value, $check_in_path, $restrictions, true);

                } else {
                    // The specified directory must NOT exist, but ensure the parent path is available so that we can
                    // create it
                    $this->checkFile(dirname($value), $check_in_path, $restrictions, true);

                    if (file_exists($value)) {
                        $this->addFailure(tr('is a directory that already exists'));
                    }
                }
            }
        });
    }


    /**
     * Validates if the selected field is a valid file
     *
     * @param Stringable|string|null $check_in_path
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param bool $exists
     * @return static
     */
    public function isFile(Stringable|string|null $check_in_path = null, RestrictionsInterface|array|string|null $restrictions = null, bool $exists = true): static
    {
        $restrictions = Restrictions::ensure($restrictions)->setLabel(tr('Validator'));

        return $this->validateValues(function(&$value) use($check_in_path, $restrictions, $exists) {
            $this->hasMinCharacters(1)->hasMaxCharacters(2048);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if ($check_in_path !== null) {
                if ($exists) {
                    // The specified file must exist
                    $this->checkFile($value, $check_in_path, $restrictions);

                } else {
                    // The specified directory must NOT exist, but ensure the parent path is available so that we can
                    // create it
                    $this->checkFile(dirname($value), $check_in_path, $restrictions, true);

                    if (file_exists($value)) {
                        $this->addFailure(tr('is a file that already exists'));
                    }
                }
            }
        });
    }


    /**
     * Checks if the specified path exists or not, and if its of the correct type
     *
     * @param string|bool $value
     * @param Stringable|string|null $exists_in_path
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param bool $directory
     * @return void
     */
    protected function checkFile(string|bool $value, Stringable|string|null $exists_in_path = null, RestrictionsInterface|array|string|null $restrictions = null, ?bool $directory = false): void
    {
        if ($directory) {
            $type = 'directory';

        } elseif (is_bool($directory)) {
            $type = 'file';

        } else {
            $type = '';
        }

        if (!$value) {
            // Some value must be specified
            $this->addFailure(tr('must contain a path'));
            return;
        }

        if (!$restrictions) {
            throw new ValidatorException(tr('Cannot validate the specified :type, no restrictions specified', [
                ':type' => $type
            ]));
        }

        $path = ($exists_in_path ? Strings::slash($exists_in_path) : null) . $value;

        Restrictions::ensure($restrictions)->check($path, false);

        if (!file_exists($path)) {
            if ($type) {
                $this->addFailure(tr('must be an existing :type', [':type' => $type]));
            } else {
                $this->addFailure(tr('must exist'));
            }
        }

        if ($directory) {
            if (!is_dir($path)) {
                $this->addFailure(tr('must be a directory'));
            }
        } elseif (is_bool($directory)) {
            if (is_dir($path)) {
                $this->addFailure(tr('cannot be a directory'));
            }
        }
    }


    /**
     * Validates if the selected field is a valid description
     *
     * @param int $characters
     * @return static
     */
    public function isDescription(int $characters = 16_777_200): static
    {
        return $this->validateValues(function(&$value) use ($characters) {
            $this->hasMaxCharacters($characters);

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
            if (static::passwordsDisabled()) {
                // Don't test passwords
                return;
            }

            $this->hasMinCharacters(10)->hasMaxCharacters(128);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            try {
                Password::testSecurity($value);

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
     * @param int $characters
     * @return static
     */
    public function isColor(int $characters = 6): static
    {
        return $this->validateValues(function(&$value) use ($characters) {
            $this->hasMinCharacters(3)->hasMaxCharacters($characters);

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            // Color (for the moment) is only accepted in hexadecimal format
            $this->isHexadecimal();
        });
    }


    /**
     * Validates if the selected field is a valid email address
     *
     * @param int $characters
     * @return static
     */
    public function isEmail(int $characters = 2048): static
    {
        return $this->validateValues(function(&$value) use ($characters) {
            $this->hasMinCharacters(3)->hasMaxCharacters($characters);

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
     * @param int $max_size
     * @return static
     */
    public function isUrl(int $max_size = 2048): static
    {
        return $this->validateValues(function(&$value) use ($max_size) {
            $this->hasMinCharacters(3)->hasMaxCharacters($max_size);

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
     * @return static
     * @copyright The used JSON regex validation taken from a twitter post by @Fish_CTO
     * @see static::isCsv()
     * @see static::isBase58()
     * @see static::isBase64()
     * @see static::isSerialized()
     * @see static::sanitizeDecodeJson()
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
            @json_decode($value);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addFailure(tr('must contain a valid JSON string'));
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
     * @see static::isBase58()
     * @see static::isBase64()
     * @see static::isSerialized()
     * @see static::sanitizeDecodeCsv()
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
     * @see static::isCsv()
     * @see static::isBase58()
     * @see static::isBase64()
     * @see static::isSerialized()
     * @see static::sanitizeDecodeSerialized()
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
     * @see static::isCsv()
     * @see static::isBase64()
     * @see static::isSerialized()
     * @see static::sanitizeDecodeBase58()
     */
    public function isBase58(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!Strings::isBase58($value)) {
                $this->addFailure(tr('must contain a valid Base58 encoded string'));
            }
        });
    }


    /**
     * Validates if the selected field is a base64 string
     *
     * @return static
     * @see static::isCsv()
     * @see static::isBase58()
     * @see static::isSerialized()
     * @see static::sanitizeDecodeBase64()
     */
    public function isBase64(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!Strings::isBase64($value)) {
                $this->addFailure(tr('must contain a valid Base64 encoded string'));
            }
        });
    }


    /**
     * Validates if the selected field is a valid version number
     *
     * @param int $characters
     * @return static
     */
    public function isVersion(int $characters = 11): static
    {
        return $this->validateValues(function(&$value) use ($characters) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}/', $value)) {
                $this->addFailure(tr('must contain a valid version number'));
            }
        });
    }


    /**
     * Validates if the specified function returns TRUE for this value
     *
     * @param callable $function
     * @param string $failure
     * @return static
     */
    public function isTrue(callable $function, string $failure): static
    {
        return $this->validateValues(function(&$value) use ($function, $failure) {
            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (!$function($value, $this->source)) {
                $this->addFailure($failure);
            }
        });
    }


    /**
     * Validates if the specified function returns FALSE for this value
     *
     * @param callable $function
     * @param string $failure
     * @return static
     */
    public function isFalse(callable $function, string $failure): static
    {
        return $this->validateValues(function(&$value) use ($function, $failure) {
            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if ($function($value, $this->source)) {
                $this->addFailure($failure);
            }
        });
    }


    /**
     * Validates the value is unique in the table
     *
     * @note This requires Validator::$id to be set with an entry id through Validator::setId()
     * @note This requires Validator::setTable() to be set with a valid, existing table
     * @param string|null $failure
     * @return static
     */
    public function isUnique(?string $failure = null): static
    {
        return $this->validateValues(function(&$value) use ($failure) {
            if ($this->process_value_failed) {
                // Validation already failed, don't test anything more
                return;
            }

            if (sql()->DataEntryExists($this->table, Strings::from($this->selected_field, $this->field_prefix), $value, $this->id)) {
                $this->addFailure($failure ?? tr('with value ":value" already exists', [':value' => $value]));
            }
        });
    }


    /**
     * Sanitize the selected value by applying htmlspecialchars()
     *
     * @return static
     * @see trim()
     */
    public function sanitizeHtmlSpecialChars(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMaxCharacters();

            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return;
                }
            }

            if (is_string($value)) {
                $value = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8', true);
            }
        });
    }


    /**
     * Sanitize the selected value by applying htmlentities()
     *
     * @return static
     * @see trim()
     */
    public function sanitizeHtmlEntities(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMaxCharacters();

            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return;
                }
            }

            if (is_string($value)) {
                $value = htmlentities($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8', true);
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
    public function sanitizeTrim(string $characters = " \t\n\r\0\x0B"): static
    {
        return $this->validateValues(function(&$value) use ($characters) {
            $this->hasMaxCharacters();

            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return;
                }
            }

            if (is_string($value)) {
                $value = trim($value, $characters);
            }
        });
    }


    /**
     * Sanitize the selected value by starting the value from the specified needle
     *
     * @param string $needle
     * @return static
     * @see String::from()
     * @see Validator::sanitizeUntil()
     * @see Validator::sanitizeFromReverse()
     */
    public function sanitizeFrom(string $needle): static
    {
        return $this->validateValues(function(&$value) use ($needle) {
            $this->isString();

            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return;
                }
            }

            $value = Strings::from($value, $needle);
        });
    }


    /**
     * Sanitize the selected value by ending the value at the specified needle
     *
     * @param string $needle
     * @return static
     * @see String::until()
     * @see Validator::sanitizeFrom()
     * @see Validator::sanitizeUntilReverse()
     */
    public function sanitizeUntil(string $needle): static
    {
        return $this->validateValues(function(&$value) use ($needle) {
            $this->isString();

            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return;
                }
            }

            $value = Strings::until($value, $needle);
        });
    }


    /**
     * Sanitize the selected value by starting the value from the specified needle, but starting search from the end of
     * the string
     *
     * @param string $needle
     * @return static
     * @see String::fromReverse()
     * @see Validator::sanitizeFrom()
     * @see Validator::sanitizeUntilReverse()
     */
    public function sanitizeFromReverse(string $needle): static
    {
        return $this->validateValues(function(&$value) use ($needle) {
            $this->isString();

            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return;
                }
            }

            $value = Strings::fromReverse($value, $needle);
        });
    }


    /**
     * Sanitize the selected value by ending the value at the specified needle, but starting search from the end of the
     * string
     *
     * @param string $needle
     * @return static
     * @see String::untilReverse()
     * @see Validator::sanitizeUntil()
     * @see Validator::sanitizeFromReverse()
     */
    public function sanitizeUntilReverse(string $needle): static
    {
        return $this->validateValues(function(&$value) use ($needle) {
            $this->isString();

            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return;
                }
            }

            $value = Strings::untilReverse($value, $needle);
        });
    }


    /**
     * Sanitize the selected value by making the entire string uppercase
     *
     * @return static
     * @see static::sanitizeTrim()
     * @see static::sanitizeLowercase()
     */
    public function sanitizeUppercase(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return;
                }
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
     * @see static::sanitizeTrim()
     * @see static::sanitizeUppercase()
     */
    public function sanitizeLowercase(): static
    {
        return $this->validateValues(function(&$value) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return;
                }
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
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return;
                }
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
     * @see static::isJson()
     * @see static::sanitizeDecodeCsv()
     * @see static::sanitizeDecodeSerialized()
     * @see static::sanitizeForceString()
     */
    public function sanitizeDecodeJson(bool $array = true): static
    {
        return $this->validateValues(function(&$value) use ($array) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return;
                }
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
     * @see static::isCsv()
     * @see static::sanitizeDecodeBase58()
     * @see static::sanitizeDecodeBase64()
     * @see static::sanitizeDecodeJson()
     * @see static::sanitizeDecodeSerialized()
     * @see static::sanitizeDecodeUrl()
     * @see static::sanitizeForceString()
     */
    public function sanitizeDecodeCsv(string $separator = ',', string $enclosure = "\"", string $escape = "\\"): static
    {
        return $this->validateValues(function(&$value) use ($separator, $enclosure, $escape) {
            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return;
                }
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
     * @see static::sanitizeDecodeBase58()
     * @see static::sanitizeDecodeBase64()
     * @see static::sanitizeDecodeCsv()
     * @see static::sanitizeDecodeJson()
     * @see static::sanitizeDecodeUrl()
     * @see static::sanitizeForceString()
     */
    public function sanitizeDecodeSerialized(): static
    {
        return $this->validateValues(function(&$value) {
            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return;
                }
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
     * @see static::sanitizeForceString()
     */
    public function sanitizeForceArray(string $characters = ','): static
    {
        return $this->validateValues(function(&$value) use ($characters) {
            $this->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return;
                }
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
     * @see static::sanitizeDecodeBase64()
     * @see static::sanitizeDecodeCsv()
     * @see static::sanitizeDecodeJson()
     * @see static::sanitizeDecodeSerialized()
     * @see static::sanitizeDecodeUrl()
     * @see static::sanitizeForceString()
     */
    public function sanitizeDecodeBase58(): static
    {
        return $this->validateValues(function(&$value) {
            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return;
                }
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
     * @see static::sanitizeDecodeBase58()
     * @see static::sanitizeDecodeCsv()
     * @see static::sanitizeDecodeJson()
     * @see static::sanitizeDecodeSerialized()
     * @see static::sanitizeDecodeUrl()
     * @see static::sanitizeForceString()
     */
    public function sanitizeDecodeBase64(): static
    {
        return $this->validateValues(function(&$value) {
            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return;
                }
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
     * @see static::sanitizeDecodeBase58()
     * @see static::sanitizeDecodeBase64()
     * @see static::sanitizeDecodeCsv()
     * @see static::sanitizeDecodeJson()
     * @see static::sanitizeDecodeSerialized()
     * @see static::sanitizeForceString()
     */
    public function sanitizeDecodeUrl(): static
    {
        return $this->validateValues(function(&$value) {
            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return;
                }
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
     * @todo KNOWN BUG: THIS DOESNT WORK
     * @see static::sanitizeDecodeBase58()
     * @see static::sanitizeDecodeBase64()
     * @see static::sanitizeDecodeCsv()
     * @see static::sanitizeDecodeJson()
     * @see static::sanitizeDecodeSerialized()
     * @see static::sanitizeDecodeUrl()
     * @see static::sanitizeForceArray()
     */
    public function sanitizeForceString(string $characters = ','): static
    {
        return $this->validateValues(function(&$value) use ($characters) {
            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return;
                }
            }

            try {
                $value = Strings::force($value, $characters);

            } catch (Throwable) {
                $this->addFailure(tr('cannot be processed'));
            }
        });
    }


    /**
     * Sanitize the selected value by decoding the specified CSV
     *
     * @param string|null $pre
     * @param string|null $post
     * @return static
     */
    public function sanitizePrePost(?string $pre, ?string $post): static
    {
        return $this->validateValues(function(&$value) use ($pre, $post) {
            if ($pre or $post) {
                if ($this->process_value_failed) {
                    if (!$this->selected_is_default) {
                        // Validation already failed, don't test anything more
                        return $this;
                    }

                    // This field contains the default
                }

                if (!is_scalar($this->selected_value)) {
                    throw new ValidatorException(tr('Cannot sanitize pre / post string data for field ":field", the field contains a non scalar value', [
                        ':field' => $this->selected_field
                    ]));
                }

                $value = $pre . $value . $post;
            }

            return $this;
        });
    }


    /**
     * Sanitize the selected value by applying the specified transformation callback
     *
     * @param callable $callback
     * @return static
     */
    public function sanitizeTransform(callable $callback): static
    {
        return $this->validateValues(function(&$value) use ($callback) {
            if ($this->process_value_failed) {
                if (!$this->selected_is_default) {
                    // Validation already failed, don't test anything more
                    return $this;
                }

                // This field contains the default
            }

            $value = $callback($value, $this->source, $this);
            return $this;
        });
    }


    /**
     * Constructor for all validator types
     *
     * @param ValidatorInterface|null $parent
     * @param array|null $source
     * @return void
     */
    protected function construct(?ValidatorInterface $parent = null, ?array &$source = []): void
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
     * Returns the field prefix value
     *
     * @return string|null
     */
    public function getFieldPrefix(): ?string
    {
        return $this->field_prefix;
    }


    /**
     * Sets the field prefix value
     *
     * @param string|null $field_prefix
     * @return $this
     */
    public function setFieldPrefix(?string $field_prefix): static
    {
        $this->field_prefix = $field_prefix;
        return $this;
    }


    /**
     * Returns the table value
     *
     * @return string|null
     */
    public function getTable(): ?string
    {
        return $this->table;
    }


    /**
     * Sets the table value
     *
     * @param string|null $table
     * @return $this
     */
    public function setTable(?string $table): static
    {
        $this->table = $table;
        return $this;
    }


    /**
     * Selects the specified key within the array that we are validating
     *
     * @param string|int $field The array key (or HTML form field) that needs to be validated / sanitized
     * @return static
     */
    public function standardSelect(string|int $field): static
    {
        // Unset various values first to ensure the byref link is broken
        unset($this->process_value);
        unset($this->process_values);
        unset($this->selected_value);

        $this->process_value_failed = false;
        $this->selected_is_default  = false;
        $this->selected_is_optional = false;

        if (!$field) {
            throw new OutOfBoundsException(tr('No field specified'));
        }

        // Add the field prefix to the field name
        $field = $this->field_prefix . $field;

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
        $this->selected_optional = null;

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
                    // Fix field names with field prefix
                    $value = $this->field_prefix . substr($value, 1);

                    if (!array_key_exists($value, $this->source)) {
                        throw new OutOfBoundsException(tr('Specified execution variable ":value" does not exist in the specified source', [
                            ':value' => $value
                        ]));
                    }

                    // Replace this value with key from the array
                    $value = $this->source[$value];
                }
            }
        }

        unset($value);
        return $execute;
    }
}
