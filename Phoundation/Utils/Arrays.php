<?php

/**
 * Class Arrays
 *
 * This is the standard Phoundation array functionality extension class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package   Phoundation\Utils
 */

declare(strict_types=1);

namespace Phoundation\Utils;

use PDOStatement;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Stringable;
use Throwable;
use UnitEnum;

class Arrays extends Utils
{
    /**
     * If all the specified keys are not in the source array, an exception will be thrown
     *
     * @param array                                              $source
     * @param IteratorInterface|Stringable|array|string|int|null $keys
     * @param string                                             $exception_class
     *
     * @return void
     */
    public static function requiredKeys(array $source, IteratorInterface|Stringable|array|string|int|null $keys, string $exception_class = OutOfBoundsException::class): void
    {
        if (!static::hasAllKeys($source, $keys)) {
            if ($exception_class) {
                throw new $exception_class(tr('The specified array does not contain all required keys ":keys"', [
                    ':keys' => $keys,
                ]));
            }

            static::ensure($source, $keys);
        }
    }


    /**
     * Returns true if the source has all specified keys
     *
     * @param array                                              $source
     * @param IteratorInterface|Stringable|array|string|int|null $keys
     *
     * @return bool
     */
    public static function hasAllKeys(array $source, IteratorInterface|Stringable|array|string|int|null $keys): bool
    {
        foreach (Arrays::force($keys) as $key) {
            if (!array_key_exists($key, $source)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Specified variable may be either string or array, but ensure that its returned as an array.
     *
     * @param string      $source The variable that should be forced to be an array
     * @param string|null $separator
     *
     * @return array The specified $source, but now converted to an array data type if it was not an array yet
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
     * @see Strings::force()
     */
    public static function force(mixed $source, ?string $separator = ','): array
    {
        if (($source === '') or ($source === null)) {
            return [];
        }

        if (is_array($source)) {
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
     * Make sure the specified keys are available on the array
     *
     * @param array|null   $source
     * @param string|array $needles
     * @param mixed        $default_value
     * @param bool         $trim_existing
     *
     * @return void
     */
    public static function ensure(?array &$source, string|array $needles = [], mixed $default_value = null, bool $trim_existing = false): void
    {
        if (!$source) {
            $source = [];
        }

        if ($needles) {
            foreach (Arrays::force($needles) as $needle) {
                if (!$needle) {
                    continue;
                }

                if (array_key_exists($needle, $source)) {
                    if ($trim_existing and is_string($source[$needle])) {
                        // Automatically trim the found value
                        $source[$needle] = trim($source[$needle], (is_bool($trim_existing) ? ' ' : $trim_existing));
                    }

                } else {
                    $source[$needle] = $default_value;
                }
            }
        }
    }


    /**
     * Returns the next key right after specified $key
     *
     * @param array      $source
     * @param string|int $current_key
     * @param bool       $delete
     *
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
     * @param array $source        The source array in which will be searched
     * @param mixed $current_value The value for which will be searched
     * @param bool  $delete        If true, will delete the specified $current_value and found next value
     *
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
            throw OutOfBoundsException::new(tr('Option ":value" does not have a required value specified, see --help or --usage', [
                ':value' => $current_value,
            ]))->makeWarning();
        }

        return null;
    }


    /**
     * Ensure that the specified $params source is an array. If it's a numeric value, convert it to
     * [$numeric_key => $params]. If its string value, convert it to [$string_key => $params]
     *
     * @param mixed       $params  A parameter array
     * @param string|null $string_key
     * @param string|null $numeric_key
     * @param bool|null   $default The default value for the non-selected key
     *
     * @return void
     *
     * @see     Arrays::ensure()
     * @note    The default value for this function for non-assigned values is boolean false, not null. The reason for
     *          this is that many of its dependancies use "false" as "do not use" because "null" would be interpreted
     *          as
     *       "compare to null"
     * @version 2.5.119: Added function and documentation
     */
    public static function params(mixed &$params, string $string_key = null, ?string $numeric_key = null, ?bool $default = false): void
    {
        if (!$params) {
            // The specified value is empty (probably null, "", etc). Convert it into an array containing the numeric and string keys with null values
            $params = [];
        }

        if (is_array($params)) {
            Arrays::ensure($params, [
                $string_key,
                $numeric_key,
            ], $default);

            return;
        }

        if (is_numeric($params)) {
            // The specified value is numeric, convert it to an array with the specified numeric key set having the value $params
            $params = [
                $numeric_key => $params,
                $string_key  => $default,
            ];

            return;
        }

        if (is_string($params)) {
            // The specified value is string, convert it to an array with the specified string key set having the value $params
            $params = [
                $numeric_key => $default,
                $string_key  => $params,
            ];

            return;
        }

        throw new OutOfBoundsException(tr('Specified $params ":params" is invalid. It is an ":datatype" but should be either one of array, integer, or string', [
            ':datatype' => gettype($params),
            ':params'   => (is_resource($params) ? '{php resource}' : $params),
        ]));
    }


    /**
     * Ensures that the specified $key exists in the specified $source.
     *
     * If the specified $key does not exist, it will be initialized with the specified $default value. This function is
     * mostly used with ensuring default values for params arrays. With using this function, you can be sure individual
     * values are each initialized with specific values, if they do not exist yet
     *
     * @param array      $source  The array that is being worked on
     * @param string|int $key     The key that must exist in the $source array
     * @param mixed      $default The default value in case $source[$key] does not exist
     *
     * @return mixed The new value of $source[$key]. This will be either the original value of $source[$key], or the
     *               $default value if $source[$key] did not exist
     * @see     Arrays::ensure()
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
     * @param array        $source
     * @param array|string $keys
     *
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
     * @param bool   $recurse
     *
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
     * @param array  $source
     * @param string $separator
     *
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
     *
     * @return object The array that was created from the specified array
     */
    public static function toObject(array $array): object
    {
        return (object) $array;
    }


    /**
     * Return a random value from the specified array
     *
     * @param array $source
     *
     * @return mixed
     */
    public static function getRandomValue(array $source): mixed
    {
        return $source[array_rand($source)];
    }


    /**
     * Return an array with the specified amount of random key/values from the specified array
     *
     * @param array $source
     * @param int   $count
     *
     * @return mixed
     */
    public static function getRandomValues(array $source, int $count = 1): array
    {
        if ($count === 1) {
            // WTF PHP? I can't have a list of 1 item?
            return Arrays::keepKeys($source, [array_rand($source, 1)]);
        }

        if ($count > count($source)) {
            throw new OutOfBoundsException(tr('The requested number of random values ":requested" is larger than the number of entries ":available" in the specified source array', [
                ':requested'  => $count,
                ':available'  => count($source),
            ]));
        }

        return Arrays::keepKeys($source, array_rand($source, $count));
    }


    /**
     * Implode the array with keys preserved
     *
     * @param IteratorInterface|array $source
     * @param string                  $row_separator
     * @param string                  $key_separator
     * @param string|null             $quote_character Quote string values with the specified quote
     * @param int|null                $options         One of Arrays::FILTER_NULL, Arrays::FILTER_EMPTY,
     *                                                 Arrays::QUOTE_REQUIRED,Arrays::QUOTE_ALWAYS
     *
     * @return string
     */
    public static function implodeWithKeys(IteratorInterface|array $source, string $row_separator, string $key_separator, ?string $quote_character = null, ?int $options = self::FILTER_NULL | self::QUOTE_ALWAYS): string
    {
        // Decode options
        $return            = [];
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
     * Quotes all string entries in the specified source array
     *
     * @note Any non-scalar entries will be ignored
     * @note If the specified source is an array, an array will be returned. If the specified source is an
     *       IteratorInterface object, an IteratorInterface object will be returned
     *
     * @param IteratorInterface|array $source
     * @param string                  $quote
     *
     * @return IteratorInterface|array
     */
    public static function quote(IteratorInterface|array $source, string $quote = "'"): IteratorInterface|array
    {
        if (is_object($source)) {
            $iterator = true;
            $source   = $source->getSource();
        }

        foreach ($source as &$value) {
            if (is_string($value)) {
                $value = Strings::quote($value, $quote);
            }
        }

        unset($value);

        if (isset($iterator)) {
            return Iterator::new($source);
        }

        return $source;
    }


    /**
     * Merge all specified arrays
     *
     * @param array $array1
     * @param array $array2
     * @param...
     *
     * @return array
     * @todo Reimplement with ...$arrays
     */
    public static function mergeFull(): array
    {
        $arguments = static::getArgumentArrays(func_get_args());
        $return    = [];

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
     * Returns the argument arrays ensuring that there are at least 2
     *
     * @param array $arguments
     * @param int   $minimum
     *
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
     * @param mixed                 $source
     * @param string|float|int|null $id
     *
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
            ':count' => $id,
        ]));
    }


    /**
     * Limit the specified array to the specified number of entries
     *
     * @param array $source
     * @param int   $count
     * @param bool  $return_source
     *
     * @return array
     * @todo This is cringy slow at large arrays (also at smaller ones, but eh...), find a more efficient way to do this
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
     * Return an array with the number of values where each value name is $base_value_name# and # is a sequential number
     *
     * @param int        $count
     * @param string|int $base_value_name
     *
     * @return array
     */
    public static function sequentialValues(int $count, string|int $base_value_name): array
    {
        if ($count < 1) {
            throw new OutOfBoundsException(tr('Invalid count specified. Make sure count is numeric, and greater than 0'));
        }

        $return = [];

        for ($i = 0; $i < $count; $i++) {
            $return[] = $base_value_name . $i;
        }

        return $return;
    }


    /**
     * Return the source array with the keys all replaced by sequential values based on base_keyname
     *
     * @param array      $source
     * @param string|int $base_key_name
     * @param bool       $filter_null
     * @param bool       $null_string
     * @param int        $start
     *
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
     * @param IteratorInterface|array                            $source
     * @param IteratorInterface|Stringable|array|string|int|null $needles
     * @param int                                                $flags
     *
     * @return array
     * @todo Rename this to better reflect what it does.
     */
    public static function listKeepKeys(IteratorInterface|array $source, IteratorInterface|Stringable|array|string|int|null $needles, int $flags = Utils::MATCH_FULL | Utils::MATCH_REQUIRE): array
    {
        $needles = Arrays::force($needles);

        if ($source instanceof IteratorInterface) {
            $source = $source->getSource();
        }

        foreach ($source as &$entry) {
            $entry = Arrays::keepMatchingKeys($entry, $needles, $flags);
        }

        unset($entry);

        return $source;
    }


    /**
     * Return the source array with the specified values kept, all else removed.
     *
     * @param IteratorInterface|array $source
     * @param string|array            $needles
     * @param string|null             $column
     * @param int                     $flags
     *
     * @todo Rename this to better reflect what it does.
     * @return array
     */
    public static function listKeepValues(IteratorInterface|array $source, string|array $needles, ?string $column = null, int $flags = Utils::MATCH_FULL | Utils::MATCH_REQUIRE): array
    {
        $needles = Arrays::force($needles);

        if ($source instanceof IteratorInterface) {
            $source = $source->getSource();
        }

        foreach ($source as &$entry) {
            $entry = Arrays::keepMatchingValues($entry, $needles, $flags, $column);
        }

        unset($entry);

        return $source;
    }


    /**
     * Return the source array with the specified keys kept, all else removed.
     *
     * @param IteratorInterface|array                            $source
     * @param IteratorInterface|Stringable|array|string|int|null $needles
     * @param bool                                               $strict
     *
     * @return array
     */
    public static function keepKeys(IteratorInterface|array $source, IteratorInterface|Stringable|array|string|int|null $needles, bool $strict = false): array
    {
        $return  = [];
        $needles = Arrays::force($needles);

        foreach ($source as $key => $value) {
            if (in_array($key, $needles, $strict)) {
                $return[$key] = $value;
            }
        }

        return $return;
    }


    /**
     * Return the source array with the specified keys removed.
     *
     * @param IteratorInterface|array                            $source
     * @param IteratorInterface|Stringable|array|string|int|null $needles
     * @param bool                                               $strict
     *
     * @return array
     */
    public static function removeKeys(IteratorInterface|array $source, IteratorInterface|Stringable|array|string|int|null $needles, bool $strict = false): array
    {
        $return  = [];
        $needles = Arrays::force($needles);

        foreach ($source as $key => $value) {
            if (!in_array($key, $needles, $strict)) {
                $return[$key] = $value;
            }
        }

        return $return;
    }


    /**
     * Return the source array with the specified values removed.
     *
     * @param IteratorInterface|array             $source
     * @param IteratorInterface|array|string|null $needles
     * @param string|null                         $column
     * @param bool                                $strict
     *
     * @return array
     */
    public static function keepValues(IteratorInterface|array $source, IteratorInterface|array|string|null $needles, ?string $column = null, bool $strict = false): array
    {
        $return  = [];
        $needles = Arrays::force($needles);

        foreach ($source as $key => $value) {
            $value = static::getStringValue($value, $column);

            if (in_array($value, $needles, $strict)) {
                $return[$key] = $value;
            }
        }

        return $return;
    }


    /**
     * Return the source array with the specified values removed.
     *
     * @param IteratorInterface|array             $source
     * @param IteratorInterface|array|string|null $needles
     * @param string|null                         $column
     * @param bool                                $strict
     *
     * @return array
     */
    public static function removeValues(IteratorInterface|array $source, IteratorInterface|array|string|null $needles, ?string $column = null, bool $strict = false): array
    {
        $return  = [];
        $needles = Arrays::force($needles);

        foreach ($source as $key => $value) {
            $test = static::getStringValue($value, $column);

            if (!in_array($test, $needles, $strict)) {
                $return[$key] = $value;
            }
        }

        return $return;
    }


    /**
     * Return the source array with the specified keys kept, all else removed.
     *
     * @param IteratorInterface|array                            $source
     * @param IteratorInterface|Stringable|array|string|int|null $needles
     * @param int                                                $flags
     *
     * @return array
     */
    public static function keepMatchingKeys(IteratorInterface|array $source, IteratorInterface|Stringable|array|string|int|null $needles, int $flags = Utils::MATCH_FULL | Utils::MATCH_REQUIRE): array
    {
        return static::matchKeys(Utils::MATCH_ACTION_RETURN_VALUES, $source, $needles, $flags);
    }


    /**
     * Return the source array with the specified keys removed.
     *
     * @param IteratorInterface|array                            $source
     * @param IteratorInterface|Stringable|array|string|int|null $needles
     * @param int                                                $flags
     *
     * @return array
     */
    public static function removeMatchingKeys(IteratorInterface|array $source, IteratorInterface|Stringable|array|string|int|null $needles, int $flags = Utils::MATCH_FULL | Utils::MATCH_REQUIRE): array
    {
        return static::matchKeys(Utils::MATCH_ACTION_RETURN_NOT_VALUES, $source, $needles, $flags);
    }


    /**
     * Return the source array with the specified values kept, all else removed.
     *
     * @param IteratorInterface|array                            $source  The source array on which to work
     * @param IteratorInterface|Stringable|array|string|int|null $needles The needles to keep
     * @param int                                                $flags
     * @param string|null                                        $column
     *
     * @return array
     * @see EnumMatchMode
     */
    public static function keepMatchingValues(IteratorInterface|array $source, IteratorInterface|Stringable|array|string|int|null $needles, int $flags = Utils::MATCH_FULL | Utils::MATCH_REQUIRE, ?string $column = null): array
    {
        return static::matchValues(Utils::MATCH_ACTION_RETURN_VALUES, $source, $needles, $flags, $column);
    }


    /**
     * Return the source array with the specified values removed.
     *
     * @param IteratorInterface|array             $source
     * @param IteratorInterface|array|string|null $needles
     * @param string|null                         $column
     * @param int                                 $flags
     *
     * @return array
     */
    public static function removeMatchingValues(IteratorInterface|array $source, IteratorInterface|array|string|null $needles, int $flags = Utils::MATCH_FULL | Utils::MATCH_REQUIRE, ?string $column = null): array
    {
        return static::matchValues(Utils::MATCH_ACTION_RETURN_NOT_VALUES, $source, $needles, $flags, $column);
    }


    /**
     * Returns a list with all the values that match the specified value
     *
     * @param IteratorInterface|array                        $source
     * @param ArrayableInterface|array|string|float|int|null $needles
     * @param int                                            $flags
     * @param string|null                                    $column
     *
     * @return array
     */
    public static function keepMatchingValuesStartingWith(IteratorInterface|array $source, ArrayableInterface|array|string|float|int|null $needles, int $flags = Utils::MATCH_CASE_INSENSITIVE | Utils::MATCH_ALL | Utils::MATCH_STARTS_WITH, ?string $column = null): array
    {
        return static::keepMatchingValues($source, $needles, $flags, $column);
    }


    /**
     * Returns a list with all the values that match the specified value
     *
     * @param IteratorInterface|array                            $source
     * @param IteratorInterface|Stringable|array|string|int|null $needles
     * @param int                                                $flags
     *
     * @return array
     */
    public static function keepMatchingKeysStartingWith(IteratorInterface|array $source, IteratorInterface|Stringable|array|string|int|null $needles, int $flags = Utils::MATCH_CASE_INSENSITIVE | Utils::MATCH_ALL | Utils::MATCH_STARTS_WITH): array
    {
        return static::keepMatchingKeys($source, $needles, $flags);
    }


    /**
     * Returns true if any of the keys in the specified source array matches any of the specified needles
     *
     * @param IteratorInterface|array                            $source
     * @param IteratorInterface|Stringable|array|string|int|null $needles
     * @param int                                                $flags
     *
     * @return bool
     */
    public static function hasAnyMatchingKeys(IteratorInterface|array $source, IteratorInterface|Stringable|array|string|int|null $needles, int $flags = Utils::MATCH_FULL | Utils::MATCH_REQUIRE): bool
    {
        return (bool) static::matchKeys(Utils::MATCH_ACTION_RETURN_KEYS, $source, $needles, $flags);
    }


    /**
     * Removes all entries from the byref source array in the specified $keys and returns those in the result array
     *
     * @param array        $source
     * @param string|array $keys
     *
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
     * Prefix all keys in this array with the specified prefix
     *
     * @param array      $source
     * @param string|int $prefix
     * @param bool       $auto
     *
     * @return array
     */
    public static function prefix(array $source, string|int $prefix, bool $auto = false): array
    {
        $count  = 0;
        $return = [];

        foreach ($source as $key => $value) {
            if ($auto) {
                $return[$prefix . $count++] = $value;

            } else {
                $return[$prefix . $key] = $value;
            }
        }

        return $return;
    }


    /**
     * Return the array keys that has a STRING value that contains the specified keyword
     *
     * NOTE: Non string values will be quietly ignored!
     *
     * @param array      $array
     * @param string|int $keyword
     *
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
     *
     * @return array
     */
    public static function copyClean(array $target, array $source, array $skip = ['id']): array
    {
        foreach ($source as $key => $value) {
            if (in_array($key, $skip)) {
                continue;
            }

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
     * @param array      $source
     * @param string|int $column
     *
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
     *
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
     * Check the specified array and ensure it has not too many elements (to avoid attack with processing foreach over
     * 2000000 elements, for example)
     *
     * @param array $source
     * @param int   $max
     *
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
     *
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
        $source    = Arrays::removeKeys($source, $filters);

        array_unshift($arguments, $source);

        return call_user_func_array('array_merge', $arguments);
    }


    /**
     * Return all elements from source1. If the value of one element is null, then try to return it from source2
     *
     * @note If a key was found in $source1 that was null, and that key does not exist, the $default value will be
     *       assigned instead
     *
     * @param array $source1
     * @param array $source2
     * @param mixed $default
     *
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
     * @param bool  $ignore_non_numbers
     *
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
     * @param int   $min
     * @param int   $max
     * @param mixed $value
     *
     * @return array
     */
    public static function range(int $min, int $max, mixed $value): array
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

        for ($i = $min; $i <= $max; $i++) {
            $return[$i] = $value;
        }

        return $return;
    }


    /**
     * Will replace each value in the source array with the output from the given callback function
     *
     * The callback function will contain arguments mixed $key, mixed $value
     *
     * Example:
     * Arrays::each(array(1, 2, 3), function($key, $value) { return $value + 1; });
     *
     * @param array    $source   The array to check
     * @param callable $function The function to execute
     * @param bool     $unset_null_result
     *
     * @return array
     */
    public static function replaceValuesWithCallbackReturn(array $source, callable $function, bool $unset_null_result = true): array
    {
        foreach ($source as $key => &$value) {
            $value = $function($key, $value);

            if (($value === null) and $unset_null_result) {
                unset($source[$key]);
            }
        }

        unset($value);

        return $source;
    }


    /**
     * Returns if the specified callback function returns true for all elements
     *
     * Example:
     * Arrays::allExecuteTrue(array(1, 2, 3), function($value) { return $value; });
     *
     * @param array    $source   The array to check
     * @param callable $function The function to execute
     *
     * @return boolean Returns true if the specified callback function returned true for all elements in the array,
     *                 false otherwise
     */
    public static function allExecuteReturnTrue(array $source, callable $function): bool
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
     * Arrays::anyExecuteTrue(array(0, 1, 2, 3), function($value) { return $value; });
     *
     * @param array    $source   The array to check
     * @param callable $function The function to execute
     *
     * @return boolean Returns true if the specified callback function returned true for any of the elements in the
     *                 array, false otherwise
     */
    public static function anyExecuteReturnTrue(array $source, callable $function): bool
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
     * Arrays::hasDuplicates(array(0, 1, 2, 1));
     *
     * @param array $source The array to check
     *
     * @return boolean Returns true if the specified array contains duplicate values, false otherwise
     */
    public static function hasDuplicates(array $source): bool
    {
        return (bool) Arrays::countDuplicates($source);
    }


    /**
     * Returns if the specified callback has duplicate values
     *
     * Example:
     * Arrays::countDuplicates(array(0, 1, 2, 1));
     *
     * @param array $source The array to check
     *
     * @return int Returns the number of duplicate entries in the specified source array
     */
    public static function countDuplicates(array $source): int
    {
        return count($source) - count(array_unique($source));
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
     *
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
     *
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
     * Hide the specified sensitivekey values from the specified array
     *
     * @param array|null   $source
     * @param string|array $keys
     * @param string       $hide
     * @param string       $empty
     * @param boolean      $recurse
     *
     * @return array|null
     */
    public static function hideSensitive(?array $source, string|array $keys = ['GLOBALS', '%pass', 'ssh_key'], string $hide = '*** HIDDEN ***', string $empty = '-', bool $recurse = true): ?array
    {
        static::requireArrayOrNull($source);

        // Ensure that the keys we need to hide are in array format
        $keys = Arrays::force($keys);

        foreach ($source as $source_key => &$source_value) {
            foreach ($keys as $key) {
                if (is_array($source_value)) {
                    if ($recurse) {
                        $source_value = Arrays::hideSensitive($source_value, $keys, $hide, $empty, $recurse);

                    } else {
                        // If we don't recurse, we'll hide the entire subarray
                        $source_value = Arrays::hideSensitive($source_value, $hide, $empty);
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
     * @param array      $source
     * @param string|int $old_key
     * @param string|int $new_key
     *
     * @return array The array with the specified key renamed
     * @version 2.7.100: Added function and documentation
     *
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
     * @param array $source The source array from which the first value must be returned
     *
     * @return mixed The first value of the specified source array
     * @see     Arrays::lastValue()
     * @version 1.27.0: Added function and documentation
     *
     */
    public static function firstValue(array $source): mixed
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
     * @param array $source The source array from which the last value must be returned
     *
     * @return mixed The last value of the specified source array
     * @see     Arrays::firstValue()
     * @version 1.27.0: Added function and documentation
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
     * Return the specified source array, making sure that the specified keys are available
     *
     * @param array|null   $source
     * @param string|array $needles
     * @param mixed        $default_value
     * @param bool         $trim_existing
     *
     * @return array
     */
    public static function ensureReturn(?array $source, string|array $needles = [], mixed $default_value = null, bool $trim_existing = false): array
    {
        $return = [];

        if ($needles) {
            foreach (Arrays::force($needles) as $needle) {
                if (!$needle) {
                    continue;
                }

                if (array_key_exists($needle, $source)) {
                    if ($trim_existing and is_string($source[$needle])) {
                        // Automatically trim the found value
                        $return[$needle] = trim($source[$needle], (is_bool($trim_existing) ? ' ' : $trim_existing));
                    }

                } else {
                    $return[$needle] = $default_value;
                }
            }
        }

        return $return;
    }


    /**
     * Ensures the source is split into an array.
     *
     * If specified source is an array, the method will assume it has already been split
     *
     * @param array|Stringable|string $source
     * @param int                     $length
     *
     * @return array
     */
    public static function forceSplit(array|Stringable|string $source, int $length = 1): array
    {
        if (is_array($source)) {
            return $source;
        }

        return str_split($source, $length);
    }


    /**
     * Recursively trim all strings in the specified array tree
     *
     * @param array $source
     * @param bool  $recurse
     *
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
     * @param array       $source
     * @param int         $add_extra
     * @param string|null $add_key
     * @param bool        $check_column_key_length
     *
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
     *
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
     * @param array            $source
     * @param string|float|int $value
     *
     * @return string|int|null NULL if the specified value didn't exist, the array key if it did
     */
    public static function unsetValue(array &$source, string|float|int $value): string|int|null
    {
        $key = array_search($value, $source);

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
     *
     * @return array
     * @todo Re-Implement with ...$arrays
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
                                    ':key' => $key,
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
                        ':value'  => $value,
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
                        ':value'  => $value,
                    ]));
                }

                $target[$key] += $value;
            }
        }

        return $target;
    }


    /**
     * Returns an array with "remove" and "add" section to indicate required actions to change $source1 into $source2
     *
     * @param IteratorInterface|array $source1
     * @param IteratorInterface|array $source2
     * @param bool                    $keep If true, the result array will also contain a "keep" column with entries
     *                                      that exists in both and should not be added nor deleted (but perhaps
     *                                      updated, for example)
     *
     * @return array
     */
    public static function valueDiff(IteratorInterface|array $source1, IteratorInterface|array $source2, bool $keep = false): array
    {
        $return = [
            'add'    => [],
            'delete' => [],
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
     *
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
     * @param array            $source
     * @param string|float|int $value
     *
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
     * @param array            $source
     * @param string|float|int $value
     * @param string|float|int $replace
     *
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
     * @param array            $source
     * @param string|float|int $key
     * @param mixed            $value
     *
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
     * @param array  $source
     * @param int    $max_size
     * @param string $fill
     * @param string $method
     * @param bool   $on_word
     *
     * @return array
     */
    public static function truncate(array $source, int $max_size, string $fill = ' ... ', string $method = 'right', bool $on_word = false): array
    {
        foreach ($source as $key => &$value) {
            if (is_string($value)) {
                $value = Strings::truncate($value, $max_size, $fill, $method, $on_word);

            } elseif (!is_scalar($value)) {
                // There is no support (yet) for non-scalar values, drop the value completely
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
     * @param array  $format
     *
     * @return array
     */
    public static function format(string $source, array $format): array
    {
        $return = [];
        $pos    = 0;

        foreach ($format as $key => $size) {
            $return[$key] = substr($source, $pos, $size);
            $pos          += $size;
        }

        return $return;
    }


    /**
     * Detects and returns a format to parse table strings using Arrays::format()
     *
     * @param string $source
     * @param string $separator
     * @param bool   $lower_keys
     *
     * @return array
     */
    public static function detectFormat(string $source, string $separator = ' ', bool $lower_keys = true): array
    {
        if (strlen($separator) !== 1) {
            throw new OutOfBoundsException(tr('Invalid separator ":separator" specified, it should be a single byte character', [
                ':separator' => $separator,
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
                        $last         = $pos;
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
                        $last         = $pos;
                        $key          = null;
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
     *
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
     *
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
     * @param array       $source
     * @param string|null $key
     * @param bool        $exception
     *
     * @return int
     */
    public static function getShortestValueLength(array $source, ?string $key = null, bool $exception = false): int
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
                if ($exception) {
                    throw new OutOfBoundsException(tr('Specified source data contains non scalar value ":value"', [
                        ':value' => $value,
                    ]));
                }

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
     * @param array       $source
     * @param string|null $key
     * @param bool        $exception
     *
     * @return int
     */
    public static function getLongestValueLength(array $source, ?string $key = null, bool $exception = false): int
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
                throw new OutOfBoundsException(tr('Specified source data contains non scalar value ":value"', [
                    ':value' => $value,
                ]));
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
     *
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
     * @param array       $source
     * @param string|null $prefix
     * @param bool        $keep_prefix
     *
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
     * @note    The id in the specified keys must be the same
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
                $prefix = Strings::until($key, '_', needle_required: true);

                if (!$prefix) {
                    // No class found, continue to the next entry
                    $prefix = null;
                    continue;
                }

                $prefix .= '_';
            }

            $key = Strings::from($key, $prefix, needle_required: true);

            if (!$key) {
                // This key didn't have the specified class
                continue;
            }

            $return[$key_prefix . $key] = $value;
        }

        return $return;
    }


    /**
     * Return all array parts until (but without) the specified key
     *
     * @param array      $source
     * @param string|int $until_key
     * @param bool       $delete
     *
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
     * Return all array parts from (but without) the specified key
     *
     * @param array      $source
     * @param string|int $from_key
     * @param bool       $delete
     * @param bool       $skip
     *
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
     * Extracts the specified key from the specified array and returns its value
     *
     * @param array  $source
     * @param string $key
     *
     * @return mixed
     */
    public static function extractKey(array &$source, string $key): mixed
    {
        try {
            $return = $source[$key];

        } catch (Throwable) {
            throw new OutOfBoundsException(tr('Key ":key" does not exist in the specified source array', [
                ':key' => $key,
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
     * @param array $source
     *
     * @return string|float|int|null
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
     */
    public static function findPrefix(array $source): string|float|int|null
    {
        foreach ($source as $key => $value) {
            $prefix = (int) Strings::until($key, '_', needle_required: true);

            if ($prefix) {
                return $prefix;
            }
        }

        return null;
    }


    /**
     * Returns true if any of the array values matches the specified needles using the specified match options
     *
     * @param array                                              $haystack
     * @param IteratorInterface|Stringable|array|string|int|null $needles
     * @param int                                                $flags
     *
     * @return bool
     */
    public static function keysMatch(array $haystack, IteratorInterface|Stringable|array|string|int|null $needles, int $flags = Utils::MATCH_CASE_INSENSITIVE | Utils::MATCH_ALL | Utils::MATCH_CONTAINS | Utils::MATCH_RECURSE): bool
    {
        return (bool) static::keepMatchingKeys($haystack, $needles, $flags);
    }


    /**
     * Returns true if any of the array values matches the specified needles using the specified match options
     *
     * @param IteratorInterface|array             $haystack
     * @param IteratorInterface|array|string|null $needles
     * @param string|null                         $column
     * @param int                                 $flags
     *
     * @return bool
     */
    public static function valuesMatch(IteratorInterface|array $haystack, IteratorInterface|array|string|null $needles, ?string $column, int $flags = Utils::MATCH_FULL | Utils::MATCH_REQUIRE): bool
    {
        return (bool) static::keepMatchingValues($haystack, $needles, $flags, $column);
    }


    /**
     * Returns the highest key found in the given source
     *
     * @param array $source
     *
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
     * Returns an array with all keys lowercase strings
     *
     * @param array $source
     *
     * @return array
     */
    public static function lowercaseKeys(array $source): array
    {
        $return = [];

        foreach ($source as $key => $value) {
            if (is_string($key)) {
                $return[strtolower($key)] = $value;

            } else {
                $return[$key] = $value;
            }
        }

        return $return;
    }


    /**
     * Returns an array with all keys uppercase strings
     *
     * @param array $source
     *
     * @return array
     */
    public static function uppercaseKeys(array $source): array
    {
        $return = [];

        foreach ($source as $key => $value) {
            if (is_string($key)) {
                $return[strtoupper($key)] = $value;

            } else {
                $return[$key] = $value;
            }
        }

        return $return;
    }


    /**
     * Returns an array with all values lowercase strings
     *
     * @note Non scalar values (except NULL) will cause OutOfBoundsException
     * @note NULL values will remain NULL
     *
     * @param array $source
     *
     * @return array
     */
    public static function lowercaseValues(array $source): array
    {
        foreach ($source as &$value) {
            if (!is_scalar($value)) {
                if ($value) {
                    throw OutOfBoundsException::new(tr('Cannot lowercase the specified array, the value ":value" is not scalar', [
                        ':value' => $value,
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
     * Returns an array with all the values uppercase strings
     *
     * @note Non scalar values (except NULL) will cause OutOfBoundsException
     * @note NULL values will remain NULL
     *
     * @param array $source
     *
     * @return array
     */
    public static function uppercaseValues(array $source): array
    {
        foreach ($source as &$value) {
            if (!is_scalar($value)) {
                if ($value) {
                    throw OutOfBoundsException::new(tr('Cannot lowercase the specified array, the value ":value" is not scalar', [
                        ':value' => $value,
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
     *
     * @param array $source
     * @param array $rename
     *
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


    /**
     * Sorts the specified source array by value length
     *
     * @note This method will treat all source values as casted string
     *
     * @param array $source
     * @param bool  $descending
     *
     * @return array
     */
    public static function sortByValueLength(array $source, bool $descending = true): array
    {
        if ($descending) {
            $callback = function ($a, $b) use ($descending) {
                $a = strlen((string) $a);
                $b = strlen((string) $b);

                if ($a < $b) {
                    return 1;
                }

                if ($a > $b) {
                    return -1;
                }

                return 0;
            };

        } else {
            $callback = function ($a, $b) use ($descending) {
                $a = strlen((string) $a);
                $b = strlen((string) $b);

                if ($a < $b) {
                    return -1;
                }

                if ($a > $b) {
                    return 1;
                }

                return 0;
            };
        }

        uasort($source, $callback);

        return $source;
    }


    /**
     * Returns an array with the specified keys which all have the specified value
     *
     * @param IteratorInterface|Stringable|array|string|int|null $keys
     * @param mixed                                              $value
     * @param array                                              $source
     *
     * @return array
     */
    public static function setKeys(IteratorInterface|Stringable|array|string|int|null $keys, mixed $value, array $source = []): array
    {
        foreach (Arrays::force($keys) as $key) {
            $source[$key] = $value;
        }

        return $source;
    }


    /**
     * Ensure that all specified array keys have the specified value
     *
     * If $keys is specified then only the specified keys will be updated
     *
     * @param IteratorInterface|array             $source
     * @param mixed                               $value
     * @param IteratorInterface|array|string|null $keys
     * @return array
     */
    public static function setValues(IteratorInterface|array $source, mixed $value, IteratorInterface|array|string|null $keys = null): array
    {
        $source = Arrays::force($source);

        if ($keys) {
            $keys = Arrays::force($keys);
        }

        foreach ($source as $key => $current_value) {
            if (($keys === null) or (array_key_exists($key, $keys))) {
                $source[$key] = $value;
            }
        }

        return $source;
    }


    /**
     * Returns the positional offset of the position in front of the specified key in the specified array
     *
     * @param array            $source
     * @param string|float|int $key
     * @param bool             $exception
     *
     * @return int|null
     */
    public static function getKeyPreviousOffset(array $source, string|float|int $key, bool $exception = true): ?int
    {
        $return = array_search($key, array_keys($source));

        if ($return === false) {
            if ($exception) {
                throw new OutOfBoundsException(tr('Cannot return offset for key ":key", it does not exist in the source array', [
                    ':key' => $key,
                ]));
            }

            return null;
        }

        if ($return === 0) {
            return 0;
        }

        return ((int) $return) - 1;
    }


    /**
     * Same as Arrays::splice() but the offset is an array key
     *
     * @param array                   $source
     * @param string|float|int        $key
     * @param int|null                $length
     * @param IteratorInterface|array $replacement
     * @param bool                    $after
     *
     * @return array
     */
    public static function spliceByKey(array &$source, string|float|int $key, ?int $length = null, IteratorInterface|array $replacement = [], bool $after = false): array
    {
        if ($after) {
            $offset = static::getKeyNextOffset($source, $key);

        } else {
            $offset = static::getKeyOffset($source, $key);
        }

        return static::splice($source, $offset, $length, $replacement);
    }


    /**
     * Returns the positional offset of the position after the specified key in the specified array
     *
     * @param array            $source
     * @param string|float|int $key
     * @param bool             $exception
     *
     * @return int|null
     */
    public static function getKeyNextOffset(array $source, string|float|int $key, bool $exception = true): ?int
    {
        $return = array_search($key, array_keys($source));

        if ($return === false) {
            if ($exception) {
                throw new OutOfBoundsException(tr('Cannot return offset for key ":key", it does not exist in the source array', [
                    ':key' => $key,
                ]));
            }

            return null;
        }

        if ($return >= (count($source))) {
            return count($source);
        }

        return ((int) $return) + 1;
    }


    /**
     * Returns the positional offset of the specified key in the specified array
     *
     * @param array            $source
     * @param string|float|int $key
     * @param bool             $exception
     *
     * @return int|null
     */
    public static function getKeyOffset(array $source, string|float|int $key, bool $exception = true): ?int
    {
        $return = array_search($key, array_keys($source));

        if ($return === false) {
            if ($exception) {
                throw new OutOfBoundsException(tr('Cannot return offset for key ":key", it does not exist in the source array', [
                    ':key' => $key,
                ]));
            }

            return null;
        }

        return (int) $return;
    }


    /**
     * Same as array_splice() but the source and replacement array keys are preserved
     *
     * @param array                   $source
     * @param int                     $offset
     * @param int|null                $length
     * @param IteratorInterface|array $replacement
     *
     * @return array
     */
    public static function splice(array &$source, int $offset, ?int $length = null, IteratorInterface|array $replacement = []): array
    {
        // Normalize offset
        if ($offset < 0) {
            $offset = count($source) + $offset;
        }

        // Normalize length
        if ($length === null) {
            $length = count($source) - $offset;

        } elseif ($length < 0) {
            $length = count($source) + $length - $offset;
        }

        // Ensure replacement is array
        if ($replacement instanceof IteratorInterface) {
            $replacement = $replacement->getSource();
        }

        // Manipulate each part and merge parts, allowing the latter overrides the former
        $before  = array_slice($source, 0, $offset, true);
        $removed = array_slice($source, $offset, $length, true);
        $after   = array_slice($source, $offset + $length, null, true);
        $source  = array_replace($before, (array) $replacement, $after);

        return $removed;
    }


    /**
     * Converts the given source to an array with key=value entries, optionally quoted
     *
     * @param array $source
     * @param bool  $quote
     *
     * @return array
     */
    public static function convertToKeyIsValue(array $source, bool $quote = true): array
    {
        $return = [];

        foreach ($source as $key => $value) {
            if ($quote) {
                $value = Strings::quote($value);
            }

            $return[] = $key . '=' . $value;
        }

        return $return;
    }


    /**
     * Converts the given source string using the specified format array
     *
     * The format array should look like
     * [
     *   key => size,
     *   key => size,
     * ]
     *
     * The string will be split up to
     * [
     *    key => value,
     *    key => value,
     *  ]
     *
     * Where each value is the size cut string from the source
     *
     * @param string $source
     * @param array  $format
     * @param bool   $trim
     *
     * @return array
     */
    public static function convertStringWithSizeFormat(string $source, array $format, bool $trim = true): array
    {
        $return   = [];
        $position = 0;

        foreach ($format as $key => $size) {
            try {
                $return[$key] = substr($source, $position, $size);

                if ($trim) {
                    $return[$key] = trim($return[$key]);
                }

                $position += $size;

            } catch (Throwable $e) {
                if (!is_numeric($size)) {
                    throw new OutOfBoundsException(tr('Invalid conversion format entry ":key" with size ":size" encountered, the size must be an integer', [
                        ':key'  => $key,
                        ':size' => $size,
                    ]));
                }

                throw $e;
            }
        }

        return $return;
    }


    /**
     * Converts the given source string using the specified format array
     *
     * The format array should look like
     * [
     *   key => separator,
     *   key => separator,
     * ]
     *
     * The string will be split up to
     * [
     *    key => value,
     *    key => value,
     *  ]
     *
     * Where each value is the size cut string from the source
     *
     * @param string $source
     * @param array  $format
     * @param bool   $trim
     *
     * @return array
     */
    public static function convertStringWithSeparatorFormat(string $source, array $format, bool $trim = true): array
    {
        $return = [];
        $source = trim($source);

        foreach ($format as $key => $separator) {
            if (!$source) {
                throw new OutOfBoundsException(tr('Failed to parse line "" with format key ":key", the source line has reached its end', [
                    ':key' => $key,
                ]));
            }

            if ($separator) {
                $return[$key] = Strings::until($source, $separator);
                $source       = trim(Strings::from($source, $separator));

            } else {
                // Take the entire string
                $return = [$source];
                $source = '';
            }

            if ($trim) {
                $return[$key] = trim($return[$key]);
            }
        }

        return $return;
    }


    /**
     * Converts the given source string using the specified format array
     *
     * The format array should look like
     * [
     *   key => size,
     *   key => size,
     * ]
     *
     * The string will be split up to
     * [
     *    key => value,
     *    key => value,
     *  ]
     *
     * Where each value is the size cut string from the source
     *
     * @param string $source
     * @param array  $format
     * @param bool   $trim
     *
     * @return array
     */
    public static function convertStringWithFixedWidths(string $source, array $format, bool $trim = true): array
    {
        throw new UnderConstructionException('The method Arrays::getFixedWidthFromSeparatorFormat() needs to first be fully tested!');
        $return = [];
        $source = trim($source);

        foreach ($format as $key => $width) {
            if (!$source) {
                throw new OutOfBoundsException(tr('Failed to parse line ":line" with format key ":key", the source line has reached its end', [
                    ':key'  => $key,
                    ':line' => $source
                ]));
            }

            if ($width) {
                $return[$key] = substr($source, 0, $width);
                $source       = substr($source, $width);

            } else {
                // Take the entire string
                $return = [$source];
                $source = '';
            }

            if ($trim) {
                $return[$key] = trim($return[$key]);
            }
        }

        return $return;
    }


    /**
     * Detects and returns the fixed widths in the specified row entry using the specified format
     *
     * The format array should look like
     * [
     *   key => separator,
     *   key => separator,
     * ]
     *
     * The return array will contain the width of each column
     * [
     *   key => size,
     *   key => size,
     * ]
     *
     * @param string $source
     * @param array  $format
     *
     * @return array
     */
    public static function getFixedWidthFromSeparatorFormat(string $source, array $format): array
    {
        throw new UnderConstructionException('The method Arrays::getFixedWidthFromSeparatorFormat() needs to first be fully tested!');
        $values   = Arrays::convertStringWithSeparatorFormat($source, $format);
        $prev_pos = 0;
        $pos      = 0;
        $return   = [];

        foreach ($values as $key => $value) {
            $pos          = strpos($source, $value, $pos);
            $return[$key] = $pos - $prev_pos;
            $prev_pos     = $pos;
            $pos          = strpos($source, $format[$key], $pos);

            if ($pos === false) {
                // No more separators found
                break;
            }
        }

        $return[$key] = 0; // AKA The rest of the string
        return $return;
    }


    /**
     * Sets the internal source directly from the specified CSV string line table
     *
     * @param IteratorInterface|PDOStatement|array|string $source
     * @param array                                       $format
     * @param string|null                                 $use_key
     * @param int                                         $skip
     * @param bool                                        $assume_fixed_width
     *
     * @return array
     */
    public static function fromCsvSource(IteratorInterface|PDOStatement|array|string $source, array $format, ?string $use_key = null, int $skip = 1, bool $assume_fixed_width = false): array
    {
        $width  = null;
        $return = [];
        $source = static::extractSourceArray($source);

        foreach ($source as $line) {
            if ($skip > 0) {
                // Continue the skip lines
                $skip--;
                continue;
            }

            if ($assume_fixed_width) {
                // Parse the entries based off the detected fixed width from the first entry
                if (empty($widths)) {
                    $widths = Arrays::getFixedWidthFromSeparatorFormat($line, $format);
                }

                $value = Arrays::convertStringWithFixedWidths($line, $widths);

            } else {
                // Parse purely on the specified format
                $value = Arrays::convertStringWithSeparatorFormat($line, $format);
            }

            if ($use_key) {
                try {
                    $return[$value[$use_key]] = $value;

                } catch (Throwable) {
                    throw new OutOfBoundsException(tr('The specified $use_key ":use_key" was not found in the source line ":line"', [
                        ':use_key' => $use_key,
                        ':line'    => $value[$use_key],
                    ]));
                }

            } else {
                $return[] = $value;
            }
        }

        return $return;
    }


    /**
     * Sets the internal source directly from the specified static size text line table
     *
     * @param IteratorInterface|PDOStatement|array|string $source
     * @param array                                       $format
     * @param string|null                                 $use_key
     * @param int                                         $skip
     *
     * @return array
     */
    public static function fromTableSource(IteratorInterface|PDOStatement|array|string $source, array $format, ?string $use_key = null, int $skip = 1): array
    {
        $return = [];
        $source = static::extractSourceArray($source);

        foreach ($source as $line) {
            if ($skip > 0) {
                // Continue the skip lines
                $skip--;
                continue;
            }

            $value = Arrays::convertStringWithSizeFormat($line, $format);

            if ($use_key) {
                try {
                    $return[$value[$use_key]] = $value;

                } catch (Throwable) {
                    throw new OutOfBoundsException(tr('The specified $use_key ":use_key" was not found in the source line ":line"', [
                        ':use_key' => $use_key,
                        ':line'    => $value[$use_key],
                    ]));
                }

            } else {
                $return[] = $value;
            }
        }

        return $return;
    }


    /**
     * Extracts the source array from the specified source
     *
     * @param IteratorInterface|\PDOStatement|array|string|null $source
     * @param array|null                                        $execute
     * @return array
     */
    public static function extractSourceArray(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): array
    {
        if (is_array($source)) {
            // This is a standard array, load it into the source
            return $source;
        }

        if (is_string($source)) {
            // This must be a query. Execute it and get a list of all entries from the result
            return sql()->list($source, $execute);
        }

        if ($source instanceof PDOStatement) {
            // Get a list of all entries from the specified query PDOStatement
            return sql()->list($source);
        }

        if ($source instanceof IteratorInterface) {
            // This is another iterator object, get the data from it
            return $source->getSource();
        }

        // NULL was specified
        return [];
    }


    /**
     * Remove empty values from the given array
     *
     * @param array $source The array to be filtered
     *
     * @return array The array with empty values removed
     */
    public static function filterEmpty(array $source): array
    {
        $return = [];

        foreach ($source as $key => $value) {
            if ($value) {
                $return[$key] = $value;
            }
        }

        return $return;
    }


    /**
     * Returns the source array with all the empty values removed
     *
     * @param array $source
     * @return array
     */
    public static function removeEmptyValues(array $source): array
    {
        $return = [];

        foreach ($source as $key => $value) {
            if ($value) {
                $return[$key] = $value;
            }
        }

        return $return;
    }
}
