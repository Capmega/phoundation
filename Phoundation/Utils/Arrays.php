<?php

declare(strict_types=1);

namespace Phoundation\Utils;

use Phoundation\Core\Enums\EnumMatchMode;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Interfaces\EnumMatchModeInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Throwable;
use UnitEnum;


/**
 * Class Arrays
 *
 * This is the standard Phoundation array functionality extension class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package Phoundation\Core
 */
class Arrays {
    const MATCH_ALL      = 1;
    const MATCH_ANY      = 2;
    const MATCH_BEGIN    = 4;
    const MATCH_END      = 8;
    const MATCH_ANYWHERE = 16;
    const MATCH_NO_CASE  = 32;


    /**
     * If set, will filter NULL values
     */
    const FILTER_NULL = 1;

    /**
     * If set, will filter all empty values
     */
    const FILTER_EMPTY = 2;

    /**
     * If set, will quote all values
     */
    const QUOTE_ALWAYS = 4;

    /**
     * If set, will only display key, not value
     */
    const HIDE_EMPTY_VALUES = 8;


    /**
     * If all the specified keys are not in the source array, an exception will be thrown
     *
     * @param array $source
     * @param array|string $keys
     * @param string $exception_class
     * @return void
     */
    public static function requiredKeys(array $source, array|string $keys, string $exception_class = OutOfBoundsException::class): void
    {
        if (!static::hasAllKeys($source, $keys)) {
            if ($exception_class) {
                throw new $exception_class(tr('The specified array does not contain all required keys ":keys"', [
                    ':keys' => $keys
                ]));
            }

            static::ensure($source, $keys);
        }
    }


    /**
     * Returns the next key right after specified $key
     *
     * @param array $source
     * @param string|int $current_key
     * @param bool $delete
     * @return string|int
     * @throws OutOfBoundsException Thrown if the specified $current_key does not exist
     * @throws OutOfBoundsException Thrown if the specified $current_key does exist, but only at the end of the
     *                              specified array, so there is no next key
     */
    public static function nextKey(array &$source, string|int $current_key, bool $delete = false): string|int
    {
        // Scan for the specified $current_key
        $next = false;

        foreach ($source as $key => $value) {
            if ($next) {
                // This is the next key!
                if ($delete) {
                    // Delete this next key from the array
                    unset($source[$key]);
                }

                return $key;
            }

            if ($key === $current_key) {
                // We found the search key
                if ($delete) {
                    // Delete the specified key from the array
                    unset($source[$key]);
                }

                $next = true;
            }
        }

        if (!empty($next)) {
            // The current_key was found, but it was at the end of the array
            throw new OutOfBoundsException(tr('The specified $current_key ":key" was found but it was the last item in the array so there is no next', [':key' => $current_key]));
        }

        throw new OutOfBoundsException(tr('The specified $current_key ":key" was not found in the specified array', [':key' => $current_key]));
    }


    /**
     * Returns the value for the next value after the specified value
     *
     * If the specified key is not found, $current_value will be returned.
     *
     * @param array $source The source array in which will be searched
     * @param mixed $current_value The value for which will be searched
     * @param bool $delete If true, will delete the specified $current_value and found next value
     * @return mixed
     * @throws OutOfBoundsException thrown if the $current_value was found at the end of the array
     */
    public static function nextValue(array &$source, mixed $current_value, bool $delete = false): mixed
    {
        foreach ($source as $key => $value) {
            if (isset($next)) {
                if ($delete) {
                    unset($source[$key]);
                }

                return $value;
            }

            if ($value === $current_value) {
                if ($delete) {
                    unset($source[$key]);
                }

                $next = true;
            }
        }

        if (isset($next)) {
            // The current value was found, but it was at the end of the array
            throw new OutOfBoundsException(tr('Option ":value" does not have a value specified', [
                ':value' => $current_value
            ]));
        }

        return null;
    }


    /**
     * Ensure that the specified $params source is an array. If it's a numeric value, convert it to
     * [$numeric_key => $params]. If its a string value, convert it to [$string_key => $params]
     *
     * @param mixed $params A parameters array
     * @param string|null $string_key
     * @param string|null $numeric_key
     * @param bool|null $default The default value for the non-selected key
     * @return void
     *
     * @see Arrays::ensure()
     * @note The default value for this function for non-assigned values is boolean false, not null. The reason for this
     *       is that many of its dependancies use "false" as "do not use" because "null" would be interpreted as
     *       "compare to null"
     * @version 2.5.119: Added function and documentation
     */
    public static function params(mixed &$params, string $string_key = null, ?string $numeric_key = null, ?bool $default = false): void
    {
        if(!$params) {
            // The specified value is empty (probably null, "", etc). Convert it into an array containing the numeric and string keys with null values
            $params = [];
        }

        if(is_array($params)) {
            Arrays::ensure($params, array($string_key, $numeric_key), $default);
            return;
        }

        if(is_numeric($params)) {
            // The specified value is numeric, convert it to an array with the specified numeric key set having the value $params
            $params = [
                $numeric_key => $params,
                $string_key  => $default
            ];

            return;
        }

        if(is_string($params)) {
            // The specified value is string, convert it to an array with the specified string key set having the value $params
            $params = [
                $numeric_key => $default,
                $string_key  => $params
            ];

            return;
        }

        throw new OutOfBoundsException(tr('Specified $params ":params" is invalid. It is an ":datatype" but should be either one of array, integer, or string', [
            ':datatype' => gettype($params),
            ':params' => (is_resource($params) ? '{php resource}' : $params)
        ]));
    }


    /**
     * Ensures that the specified $key exists in the specified $source.
     *
     * If the specified $key does not exist, it will be initialized with the specified $default value. This function is
     * mostly used with ensuring default values for params arrays. With using this function, you can be sure individual
     * values are each initialized with specific values, if they do not exist yet
     *
     * @param array $source The array that is being worked on
     * @param string|int $key The key that must exist in the $source array
     * @param mixed $default The default value in case $source[$key] does not exist
     * @return mixed The new value of $source[$key]. This will be either the original value of $source[$key], or the $default value if $source[$key] did not exist
     * @see Arrays::ensure()
     * @note $source is passed by reference and will be modified directly!
     * @version 1.22.0: Added documentation
     * @example
     * $b = [];
     * Arrays::default($b, 'foo', 'bar');
     * showdie($b)
     * /code
     *
     * This would display the following results
     * code
     * array('foo' => 'bar')
     */
    public static function default(array &$source, string|int $key, mixed $default): mixed
    {
        if (!isset($source[$key])) {
            $source[$key] = $default;
        }

        return $source[$key];
    }


    /**
     * Ensure that the specified keys are available. If not, exception
     *
     * @param array $source
     * @param array|string $keys
     * @return void
     */
    public static function keyCheck(array $source, array|string $keys): void
    {
        foreach (Arrays::force($keys) as $key) {
            if (!array_key_exists($key, $source)) {
                throw new OutOfBoundsException(tr('Key ":key" does not exist in array', [':key' => $key]));
            }
        }
    }


