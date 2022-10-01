<?php
/*
 * Array library
 *
 * This library file contains extra array functions
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package array
 */



/*
 * Ensure that the specified $params source is an array. If its a numeric value, convert it to array($numeric_key => $params). If its a string value, convert it to array($string_key => $params)
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package array
 * @see Arrays::ensure()
 * @note The default value for this function for non assigned values is boolean false, not null. The reason for this is that many of its dependancies use "false" as "do not use" because "null" would be interpreted as "compare to null"
 * @version 2.5.119: Added function and documentation
 *
 * @param params $params A parameters array
 * @param string $string_key
 * @param null string $numeric_key
 * @param null $default The default value for the non selected key
 * @return array The specified source, guaranteed as a parameters array
 */
function array_params(&$params, $string_key = null, $numeric_key = null, $default = false) {
    /*
     * IMPORTANT!! DO NOT CHANGE $default DEFAULT VALUE AWAY FROM FALSE! THIS IS A REQUIREMENT FOR THE sql_simple_list() / sql_simple_get() FUNCTIONS!!
     */
    try {
        if (!$params) {
            /*
             * The specified value is empty (probably null, "", etc). Convert it into an array containing the numeric and string keys with null values
             */
            $params = array();
        }

        if (is_array($params)) {
            Arrays::ensure($params, array($string_key, $numeric_key), $default);
            return;
        }

        if (is_numeric($params)) {
            /*
             * The specified value is numeric, convert it to an array with the specified numeric key set having the value $params
             */
            $params = array($numeric_key => $params,
                            $string_key  => $default);
            return;
        }

        if (is_string($params)) {
            /*
             * The specified value is string, convert it to an array with the specified string key set having the value $params
             */
            $params = array($numeric_key => $default,
                            $string_key  => $params);
            return;
        }

        throw new CoreException(tr('array_params(): Specified $params ":params" is invalid. It is an ":datatype" but should be either one of array, integer, or string', array(':datatype' => gettype($params), ':params' => (is_resource($params) ? '{php resource}' : $params))), 'invalid');

    }catch(Exception $e) {
        throw new CoreException(tr('array_params(): Failed'), $e);
    }
}



/*
 * Return the next key right after specified $key
 */
function array_next_key(&$array, $currentkey, $delete = false) {
    try {
        foreach ($array as $key => $value) {
            if (isset($next)) {
                if ($delete) {
                    unset($array[$key]);
                }

                return $key;
            }

            if ($key === $currentkey) {
                if ($delete) {
                    unset($array[$key]);
                }

                $next = true;
            }
        }


        if (!empty($next)) {
            /*
             * The currentvalue was found, but it was at the end of the array
             */
            throw new CoreException(tr('array_next_key(): Found currentkey ":value" but it was the last item in the array, there is no next', array(':value' => Strings::Log($currentvalue))), '');
        }

    }catch(Exception $e) {
        throw new CoreException('array_next_key(): Failed', $e);
    }
}



/*
 * Return the next key right after specified $key
 *
 * If the specified key is not found, $currentvalue will be returned.
 */
function array_next_value(&$array, $currentvalue, $delete = false, $restart = false) {
    try {
        foreach ($array as $key => $value) {
            if (isset($next)) {
                if ($delete) {
                    unset($array[$key]);
                }

                return $value;
            }

            if ($value === $currentvalue) {
                if ($delete) {
                    unset($array[$key]);
                }

                $next = true;
            }
        }

        if (!$restart) {
            /*
             * The currentvalue was found, but it was at the end of the array
             */
            throw new CoreException(tr('array_next_value(): Option ":value" does not have a value specified', array(':value' => $currentvalue)), 'invalid');
        }

        reset($array);
        return current($array);

    }catch(Exception $e) {
        throw new CoreException('array_next_value(): Failed', $e);
    }
}



/*
 * Ensure that the specified $key exists in the specified $source. If the specified $key does not exist, it will be initialized with the specified $default value.
 *
 * This function is mostly used with ensuring default values for params arrays. With using this function, you can be sure individual values are each initialized with specific values, if they do not exist yet
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package array
 * @see Arrays::ensure()
 * @note: $source is passed by reference and will be modified directly
 * @version 1.22.0: Added documentation
 * code
 * $b = array();
 * array_default($b, 'foo', 'bar');
 * showdie($b)
 * /code
 *
 * This would display the following results
 * code
 * array('foo' => 'bar')
 * /code
 *
 * @param params $source The array that is being worked on
 * @param string $key The key that must exist in the $source array
 * @param mixed $default The default value in case $source[$key] does not exist
 * @return mixed The new value of $source[$key]. This will be either the original value of $source[$key], or the $default value if $source[$key] did not exist
 */
