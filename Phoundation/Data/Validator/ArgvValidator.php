<?php

namespace Phoundation\Data\Validator;

use Phoundation\Cli\Exception\ArgumentsException;
use Phoundation\Cli\Script;
use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Data\Exception\KeyAlreadySelectedException;
use Phoundation\Exception\OutOfBoundsException;



/**
 * ArgvValidator class
 *
 * This class validates data from untrusted $argv
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
class ArgvValidator extends Validator
{
    /**
     * Internal $argv array until validation has been completed
     *
     * @var array $argv
     */
    public static array $argv;



    /**
     * Validator constructor.
     *
     * @note This class will purge the $_REQUEST array as this array contains a mix of $_GET and $_POST variables which
     *       should never be used
     *
     * @note Keys that do not exist in $data that are validated will automatically be created
     * @note Keys in $data that are not validated will automatically be removed
     *
     * @param Validator|null $parent If specified, this is actually a child validator to the specified parent
     */
    public function __construct(?Validator $parent = null) {
        global $argv;
        $this->construct($parent, $argv);
    }



    /**
     * Returns a new array validator
     *
     * @param Validator|null $parent
     * @return static
     */
    public static function new(?Validator $parent = null): static
    {
        return new static($parent);
    }



    /**
     * Move all $argv data to internal array to ensure developers cannot access them until validation has been completed
     *
     * @param array $argv
     * @return void
     */
    public static function hideData(array $argv): void
    {
        global $argv;

        // Copy $argv data and reset the global $argv
        self::$argv = $argv;
        $argv = [];
    }



    /**
     * Selects the specified key within the array that we are validating
     *
     * @param int|string $field The array key (or HTML form field) that needs to be validated / sanitized
     * @param string|bool $next
     * @return static
     */
    public function select(int|string $field, string|bool $next = false): static
    {
        // Unset various values first to ensure the byref link is broken
        unset($this->process_value);
        unset($this->process_values);
        unset($this->selected_value);

        $this->process_value_failed = false;

        if (!$field) {
            throw new OutOfBoundsException(tr('No field specified'));
        }

        $fields = Arrays::force($field, ',');
        $value  = self::argument($field, $next);

        foreach ($fields as $field) {
            $field = trim($field);

            if (str_starts_with($field, '--')) {
                // This is the long form argument
                break;
            }
        }

        // Get the value from the $argv array
        $clean_field = Strings::startsNotWith($field, '-');
        $clean_field = str_replace('-', '_', $clean_field);

        if (in_array($clean_field, $this->selected_fields)) {
            throw new KeyAlreadySelectedException(tr('The specified key ":key" has already been selected before', [
                ':key' => $field
            ]));
        }

        if ($this->source === null) {
            throw new OutOfBoundsException(tr('Cannot select field ":field", no source array specified', [
                ':field' => $field
            ]));
        }

        // Add the field to the array
        $this->source[$clean_field] = $value;

        // Select the field.
        $this->selected_field    = $clean_field;
        $this->selected_fields[] = $clean_field;
        $this->selected_value    = &$this->source[$clean_field];
        $this->process_values    = [null => &$this->selected_value];

        unset($this->selected_optional);
        return $this;
    }



    /**
     * Extracts the validated fields from the $argv and returns them as an array
     *
     * @return array
     */
    public function extract(): array
    {
        global $argv;

        $return = [];

        foreach ($this->selected_fields as $field) {
            $return[$field] = $this->source[$field];
        }

        $argv = [];
        return $return;
    }



    /**
     * Returns the amount of command line arguments still available.
     *
     * @return int
     */
    public static function count(): int
    {
        return count(self::$argv);
    }



    /**
     * Find the specified method, basically any argument without - or --
     *
     * The result will be removed from $argv, but will remain stored in a static
     * variable which will return the same result every subsequent function call
     *
     * @param int|null    $index   The method number that is requested. 0 (default) is the first method, 1 the second,
     *                             etc.
     * @param string|null $default The value to be returned if no method was found
     * @return string              The results of the executed SSH commands in an array, each entry containing one line
     *                             of the output
     *
     * @see cli_arguments()
     * @see Script::argument()
     */
    protected static function method(?int $index = null, ?string $default = null): string
    {
        global $argv;
        static $method = [];

        if (isset($method[$index])) {
            $reappeared = array_search($method[$index], $argv);

            if (is_numeric($reappeared)) {
                // The argument has been re-added to $argv. This is very likely happened by safe_exec() that included
                // the specified script into itself, and had to reset the arguments array
                unset($argv[$reappeared]);
            }

            return $method[$index];
        }

        foreach ($argv as $key => $value) {
            if (!str_starts_with($value, '-')) {
                unset($argv[$key]);
                $method[$index] = $value;
                return $value;
            }
        }

        return $default;
    }



    /**
     * Returns arguments from the command line
     *
     * This function will REMOVE and then return the argument when its found
     * If the argument is not found, $default will be returned
     *
     * @param array|string|int|null $keys   (NOTE: See $next for what will be returned) If set to a numeric value, the
     *                                      value from $argv[$key] will be selected. If set as a string value, the $argv
     *                                      key where the value is equal to $key will be selected. If set specified as
     *                                      an array, all entries in the specified array will be selected.
     * @param string|bool $next             When set to true, it REQUIRES that the specified key contains a next
     *                                      argument, and this will be returned. If set to "all", it will return all
     *                                      following arguments. If set to "optional", a next argument will be returned,
     *                                      if available.
     * @return mixed                        If $next is null, it will return a boolean value, true if the specified key
     *                                      exists, false if not. If $next is true or "optional", the next value will be
     *                                      returned as a string. However, if "optional" was used, and the next value
     *                                      was not specified, boolean FALSE will be returned instead. If $next is
     *                                      specified as all, all subsequent values will be returned in an array
     */
    protected static function argument(array|string|int|null $keys = null, string|bool $next = false): mixed
    {
        if (is_integer($keys)) {
            // Get arguments by index
            if ($next === 'all') {
                foreach (self::$argv as $argv_key => $argv_value) {
                    if ($argv_key < $keys) {
                        continue;
                    }

                    if ($argv_key == $keys) {
                        unset(self::$argv[$keys]);
                        continue;
                    }

                    if (str_starts_with($argv_value, '-')) {
                        // Encountered a new option, stop!
                        break;
                    }

                    // Add this argument to the list
                    $value[] = $argv_value;
                    unset(self::$argv[$argv_key]);
                }

                return isset_get($value);
            }

            if (isset(self::$argv[$keys++])) {
                $argument = self::$argv[$keys - 1];
                unset(self::$argv[$keys - 1]);
                return $argument;
            }

            // No arguments found (except perhaps for test or force)
            return null;
        }

        if ($keys === null) {
            // Get the next argument
            $value = array_shift(self::$argv);
            return Strings::startsNotWith((string) $value, '-');
        }

        //Detect multiple key options for the same command, but ensure only one is specified
        if (is_array($keys) or ((is_string($keys)) and str_contains($keys, ','))) {
            $keys = Arrays::force($keys);
            $results = [];

            foreach ($keys as $key) {
                if ($next === 'all') {
                    // We're requesting all values for all specified keys. It will return null in case the specified key
                    // does not exist
                    $value = static::argument($key, 'all', null);

                    if (is_array($value)) {
                        $found = true;
                        $results = array_merge($results, $value);
                    }
                } else {
                    $value = static::argument($key, $next);

                    if ($value) {
                        $results[$key] = $value;
                        break;
                    }
                }
            }

            if (($next === 'all') and isset($found)) {
                return $results;
            }

            return match (count($results)) {
                0       => null,
                1       => current($results),
                default => throw new ArgumentsException('Multiple related command line arguments ":results" for the same option specified. Please specify only one', [

                    ':results' => $results
                ])
            };
        }

        if (($key = array_search($keys, self::$argv)) === false) {
            return null;
        }

        if ($next) {
            if ($next === 'all') {
                // Return all following arguments, if available, until the next option
                $value = [];

                foreach (self::$argv as $argv_key => $argv_value) {
                    if (empty($start)) {
                        if ($argv_value == $keys) {
                            $start = true;
                            unset(self::$argv[$argv_key]);
                        }

                        continue;
                    }

                    if (str_starts_with($argv_value, '-')) {
                        // Encountered a new option, stop!
                        break;
                    }

                    //Add this argument to the list
                    $value[] = $argv_value;
                    unset(self::$argv[$argv_key]);
                }

                return $value;
            }

            // Return next argument, if available
            $value = null;

            try {
                $value = Arrays::nextValue(self::$argv, $keys, true);
            } catch (OutOfBoundsException $e) {
                if ($e->getCode() == 'invalid') {
                    if ($next !== 'optional') {
                        // This argument requires another parameter
                        throw $e->setCode('missing-arguments');
                    }

                    $value = false;
                }
            }

            if (str_starts_with($value, '-')) {
                throw new ArgumentsException(tr('Argument ":keys" has no assigned value. It is immediately followed by argument ":value"', [
                    ':keys' => $keys,
                    ':value' => $value
                ]), ['keys' => $keys]);
            }

            return $value;
        }

        unset(self::$argv[$key]);
        return true;
    }



