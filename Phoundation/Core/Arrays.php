<?php

namespace Phoundation\Core;

use Phoundation\Core\Exception\CoreException;
use Phoundation\Exception\OutOfBoundsException;



/**
 * Class Arrays
 *
 * This is the standard Phoundation array functionality extension class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package Phoundation\Core
 */
class Arrays {
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
        if (!self::hasAllKeys($source, $keys)) {
            throw new $exception_class(tr('The specified array does not contain all required keys ":keys"', [
                ':keys' => $keys
            ]));
        }
    }



    /**
     * Returns the next key right after specified $key
     *
     * @param array $source
     * @param int|string $current_key
     * @param bool $delete
     * @return int|string
     * @throws OutOfBoundsException Thrown if the specified $current_key does not exist
     * @throws OutOfBoundsException Thrown if the specified $current_key does exist, but only at the end of the
     *                              specified array, so there is no next key
     */
    public static function nextKey(array &$source, int|string $current_key, bool $delete = false): int|string
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
     * @param bool $restart
     * @return mixed
     * @throws OutOfBoundsException ?????
     */
    public static function nextValue(array &$source, mixed $current_value, bool $delete = false, bool $restart = false): mixed
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

        if (!$restart) {
            // The current value was found, but it was at the end of the array
            throw new OutOfBoundsException(tr('Option ":value" does not have a value specified', [':value' => $current_value]), 'invalid');
        }

        reset($source);
        return current($source);
    }



    /**
     * Ensure that the specified $params source is an array. If it's a numeric value, convert it to
     * [$numeric_key => $params]. If its a string value, convert it to [$string_key => $params]
     *
     * @param mixed $params A parameters array
     * @param string|null $string_key
     * @param string $numeric_key
     * @param bool|null $default The default value for the non-selected key
     * @return void
     *
     * @see Arrays::ensure()
     * @note The default value for this function for non-assigned values is boolean false, not null. The reason for this
     *       is that many of its dependancies use "false" as "do not use" because "null" would be interpreted as
     *       "compare to null"
     * @version 2.5.119: Added function and documentation
     *
     */
    public static function params(mixed &$params, string $string_key = null, $numeric_key = null, ?bool $default = false): void
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

        throw new CoreException(tr('Specified $params ":params" is invalid. It is an ":datatype" but should be either one of array, integer, or string', [':datatype' => gettype($params), ':params' => (is_resource($params) ? '{php resource}' : $params)]));
    }



    /**
     * Ensures that the specified $key exists in the specified $source.
     *
     * If the specified $key does not exist, it will be initialized with the specified $default value. This function is
     * mostly used with ensuring default values for params arrays. With using this function, you can be sure individual
     * values are each initialized with specific values, if they do not exist yet
     *
     * @param array $source The array that is being worked on
     * @param int|string $key The key that must exist in the $source array
     * @param mixed $default The default value in case $source[$key] does not exist
     * @return mixed The new value of $source[$key]. This will be either the original value of $source[$key], or the $default value if $source[$key] did not exist
     * @throws CoreException
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
    public static function default(array &$source, int|string $key, mixed $default): mixed
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
     * Make sure the array is cleared, but with specified keys available
     *
     * @param $keys
     * @param null $value
     * @return array
     */
    public static function clear($keys, $value = null): array
    {
        $array = [];
        return Arrays::ensure($array, $keys, $value);
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
    public static function implode(array $source, string $separator = ','): string
    {
        foreach ($source as &$value) {
            if (is_array($value)) {
                $value = Arrays::implode($value, $separator);
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
     * @param array $source
     * @param string $row_separator
     * @param string $key_separator
     * @param string|null $auto_quote Quote string values with the specified quote
     * @param bool $empty_values
     * @return string
     */
    public static function implodeWithKeys(array $source, string $row_separator, string $key_separator, ?string $auto_quote = null, bool $empty_values = false): string
    {
        $return = [];

        foreach ($source as $key => $value) {
            if (is_array($value)) {
                $return[] .= $key . $key_separator . $row_separator . self::implodeWithKeys($value, $row_separator, $key_separator, $auto_quote);

            }elseif ($empty_values and (!$value)) {
                $return[] .= $key;

            } elseif ($auto_quote) {
                $return[] .= $key . $key_separator . Strings::quote($value, $auto_quote);

            } else {
                $return[] .= $key . $key_separator . $value;
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
        $arguments = func_get_args();

        if (count($arguments) < 2) {
            throw new CoreException('Specify at least 2 arrays');
        }

        $return = [];
        $count  = 0;

        foreach ($arguments as $array) {
            $count++;

            if (!is_array($array)) {
                if ($array === null) {
                    // Quietly ignore NULL arguments
                    continue;
                }

                throw new OutOfBoundsException(tr('Specified argument ":count" is not an array', [
                    ':count' => $count
                ]));
            }

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
     * @param array $source
     * @param int $count
     * @param bool $return_source
     * @return array
     */
    public static function limit(array $source, int $count, bool $return_source = true): array
    {
        if (!is_numeric($count) or ($count < 0)) {
            throw new CoreException(tr('Specified count is not valid'));
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
     * @param array $values
     * @return array
     */
    public static function filterValues(array $source, array $values): array
    {
        if (!is_array($source)) {
            throw new CoreException(tr('Specified source is not an array'), 'invalid');
        }

        foreach (Arrays::force($values) as $value) {
            if (($key = array_search($value, $source)) !== false) {
                unset($source[$key]);
            }
        }

        return $source;
    }


    /**
     * Return an array with the amount of values where each value name is $base_valuename# and # is a sequential number
     *
     * @param int $count
     * @param int|string $base_valuename
     * @return array
     */
    public static function sequentialValues(int $count, int|string $base_valuename): array
    {
        if ($count < 1) {
            throw new CoreException(tr('Invalid count specified. Make sure count is numeric, and greater than 0'), 'invalid');
        }

        for($i = 0; $i < $count; $i++) {
            $return[] = $base_valuename.$i;
        }

        return $return;
    }



    /**
     * Return the source array with the keys all replaced by sequential values based on base_keyname
     *
     * @param array $source
     * @param int|string $base_keyname
     * @param bool $filter_null
     * @param bool $null_string
     * @return array
     */
    public static function sequentialKeys(array $source, int|string $base_keyname, bool $filter_null = false, $null_string = false): array
    {
        if (!is_array($source)) {
            throw new CoreException(tr('Specified source is an ":type", but it should be an array', [':type' => gettype($source)]), 'invalid');
        }

        $i      = 0;
        $return = [];

        foreach ($source as $value) {
            /*
             * Regard all "null" and "NULL" strings as NULL
             */
            if ($null_string) {
                if (($value === 'null') or ($value === 'NULL')) {
                    $value = null;
                }
            }

            /*
             * Filter out all NULL values
             */
            if ($filter_null) {
                if ($value === null) {
                    continue;
                }
            }

            $return[$base_keyname.$i++] = $value;
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
     * Return all array parts from (but without) the specified key
     *
     * @param array $source
     * @param int|string $from_key
     * @param bool $delete
     * @param bool $skip
     * @return array
     */
    public static function from(array &$source, int|string $from_key, bool $delete = false, bool $skip = true): array
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
                        /*
                         * Do not include the key itself, skip it
                         */
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
     * @param int|string $until_key
     * @param bool $delete
     * @return array
     */
    public static function until(array $source, int|string $until_key, bool $delete = false): array
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
     * Merge two arrays together, using the values of array1 as keys, and the values of array2 as values
     *
     * @param array $keys
     * @param array $values
     * @return array
     */
    public static function mergeKeysValues(array $keys, array $values): array
    {
        $return = [];

        foreach ($keys as $key) {
            if (!isset($next)) {
                $next = true;
                $return[$key] = reset($values);

            } else {
                $return[$key] = next($values);
            }
        }

        return $return;
    }



    /**
     * Prefix all keys in this array with the specified prefix
     *
     * @param array $source
     * @param int|string $prefix
     * @param bool $auto
     * @return array
     */
    public static function prefix(array $source, int|string $prefix, bool $auto = false): array
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
     * @param int|string $keyword
     * @return array
     */
    public static function find(array $array, int|string $keyword): array
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
                $target[$key] = mb_trim($value);

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
     * @param int|string $column
     * @return array
     */
    public static function getColumn(array $source, int|string $column): array
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
     * Return the value of one of the first found key of the specified keys
     *
     * @param array $source
     * @param array $keys
     * @return array
     */
    public static function extractFirst(array $source, array $keys): array
    {
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
            throw new OutOfBoundsException(tr('Specified $max value is negative. Please ensure it is a positive integer, 0 or highter'));
        }

        if (count($source) > $max) {
            throw new CoreException(tr('Specified array has too many elements'), 'arraytoolarge');
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
                throw new CoreException(tr('Specified source array contains non scalar values, cannot use non scalar values for the keys'));
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
        $args = func_get_args();

        if (count($args) < 3) {
            throw new CoreException(tr('Function requires at least 3 arguments: filters, source, merge, ...'), 'missing_argument');
        }

        $filters = array_shift($args);
        $source  = array_shift($args);
        $source  = Arrays::remove($source, $filters);
        array_unshift($args, $source);

        return call_user_func_array('array_merge', $args);
    }



    /**
     * Return all elements from source1. If the value of one element is null, then try to return it from source2
     *
     * @note If a key was found in $source1 that was null, and that key does not exist, the $default value will be
     *      assigned instead
     * @param array $source1
     * @param array $source2
     * @param mixed $default
     * @return bool Truel if $source1 had keys with NULL values and was modified with values from $source2, false
     *      otherwise
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
     * @return int
     */
    public static function average(array $source, bool $ignore_non_numbers = false): int
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
            throw new CoreException(tr('array_range(): Specified $min not numeric'), 'invalid');
        }

        if (!is_numeric($max)) {
            throw new CoreException(tr('array_range(): Specified $max not numeric'), 'invalid');
        }

        if ($min > $max) {
            throw new CoreException(tr('array_range(): Specified $min is equal or larger than $max. Please ensure that $min is smaller'), 'invalid');
        }

        $return = [];

        for($i = $min; $i <= $max; $i++) {
            $return[$i] = $i;
        }

        return $return;
    }



    /**
     * Ensure that all array values
     *
     * @param array $source
     * @param bool $recursive
     * @return array
     */
    public static function clean(array $source, bool $recursive = true): array
    {
        foreach ($source as &$value) {
            switch (gettype($value)) {
                case 'integer':
                    // no-break
                case 'double':
                    // no-break
                case 'float':
                    $value = cfi($value);
                    break;

                case 'string':
                    $value = cfm($value);
                    break;

                case 'array':
                    if ($recursive) {
                        $value = Arrays::clean($value, $recursive);
                    }

                    break;
            }
        }

        return $source;
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
        foreach (self::force($keys) as $key) {
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
        $args   = func_get_args();
        $return = [];

        foreach ($args as $array) {
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
        if (!is_array($source)) {
            if ($source === null) {
                return null;
            }

            throw new OutOfBoundsException(tr('Specified source is not an array nor NULL'));
        }

        $keys = Arrays::force($keys);

        foreach ($source as $source_key => &$source_value) {
            foreach ($keys as $key) {
                //
                if (str_contains($key, '%')) {
                    if (strstr($source_key, str_replace('%', '', $key))) {
                        $source_value = Strings::hide($source_value, $hide, $empty);
                    }

                } else {
                    if ($source_key === $key) {
                        $source_value = Strings::hide($source_value, $hide, $empty);
                    }
                }

                if (is_array($source_value)) {
                    if ($recurse) {
                        $source_value = Arrays::hide($source_value, $keys, $hide, $empty, $recurse);
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
     * @param int|string $old_key
     * @param int|string $new_key
     * @return array The array with the specified key renamed
     */
    public static function renameKey(array $source, int|string $old_key, int|string $new_key): array
    {
        if (!array_key_exists($old_key, $source)) {
            throw new CoreException(tr('Specified $old_key does not exist in the specified source array'));
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
     * @param array $source
     * @param string|array $keys
     * @param mixed $default_value
     * @param bool $trim_existing
     * @return void
     */
    public static function ensure(array &$source, string|array $keys = [], mixed $default_value = null, bool $trim_existing = false): void
    {
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
     * @param string $separator
     * @return array The specified $source, but now converted to an array data type (if it was not an array yet)
     */
    public static function force(mixed $source, string $separator = ','): array
    {
        if (($source === '') or ($source === null)) {
            return array();
        }

        if (!is_array($source)) {
            if (!is_string($source)) {
                return array($source);
            }

            if (!$separator) {
                // We cannot explode with an empty separator, assume that $source is a single item and return it as such
                return [$source];
            }

            return explode($separator, $source);
        }

        return $source;
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
                    $value = self::trimStrings($value);
                }
            }
        }

        return $source;
    }



    /**
     * Returns the longest string of all values in the specified source array
     *
     * @note Any non-scalar values will be silently ignored
     * @param array $source
     * @return int
     */
    public static function getLongestString(array $source): int
    {
        $longest = 0;

        foreach ($source as $value) {
            $len = strlen($value);

            if ($len > $longest) {
                $longest = $len;
            }
        }

        return $longest;
    }
}