function array_default(&$source, $key, $default) {
    try {
        if (!isset($source[$key])) {
            $source[$key] = $default;
        }

        return $source[$key];

    }catch(Exception $e) {
        if (!is_array($source)) {
            throw new CoreException(tr('array_default(): Specified source is not an array'), 'invalid');
        }

        if (!is_scalar($key)) {
            throw new CoreException(tr('array_default(): Specified key ":key" is not a scalar', array(':key' => $key)), 'invalid');
        }

        throw new CoreException('array_default(): Failed', $e);
    }
}



/*
 * Ensure that the specified keys are available. If not, exception
 */
function array_key_check($source, $keys) {
    try {
        if (!is_array($source)) {
            throw new CoreException(tr('array_key_check(): Specified source should be an array, but is a ":type"', array(':type' => gettype($source))), 'invalid');
        }

        foreach (Arrays::force($keys) as $key) {
            if (!array_key_exists($key, $source)) {
                throw new CoreException(tr('array_key_check(): Key ":key" was not specified in array', array(':key' => Strings::Log($key))), 'not_specified');
            }
        }

    }catch(Exception $e) {
        if ($e->getCode() == 'not_specified') {
            throw $e;
        }

        throw new CoreException('array_key_check(): Failed', $e);
    }
}



/*
 * Make sure the array is cleared, but with specified keys available
 */
function array_clear(&$array, $keys, $value = null) {
    try {
        $array = array();
        return Arrays::ensure($array, $keys, $value);

    }catch(Exception $e) {
        throw new CoreException('array_clear(): Failed', $e);
    }
}



/*
 * Return an array from the given object, recursively
 */
