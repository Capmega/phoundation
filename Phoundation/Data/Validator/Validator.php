<?php

/**
 * Validator class
 *
 * This class validates data from untrusted arrays
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Validator;

use PDOStatement;
use Phoundation\Accounts\Users\Password;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataRestrictions;
use Phoundation\Data\Validator\Exception\KeyAlreadySelectedException;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Exception\ValidatorException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Date\DateFormats;
use Phoundation\Date\DateTime;
use Phoundation\Date\DateTimeFormats;
use Phoundation\Date\Exception\UnsupportedDateFormatException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsPathInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Filesystem\FsPath;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Utils\Json;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Http\Url;
use ReflectionProperty;
use Stringable;
use Throwable;
use UnitEnum;

abstract class Validator implements ValidatorInterface
{
    use TraitValidatorCore;
    use TraitDataRestrictions;


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
     * Rename the from_key to to_key if it exists
     *
     * @param string|float|int $from_key
     * @param string|float|int $to_key
     * @param bool             $exception
     * @param bool             $overwrite
     *
     * @return static
     */
    public function renameKey(string|float|int $from_key, string|float|int $to_key, bool $exception = true, bool $overwrite = true): static
    {
        if (!array_key_exists($from_key, $this->source)) {
            if ($exception) {
                throw new OutOfBoundsException(tr('Cannot rename ":class" key from ":from" to ":to", the ":original" key does not exist', [
                    ':class'    => get_class($this),
                    ':from'     => $from_key,
                    ':to'       => $to_key,
                    ':original' => $from_key,
                ]));
            }

            // from_key doesn't exist, initialize the from_key as a null value
            $this->source[$from_key] = null;
        }

        if (array_key_exists($to_key, $this->source)) {
            // Target already exists, should we overwrite?
            if (!$overwrite) {
                // Don't overwrite, don't change anything
                return $this;
            }
        }

        // Move the from_key to the to_key
        $this->source[$to_key] = $this->source[$from_key];
        unset($this->source[$from_key]);

        return $this;
    }


    /**
     * Forcibly set the specified key of this validator source to the specified value
     *
     * @param mixed            $value
     * @param string|float|int $key
     *
     * @return static
     */
    public function set(mixed $value, string|float|int $key): static
    {
        $this->source[$key] = $value;

        return $this;
    }


    /**
     * Forcibly remove the specified source key
     *
     * @param string|float|int $key
     *
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
    public function getSelectedValue(): mixed
    {
        return $this->selected_value;
    }


    /**
     * Allow the validator to check each element in a list of values.
     *
     * Basically, each method will expect to process a list always and ->select() will put the selected value in an
     * artificial array because of this. ->Each() actually will have a list of values, so puts that list directly into
     * $this->process_values
     *
     * @return static
     * @see DataValidator::select()
     * @see DataValidator::self()
     */
    public function each(): static
    {
        // This very obviously only works on arrays
        $this->isArray();

        if (!$this->process_value_failed) {
            // Unset process_values first to ensure the byref link is broken
            unset($this->process_values);
            $this->process_values = &$this->selected_value;
        }

        return $this;
    }


    /**
     * This method will allow the currently selected key to pass without performing any validation tests
     *
     * @return $this
     */
    public function doNotValidate(): static
    {
        if ($this->test_count) {
            // Cannot NOT validate, validation tests have already been executed on it.
            throw new OutOfBoundsException(tr('Cannot skip validation tests on key ":key", there have already been ":count" validation tests been executed on it', [
                ':key'   => $this->selected_field,
                ':count' => $this->test_count
            ]));
        }

        $this->test_count++;
        return $this;
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
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
     * Apply the specified anonymous function on a single or all of the process_values for the selected field
     *
     * @param callable $function
     *
     * @return static
     */
    protected function validateValues(callable $function): static
    {
        if ($this->reflection_process_value->isInitialized($this)) {
            // A single value was selected, test only this value
            $function($this->process_value);

        } else {
            $this->ensureSelected();

            if ($this->process_value_failed or $this->selected_is_default) {
                // In the span of multiple tests on one value, one test failed, don't execute the rest of the tests
                return $this;
            }

            foreach ($this->process_values as $key => &$value) {
                // Process all process_values
                $this->process_key   = $key;
                $this->process_value = &$value;
// TODO TEST THIS! IF next line is enabled then multiple tests after each other will continue, even if the previous failed!!
//                $this->process_value_failed = false;
                $this->selected_is_default = false;
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
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a boolean
     *
     * @return static
     */
    public function isBoolean(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            if ($this->checkIsOptional($value)) {
                if (!is_bool($this->selected_optional)) {
                    if ($this->selected_optional !== null) {
                        throw new OutOfBoundsException(tr('Invalid default data ":data" specified for field ":selected", it must be boolean', [
                            ':data'     => $this->selected_optional,
                            ':selected' => $this->selected_field,
                        ]));
                    }

                    $this->selected_optional = false;
                }

                $value = $this->selected_optional;

            } else {
                $value = Strings::toBoolean($value, false);

                if ($value === null) {
                    $this->addFailure(tr('must have a boolean value'));
                }
            }
        });
    }


    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a valid natural number (integer, 1 and above)
     *
     * @param bool $allow_zero
     *
     * @return static
     */
    public function isNatural(bool $allow_zero = true): static
    {
        $this->test_count++;
        $this->isInteger();

        if ($this->process_value_failed or $this->selected_is_default) {
            // Validation already failed or defaulted, don't test anything more
            return $this;
        }

        return $this->isPositive($allow_zero);
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
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
     * This method ensures that the specified array key is positive
     *
     * @param bool $allow_zero
     *
     * @return static
     */
    public function isPositive(bool $allow_zero = false): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($allow_zero) {
            $this->isNumeric();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     * This method ensures that the specified array key is numeric
     *
     * @return static
     */
    public function isNumeric(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            if (!$this->checkIsOptional($value)) {
                if (!is_numeric($value)) {
                    if ($value !== null) {
                        $this->addFailure(tr('must have a numeric value'));
                    }

                    $value = 0;

                } else {
                    // Yay, the value is numeric, but is it a float or an integer? Detect and convert here.
                    $original = $value;
                    $value    = (int) $value;

                    if ($original == $value) {
                        // It looks like value was an int, keep it
                        return;
                    }

                    $value = (float) $original;
                }
            }
        });
    }


    /**
     * Validates that the specified value is either an integer number, or a valid amount of bytes
     *
     * 1KB    = 1000
     * 1MB    = 1000000
     * 1GB    = 1000000000
     * 1GiB   = 1073741824
     * 1.5GiB = 1610612736
     * etc...
     *
     * @return static
     * @see trim()
     */
    public function isBytes(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->hasMaxCharacters(32);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (!is_numeric_integer($value)) {
                try {
                    $value = Numbers::fromBytes($value);
                } catch (Throwable) {
                    $this->addFailure(tr('must have a valid byte size, like 1000, 1000kb, 1000MiB, etc'));
                }
            }
        });
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
        $this->test_count++;
        $this->isFloat();

        if ($this->process_value_failed or $this->selected_is_default) {
            // Validation already failed or defaulted, don't test anything more
            return $this;
        }

        return $this->isBetween(-90, 90);
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
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
     * This method ensures that the specified array key is between the two specified amounts
     *
     * @param int|float $minimum
     * @param int|float $maximum
     * @param bool      $equal
     *
     * @return static
     */
    public function isBetween(int|float $minimum, int|float $maximum, bool $equal = true): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($minimum, $maximum, $equal) {
            $this->isNumeric();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if ($equal) {
                if (($value < $minimum) or ($value > $maximum)) {
                    $this->addFailure(tr('must be between ":minimum" and ":maximum"', [
                        ':minimum' => $minimum,
                        ':maximum' => $maximum,
                    ]));
                }

            } else {
                if (($value <= $minimum) or ($value >= $maximum)) {
                    $this->addFailure(tr('must be between ":minimum" and ":maximum"', [
                        ':minimum' => $minimum,
                        ':maximum' => $maximum,
                    ]));
                }
            }
        });
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
        $this->test_count++;
        $this->isFloat();

        if ($this->process_value_failed or $this->selected_is_default) {
            // Validation already failed or defaulted, don't test anything more
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
     * @param bool $allow_negative
     *
     * @return static
     */
    public function isDbId(bool $allow_zero = false, bool $allow_negative = false): static
    {
        $this->test_count++;
        $this->isInteger();

        if ($this->process_value_failed or $this->selected_is_default) {
            // Validation already failed or defaulted, don't test anything more
            return $this;
        }

        if ($allow_negative) {
            if ($allow_zero) {
                return $this;
            }

            return $this->isNotValue(0);
        }

        return $this->isPositive($allow_zero);
    }


    /**
     * Validates that the selected field is the specified value
     *
     * @param mixed $validate_value
     * @param bool  $strict If true, will perform a strict check
     * @param bool  $secret If specified the $validate_value will not be shown
     * @param bool  $ignore_case
     *
     * @return static
     * @todo Change these individual flag parameters to one bit flag parameter
     */
    public function isNotValue(mixed $validate_value, bool $strict = false, bool $secret = false, bool $ignore_case = true): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($validate_value, $strict, $secret, $ignore_case) {
            if ($strict) {
                // Strict validation
                if ($value === $validate_value) {
                    if ($secret) {
                        $this->addFailure(tr('must not be exactly value ":value"', [':value' => $value]));

                    } else {
                        $this->addFailure(tr('has an incorrect value'));
                    }
                }

            } else {
                $this->isScalar();

                if ($this->process_value_failed or $this->selected_is_default) {
                    // Validation already failed or defaulted, don't test anything more
                    return;
                }

                if ($ignore_case) {
                    $compare_value  = strtolower((string) $value);
                    $validate_value = strtolower((string) $validate_value);

                } else {
                    $compare_value = $value;
                }

                if ($compare_value == $validate_value) {
                    if ($secret) {
                        $this->addFailure(tr('must not be value ":value"', [':value' => $value]));

                    } else {
                        $this->addFailure(tr('has an incorrect value'));
                    }
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
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
     * This method ensures that the specified array key is a valid code
     *
     * @param string|null $until
     * @param int         $max_characters
     *
     * @return static
     */
    public function isCode(?string $until = null, int $max_characters = 64): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($until, $max_characters) {
            if ($until) {
                // Truncate the code at one of the specified characters
                $value = Strings::until($value, $until);
                $value = trim($value);
            }

            $this->sanitizeTrim()->hasMinCharacters(2)->hasMaxCharacters($max_characters);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $this->isPrintable();
        });
    }


    /**
     * Validates that the selected field is equal or shorter than the specified number of characters
     *
     * @param int|null $characters
     *
     * @return static
     */
    public function hasMaxCharacters(?int $characters = null): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($characters) {
            $this->isString();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            // Validate the maximum number of characters
            $characters = $this->getMaxStringSize($characters);

            if ($characters <= 0) {
                if (!$characters) {
                    throw new ValidatorException(tr('Cannot check max characters, the amount of maximum characters specified is 0'));
                }

                throw new ValidatorException(tr('Cannot check max characters, the specified amount of maximum characters ":characters" is negative', [
                    ':characters' => $characters,
                ]));
            }

            if (strlen($value) > $characters) {
                $this->addFailure(tr('must have ":count" characters or less', [':count' => $characters]));
            }
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            if (!$this->checkIsOptional($value)) {
                if (!is_string($value)) {
                    if ($value instanceof Stringable) {
                        // Force value to be string from here
                        $value = (string) $value;

                    } elseif (!is_numeric($value)) {
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
     * Validates that the selected field is equal or larger than the specified number of characters
     *
     * @param int $characters
     *
     * @return static
     */
    public function hasMinCharacters(int $characters): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($characters) {
            $this->isString();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (strlen($value) < $characters) {
                $this->addFailure(tr('must have ":count" characters or more', [':count' => $characters]));
            }
        });
    }


    /**
     * Sanitize the selected value by trimming whitespace
     *
     * @param string $characters
     *
     * @return static
     * @see trim()
     */
    public function sanitizeTrim(string $characters = " \t\n\r\0\x0B"): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($characters) {
            $this->hasMaxCharacters();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $value = trim((string) $value, $characters);
        });
    }


    /**
     * Sanitizes the selected value by converting human-readable bytes to a positive integer number
     *
     * 1KB    = 1000
     * 1MB    = 1000000
     * 1GB    = 1000000000
     * 1GiB   = 1073741824
     * 1.5GiB = 1610612736
     * etc...
     *
     * @return static
     * @see trim()
     */
    public function sanitizeBytes(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->hasMaxCharacters(32);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            try {
                $value = Numbers::fromBytes($value);

            } catch (Throwable) {
                $this->addFailure(tr('must have a valid byte size, like 1000, 1000kb, 1000MiB, etc'));
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->isString();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (!preg_match('/^[\p{L}\p{N}\p{P}\p{M}\p{S}\p{Z}\t\r\n]+$/u', $value)) {
                $this->addFailure(tr('must contain only printable characters'));
            }
        });
    }


    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is positive
     *
     * @param int|float $amount
     * @param bool      $equal If true, it is more than or equal to, instead of only more than
     *
     * @return static
     */
    public function isMoreThan(int|float $amount, bool $equal = false): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($amount, $equal) {
            $this->isNumeric();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     * @param bool      $equal If true, it is less than or equal to, instead of only less than
     *
     * @return static
     */
    public function isLessThan(int|float $amount, bool $equal = false): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($amount, $equal) {
            $this->isNumeric();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     * This method ensures that the specified array key is negative
     *
     * @param bool $allow_zero
     *
     * @return static
     */
    public function isNegative(bool $allow_zero = false): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($allow_zero) {
            $this->isNumeric();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->isFloat();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     * @param UnitEnum $enum
     *
     * @return static
     */
    public function isInEnum(UnitEnum $enum): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($enum) {
            // This value must be scalar, and not too long. What is too long? Longer than the longest allowed item
            $this->isScalar();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (!in_enum($value, $enum)) {
                $this->addFailure(tr('must be one of ":list"', [':list' => $enum]));
            }
        });
    }


    /**
     * This method ensures that the specified key is the same as the column value in the specified query
     *
     * @param string   $column
     * @param callable $callback
     * @param bool     $ignore_case
     * @param bool     $fail_on_null = true
     *
     * @return static
     */
    public function setColumnFromCallback(string $column, callable $callback, bool $ignore_case = false, bool $fail_on_null = true): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($column, $callback, $ignore_case, $fail_on_null) {
            // This value must be scalar, and not too long. What is too long? Longer than the longest allowed item
            $this->isScalar();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $result = $callback($value, $this->source, $this);

            if (!$result and $fail_on_null) {
                $this->addFailure(Strings::plural(count($value), tr('value ":values" does not exist', [':values' => implode(', ', $value)]), tr('values ":values" do not exist', [':values' => implode(', ', $value)])));
            }

            $this->source[$this->field_prefix . $column] = $result;
        });
    }


    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified key is the same as the column value in the specified query
     *
     * @param string              $column
     * @param PDOStatement|string $query
     * @param array|null          $execute
     * @param bool                $ignore_case
     * @param bool                $fail_on_null = true
     *
     * @return static
     */
    public function setColumnFromQuery(string $column, PDOStatement|string $query, ?array $execute = null, bool $ignore_case = false, bool $fail_on_null = true): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($column, $query, $execute, $ignore_case, $fail_on_null) {
            // This value must be scalar, and not too long. What is too long? Longer than the longest allowed item
            $this->isScalar();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     * Go over the specified SQL execute array and apply any variable
     *
     * @param array|null $execute
     *
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
                            ':value' => $value,
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


    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified key value contains the column value in the specified query
     *
     * @param PDOStatement|string $query
     * @param array|null          $execute
     *
     * @return static
     */
    public function containsQueryColumn(PDOStatement|string $query, ?array $execute = null): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($query, $execute) {
            // This value must be scalar, and not too long. What is too long? Longer than the longest allowed item
            $this->isScalar();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $execute = $this->applyExecuteVariables($execute);
            $column  = sql()->getColumn($query, $execute);

            $this->contains($column);
        });
    }


    /**
     * Ensures that the value has the specified string
     *
     * This method ensures that the specified array key contains the specified string
     *
     * @param string $string
     * @param bool   $regex
     *
     * @return static
     */
    public function contains(string $string, bool $regex = false): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($string, $regex) {
            // This value must be scalar
            $this->isScalar();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if ($regex) {
                try {
                    if (!preg_match($string, (string) $value)) {
                        $this->addFailure(tr('must match regex ":value"', [':value' => $string]));
                    }
                } catch (Throwable $e) {
                    if (str_contains($e->getMessage(), 'preg_match')) {
                        throw new ValidatorException(tr('Specified regex ":regex" is invalid', [
                            ':regex' => $string
                        ]), $e);
                    }

                    throw new ValidatorException(tr('Failed validation'), $e);
                }

            } else {
                if (!str_contains((string) $value, $string)) {
                    $this->addFailure(tr('must contain ":value"', [':value' => $string]));
                }
            }
        });
    }


    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the value is in the results from the specified query
     *
     * @param PDOStatement|string $query
     * @param array|null          $execute
     *
     * @return static
     */
    public function inQueryResultArray(PDOStatement|string $query, ?array $execute = null): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($query, $execute) {
            // This value must be scalar, and not too long. What is too long? Longer than the longest allowed item
            $this->isScalar();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     * This method ensures that the specified array key is a scalar value
     *
     * @param IteratorInterface|array $array
     *
     * @return static
     */
    public function isInArray(IteratorInterface|array $array): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($array) {
            // This value must be scalar, and not too long. What is too long? Longer than the longest allowed item
            $this->isScalar();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if ($array instanceof IteratorInterface) {
                $this->sanitizeTrim()->hasMaxCharacters($array->getLongestValueLength());

                if ($this->process_value_failed or $this->selected_is_default) {
                    // Validation already failed or defaulted, don't test anything more
                    return;
                }

                $failed = !$array->valueExists($value);

            } else {
                $this->sanitizeTrim()->hasMaxCharacters(Arrays::getLongestValueLength($array));

                if ($this->process_value_failed or $this->selected_is_default) {
                    // Validation already failed or defaulted, don't test anything more
                    return;
                }

                $failed = !in_array($value, $array);
            }

            if ($failed) {
                $this->addFailure(tr('must be one of ":list"', [':list' => $array]));
            }
        });
    }


    /**
     * Validates that the selected field is equal or larger than the specified number of characters
     *
     * @param int $characters
     *
     * @return static
     */
    public function hasCharacters(int $characters): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($characters) {
            $this->isString();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (strlen($value) != $characters) {
                $this->addFailure(tr('must have exactly ":count" characters', [':count' => $characters]));
            }
        });
    }


    /**
     * Validates that the selected field NOT matches the specified regex
     *
     * @param string $regex
     *
     * @return static
     */
    public function matchesNotRegex(string $regex): static
    {
        $this->test_count++;

        return $this->containsNot($regex, true);
    }


    /**
     * Ensures that the value has the specified string
     *
     * This method ensures that the specified array key contains the specified string
     *
     * @param string $string
     * @param bool   $regex
     *
     * @return static
     */
    public function containsNot(string $string, bool $regex = false): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($string, $regex) {
            // This value must be scalar
            $this->isScalar();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     * Validates that the selected field starts with the specified string
     *
     * @param string $string
     *
     * @return static
     */
    public function startsWith(string $string): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($string) {
            // This value must be scalar
            $this->isScalar();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     *
     * @return static
     */
    public function endsWith(string $string): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($string) {
            // This value must be scalar
            $this->isScalar();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->isString();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (!ctype_alpha($value)) {
                $this->addFailure(tr('must contain only letters'));
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->isString();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->isString();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->isString();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (!ctype_punct($value)) {
                $this->addFailure(tr('must contain only uppercase letters'));
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->isString();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->isString();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (!ctype_space($value)) {
                $this->addFailure(tr('must contain only whitespace characters'));
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->isString();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     * @param bool  $strict If true, will perform a strict check
     * @param bool  $secret If specified the $validate_value will not be shown
     * @param bool  $ignore_case
     *
     * @return static
     * @todo Change these individual flag parameters to one bit flag parameter
     */
    public function isValue(mixed $validate_value, bool $strict = false, bool $secret = false, bool $ignore_case = true): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($validate_value, $strict, $secret, $ignore_case) {
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

                if ($this->process_value_failed or $this->selected_is_default) {
                    // Validation already failed or defaulted, don't test anything more
                    return;
                }

                if ($ignore_case) {
                    $compare_value  = strtolower((string) $value);
                    $validate_value = strtolower((string) $validate_value);

                } else {
                    $compare_value = $value;
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
     *
     * @param array|string|null $formats
     *
     * @return static
     * @todo Add locale support instead , see https://www.php.net/manual/en/book.intl.php and
     *       https://stackoverflow.com/questions/8827514/get-date-format-according-to-the-locale-in-php (INTL section)
     */
    public function isDate(array|string|null $formats = null): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($formats) {
            // Sort-of arbitrary max size, just to ensure Date class won't receive a 2MB string
            $this->sanitizeTrim()->hasMinCharacters(4)->hasMaxCharacters(32);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            // Ensure we have formats to work with, default to a number of acceptable formats
            $formats = $formats ?? DateFormats::getSupportedPhp();
            $formats = Arrays::force($formats, null);

            // We must be able to create a date object using the given formats without failure, and the resulting date
            // must be the same as the specified date
            if (!static::dateMatchesFormats($value, $formats)) {
                $this->addFailure(tr('must be a valid date'));
            }
        });
    }


    /**
     * Returns the given date sanitized if the specified date matches any of the specified formats, NULL otherwise
     *
     * @param string $date
     * @param array  $formats
     *
     * @return string|null
     */
    protected static function dateMatchesFormats(string $date, array $formats): ?string
    {
        // We must be able to create a date object using the given formats without failure, and the resulting date
        // must be the same as the specified date
        $given = DateFormats::normalizeDate($date);

        foreach ($formats as $format) {
            try {
                // Create DateTime object
                $format = DateFormats::normalizeDateFormat($format);
                $value = DateTime::createFromFormat($format, $given);

                if ($value) {
                    // DateTime object created successfully! Now get a dateformat, and normalize it
                    $test = DateFormats::normalizeDate($value->format($format));

                    // Test the normalized test DateTime against the specified normalized date time string
                    if ($test === $given) {
                        return $given;
                    }
                }

                // Yeah, this is not a valid date, try again
            } catch (UnsupportedDateFormatException $e) {
                // The specified date format is invalid
                throw new ValidatorException($e->getMessage(), $e);

            } catch (Throwable) {
                // Yeah, this is not a valid date, try again
            }
        }

        // Nothing matched
        return null;
    }


    /**
     * Validates that the selected field is a date
     *
     * @note Regex taken from https://code.oursky.com/regex-date-currency-and-time-accurate-data-extraction/
     *
     * @param array|string|null $formats
     *
     * @return static
     */
    public function isTime(array|string|null $formats = null): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($formats) {
            $this->sanitizeTrim()->hasMinCharacters(5)->hasMaxCharacters(18); // 00:00:00.000000 AM

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            // Ensure we have formats to work with
            if (!$formats) {
                // Default to a number of acceptable formats
                $formats = Config::get('locale.formats.time', [
                    'h:i a',
                    'H:i',
                    'h:i:s a',
                    'H:i:s',
                ]);
            }

            $formats = Arrays::force($formats, null);

            // Validate the user time against all allowed formats
            foreach ($formats as $format) {
                if (is_object(DateTime::createFromFormat($format, $value))) {
                    // The specified time matches one of the allowed formats
                    return;
                }
            }

            $this->addFailure(tr('must be a valid time'));
        });
    }


    /**
     * Validates that the selected field is a date time field
     *
     * @note Regex taken from https://code.oursky.com/regex-date-currency-and-time-accurate-data-extraction/
     * @todo Add locale support, see https://www.php.net/manual/en/book.intl.php and
     *       https://stackoverflow.com/questions/8827514/get-date-format-according-to-the-locale-in-php (INTL section)
     *
     * @param array|string|null $formats
     *
     * @return static
     */
    public function isDateTime(array|string|null $formats = null): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($formats) {
            // Sort-of arbitrary max size, just to ensure Date class won't receive a 2MB string
            $this->sanitizeTrim()
                 ->hasMaxCharacters(32);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            // Ensure we have formats to work with
            if (!$formats) {
                // Default to a number of acceptable formats
                $formats = DateTimeFormats::getSupportedPhp();
            }

            $formats = Arrays::force($formats, null);

            // We must be able to create a date object using the given formats without failure, and the resulting date
            // must be the same as the specified date
            if (!static::dateMatchesFormats($value, $formats)) {
                $this->addFailure(tr('must be a valid date time'));
            }
        });
    }


    /**
     * Validates that the selected field is in the past
     *
     * @param DateTime|null $before
     *
     * @return static
     */
    public function isBefore(?DateTime $before): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($before) {
            // Sort-of arbitrary max size, just to ensure Date class won't receive a 2MB string
            $this->sanitizeTrim()
                 ->hasMaxCharacters(32);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $value = new DateTime($value);

            if ($value > $before) {
                $this->addFailure(tr('must be a valid date before ":date"', [
                    ':date' => $before->getHumanReadableDateTime(),
                ]));
            }
        });
    }


    /**
     * Validates that the selected field is in the past
     *
     * @param DateTime|null $after
     *
     * @return static
     */
    public function isAfter(?DateTime $after): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($after) {
            // Sort-of arbitrary max size, just to ensure Date class won't receive a 2MB string
            $this->sanitizeTrim()->hasMaxCharacters(32);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $value = new DateTime($value);

            if ($value > $after) {
                $this->addFailure(tr('must be a valid date after ":date"', [
                    ':date' => $after->getHumanReadableDateTime(),
                ]));
            }
        });
    }


    /**
     * Validates that the selected field is a credit card
     *
     * @todo Add car number CRC checking as well
     * @note Card regexes taken from https://code.oursky.com/regex-date-currency-and-time-accurate-data-extraction/
     * @note From the site: A huge disclaimer: Never depend your code on card regex. The reason behind is simple: Card
     *       issuers carry on adding new card number patterns or removing old ones. You are likely to end up with
     *       maintaining/debugging the regular expressions that way. It’s still fine to use them for visual effects,
     *       like for identifying the card type on the screen.
     * @return static
     */
    public function isCreditCard(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            // Sort-of arbitrary max size, just to ensure regex won't receive a 2MB string
            $this->sanitizeTrim()->hasMaxCharacters(32);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
                'Visa Master Card'   => '^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14})$',
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (!($value instanceof EnumDisplayMode)) {
                if (is_string($value)) {
                    // Maybe a string representation of a backed enum?
                    $test = EnumDisplayMode::tryFrom($value);

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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            // Sort-of arbitrary max size, just to ensure Date class won't receive a 2MB string
            $this->sanitizeTrim()->hasMaxCharacters(64);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $this->isQueryResult('SELECT `id` FROM `geo_timezones` WHERE `name` = :name', [':name' => $value]);
        });
    }


    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified key is the same as the column value in the specified query
     *
     * @param PDOStatement|string $query
     * @param array|null          $execute
     * @param bool                $ignore_case
     *
     * @return static
     */
    public function isQueryResult(PDOStatement|string $query, ?array $execute = null, bool $ignore_case = false): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($query, $execute, $ignore_case) {
            // This value must be scalar, and not too long. What is too long? Longer than the longest allowed item
            $this->isScalar();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $execute        = $this->applyExecuteVariables($execute);
            $validate_value = sql()->getColumn($query, $execute);

            if ($ignore_case) {
                $compare_value  = strtolower((string) $value);
                $validate_value = strtolower((string) $validate_value);

            } else {
                $compare_value = $value;
            }

            if ($compare_value != $validate_value) {
                $this->addFailure(tr(' has a non existing identifier value'));
            }
        });
    }


    /**
     * Validates that the selected field array has a minimal number of elements
     *
     * @param int $count
     *
     * @return static
     */
    public function hasElements(int $count): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($count) {
            $this->isArray();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (count($value) != $count) {
                $this->addFailure(tr('must have exactly ":count" elements', [':count' => $count]));
            }
        });
    }


    /**
     * Validates that the selected field array has a minimal number of elements
     *
     * @param int $count
     *
     * @return static
     */
    public function hasMinimumElements(int $count): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($count) {
            $this->isArray();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (count($value) < $count) {
                $this->addFailure(tr('must have ":count" elements or more', [':count' => $count]));
            }
        });
    }


    /**
     * Validates that the selected field array has a maximum number of elements
     *
     * @param int $count
     *
     * @return static
     */
    public function hasMaximumElements(int $count): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($count) {
            $this->isArray();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->sanitizeTrim()->hasMinCharacters(3)->hasMaxCharacters(128);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     * Validates if the selected field is a valid multiple phones field
     *
     * @param string $separator
     *
     * @return static
     */
    public function isPhoneNumbers(string $separator = ','): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($separator) {
            $this->sanitizeTrim()->hasMinCharacters(10)->hasMaxCharacters(64);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $separator = Strings::escapeForRegex($separator);

            $this->matchesRegex('/[0-9- ' . $separator . '].+?/');
        });
    }


    /**
     * Validates that the selected field matches the specified regex
     *
     * @param string $regex
     *
     * @return static
     */
    public function matchesRegex(string $regex): static
    {
        return $this->contains($regex, true);
    }


    /**
     * Validates if the selected field is a valid gender
     *
     * @return static
     */
    public function isGender(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->sanitizeTrim()->hasMinCharacters(2)->hasMaxCharacters(16);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $this->isPrintable();
        });
    }


    /**
     * Validates if the selected field is a valid name
     *
     * @param int $characters
     *
     * @return static
     */
    public function isName(int $characters = 128): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($characters) {
            $this->sanitizeTrim()->hasMinCharacters(1)->hasMaxCharacters($characters);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $this->isPrintable()->isNotNumeric();
        });
    }


    /**
     * Validates that the selected field is not a number
     *
     * @return static
     */
    public function isNotNumeric(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->isString();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (is_numeric($value)) {
                $this->addFailure(tr('cannot be a number'));
            }
        });
    }


    /**
     * Validates if the selected field is a valid name
     *
     * @param int $characters
     *
     * @return static
     */
    public function isUsername(int $characters = 64): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($characters) {
            $this->sanitizeTrim()->hasMinCharacters(2)->hasMaxCharacters($characters);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $this->isAlphaNumeric()->isNotNumeric();
        });
    }


    /**
     * Validates that the selected field contains only alphanumeric characters
     *
     * @return static
     */
    public function isAlphaNumeric(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->isString();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (!ctype_alnum($value)) {
                $this->addFailure(tr('must contain only letters and numbers'));
            }
        });
    }


    /**
     * Validates if the selected field is a valid word
     *
     * @return static
     */
    public function isWord(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->sanitizeTrim()->hasMinCharacters(2)->hasMaxCharacters(32);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->sanitizeTrim()->hasMinCharacters(2)->hasMaxCharacters(32);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $this->matchesRegex('/^[a-z][a-z0-9_.]*$/i');
        });
    }


    /**
     * Validates if the selected field is a valid variable name or label
     *
     * @param int $length
     *
     * @return static
     */
    public function isVariableName(int $length = 128): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($length) {
            $this->sanitizeTrim()->hasMinCharacters(2)->hasMaxCharacters($length);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $this->matchesRegex('/^[a-z0-9][a-z0-9-_.]*$/i');
        });
    }


    /**
     * Checks if the specified path exists in one the required directories or not, and if its of the correct type
     *
     * @param string                          $path
     * @param FsDirectoryInterface|array|null $exists_in_directories
     * @param bool                            $must_be_directory
     * @param bool|null                       $exist
     *
     * @return FsPathInterface
     */
    protected function validatePath(string $path, FsDirectoryInterface|array|null $exists_in_directories, ?bool $must_be_directory, ?bool $exist): FsPathInterface
    {
        // Determine type, if any
        if ($must_be_directory) {
            $type  = 'directory';
            $class = FsDirectory::class;

        } elseif (is_bool($must_be_directory)) {
            $type = 'file';
            $class = FsFile::class;

        } else {
            $type  = null;
            $class = FsPath::class;
        }

        // Is the path required to exist anywhere?
        if (!$exists_in_directories) {
            return new $class($path, $this->restrictions);
        }

        // Was a path specified? We need a path here!
        if (!$path) {
            // Some value must be specified
            $this->addFailure(tr('must contain a path'));

            return new $class($path, $this->restrictions);
        }

        $path = FsPath::realPath($path);

        foreach (Arrays::force($exists_in_directories) as $exists_in_directory) {
            if (!$exists_in_directory instanceof FsDirectoryInterface) {
                throw new OutOfBoundsException(tr('Cannot validate if path ":path", the specified "$exists_in_directory" value ":value" must be an FsDirectoryInterface object or an array with FsDirectoryInterface objects', [
                    ':path'  => $path,
                    ':value' => $exists_in_directory
                ]));
            }

            $exists_in_directory->makeAbsolute(must_exist: false)
                                ->checkRestrictions(false);

            // The path should be an FsPath object with restrictions from the specified directory we're testing
            $path = new $class($path, $exists_in_directory->getRestrictions());

            if ($path->isInDirectory($exists_in_directory)) {
                $does_exist = true;
                break;
            }
        }

        if (empty($does_exist)) {
            // The file, whatever it is, does NOT exist
            if ($exist) {
                // FsFileFileInterface does NOT exist, but should exist
                if ($type) {
                    $this->addFailure(tr('must be an existing ":type" in paths ":paths"', [
                        ':type'  => $type,
                        ':paths' => $exists_in_directories
                    ]));

                } else {
                    $this->addFailure(tr('must exist in paths ":paths"', [
                        ':paths' => $exists_in_directories
                    ]));
                }

            } else {
                // FsFileFileInterface should not exist, and does not exist, but ensure the parent path will exist!
                if (empty($parent_exists)) {
                    $path->getParentDirectory()->ensure();
                }
            }

        } else {
            // The file, whatever it is, does exist
            if ($exist === false) {
                // The file exists, but should NOT exist
                $this->addFailure(tr('must not exist'));
            }

            if ($must_be_directory) {
                // The file should be a directory
                if (!$path->isDirectory()) {
                    $this->addFailure(tr('must be a directory'));
                }

            } elseif (is_bool($must_be_directory)) {
                // The file should NOT be a directory
                if ($path->isDirectory()) {
                    $this->addFailure(tr('cannot be a directory'));
                }
            }
        }

        return $path;
    }


    /**
     * Validates if the selected field is a valid file path
     *
     * @param FsDirectoryInterface|array|null $exists_in_directories
     * @param bool                            $exists
     *
     * @return static
     */
    public function isPath(FsDirectoryInterface|array|null $exists_in_directories = null, bool $exists = true): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($exists_in_directories, $exists) {
            $this->sanitizeTrim()->hasMinCharacters(1)->hasMaxCharacters(2048);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            // Check the path
            $this->validatePath($value, $exists_in_directories, null, $exists);
        });
    }


    /**
     * Validates if the selected field is a valid directory
     *
     * @param FsDirectoryInterface|array|null $exists_in_directories
     * @param bool                            $exists
     *
     * @return static
     */
    public function isDirectory(FsDirectoryInterface|array|null $exists_in_directories = null, bool $exists = true): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($exists_in_directories, $exists) {
            $this->sanitizeTrim()->hasMinCharacters(1)->hasMaxCharacters(2048);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            // Check the directory
            $this->validatePath($value, $exists_in_directories, true, $exists);
        });
    }


    /**
     * Validates if the selected field is a valid file
     *
     * @param FsDirectoryInterface|array|null $exists_in_directories
     * @param bool|null                       $exists
     *
     * @return static
     */
    public function isFile(FsDirectoryInterface|array|null $exists_in_directories = null, ?bool $exists = true): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($exists_in_directories, $exists) {
            $this->sanitizeTrim()->hasMinCharacters(1)->hasMaxCharacters(2048);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            // Check the file
            $this->validatePath($value, $exists_in_directories, false, $exists);
        });
    }


    /**
     * Validates if the selected field is a valid file path and converts the value into FsPath object
     *
     * @param FsDirectoryInterface|array $exists_in_directories
     * @param bool                       $exists
     *
     * @return static
     */
    public function sanitizePath(FsDirectoryInterface|array $exists_in_directories, bool $exists = true): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($exists_in_directories, $exists) {
            $this->sanitizeTrim()->hasMinCharacters(1)->hasMaxCharacters(2048);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            // Check the path and convert into FsPath object
            $value = $this->validatePath($value, $exists_in_directories, null, $exists);
        });
    }


    /**
     * Validates if the selected field is a valid directory and converts the value into FsDirectory object
     *
     * @param FsDirectoryInterface|array $exists_in_directories
     * @param bool                       $exists
     *
     * @return static
     */
    public function sanitizeDirectory(FsDirectoryInterface|array $exists_in_directories, bool $exists = true): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($exists_in_directories, $exists) {
            $this->sanitizeTrim()->hasMinCharacters(1)->hasMaxCharacters(2048);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            // Check the directory and convert into FsDirectory object
            $value = $this->validatePath($value, $exists_in_directories, true, $exists);
        });
    }


    /**
     * Validates if the selected field is a valid file and converts the value into an FsFile object
     *
     * @param FsDirectoryInterface|array $exists_in_directories
     * @param bool|null                  $exists
     *
     * @return static
     */
    public function sanitizeFile(FsDirectoryInterface|array $exists_in_directories, ?bool $exists = true): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($exists_in_directories, $exists) {
            $this->sanitizeTrim()->hasMinCharacters(1)->hasMaxCharacters(2048);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            // Check the file and convert into FsFile object
            $value = $this->validatePath($value, $exists_in_directories, false, $exists);
        });
    }


    /**
     * Validates if the selected field is a valid description
     *
     * @param int $characters
     *
     * @return static
     */
    public function isDescription(int $characters = 16_777_200): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($characters) {
            $this->sanitizeTrim()->hasMaxCharacters($characters);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            if (static::passwordsDisabled()) {
                // Don't test passwords
                return;
            }

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            try {
                $value = Password::testSecurity((string) $value);

            } catch (ValidationFailedException $e) {
                $this->addFailure(tr('failed because ":e"', [':e' => $e->getMessage()]));
            }
        });
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
     * Validates if the selected field is a valid and strong enough password
     *
     * @return static
     */
    public function isStrongPassword(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->sanitizeTrim()->hasMinCharacters(10)->hasMaxCharacters(128);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            // TODO Implement
        });
    }


    /**
     * Validates if the selected field is a valid email address
     *
     * @param int $characters
     *
     * @return static
     */
    public function isColor(int $characters = 6): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($characters) {
            $this->sanitizeTrim()->hasMinCharacters(3)->hasMaxCharacters($characters);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            // Color (for the moment) is only accepted in hexadecimal format
            $this->isHexadecimal();
        });
    }


    /**
     * Validates that the selected field contains only hexadecimal characters
     *
     * @return static
     */
    public function isHexadecimal(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->isString();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (!ctype_xdigit($value)) {
                $this->addFailure(tr('must contain only hexadecimal characters'));
            }
        });
    }


    /**
     * Validates if the selected field is a valid email address
     *
     * @param int $characters
     *
     * @return static
     */
    public function isEmail(int $characters = 2048): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($characters) {
            $this->sanitizeTrim()->hasMinCharacters(3)->hasMaxCharacters($characters);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     *
     * @return static
     */
    public function isUrl(int $max_size = 2048): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($max_size) {
            $this->sanitizeTrim()->hasMinCharacters(3)->hasMaxCharacters($max_size);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->sanitizeTrim()->hasMinCharacters(3)->hasMaxCharacters(128);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->sanitizeTrim()->hasMinCharacters(3)->hasMaxCharacters(48);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (!filter_var($value, FILTER_VALIDATE_IP)) {
                $this->addFailure(tr('must contain a valid IP address'));
            }
        });
    }


    /**
     * Validates if the selected field is a valid domain name or IP address
     *
     * @return static
     */
    public function isDomainOrIp(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->sanitizeTrim()->hasMinCharacters(3)->hasMaxCharacters(128);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (!filter_var($value, FILTER_VALIDATE_DOMAIN)) {
                if (!filter_var($value, FILTER_VALIDATE_IP)) {
                    $this->addFailure(tr('must contain a valid domain or IP address'));
                }
            }
        });
    }


    /**
     * Validates if the selected field is a valid formatted UUID
     *
     * @return static
     */
    public function isUuid(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->sanitizeTrim()->hasMinCharacters(3)->hasMaxCharacters(48);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-5][0-9a-f]{3}-[089ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value)) {
                $this->addFailure(tr('must contain a valid UUID string'));
            }
        });
    }


    /**
     * Validates if the selected field is a valid JSON string
     *
     * @return static
     * @copyright The used JSON regex validation taken from a twitter post by @Fish_CTO
     * @see       static::isCsv()
     * @see       static::isBase58()
     * @see       static::isBase64()
     * @see       static::isSerialized()
     * @see       static::sanitizeDecodeJson()
     */
    public function isJson(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->sanitizeTrim()->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     *
     * @return static
     * @see static::isBase58()
     * @see static::isBase64()
     * @see static::isSerialized()
     * @see static::sanitizeDecodeCsv()
     */
    public function isCsv(string $separator = ',', string $enclosure = "\"", string $escape = "\\"): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($separator, $enclosure, $escape) {
            $this->sanitizeTrim()->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            try {
                str_getcsv($value, $separator, $enclosure, $escape);

            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid ":separator" separated string', [
                    ':separator' => $separator,
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->sanitizeTrim()->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->sanitizeTrim()->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->sanitizeTrim()->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     *
     * @return static
     */
    public function isVersion(int $characters = 11): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($characters) {
            $this->sanitizeTrim()->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     * @param string   $failure
     *
     * @return static
     */
    public function isTrue(callable $function, string $failure): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($function, $failure) {
            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     * @param string   $failure
     *
     * @return static
     */
    public function isFalse(callable $function, string $failure): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($function, $failure) {
            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     *
     * @param string|null $failure
     *
     * @return static
     */
    public function isUnique(?string $failure = null): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($failure) {
            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (sql()->exists($this->table, Strings::from($this->selected_field, $this->field_prefix), $value, $this->id)) {
                $this->addFailure($failure ?? tr('it already exists'));
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->sanitizeTrim()->hasMaxCharacters();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (is_string($value)) {
                $value = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8', true);
            }
        });
    }


    /**
     * Makes the current field a boolean value
     *
     * This method ensures that the specified array key is a boolean
     *
     * @return static
     */
    public function sanitizeToBoolean(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            if (!$this->checkIsOptional($value)) {
                $value = (bool) $value;
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->sanitizeTrim()->hasMaxCharacters();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            if (is_string($value)) {
                $value = htmlentities($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8', true);
            }
        });
    }


    /**
     * Sanitize the selected value by starting the value from the specified needle
     *
     * @param string $needle
     *
     * @return static
     * @see String::from()
     * @see Validator::sanitizeUntil()
     * @see Validator::sanitizeFromReverse()
     */
    public function sanitizeFrom(string $needle): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($needle) {
            $this->isString();
            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $value = Strings::from($value, $needle);
        });
    }


    /**
     * Sanitize the selected value by ending the value at the specified needle
     *
     * @param string $needle
     *
     * @return static
     * @see String::until()
     * @see Validator::sanitizeFrom()
     * @see Validator::sanitizeUntilReverse()
     */
    public function sanitizeUntil(string $needle): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($needle) {
            $this->isString();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $value = Strings::until($value, $needle);
        });
    }


    /**
     * Sanitize the selected value by starting the value from the specified needle, but starting search from the end of
     * the string
     *
     * @param string $needle
     *
     * @return static
     * @see String::fromReverse()
     * @see Validator::sanitizeFrom()
     * @see Validator::sanitizeUntilReverse()
     */
    public function sanitizeFromReverse(string $needle): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($needle) {
            $this->isString();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $value = Strings::fromReverse($value, $needle);
        });
    }


    /**
     * Sanitize the selected value by ending the value at the specified needle, but starting search from the end of the
     * string
     *
     * @param string $needle
     *
     * @return static
     * @see String::untilReverse()
     * @see Validator::sanitizeUntil()
     * @see Validator::sanitizeFromReverse()
     */
    public function sanitizeUntilReverse(string $needle): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($needle) {
            $this->isString();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->sanitizeTrim()->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            try {
                if (!$this->selected_is_default or ($value !== null)) {
                    $value = mb_strtoupper($value);
                }

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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->sanitizeTrim()
                 ->hasMinCharacters(3)
                 ->hasMaxCharacters();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            try {
                if (!$this->selected_is_default or ($value !== null)) {
                    $value = mb_strtolower($value);
                }

            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid string'));
            }
        });
    }


    /**
     * Sanitize the selected value with a search / replace
     *
     * @param array $replace A key => value map of all items that should be searched / replaced
     * @param bool  $regex   If true, all keys in the $replace array will be treated as a regex instead of a normal
     *                       string This is slower and more memory intensive, but more flexible as well.
     *
     * @return static
     * @see trim()
     */
    public function sanitizeSearchReplace(array $replace, bool $regex = false): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($replace, $regex) {
            $this->sanitizeTrim()->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     *
     * @return static
     * @see static::isJson()
     * @see static::sanitizeDecodeCsv()
     * @see static::sanitizeDecodeSerialized()
     * @see static::sanitizeForceString()
     */
    public function sanitizeDecodeJson(bool $array = true): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($array) {
            $this->sanitizeTrim()->hasMinCharacters(3)->hasMaxCharacters();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     * Sanitize the selected value by encoding the data to JSON
     *
     * @return static
     * @see static::isJson()
     * @see static::sanitizeDecodeJson()
     */
    public function sanitizeEncodeJson(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            try {
                $value = Json::encode($value);

            } catch (JsonException) {
                $this->addFailure(tr('could not be processed'));
            }
        });
    }


    /**
     * Sanitize the selected value by decoding the specified CSV
     *
     * @param string $separator The separation character, defaults to comma
     * @param string $enclosure
     * @param string $escape
     *
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
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($separator, $enclosure, $escape) {
            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            try {
                $value = str_getcsv($value, $separator, $enclosure, $escape);

            } catch (Throwable) {
                $this->addFailure(tr('must contain a valid ":separator" separated string', [
                    ':separator' => $separator,
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
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     * Sanitize the selected value by decoding the specified CSV
     *
     * @return static
     * @see static::sanitizeDecodeSerialized()
     */
    public function sanitizeEncodeSerialized(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            try {
                $value = serialize($value);

            } catch (Throwable $e) {
                $this->addFailure(tr('could not be processed'));
            }
        });
    }


    /**
     * Sanitize the selected value by converting it to an array
     *
     * @param string $characters
     *
     * @return static
     * @see trim()
     * @see static::sanitizeForceString()
     */
    public function sanitizeForceArray(string $characters = ','): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($characters) {
            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     * @see static::sanitizeDecodeBase64()
     * @see static::sanitizeDecodeCsv()
     * @see static::sanitizeDecodeJson()
     * @see static::sanitizeDecodeSerialized()
     * @see static::sanitizeDecodeUrl()
     * @see static::sanitizeForceString()
     */
    public function sanitizeDecodeBase58(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     * @see static::sanitizeDecodeBase58()
     * @see static::sanitizeDecodeCsv()
     * @see static::sanitizeDecodeJson()
     * @see static::sanitizeDecodeSerialized()
     * @see static::sanitizeDecodeUrl()
     * @see static::sanitizeForceString()
     */
    public function sanitizeDecodeBase64(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     * @see static::sanitizeDecodeBase58()
     * @see static::sanitizeDecodeBase64()
     * @see static::sanitizeDecodeCsv()
     * @see static::sanitizeDecodeJson()
     * @see static::sanitizeDecodeSerialized()
     * @see static::sanitizeForceString()
     */
    public function sanitizeDecodeUrl(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     *
     * @return static
     * @todo KNOWN BUG: THIS DOESNT WORK
     * @see  static::sanitizeDecodeBase58()
     * @see  static::sanitizeDecodeBase64()
     * @see  static::sanitizeDecodeCsv()
     * @see  static::sanitizeDecodeJson()
     * @see  static::sanitizeDecodeSerialized()
     * @see  static::sanitizeDecodeUrl()
     * @see  static::sanitizeForceArray()
     */
    public function sanitizeForceString(string $characters = ','): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($characters) {
            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
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
     * Sanitize the selected value by decoding the specified CSV
     *
     * @param string|null $pre
     * @param string|null $post
     *
     * @return static
     */
    public function sanitizePrePost(?string $pre, ?string $post): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($pre, $post) {
            if ($pre or $post) {
                if ($this->process_value_failed or $this->selected_is_default) {
                    if (!$this->selected_is_default) {
                        // Validation already failed or defaulted, don't test anything more
                        return $this;
                    }

                    // This field contains the default
                }

                if (!is_scalar($this->selected_value)) {
                    throw new ValidatorException(tr('Cannot sanitize pre / post string data for field ":field", the field contains a non scalar value', [
                        ':field' => $this->selected_field,
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
     *
     * @return static
     */
    public function sanitizeTransform(callable $callback): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($callback) {
            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return $this;
            }

            $value = $callback($value, $this->source, $this);

            return $this;
        });
    }


    /**
     * Sanitize the selected value by executing the specified callback over it
     *
     * @note The callback should accept values mixed $value and array $source
     *
     * @param callback $callback
     *
     * @return static
     * @see  trim()
     */
    public function sanitizeCallback(callable $callback): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($callback) {
            $this->sanitizeTrim()->hasMaxCharacters();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $value = $callback($value, $this->source);
        });
    }


    /**
     * Sanitize the selected value by executing the specified callback over it, but the results may NOT be NULL
     *
     * @note The callback should accept values mixed $value and array $source
     *
     * @param callback $callback
     *
     * @return static
     * @see  trim()
     */
    public function sanitizeCallbackNoNull(callable $callback): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) use ($callback) {
            $this->sanitizeTrim()
                 ->hasMaxCharacters();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $results = $callback($value, $this->source);

            if ($results === null) {
                $this->addFailure(tr('is not valid'));

            } else {
                $value = $results;
            }
        });
    }


    /**
     * Sanitize the phone number in the selected value
     *
     * @return static
     * @see trim()
     */
    public function sanitizePhoneNumber(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->isPhoneNumber();

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $value = Sanitize::new($value)->phoneNumber()->getSource();
        });
    }


    /**
     * Validates if the selected field is a valid phone number
     *
     * @return static
     */
    public function isPhoneNumber(): static
    {
        $this->test_count++;

        return $this->validateValues(function (&$value) {
            $this->sanitizeTrim()->hasMinCharacters(10)->hasMaxCharacters(30);

            if ($this->process_value_failed or $this->selected_is_default) {
                // Validation already failed or defaulted, don't test anything more
                return;
            }

            $this->matchesRegex('/^\+?[0-9-#\*\(\) ].+?$/');
        });
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
     *
     * @return $this
     */
    public function setColumnPrefix(?string $field_prefix): static
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
     *
     * @return $this
     */
    public function setTable(?string $table): static
    {
        $this->table = $table;

        return $this;
    }


    /**
     * Returns the amount of tests performed on the current column
     *
     * @return int
     */
    public function getTestCount(): int
    {
        return $this->test_count;
    }


    /**
     * Increases the test counter by the specified amount
     *
     * @param int $count
     *
     * @return static
     */
    public function increaseTestCount(int $count = 1): static
    {
        $this->test_count += $count;
        return $this;
    }


    /**
     * Selects the specified key within the array that we are validating
     *
     * @param string|int $field The array key (or HTML form field) that needs to be validated / sanitized
     *
     * @return static
     */
    public function standardSelect(string|int $field): static
    {
        if (!$field) {
            throw new OutOfBoundsException(tr('No field specified'));
        }

        if ($this->selected_field and !$this->test_count) {
            throw new ValidationFailedException(tr('Cannot select object ":object" field ":field", the previously selected field ":previous" has no validations performed yet', [
                ':object'   => $this->source_object_class,
                ':field'    => $field,
                ':previous' => $this->selected_field,
            ]));
        }

        // Unset various values first to ensure the byref link is broken
        unset($this->process_value);
        unset($this->process_values);
        unset($this->selected_value);

        $this->process_value_failed = false;
        $this->selected_is_default  = false;
        $this->selected_is_optional = false;

        // Add the field prefix to the field name
        $field = $this->field_prefix . $field;

        if (in_array($field, $this->selected_fields)) {
            throw new KeyAlreadySelectedException(tr('The specified key ":key" has already been selected before', [
                ':key' => $field,
            ]));
        }

        if ($this->source === null) {
            throw new OutOfBoundsException(tr('Cannot select field ":field", no source array specified', [
                ':field' => $field,
            ]));
        }

        // Does the field exist in the source? If not, initialize it with NULL to be able to process it
        if (!array_key_exists($field, $this->source)) {
            $this->source[$field] = null;
        }

        // Select the field.
        $this->test_count        = 0;
        $this->selected_field    = $field;
        $this->selected_fields[] = $field;
        $this->selected_value    = &$this->source[$field];
        $this->process_values    = [null => &$this->selected_value];
        $this->selected_optional = null;

        return $this;
    }


    /**
     * Constructor for all validator types
     *
     * @param ValidatorInterface|null $parent
     * @param array|null              $source
     *
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
     * Returns the number values that this validator holds in its source
     *
     * @return int
     */
    public function getCount(): int
    {
        return count($this->source);
    }


    /**
     * Returns true if the current Validator has no values in its source
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !$this->getCount();
    }
}
