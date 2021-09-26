<?php

namespace Phoundation\Core;

use Exception;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Exception\ElementNotFoundException;
use Phoundation\Core\Exception\NoNextElementException;
use Phoundation\Exception\OutOfBoundsException;

/**
 * Class Arrays
 *
 * This is the standard Phoundation array functionality extension class
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2021 <copyright@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package Phoundation\Core
 */
class Arrays {
    /**
     * Returns the next key right after specified $key
     *
     * @param array $source
     * @param int|string $current_key
     * @param bool $delete
     * @return int|string
     * @throws NoNextElementException Thrown if the specified $current_key does exist, but only at the end of the
     *      specified array, so there is no next key
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
            throw new NoNextElementException(tr('The specified $current_key ":key" was found but it was the last item in the array so there is no next', [':key' => $current_key]));
        }

        throw new ElementNotFoundException(tr('The specified $current_key ":key" was not found in the specified array', [':key' => $current_key]));
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
            throw new NoNextElementException(tr('Arrays::next_value(): Option ":value" does not have a value specified', array(':value' => $current_value)), 'invalid');
        }

        reset($source);
        return current($source);
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
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @see Arrays::ensure()
     * @note $source is passed by reference and will be modified directly!
     * @version 1.22.0: Added documentation
     * @example
     * $b = array();
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
     * @param array $keys
     * @return void
     * @throws CoreException
     */
    public static function keyCheck(array $source, array $keys): void
    {
        foreach (Arrays::force($keys) as $key) {
            if (!array_key_exists($key, $source)) {
                throw new CoreException(tr('Key ":key" does not exist in array', [':key' => Strings::log($key)]));
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
    public static function clear($keys, $value = null)
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
        $retval = array();

        foreach ($object as $key => $value) {
            if (is_object($value) and $recurse) {
                $value = Arrays::fromObject($value, true);
            }

            $retval[$key] = $value;
        }

        return $retval;
    }



    /**
     * Return an object from the given array, recursively
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
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
     */
    public static function getRandomValue(array $array): mixed
    {
        return $array[array_rand($array)];
    }



    /**
     * Implode the array with keys
     */
    public static function implodeWithKeys(array $source, string $row_separator, string $key_separator, bool $auto_quote = false): string
    {
        $retval = array();

        foreach ($source as $key => $value) {
            if (is_array($value)) {
                $retval[] .= $key.$key_separator.$row_separator.self::implodeWithKeys($value, $row_separator, $key_separator, $auto_quote);

            } elseif ($auto_quote) {
                $retval[] .= $key.$key_separator.Strings::quote($value);

            } else {
                $retval[] .= $key.$key_separator.$value;
            }
        }

        return implode($row_separator, $retval);
    }



    /**
     *
     */
    public static function mergeComplete()
    {
        try{
            $arguments = func_get_args();

            if (count($arguments) < 2) {
                throw new CoreException('array_merge_complete(): Specify at least 2 arrays');
            }

            $retval = array();
            $count  = 0;

            foreach ($arguments as $argk => $argv) {
                $count++;

                if (!is_array($argv)) {
                    throw new CoreException(tr('array_merge_complete(): Specified argument ":count" is not an array', array(':count' => Strings::log($count))));
                }

                foreach ($argv as $key => $value) {
                    if (is_array($value) and array_key_exists($key, $retval) and is_array($retval[$key])) {
                        $retval[$key] = Arrays::mergeComplete($retval[$key], $value);

                    } else {
                        $retval[$key] = $value;
                    }
                }
            }

            return $retval;

        } catch (Exception $e) {
            throw new CoreException('array_merge_complete(): Failed', $e);
        }
    }



    /**
     * Limit the specified array to the specified amount of entries
     */
    public static function limit(array $source, int $count, bool $return_source = true): array
    {
        try{
            if (!is_array($source)) {
                throw new CoreException(tr('array_limit(): Specified source is not an array'));
            }

            if (!is_numeric($count) or ($count < 0)) {
                throw new CoreException(tr('array_limit(): Specified count is not valid'));
            }

            $retval = array();

            while (count($source) > $count) {
                $retval[] = array_pop($source);
            }

            if ($return_source) {
                return $source;
            }

            return $retval;

        } catch (Exception $e) {
            throw new CoreException('array_limit(): Failed', $e);
        }
    }



    /**
     *
     */
    public static function filterValues(array $source, array $values): array
    {
        try{
            if (!is_array($source)) {
                throw new CoreException(tr('array_filter_values(): Specified source is not an array'), 'invalid');
            }

            foreach (Arrays::force($values) as $value) {
                if (($key = array_search($value, $source)) !== false) {
                    unset($source[$key]);
                }
            }

            return $source;

        } catch (Exception $e) {
            throw new CoreException('array_filter_values(): Failed');
        }
    }



    /**
     * Return an array with the amount of values where each value name is $base_valuename# and # is a sequential number
     */
    public static function sequentialValues(int $count, int|string $base_valuename): array
    {
        try{
            if ($count < 1) {
                throw new CoreException(tr('array_sequential_values(): Invalid count specified. Make sure count is numeric, and greater than 0'), 'invalid');
            }

            for($i = 0; $i < $count; $i++) {
                $retval[] = $base_valuename.$i;
            }

            return $retval;

        } catch (Exception $e) {
            throw new CoreException('array_sequential_values(): Failed', $e);
        }
    }



    /**
     * Return the source array with the keys all replaced by sequential values based on base_keyname
     */
    public static function sequentialKeys(array $source, int|string $base_keyname, bool $filter_null = false, $null_string = false): array
    {
        try{
            if (!is_array($source)) {
                throw new CoreException(tr('array_sequential_keys(): Specified source is an ":type", but it should be an array', array(':type' => gettype($source))), 'invalid');
            }

            $i      = 0;
            $retval = array();

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

                $retval[$base_keyname.$i++] = $value;
            }

            return $retval;

        } catch (Exception $e) {
            throw new CoreException('array_sequential_keys(): Failed', $e);
        }
    }



    /**
     * Return the source array with the specified keys kept, all else removed.
     */
    public static function keep(array $source, array $keys): array
    {
        try{
            $retval = array();

            foreach (Arrays::force($keys) as $key) {
                if (array_key_exists($key, $source)) {
                    $retval[$key] = $source[$key];
                }
            }

            return $retval;

        } catch (Exception $e) {
            throw new CoreException('array_keep(): Failed', $e);
        }
    }



    /**
     * Return the source array with the specified keys removed.
     */
    public static function remove(array $source, array $keys): array
    {
        try{
            foreach (Arrays::force($keys) as $key) {
                unset($source[$key]);
            }

            return $source;

        } catch (Exception $e) {
            throw new CoreException('array_remove(): Failed', $e);
        }
    }



    /**
     * Return all array parts from (but without) the specified key
     */
    public static function from(array &$source, int|string $from_key, bool $delete = false, bool $skip = true): array
    {
        try{
            $retval = array();
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

                $retval[$key] = $value;

                if ($delete) {
                    unset($source[$key]);
                }
            }

            return $retval;

        } catch (Exception $e) {
            throw new CoreException('array_from(): Failed', $e);
        }
    }