function array_from_object($object, $recurse = true) {
    try {
        if (!is_object($object)) {
            throw new CoreException(tr('array_from_object(): Specified variable is not an object'));
        }

        $retval = array();

        foreach ($object as $key => $value) {
            if (is_object($value) and $recurse) {
                $value = array_from_object($value, true);
            }

            $retval[$key] = $value;
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('array_from_object(): Failed', $e);
    }
}



/*
 * Return an object from the given array, recursively
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package array
 *
 * @param array $array
 * @return object The array that was created from the specified array
 */
function array_to_object($array) {
    try {
        return (object) $array;

    }catch(Exception $e) {
        throw new CoreException('array_to_object(): Failed', $e);
    }
}



/*
 * Return a random value from the specified array
 */
function array_random_value($array) {
    try {
        return $array[array_rand($array)];

    }catch(Exception $e) {
        throw new CoreException('array_random_value(): Failed', $e);
    }
}

// :DEPRECATED: Use the above function
function array_get_random($array) {
    try {
        if (empty($array)) {
            throw new CoreException(tr('array_get_random(): The specified array is empty'), 'empty');
        }

        return $array[array_rand($array)];

    }catch(Exception $e) {
        throw new CoreException('array_get_random(): Failed', $e);
    }
}



/*
 * Implode the array with keys
 */
function array_implode_with_keys($source, $row_separator, $key_separator, $auto_quote = false, $recurse = true) {
    try {
        if (!is_array($source)) {
            throw new CoreException(tr('array_implode_with_keys(): Specified source is not an array but an ":type"', array(':type' => gettype($source))));
        }

        $retval = array();

        foreach ($source as $key => $value) {
            if (is_array($value)) {
                /*
                 * Recurse?
                 */
                if (!$recurse) {
                    throw new CoreException(tr('array_implode_with_keys(): Specified source contains sub arrays and recurse is not enabled'));
                }

                $retval[] .= $key.$key_separator.$row_separator.array_implode_with_keys($value, $row_separator, $key_separator, $auto_quote, $recurse);

            } else {
                if ($auto_quote) {
                    $retval[] .= $key.$key_separator.str_auto_quote($value);

                } else {
                    $retval[] .= $key.$key_separator.$value;
                }
            }
        }

        return implode($row_separator, $retval);

    }catch(Exception $e) {
        throw new CoreException('array_implode_with_keys(): Failed', $e);
    }
}



/*
 *
 */
function array_merge_complete() {
    try {
        $arguments = func_get_args();

        if (count($arguments) < 2) {
            throw new CoreException('array_merge_complete(): Specify at least 2 arrays');
        }

        $retval = array();
        $count  = 0;

        foreach ($arguments as $argk => $argv) {
            $count++;

            if (!is_array($argv)) {
                throw new CoreException(tr('array_merge_complete(): Specified argument ":count" is not an array', array(':count' => Strings::Log($count))));
            }

            foreach ($argv as $key => $value) {
                if (is_array($value) and array_key_exists($key, $retval) and is_array($retval[$key])) {
                    $retval[$key] = array_merge_complete($retval[$key], $value);

                } else {
                    $retval[$key] = $value;
                }
            }
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('array_merge_complete(): Failed', $e);
    }
}



/*
 * Limit the specified array to the specified amount of entries
 */
function array_limit($source, $count, $return_source = true) {
    try {
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

    }catch(Exception $e) {
        throw new CoreException('array_limit(): Failed', $e);
    }
}



/*
 *
 */
function array_filter_values($source, $values) {
    try {
        if (!is_array($source)) {
            throw new CoreException(tr('array_filter_values(): Specified source is not an array'), 'invalid');
        }

        foreach (Arrays::force($values) as $value) {
            if (($key = array_search($value, $source)) !== false) {
                unset($source[$key]);
            }
        }

        return $source;

    }catch(Exception $e) {
        throw new CoreException('array_filter_values(): Failed');
    }
}



/*
 * Return an array with the amount of values where each value name is $base_valuename# and # is a sequential number
 */
function array_sequential_values($count, $base_valuename) {
    try {
        if (!is_numeric($count) or ($count < 1)) {
            throw new CoreException(tr('array_sequential_values(): Invalid count specified. Make sure count is numeric, and greater than 0'), 'invalid');
        }

        for($i = 0; $i < $count; $i++) {
            $retval[] = $base_valuename.$i;
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('array_sequential_values(): Failed', $e);
    }
}



/*
 * Return the source array with the keys all replaced by sequential values based on base_keyname
 */
function array_sequential_keys($source, $base_keyname, $filter_null = false, $null_string = false) {
    try {
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

    }catch(Exception $e) {
        throw new CoreException('array_sequential_keys(): Failed', $e);
    }
}



/*
 * Return the source array with the specified keys kept, all else removed.
 */
function array_keep($source, $keys) {
    try {
        $retval = array();

        foreach (Arrays::force($keys) as $key) {
            if (array_key_exists($key, $source)) {
                $retval[$key] = $source[$key];
            }
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('array_keep(): Failed', $e);
    }
}



/*
 * Return the source array with the specified keys removed.
 */
function array_remove($source, $keys) {
    try {
        foreach (Arrays::force($keys) as $key) {
            unset($source[$key]);
        }

        return $source;

    }catch(Exception $e) {
        throw new CoreException('array_remove(): Failed', $e);
    }
}



/*
 * Return all array parts from (but without) the specified key
 */
function array_from(&$source, $from_key, $delete = false, $skip = true) {
    try {
        if (!is_array($source)) {
            throw new CoreException(tr('array_from(): Specified source is an ":type", but it should be an array', array(':type' => gettype($source))), 'invalid');
        }

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

    }catch(Exception $e) {
        throw new CoreException('array_from(): Failed', $e);
    }
}



/*
 * Return all array parts until (but without) the specified key
 */
function array_until($source, $until_key, $delete = false) {
    try {
        if (!is_array($source)) {
            throw new CoreException(tr('array_until(): Specified source is an ":type", but it should be an array', array(':type' => gettype($source))), 'invalid');
        }

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

    }catch(Exception $e) {
        throw new CoreException('array_until(): Failed', $e);
    }
}



/*
 * Merge two arrays together, using the values of array1 as keys, and the values of array2 as values
 */
function array_merge_keys_values($keys, $values) {
    try {
        if (!is_array($keys)) {
            throw new CoreException(tr('array_merge_keys_values(): Specified keys variable is an ":type", but it should be an array', array(':type' => gettype($keys))), 'invalid');
        }

        if (!is_array($values)) {
            throw new CoreException(tr('array_merge_keys_values(): Specified values variable is an ":type", but it should be an array', array(':type' => gettype($values))), 'invalid');
        }

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

    }catch(Exception $e) {
        throw new CoreException('array_merge_keys_values(): Failed', $e);
    }
}



/*
 * Prefix all keys in this array with the specified prefix
 */
function array_prefix($source, $prefix, $auto = false) {
    try {
        if (!is_array($source)) {
            throw new CoreException(tr('array_prefix_keys(): Specified source is an ":type", but it should be an array', array(':type' => gettype($source))), 'invalid');
        }

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

    }catch(Exception $e) {
        throw new CoreException('array_prefix(): Failed', $e);
    }
}



/*
 * Return the array keys that has a STRING value that contains the specified keyword
 *
 * NOTE: Non string values will be quietly ignored!
 */
function array_find($array, $keyword) {
    try {
        $retval = array();

        foreach ($array as $key => $value) {
            if (is_string($value)) {
                if (strpos($value, $keyword) !== false) {
                    $retval[$key] = $value;
                }
            }
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('array_find(): Failed', $e);
    }
}



/*
 * Copy all elements from source to target, and clean them up. Any columns specified in "skip" will be skipped
 */
function array_copy_clean($target, $source, $skip = 'id') {
    try {
        $skip = Arrays::force($skip);

        foreach ($source as $key => $value) {
            if (in_array($key, $skip)) continue;

            if (is_string($value)) {
                $target[$key] = mb_trim($value);

            } elseif ($value !== null) {
                $target[$key] = $value;
            }
        }

        return $target;

    }catch(Exception $e) {
        throw new CoreException('array_copy_clean(): Failed', $e);
    }
}



/*
 * Return an array with all the values in the specified column
 */
function array_get_column($source, $column) {
    try {
        $retval = array();

        foreach ($source as $id => $value) {
            if (array_key_exists($column, $value)) {
                $retval[] = $value[$column];
            }
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('array_get_column(): Failed', $e);
    }
}



/*
 * Return the value of one of the first found key of the specified keys
 */
function array_extract_first($source, $keys) {
    try {
        if (!is_array($source)) {
            throw new CoreException(tr('array_extract(): Specified source is not an array'));
        }

        foreach (Arrays::force($keys) as $key) {
            if (!empty($source[$key])) {
                return $source[$key];
            }
        }

    }catch(Exception $e) {
        throw new CoreException('array_extract(): Failed', $e);
    }
}



/*
 * Check the specified array and ensure it has not too many elements (to avoid attack with processing foreach over 2000000 elements, for example)
 */
function array_max($source, $max = 20) {
    if (count($source) > $max) {
        throw new CoreException(tr('array_max(): Specified array has too many elements'), 'arraytoolarge');
    }

    return $source;
}



/*
 *
 */
function array_value_to_keys($source) {
    try {
        if (!is_array($source)) {
            throw new CoreException(tr('array_value_to_keys(): Specified source is not an array'));
        }

        $retval = array();

        foreach ($source as $value) {
            if (!is_scalar($value)) {
                throw new CoreException(tr('array_value_to_keys(): Specified source array contains non scalar values, cannot use non scalar values for the keys'));
            }

            $retval[$value] = $value;
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('array_value_to_keys(): Failed', $e);
    }
}



/*
 *
 */
function array_filtered_merge() {
    try {
        $args = func_get_args();

        if (count($args) < 3) {
            throw new CoreException(tr('array_filtered_merge(): Function requires at least 3 arguments: filter, source, merge, ...'), 'missing_argument');
        }

        $filter = array_shift($args);
        $source = array_shift($args);
        $source = array_remove($source, $filter);
        array_unshift($args, $source);

        return call_user_func_array('array_merge', $args);

    }catch(Exception $e) {
        throw new CoreException('array_filtered_merge(): Failed', $e);
    }
}



/*
 * Return all elements from source1. If the value of one element is null, then try to return it from source2
 */
function array_not_null(&$source1, $source2) {
    try {
        $modified = false;

        foreach ($source1 as $key => $value) {
            if ($value === null) {
                $source1[$key] = isset_get($source2[$key]);
                $modified      = true;
            }
        }

        return $modified;

    }catch(Exception $e) {
        throw new CoreException('array_not_null(): Failed', $e);
    }
}



/*
 *
 */
function array_average($source) {
    try {
        $total = 0;

        foreach ($source as $key => $value) {
            $total += $value;
        }

        return $total / count($source);

    }catch(Exception $e) {
        throw new CoreException('array_average(): Failed', $e);
    }
}



/*
 * Return an array with values ranging from $min to $max
 */
function array_range($min, $max) {
    try {
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

    }catch(Exception $e) {
        throw new CoreException('array_range(): Failed', $e);
    }
}



/*
 * Ensure that all array values
 */
function array_clean($source, $recursive = true) {
    try {
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
                        $value = array_clean($value, $recursive);
                    }

                    break;
            }
        }

        return $source;

    }catch(Exception $e) {
        throw new CoreException('array_clean(): Failed', $e);
    }
}



/*
 * Returns if the specified callback function returns true for all elements
 *
 * Example:
 * array_all(array(1, 2, 3), function($value) { return $value });
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package array
 *
 * @param array $source The array to check
 * @param string $function The function to execute
 * @return boolean Returns true if the specified callback function returned true for all elements in the array, false otherwise
 */
function array_all($source, $function) {
    try {
        foreach ($source as $key => $value) {
            if (!$function($value)) {
                return false;
            }
        }

        return true;

    }catch(Exception $e) {
        throw new CoreException('array_all(): Failed', $e);
    }
}



/*
 * Returns if the specified callback function returns true for all elements
 *
 * Example:
 * array_any(array(0, 1, 2, 3), function($value) { return $value });
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package array
 *
 * @param array $source The array to check
 * @param string $function The function to execute
 * @return boolean Returns true if the specified callback function returned true for any of the elements in the array, false otherwise
 */
function array_any($source, $function) {
    try {
        foreach ($source as $key => $value) {
            if ($function($value)) {
                return true;
            }
        }

        return false;

    }catch(Exception $e) {
        throw new CoreException('array_any(): Failed', $e);
    }
}



/*
 * Returns if the specified callback has duplicate values
 *
 * Example:
 * array_has_duplicates(array(0, 1, 2, 1));
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package array
 *
 * @param array $source The array to check
 * @return boolean Returns true if the specified array contains duplicate values, false otherwise
 */
function array_has_duplicates($source) {
    try {
        return count($items) > count(array_unique($items));

    }catch(Exception $e) {
        throw new CoreException('array_has_duplicates(): Failed', $e);
    }
}



/*
 * Returns all values (with their keys) from the specified array that match the specified regex
 *
 * NOTE: Any non string values will be skipped
 *
 * Example:
 * array_pluck(array('foo', 'bar', 'Frack!', 'test'), '/^F/i');
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package array
 *
 * @param array $source The array to check
 * @return boolean Returns true if the specified array contains duplicate values, false otherwise
 */
function array_pluck($source, $regex) {
    try {
        $retval = array();

        foreach ($source as $key => $value) {
            if (is_string($value)) {
                if (preg_match($regex, $value)) {
                    $retval[$key] = $value;
                }
            }
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('array_pluck(): Failed', $e);
    }
}



/*
 * OBSOLETE
 */



/*
 * Merge multiple arrays together, but overwrite null values
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package array
 *
 * @param array
 * @return array
 */
function array_merge_null() {
    try {
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

    }catch(Exception $e) {
        throw new CoreException('array_merge_null(): Failed', $e);
    }
}



/*
 * Hide the specified keys from the specified array
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package array
 *
 * @param array $source
 * @param mixed $keys
 * @param string $hide
 * @param string $empty
 * @param boolean $recurse
 * @return array
 */
function array_hide($source, $keys = 'GLOBALS,%pass,ssh_key', $hide = '*** HIDDEN ***', $empty = '-', $recurse = true) {
    try {
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
                    if (strstr($source_key, str_replace('%', '', $key))) {
                        $source_value = str_hide($source_value, $hide, $empty);
                    }

                } else {
                    if ($source_key === $key) {
                        $source_value = str_hide($source_value, $hide, $empty);
                    }
                }

                if (is_array($source_value)) {
                    if ($recurse) {
                        $source_value = array_hide($source_value, $keys, $hide, $empty, $recurse);
                    }
                }
            }
        }

        unset($source_value);
        return $source;

    }catch(Exception $e) {
        throw new CoreException('array_merge_null(): Failed', $e);
    }
}



/*
 * Rename the specified old key to the new key
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package array
 * @version 2.7.100: Added function and documentation
 *
 * @param array $source
 * @param string $old_key
 * @param string $new_key
 * @return string The array with the specified key renamed
 */
function array_rename_key($source, $old_key, $new_key) {
    try {
        if (!is_array($source)) {
            throw new CoreException(tr('array_rename_key(): Specified source is not an array'), 'invalid');
        }

        if (!array_key_exists($old_key, $source)) {
            throw new CoreException(tr('array_rename_key(): Specified $old_key does not exist in the specified source array'), 'not-exists');
        }

        $source[$new_key] = $source[$old_key];
        unset($source[$old_key]);

        return $source;

    }catch(Exception $e) {
        throw new CoreException(tr('array_rename_key(): Failed'), $e);
    }
}