    /**
     * Return an array from the given object, recursively
     *
     * @param object $object
     * @param bool $recurse
     * @return array
     */
    public static function fromObject(object $object, bool $recurse = true): array
    {
        $return = [];

        foreach ($object as $key => $value) {
            if (is_object($value) and $recurse) {
                $value = Arrays::fromObject($value, true);
            }

            $return[$key] = $value;
        }

        return $return;
    }


    /**
     * Return an array from the given object, recursively
     *
     * @param array $source
     * @param string $separator
     * @return string
     */
    public static function implodeRecursively(array $source, string $separator = ','): string
    {
        foreach ($source as &$value) {
            if (is_array($value)) {
                $value = Arrays::implodeRecursively($value, $separator);
            }
        }

        return implode($separator, $source);
    }


    /**
     * Return an object from the given array, recursively
     *
     * @param array $array
     * @return object The array that was created from the specified array
     */
    public static function toObject(array $array): object
    {
        return (object) $array;
    }


    /**
     * Return a random value from the specified array
     *
     * @param array $array
     * @return mixed
     */
    public static function getRandomValue(array $array): mixed
    {
        return $array[array_rand($array)];
    }


    /**
     * Implode the array with keys preserved
     *
     * @param IteratorInterface|array $source
     * @param string $row_separator
     * @param string $key_separator
     * @param string|null $quote_character Quote string values with the specified quote
     * @param int|null $options One of Arrays::FILTER_NULL, Arrays::FILTER_EMPTY, Arrays::QUOTE_REQUIRED,Arrays::QUOTE_ALWAYS
     * @return string
     */
    public static function implodeWithKeys(IteratorInterface|array $source, string $row_separator, string $key_separator, ?string $quote_character = null, ?int $options = self::FILTER_NULL | self::QUOTE_ALWAYS): string
    {
        $return = [];

        // Decode options
        $filter_null       = (bool) ($options & self::FILTER_NULL);
        $filter_empty      = (bool) ($options & self::FILTER_EMPTY);
        $quote_always      = (bool) ($options & self::QUOTE_ALWAYS);
        $hide_empty_values = (bool) ($options & self::HIDE_EMPTY_VALUES);

        foreach ($source as $key => $value) {
            if (is_array($value)) {
                $return[] .= $key . $key_separator . $row_separator . static::implodeWithKeys($value, $row_separator, $key_separator, $quote_character, $options);

            } else {
                if (!$value) {
                    if ($filter_empty) {
                        // Don't add this value at all
                        continue;
                    }

                    if ($value === null) {
                        if ($filter_null) {
                            // Don't add this value at all
                            continue;
                        }
                    }

                    if ($hide_empty_values) {
                        // Display only the key, not the value
                        $return[] .= $key;
                        continue;
                    }
                }

                if ($quote_character) {
                    $return[] .= $key . $key_separator . Strings::quote((string) $value, $quote_character, $quote_always);

                } else {
                    $return[] .= $key . $key_separator . $value;
                }
            }
        }

        return implode($row_separator, $return);
    }


    /**
     * Merge all specified arrays
     *
     * @param array $array1
     * @param array $array2
     * @param...
     * @return array
     */
    public static function mergeFull(): array
    {
        $arguments = static::getArgumentArrays(func_get_args());
        $return    = [];
        $count     = 0;

        foreach ($arguments as $id => $array) {
            static::requireArrayOrNull($array, $id);

            foreach ($array as $key => $value) {
                if (is_array($value) and array_key_exists($key, $return) and is_array($return[$key])) {
                    $return[$key] = Arrays::mergeFull($return[$key], $value);

                } else {
                    $return[$key] = $value;
                }
            }
        }

        return $return;
    }


    /**
     * Limit the specified array to the specified amount of entries
     *
     * @todo This is cringy slow at large arrays (also at smaller ones, but eh...), find a more efficient way to do this
     * @param array $source
     * @param int $count
     * @param bool $return_source
     * @return array
     */
    public static function limit(array $source, int $count, bool $return_source = true): array
    {
        if (!is_numeric($count) or ($count < 0)) {
            throw new OutOfBoundsException(tr('Specified count is not valid'));
        }

        $return = [];

        while (count($source) > $count) {
            $return[] = array_pop($source);
        }

        if ($return_source) {
            return $source;
        }

        return $return;
    }


    /**
     * Filter the specified values out of the source array
     *
     * @param array $source
     * @param array|string $values
     * @param EnumMatchModeInterface $match_mode
     * @return array
     */
    public static function filterValues(array $source, array|string $values, EnumMatchModeInterface $match_mode = EnumMatchMode::full): array
    {
        switch ($match_mode) {
            case EnumMatchMode::full:
                // Perform full match
                foreach (Arrays::force($values, null) as $filter_value) {
                    if (($key = array_search($filter_value, $source)) !== false) {
                        unset($source[$key]);
                    }
                }

                return $source;

            case EnumMatchMode::partial:
                // Perform partial match
                foreach ($source as $key => $value) {
                    foreach (Arrays::force($values, null) as $filter_value) {
                        if (str_contains($filter_value, $value)) {
                            unset($source[$key]);
                        }
                    }
                }

                return $source;

            case EnumMatchMode::regex:
                // Perform regex match
                foreach ($source as $key => $value) {
                    foreach (Arrays::force($values, null) as $filter_value) {
                        if (preg_match($filter_value, $value)) {
                            unset($source[$key]);
                        }
                    }
                }

                return $source;
        }
    }


    /**
     * Return an array with the amount of values where each value name is $base_value_name# and # is a sequential number
     *
     * @param int $count
     * @param string|int $base_value_name
     * @return array
     */
    public static function sequentialValues(int $count, string|int $base_value_name): array
    {
        if ($count < 1) {
            throw new OutOfBoundsException(tr('Invalid count specified. Make sure count is numeric, and greater than 0'));
        }

        $return = [];

        for($i = 0; $i < $count; $i++) {
            $return[] = $base_value_name.$i;
        }

        return $return;
    }


    /**
     * Return the source array with the keys all replaced by sequential values based on base_keyname
     *
     * @param array $source
     * @param string|int $base_key_name
     * @param bool $filter_null
     * @param bool $null_string
     * @param int $start
     * @return array
     */
    public static function sequentialKeys(array $source, string|int $base_key_name, bool $filter_null = false, bool $null_string = false, int $start = 0): array
    {
        $i      = $start;
        $return = [];

        foreach ($source as $value) {
            // Regard all "null" and "NULL" strings as NULL
            if ($null_string) {
                if (($value === 'null') or ($value === 'NULL')) {
                    $value = null;
                }
            }

            // Filter out all NULL values
            if ($filter_null) {
                if ($value === null) {
                    continue;
                }
            }

            $return[$base_key_name . $i++] = $value;
        }

        return $return;
    }