    /**
     * Return all array parts until (but without) the specified key
     */
    public static function until(array $source, int|string $until_key, bool $delete = false): array
    {
        try{
            $retval = array();

            foreach ($source as $key => $value) {
                if ($key == $until_key) {
                    break;
                }

                $retval[$key] = $value;

                if ($delete) {
                    unset($source[$key]);
                }
            }

            return $retval;

        } catch (Exception $e) {
            throw new CoreException('array_until(): Failed', $e);
        }
    }



    /**
     * Merge two arrays together, using the values of array1 as keys, and the values of array2 as values
     */
    public static function mergeKeysValues(array $keys, array $values): array
    {
        try{
            $retval = array();

            foreach ($keys as $key) {
                if (!isset($next)) {
                    $next = true;
                    $retval[$key] = reset($values);

                } else {
                    $retval[$key] = next($values);
                }
            }

            return $retval;

        } catch (Exception $e) {
            throw new CoreException('array_merge_keys_values(): Failed', $e);
        }
    }



    /**
     * Prefix all keys in this array with the specified prefix
     */
    public static function prefix(array $source, int|string $prefix, bool $auto = false): array
    {
        try{
            $count  = 0;
            $retval = array();

            foreach ($source as $key => $value) {
                if ($auto) {
                    $retval[$prefix.$count++] = $value;

                } else {
                    $retval[$prefix.$key] = $value;
                }
            }

            return $retval;

        } catch (Exception $e) {
            throw new CoreException('array_prefix(): Failed', $e);
        }
    }