//    /**
//     * Returns true if the specified key exists
//     *
//     * @param int|string|null $keys
//     * @param bool|null $default
//     * @return bool
//     */
//    protected static function boolArgument(int|string|null $keys = null, ?bool $default = null): bool
//    {
//        return (bool) self::argument($keys, false, $default);
//    }
//
//
//
//    /**
//     * Returns the value for the specified key and ensures it is an integer number
//     *
//     * @param int|string|null $keys
//     * @param int|null $default
//     * @return int
//     */
//    protected static function integerArgument(int|string|null $keys = null, ?int $default = null): int
//    {
//        $value = self::argument($keys, true, $default);
//
//        if (!is_numeric($value) and ((integer) $value != $value)) {
//            throw new ArgumentsException(tr('Value for key ":keys" should be an integer number', [
//                ':keys' => $keys
//            ]));
//        }
//
//        return $value;
//    }
//
//
//
//    /**
//     * Returns the value for the specified key and ensures it is a natural number
//     *
//     * @param int|string|null $keys
//     * @param int|null $default
//     * @return int
//     */
//    protected static function naturalArgument(int|string|null $keys = null, ?int $default = null): int
//    {
//        $value = self::argument($keys, true, $default);
//
//        if (!is_natural($value)) {
//            throw new ArgumentsException(tr('Value for key ":keys" should be a natural number', [
//                ':keys' => $keys
//            ]));
//        }
//
//        return $value;
//    }
//
//
//
//    /**
//     * Returns the value for the specified key and ensures it is a float number
//     *
//     * @param int|string|null $keys
//     * @param float|null $default
//     * @return float
//     */
//    protected static function floatArgument(int|string|null $keys = null, ?float $default = null): float
//    {
//        $value = self::argument($keys, true, $default);
//
//        // TODO Test this following line, float casting may have slightly different results
//        if (!is_numeric($value) and ((float) $value != $value)) {
//            throw new ArgumentsException(tr('Value for key ":keys" should be a float number', [
//                ':keys' => $keys
//            ]));
//        }
//
//        return $value;
//    }
}