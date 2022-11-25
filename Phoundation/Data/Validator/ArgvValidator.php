<?php

namespace Phoundation\Data\Validator;

use Phoundation\Cli\Exception\ArgumentsException;
use Phoundation\Cli\Script;
use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Data\Exception\KeyAlreadySelectedException;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Exception\ValidatorException;
use Phoundation\Exception\Exceptions;
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
     * @param int|string $fields The array key (or HTML form field) that needs to be validated / sanitized
     * @param string|bool $next
     * @return static
     */
    public function select(int|string $fields, string|bool $next = false): static
    {
        if ($this->source === null) {
            throw new OutOfBoundsException(tr('Cannot select fields ":fields", no source array specified', [
                ':fields' => $fields
            ]));
        }

        // Unset various values first to ensure the byref link is broken
        unset($this->process_value);
        unset($this->process_values);
        unset($this->selected_value);

        $this->process_value_failed = false;
        $clean_field = null;

        $field       = null;

        if (!$fields) {
            throw new OutOfBoundsException(tr('No field specified'));
        }

        // Determine the correct clean field name for the specified argument field
        foreach (Arrays::force($fields, ',') as $field) {
            $field = trim($field);

            if (str_starts_with($field, '--')) {
                // This is the long form argument
                $clean_field = Strings::startsNotWith($field, '-');
                $clean_field = str_replace('-', '_', $clean_field);
                break;
            }

            if (str_starts_with($field, '-')) {
                // This is the short form argument, won't  be a variable name unless there is no alternative
                $clean_field = Strings::startsNotWith($field, '-');
                $clean_field = str_replace('-', '_', $clean_field);
                continue;
            }

            // This is not a modifier field but a method or value argument instead. Do not modify the field name
            // Do change the field value to NULL, which will cause ArgvValidator::argument() to return the next
            // available argument
            $clean_field = $field;
            $field       = null;
        }

        if (!$clean_field) {
            throw new ValidatorException(tr('Failed to determine clean field name for ":field"', [
                ':field' => $field
            ]));
        }

        // Get the value from the arguments list
        $value = self::argument($fields, $next);

        if (!$field and str_starts_with((string) $value, '-')) {
            // TODO Improve argument handling here. We should be able to mix "--modifier modifiervalue value" with "value --modifier modifiervalue" but with this design we currently can'y
            // We're looking not for a modifier, but for a method or value. This is a modifier, so don't use it. Put the
            // value back on the arguments list
            self::$argv[] = $value;
            $value = null;
        }

        if (in_array($clean_field, $this->selected_fields)) {
            throw new KeyAlreadySelectedException(tr('The specified key ":key" has already been selected before', [
                ':key' => $fields
            ]));
        }

        // Add the cleaned field to the source array
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
     * Returns the $argv array
     *
     * @return array
     */
    public function getArgv(): array
    {
        global $argv;
        return $argv;
    }



    /**
     * Throws an exception if there are still arguments left in the Arguments validator object
     *
     * @return static
     */
    public function noArgumentsLeft(): static
    {
        if (empty(self::$argv)) {
            return $this;
        }

        throw Exceptions::CliInvalidArgumentsException(tr('Invalid arguments ":arguments" encountered', [
            ':arguments' => Strings::force(self::$argv, ', ')
        ]))->makeWarning();
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
     * Returns an array of command line methods
     *
     * @return array
     */
    public static function getMethods(): array
    {
        $methods = [];

        // Scan all arguments until named parameters start
        foreach (self::$argv as $argument) {
            if (str_starts_with($argument, '-')) {
                break;
            }

            $methods[] = $argument;
        }

        // Validate all methods
        foreach ($methods as $method) {
            if (strlen($method) > 32) {
                throw new ValidationFailedException(tr('Specified method ":method" is too long, it should be less than 32 characters', [
                    ':method' => $method
                ]));
            }
        }

        return $methods;
    }



    /**
     * Remove the specified method from the arguments list
     *
     * @param string $method
     * @return void
     */
    public static function removeMethod(string $method): void
    {
        $key = array_search($method, self::$argv);

        if ($key === false) {
            throw new ValidatorException(tr('Cannot remove method ":method", it does not exist', [
                ':method' => $method
            ]));
        }

        unset(self::$argv[$key]);
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
            // Get the next argument?
            return array_shift(self::$argv);
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
}