    /**
     * Return the array keys that has a STRING value that contains the specified keyword
     *
     * NOTE: Non string values will be quietly ignored!
     */
    public static function find(array $array, int|string $keyword): array
    {
        try{
            $retval = array();

            foreach ($array as $key => $value) {
                if (is_string($value)) {
                    if (str_contains($value, $keyword)) {
                        $retval[$key] = $value;
                    }
                }
            }

            return $retval;

        } catch (Exception $e) {
            throw new CoreException('array_find(): Failed', $e);
        }
    }



    /**
     * Copy all elements from source to target, and clean them up. Any columns specified in "skip" will be skipped
     */
    public static function copyClean(array $target, array $source, array $skip = ['id']): array
    {
        try{
            foreach ($source as $key => $value) {
                if (in_array($key, $skip)) continue;

                if (is_string($value)) {
                    $target[$key] = mb_trim($value);

                } elseif ($value !== null) {
                    $target[$key] = $value;
                }
            }

            return $target;

        } catch (Exception $e) {
            throw new CoreException('array_copy_clean(): Failed', $e);
        }
    }



    /**
     * Return an array with all the values in the specified column
     */
    public static function getColumn(array $source, int|string $column): array
    {
        try{
            $retval = array();

            foreach ($source as $id => $value) {
                if (array_key_exists($column, $value)) {
                    $retval[] = $value[$column];
                }
            }

            return $retval;

        } catch (Exception $e) {
            throw new CoreException('array_get_column(): Failed', $e);
        }
    }



    /**
     * Return the value of one of the first found key of the specified keys
     */
    public static function extractFirst(array $source, array $keys): array
    {
        try{
            foreach ($keys as $key) {
                if (!empty($source[$key])) {
                    return $source[$key];
                }
            }

        } catch (Exception $e) {
            throw new CoreException('array_extract(): Failed', $e);
        }
    }



    /**
     * Check the specified array and ensure it has not too many elements (to avoid attack with processing foreach over 2000000 elements, for example)
     */
    public static function max(array $source, int $max = 20): array
    {
        if ($max < 0) {
            throw new OutOfBoundsException(tr('Specified $max value is negative. Please ensure it is a positive integer, 0 or highter'));
        }

        if (count($source) > $max) {
            throw new CoreException(tr('array_max(): Specified array has too many elements'), 'arraytoolarge');
        }

        return $source;
    }



    /**
     *
     */
    public static function valueToKeys(array $source): array
    {
        try{
            $retval = array();

            foreach ($source as $value) {
                if (!is_scalar($value)) {
                    throw new CoreException(tr('array_value_to_keys(): Specified source array contains non scalar values, cannot use non scalar values for the keys'));
                }

                $retval[$value] = $value;
            }

            return $retval;

        } catch (Exception $e) {
            throw new CoreException('array_value_to_keys(): Failed', $e);
        }
    }



    /**
     *
     */
    public static function filteredMerge()
    {
        try{
            $args = func_get_args();

            if (count($args) < 3) {
                throw new CoreException(tr('array_filtered_merge(): Function requires at least 3 arguments: filter, source, merge, ...'), 'missing_argument');
            }

            $filter = array_shift($args);
            $source = array_shift($args);
            $source = Arrays::remove($source, $filter);
            array_unshift($args, $source);

            return call_user_func_array('array_merge', $args);

        } catch (Exception $e) {
            throw new CoreException('array_filtered_merge(): Failed', $e);
        }
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
        try{
            $modified = false;

            foreach ($source1 as $key => $value) {
                if ($value === null) {
                    $source1[$key] = isset_get($source2[$key], $default);
                    $modified      = true;
                }
            }

            return $modified;

        } catch (Exception $e) {
            throw new CoreException('array_not_null(): Failed', $e);
        }
    }



    /**
     * Return the average value of all values in the specified source array
     */
    public static function average(array $source, bool $ignore_non_numbers = false): int
    {
        try{
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

        } catch (Exception $e) {
            throw new CoreException('array_average(): Failed', $e);
        }
    }