    /**
     * Return the source array with the specified keys kept, all else removed.
     *
     * @param array $source
     * @param string|array $keys
     * @return array
     */
    public static function keep(array $source, string|array $keys): array
    {
        $return = [];

        foreach (Arrays::force($keys) as $key) {
            if (array_key_exists($key, $source)) {
                $return[$key] = $source[$key];
            }
        }

        return $return;
    }


    /**
     * Return the source array with the specified keys kept, all else removed.
     *
     * @param array $source
     * @param string|array $keys
     * @return array
     */
    public static function listKeep(array $source, string|array $keys): array
    {
        $keys   = Arrays::force($keys);

        foreach ($source as &$entry) {
            $entry = Arrays::keep($entry, $keys);
        }

        unset($entry);

        return $source;
    }


    /**
     * Return the source array with the specified keys removed.
     *
     * @param array $source
     * @param array|string $keys
     * @return array
     */
    public static function remove(array $source, array|string $keys): array
    {
        foreach (Arrays::force($keys) as $key) {
            unset($source[$key]);
        }

        return $source;
    }


    /**
     * Removes all entries from the byref source array in the specified $keys and returns those in the result array
     *
     * @param array $source
     * @param string|array $keys
     * @return array
     */
    public static function extract(array &$source, string|array $keys): array
    {
        $return = [];

        foreach (Arrays::force($keys) as $key) {
            if (array_key_exists($key, $source)) {
                $return[$key] = $source[$key];
                unset($source[$key]);
            }
        }

        return $return;
    }


    /**
     * Return all array parts from (but without) the specified key
     *
     * @param array $source
     * @param string|int $from_key
     * @param bool $delete
     * @param bool $skip
     * @return array
     */
    public static function from(array &$source, string|int $from_key, bool $delete = false, bool $skip = true): array
    {
        $return = [];
        $add    = false;

        foreach ($source as $key => $value) {
            if (!$add) {
                if ($key == $from_key) {
                    if ($delete) {
                        unset($source[$key]);
                    }

                    $add = true;

                    if ($skip) {
                        // Do not include the key itself, skip it
                        continue;
                    }

                } else {
                    continue;
                }
            }

            $return[$key] = $value;

            if ($delete) {
                unset($source[$key]);
            }
        }

        return $return;
    }


    /**
     * Return all array parts until (but without) the specified key
     *
     * @param array $source
     * @param string|int $until_key
     * @param bool $delete
     * @return array
     */
    public static function until(array $source, string|int $until_key, bool $delete = false): array
    {
        $return = [];

        foreach ($source as $key => $value) {
            if ($key == $until_key) {
                break;
            }

            $return[$key] = $value;

            if ($delete) {
                unset($source[$key]);
            }
        }

        return $return;
    }


    /**
     * Prefix all keys in this array with the specified prefix
     *
     * @param array $source
     * @param string|int $prefix
     * @param bool $auto
     * @return array
     */
    public static function prefix(array $source, string|int $prefix, bool $auto = false): array
    {
        $count  = 0;
        $return = [];

        foreach ($source as $key => $value) {
            if ($auto) {
                $return[$prefix.$count++] = $value;

            } else {
                $return[$prefix.$key] = $value;
            }
        }

        return $return;
    }


    /**
     * Return the array keys that has a STRING value that contains the specified keyword
     *
     * NOTE: Non string values will be quietly ignored!
     *
     * @param array $array
     * @param string|int $keyword
     * @return array
     */
    public static function find(array $array, string|int $keyword): array
    {
        $return = [];

        foreach ($array as $key => $value) {
            if (is_string($value)) {
                if (str_contains($value, $keyword)) {
                    $return[$key] = $value;
                }
            }
        }

        return $return;
    }


    /**
     * Copy all elements from source to target, and clean them up. Any columns specified in "skip" will be skipped
     *
     * @param array $target
     * @param array $source
     * @param array $skip
     * @return array
     */
    public static function copyClean(array $target, array $source, array $skip = ['id']): array
    {
        foreach ($source as $key => $value) {
            if (in_array($key, $skip)) continue;

            if (is_string($value)) {
                $target[$key] = trim($value);

            } elseif ($value !== null) {
                $target[$key] = $value;
            }
        }

        return $target;
    }


    /**
     * Return an array with all the values in the specified column
     *
     * @param array $source
     * @param string|int $column
     * @return array
     */
    public static function getColumn(array $source, string|int $column): array
    {
        $return = [];

        foreach ($source as $id => $value) {
            if (array_key_exists($column, $value)) {
                $return[] = $value[$column];
            }
        }

        return $return;
    }


    /**
     * ???? Return the value of one of the first found key of the specified keys
     *
     * Not sure what this is supposed to be doing
     *
     * @param array $source
     * @param array $keys
     * @return array
     */
    public static function extractFirst(array $source, array $keys): array
    {
        throw new UnderConstructionException();
        foreach ($keys as $key) {
            if (!empty($source[$key])) {
                return $source[$key];
            }
        }
    }


    /**
     * Check the specified array and ensure it has not too many elements (to avoid attack with processing foreach over 2000000 elements, for example)
     *
     * @param array $source
     * @param int $max
     * @return array
     */
    public static function max(array $source, int $max = 20): array
    {
        if ($max < 0) {
            throw new OutOfBoundsException(tr('Specified $max value is negative. Please ensure it is a positive integer, 0 or higher'));
        }

        if (count($source) > $max) {
            throw new OutOfBoundsException(tr('Specified array has too many elements'));
        }

        return $source;
    }


    /**
     * Returns the values of the source array as array[value] = value
     *
     * @param array $source
     * @return array
     */
    public static function valueToKeys(array $source): array
    {
        $return = [];

        foreach ($source as $value) {
            if (!is_scalar($value)) {
                throw new OutOfBoundsException(tr('Specified source array contains non scalar values, cannot use non scalar values for the keys'));
            }

            $return[$value] = $value;
        }

        return $return;
    }


    /**
     * Filter
     */
    public static function filteredMerge()
    {
        $arguments = static::getArgumentArrays(func_get_args(), 3);
        $filters   = array_shift($arguments);
        $source    = array_shift($arguments);
        $source    = Arrays::remove($source, $filters);

        array_unshift($arguments, $source);

        return call_user_func_array('array_merge', $arguments);
    }


    /**
     * Return all elements from source1. If the value of one element is null, then try to return it from source2
     *
     * @note If a key was found in $source1 that was null, and that key does not exist, the $default value will be
     *       assigned instead
     * @param array $source1
     * @param array $source2
     * @param mixed $default
     * @return bool True if $source1 had keys with NULL values and was modified with values from $source2, false
     *              otherwise
     */
    public static function notNull(array &$source1, array $source2, mixed $default = null): bool
    {
        $modified = false;

        foreach ($source1 as $key => $value) {
            if ($value === null) {
                $source1[$key] = isset_get($source2[$key], $default);
                $modified      = true;
            }
        }

        return $modified;
    }