    /**
     * Return an array with values ranging from $min to $max
     */
    public static function range(int $min, int $max): array
    {
        try{
            if (!is_numeric($min)) {
                throw new CoreException(tr('array_range(): Specified $min not numeric'), 'invalid');
            }

            if (!is_numeric($max)) {
                throw new CoreException(tr('array_range(): Specified $max not numeric'), 'invalid');
            }

            if ($min > $max) {
                throw new CoreException(tr('array_range(): Specified $min is equal or larger than $max. Please ensure that $min is smaller'), 'invalid');
            }

            $retval = array();

            for($i = $min; $i <= $max; $i++) {
                $retval[$i] = $i;
            }

            return $retval;

        } catch (Exception $e) {
            throw new CoreException('array_range(): Failed', $e);
        }
    }



    /**
     * Ensure that all array values
     */
    public static function clean(array $source, bool $recursive = true): array
    {
        try{
            foreach ($source as &$value) {
                switch (gettype($value)) {
                    case 'integer':
                        // FALLTHROUGH
                    case 'double':
                        // FALLTHROUGH
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

        } catch (Exception $e) {
            throw new CoreException('array_clean(): Failed', $e);
        }
    }



    /**
     * Returns if the specified callback function returns true for all elements
     *
     * Example:
     * Arrays::all(array(1, 2, 3), function($value) { return $value });
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package array
     *
     * @param array $source The array to check
     * @param string $function The function to execute
     * @return boolean Returns true if the specified callback function returned true for all elements in the array, false otherwise
     */
    public static function allExecuteTrue(array $source, callable $function): bool
    {
        try{
            foreach ($source as $key => $value) {
                if (!$function($value)) {
                    return false;
                }
            }

            return true;

        } catch (Exception $e) {
            throw new CoreException('array_all(): Failed', $e);
        }
    }



    /**
     * Returns if the specified callback function returns true for all elements
     *
     * Example:
     * array_any(array(0, 1, 2, 3), function($value) { return $value });
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package array
     *
     * @param array $source The array to check
     * @param string $function The function to execute
     * @return boolean Returns true if the specified callback function returned true for any of the elements in the array, false otherwise
     */
    public static function anyExecuteTrue(array $source, callable $function): bool
    {
        try{
            foreach ($source as $key => $value) {
                if ($function($value)) {
                    return true;
                }
            }

            return false;

        } catch (Exception $e) {
            throw new CoreException('array_any(): Failed', $e);
        }
    }



    /**
     * Returns if the specified callback has duplicate values
     *
     * Example:
     * array_has_duplicates(array(0, 1, 2, 1));
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package array
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
     * array_has_duplicates(array(0, 1, 2, 1));
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package array
     *
     * @param array $source The array to check
     * @return boolean Returns true if the specified array contains duplicate values, false otherwise
     */
    public static function hasDuplicates(array $source): bool
    {
        try{
            return (bool) Arrays::countDuplicates($source);

        } catch (Exception $e) {
            throw new CoreException('array_has_duplicates(): Failed', $e);
        }
    }



    /**
     * Returns all values (with their keys) from the specified array that match the specified regex
     *
     * NOTE: Any non string values will be skipped
     *
     * Example:
     * Arrays::pluck(array('foo', 'bar', 'Frack!', 'test'), '/^F/i');
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package array
     *
     * @param array $source The array to check
     * @return boolean Returns true if the specified array contains duplicate values, false otherwise
     */
    public static function pluck(array $source, string $regex): array
    {
        try{
            $retval = array();

            foreach ($source as $key => $value) {
                if (is_string($value)) {
                    if (preg_match($regex, $value)) {
                        $retval[$key] = $value;
                    }
                }
            }

            return $retval;

        } catch (Exception $e) {
            throw new CoreException('array_pluck(): Failed', $e);
        }
    }



    /*
     * OBSOLETE
     */



    /**
     * Merge multiple arrays together, but overwrite null values
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package array
     *
     * @param array
     * @return array
     */
    public static function mergeNull()
    {
        try{
            $args   = func_get_args();
            $retval = array();

            foreach ($args as $array) {
                foreach ($array as $key => $value) {
                    if (!isset($retval[$key]) or ($value !== null)) {
                        $retval[$key] = $value;
                    }
                }
            }

            return $retval;

        } catch (Exception $e) {
            throw new CoreException('array_merge_null(): Failed', $e);
        }
    }



    /**
     * Hide the specified keys from the specified array
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package array
     *
     * @param array|null $source
     * @param mixed $keys
     * @param string $hide
     * @param string $empty
     * @param boolean $recurse
     * @return array|null
     */
    public static function hide(?array $source, array $keys = ['GLOBALS', '%pass', 'ssh_key'], string $hide = '*** HIDDEN ***', string $empty = '-', bool $recurse = true): ?array
    {
        try{
            if (!is_array($source)) {
                if ($source === null) {
                    return null;
                }

                throw new CoreException(tr('array_hide(): Specified source is not an array'), 'invalid');
            }

            $keys = Arrays::force($keys);

            foreach ($source as $source_key => &$source_value) {
                foreach ($keys as $key) {
                    /*
                     *
                     */
                    if (strstr($key, '%')) {
                        if (strstr($source_key, Strings::replace('%', '', $key))) {
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

        } catch (Exception $e) {
            throw new CoreException('array_merge_null(): Failed', $e);
        }
    }



    /**
     * Rename the specified old key to the new key
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package array
     * @version 2.7.100: Added function and documentation
     *
     * @param array $source
     * @param int|string $old_key
     * @param int|string $new_key
     * @return array The array with the specified key renamed
     */
    public static function renameKey(array $source, int|string $old_key, int|string $new_key): array
    {
        try{
            if (!is_array($source)) {
                throw new CoreException(tr('array_rename_key(): Specified source is not an array'), 'invalid');
            }

            if (!array_key_exists($old_key, $source)) {
                throw new CoreException(tr('array_rename_key(): Specified $old_key does not exist in the specified source array'), 'not-exists');
            }

            $source[$new_key] = $source[$old_key];
            unset($source[$old_key]);

            return $source;

        } catch (Exception $e) {
            throw new CoreException(tr('array_rename_key(): Failed'), $e);
        }
    }


    /**
     * Returns the value of the first element of the specified array
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package array
     * @see array_last()
     * @version 1.27.0: Added function and documentation
     *
     * @param array $source The source array from which the first value must be returned
     * @return mixed The first value of the specified source array
     */
    public static function first(array$source): array
    {
        try{
            reset($source);
            return current($source);

        } catch (Exception $e) {
            throw new CoreException('array_first(): Failed', $e);
        }
    }



    /**
     * Returns the value of the last element of the specified array
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package array
     * @see array_first()
     * @see date_convert() Used to convert the sitemap entry dates
     * @version 1.27.0: Added function and documentation
     *
     * @param array $source The source array from which the last value must be returned
     * @return mixed The last value of the specified source array
     */
    public static function last(array $source): array
    {
        try{
            return end($source);

        } catch (Exception $e) {
            throw new CoreException('array_last(): Failed', $e);
        }
    }



    /**
     * Make sure the specified keys are available on the array
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package array
     *
     * @param array $source
     * @param array (optional) $keys
     * @param mixed (optional) $default_value
     * @param bool (optional) $trim_existing
     * @return array
     */
    public static function ensure(array &$source, array $keys = [], mixed $default_value = null, bool $trim_existing = false): array
    {
        try{
            if (!$source) {
                $source = array();

            } elseif (!is_array($source)) {
                if (is_object($source)) {
                    throw new CoreException(tr('array_ensure(): Specified source is not an array but an object of the class ":class"', array(':class' => get_class($source))), 'invalid');
                }

                throw new CoreException(tr('array_ensure(): Specified source is not an array but a ":type"', array(':type' => gettype($source))), 'invalid');
            }

            if ($keys) {
                foreach (array_force($keys) as $key) {
                    if (!$key) {
                        continue;
                    }

                    if (array_key_exists($key, $source)) {
                        if ($trim_existing and is_string($source[$key])) {
                            /*
                             * Automatically trim the found value
                             */
                            $source[$key] = trim($source[$key], (is_bool($trim_existing) ? ' ' : $trim_existing));
                        }

                    } else {
                        $source[$key] = $default_value;
                    }
                }
            }

        } catch (Exception $e) {
            throw new CoreException('array_ensure(): Failed', $e);
        }
    }



    /**
     * Specified variable may be either string or array, but ensure that its returned as an array.
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @see Strings::force()
     * @example
     * code
     * print_r(array_force(array('test')));
     * /code
     *
     * This will return something like
     *
     * code
     * array('test')
     * /code
     *
     * code
     * print_r(array_force('test'));
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
        try{
            if (($source === '') or ($source === null)) {
                return array();
            }

            if (!is_array($source)) {
                if (!is_string($source)) {
                    return array($source);
                }

                return explode($separator, $source);
            }

            return $source;

        } catch (Exception $e) {
            throw new CoreException('array_force(): Failed', $e);
        }
    }
}