    /**
     * Return the average value of all values in the specified source array
     *
     * @param array $source
     * @param bool $ignore_non_numbers
     * @return float
     */
    public static function average(array $source, bool $ignore_non_numbers = false): float
    {
        $total = 0;

        foreach ($source as $key => $value) {
            if (!is_numeric($value)) {
                if (!$ignore_non_numbers) {
                    throw new OutOfBoundsException('The specified source array contains non numeric values');
                }
            }

            $total += $value;
        }

        return $total / count($source);
    }


    /**
     * Return an array with values ranging from $min to $max
     *
     * @param int $min
     * @param int $max
     * @return array
     */
    public static function range(int $min, int $max): array
    {
        if (!is_numeric($min)) {
                throw new OutOfBoundsException(tr('Specified $min is not numeric'));
        }

        if (!is_numeric($max)) {
            throw new OutOfBoundsException(tr('Specified $max is not numeric'));
        }

        if ($min > $max) {
            throw new OutOfBoundsException(tr('Specified $min is equal or larger than $max. Please ensure that $min is smaller'));
        }

        $return = [];

        for($i = $min; $i <= $max; $i++) {
            $return[$i] = $i;
        }

        return $return;
    }


    /**
     * Returns if the specified callback function returns true for all elements
     *
     * Example:
     * Arrays::all(array(1, 2, 3), function($value) { return $value });
     *
     * @param array $source The array to check
     * @param callable $function The function to execute
     * @return boolean Returns true if the specified callback function returned true for all elements in the array, false otherwise
     */
    public static function allExecuteTrue(array $source, callable $function): bool
    {
        foreach ($source as $key => $value) {
            if (!$function($value)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Returns if the specified callback function returns true for all elements
     *
     * Example:
     * Arrays::any(array(0, 1, 2, 3), function($value) { return $value });
     *
     * @param array $source The array to check
     * @param callable $function The function to execute
     * @return boolean Returns true if the specified callback function returned true for any of the elements in the array, false otherwise
     */
    public static function anyExecuteTrue(array $source, callable $function): bool
    {
        foreach ($source as $key => $value) {
            if ($function($value)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Returns if the specified callback has duplicate values
     *
     * Example:
     * Arrays::countDuplicates(array(0, 1, 2, 1));
     *
     * @param array $source The array to check
     * @return int Returns the amount of duplicate entries in the specified source array
     */
    public static function countDuplicates(array $source): int
    {
        return count($source) - count(array_unique($source));
    }


    /**
     * Returns if the specified callback has duplicate values
     *
     * Example:
     * Arrays::hasDuplicates(array(0, 1, 2, 1));
     *
     * @param array $source The array to check
     * @return boolean Returns true if the specified array contains duplicate values, false otherwise
     */
    public static function hasDuplicates(array $source): bool
    {
        return (bool) Arrays::countDuplicates($source);
    }


    /**
     * Returns true if the source has all specified keys
     *
     * @param array $source
     * @param array|string $keys
     * @return bool
     */
    public static function hasAllKeys(array $source, array|string $keys): bool
    {
        foreach (static::force($keys) as $key) {
            if (!array_key_exists($key, $source)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Returns all values (with their keys) from the specified array that match the specified regex
     *
     * NOTE: Any non string values will be skipped
     *
     * Example:
     * Arrays::pluck(array('foo', 'bar', 'Frack!', 'test'), '/^F/i');
     *
     * @param array $source The array to check
     * @return array Returns true if the specified array contains duplicate values, false otherwise
     */
    public static function pluck(array $source, string $regex): array
    {
        $return = [];

        foreach ($source as $key => $value) {
            if (is_string($value)) {
                if (preg_match($regex, $value)) {
                    $return[$key] = $value;
                }
            }
        }

        return $return;
    }


    /**
     * Merge multiple arrays together, but overwrite null values
     *
     * @param array ...
     * @return array
     */
    public static function mergeNull(): array
    {
        $arguments = static::getArgumentArrays(func_get_args(), 3);
        $return    = [];

        foreach ($arguments as $array) {
            foreach ($array as $key => $value) {
                if (!isset($return[$key]) or ($value !== null)) {
                    $return[$key] = $value;
                }
            }
        }

        return $return;
    }


    /**
     * Hide the specified keys from the specified array
     *
     * @param array|null $source
     * @param string|array $keys
     * @param string $hide
     * @param string $empty
     * @param boolean $recurse
     * @return array|null
     */
    public static function hide(?array $source, string|array $keys = ['GLOBALS', '%pass', 'ssh_key'], string $hide = '*** HIDDEN ***', string $empty = '-', bool $recurse = true): ?array
    {
        static::requireArrayOrNull($source);

        // Ensure that the keys we need to hide are in array format
        $keys = Arrays::force($keys);

        foreach ($source as $source_key => &$source_value) {
            foreach ($keys as $key) {
                if (is_array($source_value)) {
                    if ($recurse) {
                        $source_value = Arrays::hide($source_value, $keys, $hide, $empty, $recurse);
                    } else {
                        // If we don't recurse, we'll hide the entire subarray
                        $source_value = Arrays::hide($source_value, $hide, $empty);
                    }

                } else {
                    if (str_contains($key, '%')) {
                        // These keys can match partial source keys, so "%pass" will also match the source key
                        // "password" for example
                        if (str_contains((string) $source_key, str_replace('%', '', $key))) {
                            $source_value = Strings::hide((string) $source_value, $hide, $empty);
                        }

                    } else {
                        if ($source_key === $key) {
                            $source_value = Strings::hide((string) $source_value, $hide, $empty);
                        }
                    }
                }
            }
        }

        unset($source_value);
        return $source;
    }


    /**
     * Rename the specified old key to the new key
     *
     * @version 2.7.100: Added function and documentation
     *
     * @param array $source
     * @param string|int $old_key
     * @param string|int $new_key
     * @return array The array with the specified key renamed
     */
    public static function renameKey(array $source, string|int $old_key, string|int $new_key): array
    {
        if (!array_key_exists($old_key, $source)) {
            throw new OutOfBoundsException(tr('Specified $old_key does not exist in the specified source array'));
        }

        $source[$new_key] = $source[$old_key];
        unset($source[$old_key]);

        return $source;
    }


    /**
     * Returns the value of the first element of the specified array
     *
     * @see Arrays::lastValue()
     * @version 1.27.0: Added function and documentation
     *
     * @param array $source The source array from which the first value must be returned
     * @return mixed The first value of the specified source array
     */
    public static function firstValue(array$source): mixed
    {
        reset($source);
        $current = current($source);

        if ($current === false) {
            return null;
        }

        return current($source);
    }


    /**
     * Returns the value of the last element of the specified array
     *
     * @see Arrays::firstValue()
     * @version 1.27.0: Added function and documentation
     * @param array $source The source array from which the last value must be returned
     * @return mixed The last value of the specified source array
     */
    public static function lastValue(array $source): mixed
    {
        $end = end($source);

        if ($end === false) {
            return null;
        }

        return $end;
    }


    /**
     * Make sure the specified keys are available on the array
     *
     * @param array|null $source
     * @param string|array $keys
     * @param mixed $default_value
     * @param bool $trim_existing
     * @return void
     */
    public static function ensure(?array &$source, string|array $keys = [], mixed $default_value = null, bool $trim_existing = false): void
    {
        if (!$source) {
            $source = [];
        }

        if ($keys) {
            foreach (Arrays::force($keys) as $key) {
                if (!$key) {
                    continue;
                }

                if (array_key_exists($key, $source)) {
                    if ($trim_existing and is_string($source[$key])) {
                        // Automatically trim the found value
                        $source[$key] = trim($source[$key], (is_bool($trim_existing) ? ' ' : $trim_existing));
                    }

                } else {
                    $source[$key] = $default_value;
                }
            }
        }
    }


    /**
     * Specified variable may be either string or array, but ensure that its returned as an array.
     *
     * @see Strings::force()
     * @example
     * code
     * print_r(Arrays::force(array('test')));
     * /code
     *
     * This will return something like
     *
     * code
     * array('test')
     * /code
     *
     * code
     * print_r(Arrays::force('test'));
     * /code
     *
     * This will return something like
     *
     * code
     * array('test')
     * /code
     *
     * @param string $source The variable that should be forced to be an array
     * @param string|null $separator
     * @return DataListInterface|IteratorInterface|array The specified $source, but now converted to an array data type
     *         (if it was not an array yet)
     */
    public static function force(mixed $source, ?string $separator = ','): DataListInterface|IteratorInterface|array
    {
        if (($source === '') or ($source === null)) {
            return [];
        }

        if (is_array($source) or ($source instanceof DataListInterface) or ($source instanceof IteratorInterface)) {
            return $source;
        }

        if (!is_string($source)) {
            if (!is_object($source) or !($source instanceof ArrayableInterface)) {
                // Unknown datatype
                return [$source];
            }

            // This is an object that can convert to string
            return $source->__toArray();
        }

        if (!$separator) {
            // We cannot explode with an empty separator, assume that $source is a single item and return it as such
            return [$source];
        }

        return explode($separator, $source);
    }


    /**
     * Recursively trim all strings in the specified array tree
     *
     * @param array $source
     * @param bool $recurse
     * @return array
     */
    public static function trimStrings(array $source, bool $recurse = true): array
    {
        foreach ($source as $key => &$value) {
            if (is_string($value)) {
                $value = trim($value);

            } elseif (is_array($value)) {
                if ($recurse) {
                    // Recurse
                    $value = static::trimStrings($value);
                }
            }
        }

        return $source;
    }


    /**
     * Returns the longest value string for each column from each row in the specified source array
     *
     * @note Any non-string keys will be treated as displayed strings
     * @note The required format for the source is as follows:
     *       $source[$id] = [$column1 => $value1, $column2 => $value2, ...];
     *
     * @param array $source
     * @param int $add_extra
     * @param string|null $add_key
     * @param bool $check_column_key_length
     * @return array
     */
    public static function getLongestStringPerColumn(array $source, int $add_extra = 0, ?string $add_key = null, bool $check_column_key_length = true): array
    {
        $columns = [];

        foreach ($source as $key => $row) {
            if (!is_array($row)) {
                $row = [$row];
            }

            // Initialize the return array
            if (empty($columns)) {
                $columns = Arrays::initialize(array_keys($row), $add_extra);

                if ($add_key) {
                    $columns[$add_key] = $add_extra;
                }
            }

            // The key length
            if ($add_key !== null) {
                $length = (strlen((string) $key) + $add_extra);

                if ($length > $columns[$add_key]) {
                    $columns[$add_key] = $length;
                }
            }

            // The length of each column
            foreach ($row as $column => $value) {
                $length = (strlen((string) $value) + $add_extra);

                if ($length > $columns[$column]) {
                    $columns[$column] = $length;
                }

                if ($check_column_key_length) {
                    $length = (strlen((string) $column) + $add_extra);

                    if ($length > $columns[$column]) {
                        $columns[$column] = $length;
                    }
                }
            }
        }

        return $columns;
    }


    /**
     * Returns a new array with the specified keys, all having the specified default value
     *
     * @param array $keys
     * @param mixed $default
     * @return array
     */
    public static function initialize(array $keys, mixed $default = null): array
    {
        $return = [];

        foreach ($keys as $key) {
            $return[$key] = $default;
        }

        return $return;
    }


    /**
     * Remove the key with the specified value from the given source array
     *
     * @param array $source
     * @param string|float|int $value
     * @return string|int|null NULL if the specified value didn't exist, the array key if it did
     */
    public static function unsetValue(array &$source, string|float|int $value): string|int|null
    {
        $key = array_search($value , $source);

        if ($key === false) {
            return null;
        }

        unset($source[$key]);
        return $key;
    }


    /**
     * Add all the values from array2, array3, etc to array1
     *
     * @param array $array1
     * @param array $array2
     * @param array $array3
     * @param...
     * @return array
     */
    public static function addValues(): array
    {
        $arguments = static::getArgumentArrays(func_get_args());
        $target    = array_shift($arguments);

        // Ensure target is an array
        static::requireArrayOrNull($target);

        foreach ($arguments as $id => $source) {
            // Ensure all sources are arrays
            static::requireArrayOrNull($source, $id);

            foreach ($source as $key => $value) {
                // Ensure source is numeric!
                if (!is_numeric($value)) {
                    if (is_array($value)) {
                        // Value is an array, check if target has same format
                        if (array_key_exists($key, $target)) {
                            if (!is_array($target[$key])) {
                                throw new OutOfBoundsException(tr('Target / source data incompatibility detected. Source ":id" key ":key" is an array while the target key is not', [
                                    ':id'  => $id,
                                    ':key' => $key
                                ]));
                            }
                        } else {
                            // Initialize with an empty array
                            $target[$key] = [];
                        }

                        // Target is also an array, recurse!
                        $target[$key] = static::addValues($target[$key], $value);
                        continue;
                    }

                    throw new OutOfBoundsException(tr('Target and all source arrays must contain only numeric values while source ":source" contains key ":key" with non numeric value ":value"', [
                        ':source' => $id,
                        ':key'    => $key,
                        ':value'  => $value
                    ]));
                }

                // Source value is numeric, continue!
                if (!array_key_exists($key, $target)) {
                    // Clean copy
                    $target[$key] = $value;
                    continue;
                }

                // Ensure target is numeric!
                if (!is_numeric($target[$key])) {
                    throw new OutOfBoundsException(tr('Target and all source arrays must contain only numeric values while target ":target" contains key ":key" with non numeric value ":value"', [
                        ':source' => $id,
                        ':key'    => $key,
                        ':value'  => $value
                    ]));
                }

                $target[$key] += $value;
            }
        }

        return $target;
    }


    /**
     * Returns the argument arrays ensuring that there are at least 2
     *
     * @param array $arguments
     * @param int $minimum
     * @return array
     */
    protected static function getArgumentArrays(array $arguments, int $minimum = 2): array
    {
        if ($minimum < 1) {
            throw new OutOfBoundsException(tr('Minimum must be 1 or more'));
        }

        if (count($arguments) < $minimum) {
            throw new OutOfBoundsException('Specify at least 2 arrays');
        }

        return $arguments;
    }


    /**
     * Validates the specified source and ensures it is an array or a NULL value
     *
     * @param mixed $source
     * @param string|float|int|null $id
     * @return void
     */
    protected static function requireArrayOrNull(mixed $source, string|float|int|null $id = null): void
    {
        if (is_array($source)) {
            // All good
            return;
        }

        if ($source === null) {
            // Quietly ignore NULL arguments
            return;
        }

        if ($id === null) {
            throw new OutOfBoundsException(tr('Specified argument is not an array'));
        }

        throw new OutOfBoundsException(tr('Specified argument ":count" is not an array', [
            ':count' => $id
        ]));
    }


    /**
     * Returns an array with "remove" and "add" section to indicate required actions to change $source1 into $source2
     *
     * @param array $source1
     * @param array $source2
     * @param bool $keep If true, the result array will also contain a "keep" column with entries that exists in both
     *                   and should not be added nor deleted (but perhaps updated, for example)
     * @return array
     */
    public static function valueDiff(array $source1, array $source2, bool $keep = false): array
    {
        $return = [
            'add'    => [],
            'delete' => []
        ];

        $keep_list = [];

        foreach ($source1 as $key => $value) {
            if ($value and !is_scalar($value)) {
                throw new OutOfBoundsException(tr('Can only take diffs from scalar values while source 1 has a non-scalar value'));
            }

            if (in_array($value, $source2)) {
                $keep_list[$key] = $value;
            } else {
                // Key doesn't exist in source2, add it
                $return['delete'][$key] = $value;
            }
        }

        foreach ($source2 as $key => $value) {
            if ($value and !is_scalar($value)) {
                throw new OutOfBoundsException(tr('Only scalar values are supported while source 2 has a non-scalar value'));
            }

            if (!in_array($value, $source1)) {
                // Key doesn't exist in source1, add it and next
                $return['add'][$key] = $value;
            }
        }

        if ($keep) {
            $return['keep'] = $keep_list;
        }

        return $return;
    }


    /**
     * Compares the given source list to the add / keep / remove diff and places all entries in remove that are marked
     * with "delete", or "remove" keys
     *
     * @param array $diff
     * @param array $source
     * @return array
     */
    public static function deleteDiff(array $diff, array $source): array
    {
        foreach ($source as $id => $entry) {
            if (array_key_exists('delete', $entry)) {
                if ($entry['delete']) {
                    $key = array_search($id, $diff['keep']);

                    if ($key) {
                        $diff['delete'][$key] = $diff['keep'][$key];
                        unset($diff['keep'][$key]);
                    }
                }
            }
        }

        return $diff;
    }


    /**
     * Will return true if the specified value exists, and remove if from the array
     *
     * @param array $source
     * @param string|float|int $value
     * @return bool
     */
    public static function removeIfExists(array &$source, string|float|int $value): bool
    {
        $key = array_search($value, $source);

        if ($key) {
            unset($source[$key]);
            return true;
        }

        return false;
    }


    /**
     * Will return true if the specified value exists, and modify it in array
     *
     * @param array $source
     * @param string|float|int $value
     * @param string|float|int $replace
     * @return bool
     */
    public static function replaceIfExists(array &$source, string|float|int $value, string|float|int $replace): bool
    {
        $key = array_search($value, $source);

        if ($key) {
            $source[$key] = $replace;
            return true;
        }

        return false;
    }


    /**
     * Prepend the key + value to the specified source array
     *
     * @param array $source
     * @param string|float|int $key
     * @param mixed $value
     * @return array
     */
    public static function prepend(array $source, string|float|int $key, mixed $value): array
    {
        $source       = array_reverse($source, true);
        $source[$key] = $value;
        $source       = array_reverse($source, true);

        return $source;
    }


    /**
     * Truncates an array by cutting entries to the specified size
     *
     * @param array $source
     * @param int $max_size
     * @param string $fill
     * @param string $method
     * @param bool $on_word
     * @return array
     */
    public static function truncate(array $source, int $max_size, string $fill = ' ... ', string $method = 'right', bool $on_word = false): array
    {
        foreach ($source as $key => &$value) {
            if (is_string($value)) {
                $value = Strings::truncate($value, $max_size, $fill, $method, $on_word);
            } elseif (!is_scalar($value)) {
                // No support (yet) for non scalars, just drop it completely
                unset($source[$key]);
            }
        }

        unset($value);
        return $source;
    }


    /**
     * Splits up the specified source string into an array according to the specified format and returns it
     *
     * Format is specified like this: [$keyname => $size, $keyname => $size, ...]
     *
     * @param string $source
     * @param array $format
     * @return array
     */
    public static function format(string $source, array $format): array
    {
        $return = [];
        $pos    = 0;

        foreach ($format as $key => $size) {
            $return[$key] = substr($source, $pos, $size);
            $pos         += $size;
        }

        return $return;
    }


    /**
     * Detects and returns a format to parse table strings using Arrays::format()
     *
     * @param string $source
     * @param string $separator
     * @param bool $lower_keys
     * @return array
     */
    public static function detectFormat(string $source, string $separator = ' ', bool $lower_keys = true): array
    {
        if (strlen($separator) !== 1) {
            throw new OutOfBoundsException(tr('Invalid separator ":separator" specified, it should be a single byte character', [
                ':separator' => $separator
            ]));
        }

        $return = [];
        $start  = true;
        $last   = 0;
        $key    = 'a';

        for ($pos = 0; $pos < strlen($source); $pos++) {
            if (!$pos) {
                // First row. Do we start with a separator? If so, we're in end mode
                if ($source[$pos] === $separator) {
                    $start = false;
                    $key   = null;
                    continue;
                }
            }

            if ($start) {
                // Column headers are at the start of the column
                if ($source[$pos] !== $separator) {
                    if (!$key) {
                        // When the key ends, we have a column, which just happened
                        $key = trim(substr($source, $last, $pos - $last));

                        if ($lower_keys) {
                            $key = strtolower($key);
                        }

                        $return[$key] = $pos - $last;
                        $last = $pos;
                    }
                } else {
                    // We have a separator character, reset the key
                    $key = null;
                }

            } else {
                // Column headers are at the end of the column
                if ($source[$pos] === $separator) {
                    if ($key) {
                        // We passed the key and now have a separator
                        $key = trim(substr($source, $last, $pos - $last));

                        if ($lower_keys) {
                            $key = strtolower($key);
                        }

                        $return[$key] = $pos - $last;
                        $last = $pos;
                        $key = null;
                    }

                } else {
                    // Give key a character, doesn't matter which, so that we know that we've encountered a non
                    // separator character
                    $key = 'a';
                }
            }
        }

        // Add the last key
        $key = trim(substr($source, $last, $pos - $last));

        if ($lower_keys) {
            $key = strtolower($key);
        }

        $return[$key] = $pos;
        return $return;
    }


    /**
     * Returns the size of the shortest key in the specified array.
     *
     * @param array $source
     * @return int
     */
    public static function getShortestKeyLength(array $source): int
    {
        $largest = PHP_INT_MAX;

        foreach ($source as $key => $value) {
            // Determine the largest key
            $size = strlen((string) $key);

            if ($size < $largest) {
                $largest = $size;
            }
        }

        return $largest;
    }


    /**
     * Returns the size of the largest key in the specified array.
     *
     * @param array $source
     * @return int
     */
    public static function getLongestKeyLength(array $source): int
    {
        $largest = 0;

        foreach ($source as $key => $value) {
            // Determine the largest key
            $size = strlen((string) $key);

            if ($size > $largest) {
                $largest = $size;
            }
        }

        return $largest;
    }


    /**
     * Returns the size of the shortest scalar value in the specified array.
     *
     * @note This function will ignore any and all non-scalar values
     *
     * @param array $source
     * @param string|null $key
     * @return int
     */
    public static function getShortestValueLength(array $source, ?string $key = null): int
    {
        $shortest = PHP_INT_MAX;

        foreach ($source as $value) {
            if ($key) {
                if (!is_array($value)) {
                    // $key requires string to be a subarray! Ignore this entry
                    continue;
                }

                if (!array_key_exists($key, $value)) {
                    // $key requires the key to exist in the subarray. Ignore this entry
                    continue;
                }

                $value = $value[$key];
            }

            if (!is_scalar($value)) {
                // $string must be a scalar value! Ignore this entry
                continue;
            }

            // Determine the largest call line
            $size = strlen((string) $value);

            if ($size < $shortest) {
                $shortest = $size;
            }
        }

        return $shortest;
    }


    /**
     * Returns the size of the largest scalar value in the specified array.
     *
     * @note This function will ignore any and all non-scalar values
     *
     * @param array $source
     * @param string|null $key
     * @return int
     */
    public static function getLongestValueLength(array $source, ?string $key = null): int
    {
        $largest = 0;

        foreach ($source as $value) {
            if ($key) {
                if (!is_array($value)) {
                    // $key requires string to be a subarray! Ignore this entry
                    continue;
                }

                if (!array_key_exists($key, $value)) {
                    // $key requires the key to exist in the subarray. Ignore this entry
                    continue;
                }

                $value = $value[$key];
            }

            if (!is_scalar($value)) {
                // $string must be a scalar value! Ignore this entry
                continue;
            }

            // Determine the largest call line
            $size = strlen((string) $value);

            if ($size > $largest) {
                $largest = $size;
            }
        }

        return $largest;
    }


    /**
     * Returns an array with all the values from the specified enum
     *
     * @param UnitEnum $enum
     * @return array
     */
    public static function fromEnum(UnitEnum $enum): array
    {
        $array = $enum::cases();
        $array = array_column($array, 'value');

        return $array;
    }


    /**
     * Extracts entries with the specified prefix from the keys from the given source
     *
     * This function will return an array with all keys that have the specified prefix. If no prefix has been specified,
     * the function will try to detect a prefix. A prefix is presumed to be a string of at least one character ending
     * with an underscore, so entries like "230984_name" will have presumed to have the prefix "230984_"
     *
     * @param array $source
     * @param string|null $prefix
     * @param bool $keep_prefix
     * @return array
     * @example :
     * [
     *   $keya         => $value,
     *   $prefix_$key1 => $value,
     *   $prefix_$key2 => $value
     * ]
     *
     * Will return
     * [
     *   $prefix_$key1 => $value,
     *   $prefix_$key2 => $value
     * ]
     *
     * @example :
     * [
     *   25346_name        => $value,
     *   25346_description => $value,
     *   information       => $value
     * ]
     *
     * will return
     * [
     *   25346_name        => $value,
     *   25346_description => $value
     * ]
     *
     * @note The id in the specified keys must be the same
     *
     */
    public static function extractPrefix(array $source, ?string $prefix = null, bool $keep_prefix = false): array
    {
        $return = [];

        if ($keep_prefix) {
            $key_prefix = $prefix;

        } else {
            $key_prefix = null;
        }

        foreach ($source as $key => $value) {
            if ($prefix === null) {
                // Auto detect class
                $prefix = Strings::until($key, '_', require: true);

                if (!$prefix) {
                    // No class found, continue to the next entry
                    $prefix = null;
                    continue;
                }

                $prefix .= '_';
            }

            $key = Strings::from($key, $prefix, require: true);

            if (!$key) {
                // This key didn't have the specified class
                continue;
            }

            $return[$key_prefix . $key] = $value;
        }

        return $return;
    }


    /**
     * Extracts the specified key from the specified array and returns its value
     *
     * @param array $source
     * @param string $key
     * @return mixed
     */
    public static function extractKey(array &$source, string $key): mixed
    {
        try {
            $return = $source[$key];

        } catch (Throwable) {
            throw new OutOfBoundsException(tr('Key ":key" does not exist in the specified source array', [
                ':key' => $key
            ]));
        }

        unset($source[$key]);
        return $return;
    }


    /**
     * Finds and returns a prefix code in array keys
     *
     * This function will find and return the first prefix code that it can find. A prefix is presumed to be a string of
     * at least one character ending with an underscore, so entries like "230984_name" will have presumed to have the
     * prefix "230984_". Returns NULL if no prefix was found.
     *
     * @example :
     * [
     *   $keya         => $value,
     *   $prefix_$key1 => $value,
     *   $prefix_$key2 => $value
     * ]
     *
     * Will return
     * $prefix
     *
     * @example :
     * [
     *   25346_name        => $value,
     *   25346_description => $value,
     *   information       => $value
     * ]
     *
     * will return
     * "25346"
     *
     * @example :
     * [
     *   name        => $value,
     *   description => $value
     * ]
     *
     * will return
     * NULL
     *
     * @example :
     * [
     *   name         => $value,
     *   _description => $value,
     *   _information => $value
     * ]
     *
     * will return
     * NULL
     *
     * @param array $source
     * @return string|float|int|null
     */
    public static function findPrefix(array $source): string|float|int|null
    {
        foreach ($source as $key => $value) {
            $prefix = (int) Strings::until($key, '_', require: true);

            if ($prefix) {
                return $prefix;
            }
        }

        return null;
    }


    /**
     * Returns all array values from the haystack that matches the needle(s)
     *
     * @param array|string $needles
     * @param array $haystack
     * @param int $options          Flags that will modify this functions behaviour. Current flags are one of
     *                              Arrays::MATCH_ALL, Arrays::MATCH_BEGIN, Arrays::MATCH_END, or Arrays::MATCH_ANYWHERE
     *                              Arrays::MATCH_ANY
     *
     * Arrays::MATCH_NO_CASE:  Will match entries in case-insensitive mode
     * Arrays::MATCH_ALL:      Will match entries that contain all the specified needles
     * Arrays::MATCH_ANY:      Will match entries that contain any of the specified needles
     * Arrays::MATCH_BEGIN:    Will match entries that start with the specified needles. Mutually exclusive with
     *                         Arrays::MATCH_END, Arrays::MATCH_ANYWHERE
     * Arrays::MATCH_END:      Will match entries that end with the specified needles. Mutually exclusive with
     *                         Arrays::MATCH_BEGIN, Arrays::MATCH_ANYWHERE
     * Arrays::MATCH_ANYWHERE: Will match entries that contain the specified needles anywhere. Mutually exclusive with
     *                         Arrays::MATCH_BEGIN, Arrays::MATCH_ANYWHERE
     * @return array
     */
    public static function match(array $haystack, array|string $needles, int $options = self::MATCH_NO_CASE | self::MATCH_ALL | self::MATCH_ANYWHERE): array
    {
        if (!$needles) {
            throw new OutOfBoundsException(tr('No needles specified'));
        }

        // Decode options
        $match_no_case  = (bool) ($options & self::MATCH_NO_CASE);
        $match_all      = (bool) ($options & self::MATCH_ALL);
        $match_any      = (bool) ($options & self::MATCH_ANY);
        $match_begin    = (bool) ($options & self::MATCH_BEGIN);
        $match_end      = (bool) ($options & self::MATCH_END);
        $match_anywhere = (bool) ($options & self::MATCH_ANYWHERE);

        // Validate options
        if ($match_begin) {
            if ($match_end or $match_anywhere) {
                throw new OutOfBoundsException(tr('Cannot mix location flags MATCH_BEGIN with MATCH_END or MATCH_ANYWHERE, they are mutually exclusive'));
            }

        } else {
            if ($match_end and $match_anywhere) {
                throw new OutOfBoundsException(tr('Cannot mix location flags MATCH_END with MATCH_ANYWHERE, they are mutually exclusive'));
            }

            if (!$match_end and !$match_anywhere) {
                throw new OutOfBoundsException(tr('No match location flag specified. One of MATCH_BEGIN, MATCH_END, or MATCH_ANYWHERE must be specified'));
            }
        }

        if ($match_all) {
            if ($match_any) {
                throw new OutOfBoundsException(tr('Cannot mix combination flags MATCH_ALL with MATCH_ANY, they are mutually exclusive'));
            }

        } else {
            if (!$match_any) {
                throw new OutOfBoundsException(tr('No match combination flag specified. Either one of MATCH_ALL or MATCH_ANY must be specified'));
            }
        }

        $needles = Arrays::force($needles);

        // In case of caseless compare, prepare the needles
        if ($match_no_case) {
            foreach ($needles as &$needle) {
                $needle = strtolower((string) $needle);
            }

            unset($needle);
        }

        $return = [];

        foreach ($haystack as $key => $value) {
            if (!$value) {
                // Ignore empty lines
                continue;
            }

            if (!is_scalar($value)) {
                Log::warning(tr('Arrays match ignoring key ":key" with non scalar value ":value"', [
                    ':key'   => $key,
                    ':value' => $value
                ]));
                continue;
            }

            // Caseless match? Compare lowercase
            if ($match_no_case) {
                $test_value = strtolower((string) $value);

            } else {
                $test_value = $value;
            }

            $match = true;

            // Compare to each needle
            foreach ($needles as $needle) {
                if ($match_begin) {
                    if (str_starts_with($test_value, $needle)) {
                        // This needle matched
                        if ($match_any) {
                            $match = true;
                            break;
                        }

                        // This needle matched
                        continue;
                    }

                } elseif ($match_end) {
                    if (str_ends_with($test_value, $needle)) {
                        // This needle matched
                        if ($match_any) {
                            $match = true;
                            break;
                        }

                        continue;
                    }

                } else {
                    if (str_contains($test_value, $needle)) {
                        // This needle matched
                        if ($match_any) {
                            $match = true;
                            break;
                        }

                        continue;
                    }
                }

                $match = false;

                if ($match_all) {
                    // This needle failed, no need to check other needles
                    break;
                }
            }

            if ($match) {
                $return[$key] = $value;
            }
        }

        return $return;
    }


    /**
     * Returns the highest key found in the given source
     *
     * @param array $source
     * @return string|float|int|null
     */
    public static function getHighestKey(array $source): string|float|int|null
    {
        $highest = null;

        foreach ($source as $key => $value) {
            if ($key > $highest) {
                $highest = $key;
            }
        }

        return $highest;
    }


    /**
     * Returns an array with all values uppercase strings
     *
     * @note Non scalar values (except NULL) will cause OutOfBoundsException
     * @note NULL values will remain NULL
     * @param array $source
     * @return array
     */
    public static function lowercase(array $source): array
    {
        foreach ($source as &$value) {
            if (!is_scalar($value)) {
                if ($value) {
                    throw OutOfBoundsException::new(tr('Cannot lowercase the specified array, the value ":value" is not scalar', [
                        ':value' => $value
                    ]));
                }

                // Value is just null, continue
                continue;
            }

            $value = strtolower($value);
        }

        unset($value);
        return $source;
    }


    /**
     * Returns an array with all values uppercase strings
     *
     * @note Non scalar values (except NULL) will cause OutOfBoundsException
     * @note NULL values will remain NULL
     * @param array $source
     * @return array
     */
    public static function uppercase(array $source): array
    {
        foreach ($source as &$value) {
            if (!is_scalar($value)) {
                if ($value) {
                    throw OutOfBoundsException::new(tr('Cannot lowercase the specified array, the value ":value" is not scalar', [
                        ':value' => $value
                    ]));
                }

                // Value is just null, continue
                continue;
            }

            $value = strtoupper($value);
        }

        unset($value);
        return $source;
    }


    /**
     * Renames the keys in the specified source
     *
     *
     * $rename must be an array with FROM_KEY => TO_KEY, FROM_KEY => TO_KEY, ...
     * @param array $source
     * @param array $rename
     * @return array
     */
    public static function renameKeys(array $source, array $rename): array
    {
        foreach ($rename as $from => $to) {
            $source[$to] = $source[$from];
            unset($source[$from]);
        }

        return $source;
    }
}