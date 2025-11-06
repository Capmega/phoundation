<?php

/**
 * Functions file
 *
 * This is the core functions library file.
 *
 * In here are smaller functions which give generic functionality that can be used anywhere.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package   functions
 */


declare(strict_types=1);

use CNZ\Helpers\Yml;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Accounts\Config\Config;
use Phoundation\Accounts\Config\Interfaces\ConfigInterface;
use Phoundation\Cache\Cache;
use Phoundation\Cache\Enums\EnumCacheGroups;
use Phoundation\Cache\Interfaces\CacheInterface;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Hooks\Interfaces\HookInterface;
use Phoundation\Core\Interfaces\FloatableInterface;
use Phoundation\Core\Interfaces\IntegerableInterface;
use Phoundation\Core\Log\Interfaces\LogInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Validate;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Databases;
use Phoundation\Databases\FileDb\FileDb;
use Phoundation\Databases\Memcached\Interfaces\MemcachedInterface;
use Phoundation\Databases\MongoDb\MongoDb;
use Phoundation\Databases\NullDb\NullDb;
use Phoundation\Databases\Redis\Redis;
use Phoundation\Databases\Sql\Interfaces\SqlInterface;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Date\Interfaces\PhoDateTimeZoneInterface;
use Phoundation\Developer\Debug\Debug;
use Phoundation\Developer\Debug\FunctionCall;
use Phoundation\Exception\DatatypeNotPermittedException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhoException;
use Phoundation\Exception\PhpException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Phoundation\Web\Requests\Request;


/**
 * Improved version of PHP's empty that makes slightly more sense
 *
 * @param mixed $value
 *
 * @return bool
 */
function is_empty(mixed $value): bool
{
    if (empty($value)) {
        if ($value === '0') {
            return false;
        }

        return true;
    }

    return false;
}


/**
 * Returns the given value to the specified new value unless the new value is boolean FALSE
 *
 * @param mixed $original
 * @param mixed $value
 *
 * @return mixed
 */
function get_value_unless_false(mixed $original, mixed $value): mixed
{
    if ($value === false) {
        // Do NOT update the value
        return $original;
    }

    return $value;
}


/**
 * Adds the specified value to the given source array if the value is NOT NULL
 *
 * @param array                            $source The source array to modify
 * @param mixed                            $value  The value to (possibly) add to the specified source array
 * @param Stringable|string|float|int|null $key    If specified, the array value will be added with this key. If NULL, the value will just be appended at the
 *                                                 end of the array
 *
 * @return array
 */
function array_add_not_null(array $source, mixed $value, Stringable|string|float|int|null $key = null): array
{
    if ($value === null) {
        return $source;
    }

    if ($key === null) {
        $source[] = $value;

    } else {
        $source[$key] = $value;
    }

    return $source;
}


/**
 * Returns a realpath() version from the specified path, but ensures it will return an existing directory
 *
 * @param string $path
 *
 * @return string
 */
function realpath_safe(string $path): string
{
    $return = realpath($path);

    if ($return) {
        if (is_dir($path)) {
            return $path . '/';
        }

        return $path;
    }

    if (file_exists($path)) {
        throw new CoreException(tr('The function realpath() for path ":path" failed for unknown reasons', [
            ':path' => $path
        ]));
    }

    throw new CoreException(tr('Cannot get realpath() for path ":path", it does not exist', [
        ':path' => $path
    ]));
}


/**
 * Returns the value for the Nth array key
 *
 * @param IteratorInterface|array $source
 * @param int                     $index
 * @param bool                    $exception
 *
 * @return mixed
 */
function get_index_value(IteratorInterface|array $source, int $index, bool $exception = true): mixed
{
    if ($index < 0) {
        throw new OutOfBoundsException(ts('The specified index ":index" is invalid, it must be a positive integer', [
            ':index' => $index,
        ]));
    }

    $return = array_slice($source, $index, 1);

    if (empty($return)) {
        if ($exception) {
            throw OutOfBoundsException::new(ts('The specified index ":index" does not exist in the specified source array', [
                ':index' => $index,
            ]))->addData([
                'index'  => $index,
                'source' => $source,
            ]);
        }
    }

    return array_pop($return);
}


/**
 * Returns the array index for the specified array key
 *
 * @param IteratorInterface|array          $source
 * @param Stringable|string|float|int|null $key
 * @param bool                             $exception
 *
 * @return mixed
 */
function get_key_index(IteratorInterface|array $source, Stringable|string|float|int|null $key, bool $exception = true): mixed
{
    if (array_key_exists($key, $source)) {
        return array_search($key, array_keys($source));
    }

    if ($exception) {
        throw OutOfBoundsException::new(ts('The specified key ":key" does not exist in the specified source array', [
            ':key' => $key,
        ]))->addData([
            'key'    => $key,
            'source' => $source,
        ]);
    }
}




/**
 * Returns true if the specified string is a valid version string
 *
 * @param string $version
 *
 * @return bool
 */
function is_version(string $version): bool
{
    $return = preg_match('/\d{1,4}\.\d{1,4}\.\d{1,4}/', $version);

    if ($return === false) {
        throw new PhoException(tr('Failed to determine if ":version" is a valid version or not', [
            ':version' => $version,
        ]));
    }

    return (bool) $return;
}


/**
 * Returns if this is a scalar with usable data
 *
 * @param mixed $source
 * @param bool  $allow_null
 *
 * @return bool
 */
function is_data_scalar(mixed $source, bool $allow_null = false): bool
{
    if ($allow_null){
        return is_string($source) || is_int($source) || is_float($source) || is_null($source);
    }

    return is_string($source) || is_int($source) || is_float($source);
}


/**
 * Sleep for the specified number of milliseconds
 *
 * @param int $milliseconds
 *
 * @return void
 */
function msleep(int $milliseconds): void
{
    usleep($milliseconds * 1000);
}


/**
 * Shortcut to echo-ing a new line
 *
 * @return void
 */
function nl(): void
{
    echo PHP_EOL;
}


/**
 * Translator marker.
 *
 * tr() is a translation marker function. It basic function is to tell the translation system that the text within
 * should be translated.
 *
 * Since text may contain data from either variables or function output, and translators should not be burdened with
 * copying variables or function calls, all variable data should be identified in the text by a :marker, and the :marker
 * should be a key (with its value) in the $replace array.
 *
 * $replace values are always processed first by Strings::log() to ensure they are readable texts, so the texts sent to
 * tr() do NOT require Strings::log().
 *
 * On non-production systems, tr() will perform a check on both the $text and $replace data to ensure that all markers
 * have been replaced, and non were forgotten. If results were found, an exception will be thrown. This behaviour does
 * NOT apply to production systems.
 *
 * @param string     $text
 * @param array|null $replace
 * @param bool       $clean
 * @param bool       $check
 *
 * @return string
 */
function ts(string $text, ?array $replace = null, bool $clean = true, bool $check = true): string
{
    // Only on non-production machines, crash when not all entries were replaced as an extra check.
    if (!Core::isProductionEnvironment() and $check) {
        preg_match_all('/:\w+/', $text, $matches);

        if (!empty($matches[0])) {
            if (empty($replace)) {
                throw new OutOfBoundsException(ts('The ts() text ":text" contains key(s) ":keys" but no replace values were specified', [
                    ':keys' => Strings::force($matches[0], ', '),
                    ':text' => $text,
                ]));
            }

            // Verify that all specified text keys are available in the replacement array
            foreach ($matches[0] as $match) {
                if (!array_key_exists($match, $replace)) {
                    throw new OutOfBoundsException(ts('The ts() text key ":key" does not exist in the specified replace values for the text ":text"', [
                        ':key'  => $match,
                        ':text' => $text,
                    ]));
                }
            }
        }

        if ($replace) {
            if (empty($matches[0])) {
                throw new OutOfBoundsException(ts('The ts() replace array contains key(s) ":keys" but the text ":text" contains no keys', [
                    ':keys' => Strings::force(array_keys($replace), ', '),
                    ':text' => $text,
                ]));
            }

            // Verify that all specified replacement keys are available in the text
            $matches = array_flip($matches[0]);

            foreach ($replace as $key => $value) {
                if (!array_key_exists($key, $matches)) {
                    throw new OutOfBoundsException(ts('The ts() replace key ":key" does not exist in the specified text ":text"', [
                        ':key'  => $key,
                        ':text' => $text,
                    ]));
                }
            }
        }
    }

    if ($replace) {
        if ($clean) {
            foreach ($replace as &$value) {
                $value = Strings::log($value);
            }

        } else {
            // Ensure all replacements are strings to avoid a crash
            foreach ($replace as &$value) {
                $value = Strings::force($value);
            }
        }

        unset($value);
        return str_replace(array_keys($replace), array_values($replace), $text);
    }

    return $text;
}


/**
 * Translator marker.
 *
 * tr() is a translation marker function. It basic function is to tell the translation system that the text within
 * should be translated.
 *
 * Since text may contain data from either variables or function output, and translators should not be burdened with
 * copying variables or function calls, all variable data should be identified in the text by a :marker, and the :marker
 * should be a key (with its value) in the $replace array.
 *
 * $replace values are always processed first by Strings::log() to ensure they are readable texts, so the texts sent to
 * tr() do NOT require Strings::log().
 *
 * On non-production systems, tr() will perform a check on both the $text and $replace data to ensure that all markers
 * have been replaced, and non were forgotten. If results were found, an exception will be thrown. This behaviour does
 * NOT apply to production systems.
 *
 * @param string     $text
 * @param array|null $replace
 * @param bool       $clean
 * @param bool       $check
 *
 * @return string
 */
function tr(string $text, ?array $replace = null, bool $clean = true, bool $check = true): string
{
    // Only on non-production machines, crash when not all entries were replaced as an extra check.
    if (!Core::isProductionEnvironment() and $check) {
        preg_match_all('/:\w+/', $text, $matches);

        if (!empty($matches[0])) {
            if (empty($replace)) {
                throw new OutOfBoundsException(tr('The tr() text ":text" contains key(s) ":keys" but no replace values were specified', [
                    ':keys' => Strings::force($matches[0], ', '),
                    ':text' => $text,
                ]));
            }

            // Verify that all specified text keys are available in the replacement array
            foreach ($matches[0] as $match) {
                if (!array_key_exists($match, $replace)) {
                    throw new OutOfBoundsException(tr('The tr() text key ":key" does not exist in the specified replace values for the text ":text"', [
                        ':key'  => $match,
                        ':text' => $text,
                    ]));
                }
            }
        }

        if ($replace) {
            if (empty($matches[0])) {
                throw new OutOfBoundsException(tr('The tr() replace array contains key(s) ":keys" but the text ":text" contains no keys', [
                    ':keys' => Strings::force(array_keys($replace), ', '),
                    ':text' => $text,
                ]));
            }

            // Verify that all specified replacement keys are available in the text
            $matches = array_flip($matches[0]);

            foreach ($replace as $key => $value) {
                if (!array_key_exists($key, $matches)) {
                    throw new OutOfBoundsException(tr('The tr() replace key ":key" does not exist in the specified text ":text"', [
                        ':key'  => $key,
                        ':text' => $text,
                    ]));
                }
            }
        }
    }

    if ($replace) {
        if ($clean) {
            foreach ($replace as &$value) {
                $value = Strings::log($value);
            }

        } else {
            // Ensure all replacements are strings to avoid a crash
            foreach ($replace as &$value) {
                $value = Strings::force($value);
            }
        }

        unset($value);
        return str_replace(array_keys($replace), array_values($replace), $text);
    }

    return $text;
}


/**
 * Will return $return if the specified item id is in the specified source.
 *
 * @param array      $source
 * @param string|int $key
 *
 * @return bool
 */
function in_source(array $source, string|int $key): bool
{
    if (array_get_safe($source, $key)) {
        return true;
    }

    return false;
}


/**
 * Returns true if the specified source is an enum
 *
 * @param mixed $source
 *
 * @return bool
 */
function is_enum(mixed $source)
{
    return (is_object($source) and ($source instanceof UnitEnum));
}


/**
 * Returns true if the specified needle is in the given Enum haystack
 *
 * @param mixed  $needle
 * @param string $haystack
 *
 * @return bool
 */
function in_enum(mixed $needle, string $haystack): bool
{
    try {
        $haystack::{$needle};
        return true;

    } catch (PhpException) {
        return false;
    }
}


/**
 * Return the value if it actually exists, or default instead.
 *
 * If (for example) a non-existing key from an array was specified, default will be returned instead of causing a
 * "variable not defined exception"
 *
 * @note IMPORTANT! After calling this function, $var will exist in the scope of the calling function!
 *
 * @param mixed $variable The variable to test
 * @param mixed $default  (optional) The value to return in case the specified $variable did not exist or was NULL.*
 *
 * @return mixed
 */
function isset_get(mixed &$variable, mixed $default = null): mixed
{
    // The variable exists
    if (isset($variable)) {
        return $variable;
    }

    // The previous isset would have actually set the variable with null, unset it to ensure it won't exist
    unset($variable);
    return $default;
}


/**
 * Return the value if it actually exists, or default instead.
 *
 * If (for example) a non-existing key from an array was specified, NULL will be returned instead of causing a variable
 *
 * @note IMPORTANT! After calling this function, $var will exist in the scope of the calling function!
 *
 * @param array|null            $source
 * @param string|float|int|null $key
 * @param mixed                 $default (optional) The value to return in case the specified $variable did not exist or was NULL.*
 * @param bool                  $exception
 *
 * @return mixed
 */
function array_get_safe(?array $source, string|float|int|null $key, mixed $default = null, bool $exception = false): mixed
{
    if ($source) {
        if (array_key_exists($key, $source)) {
            if ($source[$key] === null) {
                return $default;
            }

            return $source[$key];
        }
    }

    if ($exception) {
        throw OutOfBoundsException::new(tr('Cannot return key ":key", that key does not exist in the specified source array', [
            ':key' => $key,
        ]))->setData([
            'key'    => $key,
            'source' => $source,
        ]);
    }

    return $default;
}


/**
 * Return the value (with corrected datatype) if it actually exists with the correct datatype, or $default instead.
 *
 * If the variable partially matches a datatype, like the string "15" for datatype integer, the variable will be
 * accepted and corrected
 *
 * @note The variable is specified by reference, allowing non set variables to be used when calling this function, but
 *       this causes it to disallow static values or function outputs to be passed
 *
 * @note IMPORTANT! After calling this function, $variable will exist in the scope of the calling function!
 *
 * @param array|string $types     If the data exists, it must have one of these data types. Can be specified as array or
 *                                | separated string
 * @param mixed        $variable  The variable to test
 * @param mixed        $default   (optional) The value to return in case the specified $variable did not exist or was
 *                                NULL.
 * @param bool         $exception If true, will throw an exception instead of returning $default if the specified value
 *                                does not match the specified types
 *
 * @return mixed
 * @throws DatatypeNotPermittedException
 */
function isset_get_typed(array|string $types, mixed &$variable, mixed $default = null, bool $exception = true): mixed
{
    // The variable exists
    if (isset($variable)) {
        // Ensure datatype
        foreach (Arrays::force($types, '|') as $type) {
            switch ($type) {
                case 'scalar':
                    if (is_scalar($variable)) {
                        return $variable;
                    }

                    break;

                case 'string':
                    if (is_string($variable)) {
                        return $variable;
                    }

                    if (is_object($variable) and ($variable instanceof Stringable)) {
                        // This is fine, this object has __toString() implemented
                        return (string) $variable;
                    }

                    break;

                case 'int':
                    // no break
                case 'integer':
                    if (is_integer($variable)) {
                        return $variable;
                    }

                    if (is_numeric($variable)) {
                        // This is a number stored as a string, if it's an integer, then type cast it
                        if ((int) $variable == $variable) {
                            return (int) $variable;
                        }
                    }

                    break;

                case 'double':
                    // no break
                case 'float':
                    if (is_float($variable)) {
                        return $variable;
                    }

                    if (is_numeric($variable)) {
                        if (!is_integer($variable)) {
                            // This is a float number stored as a string, convert it to integer
                            return (float) $variable;
                        }
                    }

                    break;

                case 'bool':
                    // no break
                case 'boolean':
                    if (is_bool($variable)) {
                        return $variable;
                    }

                    if (is_integer($variable)) {
                        if ($variable === 1) {
                            return true;
                        }

                        if ($variable === 0) {
                            return false;
                        }
                    }

                    if (is_string($variable)) {
                        $variable = strtolower(trim($variable));

                        if (($variable === 'true') or ($variable === '1')) {
                            return true;
                        }

                        if (($variable === 'false') or ($variable === '0')) {
                            return false;
                        }
                    }

                    break;

                case 'array':
                    if (is_array($variable)) {
                        return $variable;
                    }

                    break;

                case 'resource':
                    if (is_resource($variable)) {
                        return $variable;
                    }

                    break;

                case 'function':
                    // no break
                case 'callable':
                    // no break
                case 'closure':
                    if (is_callable($variable)) {
                        return $variable;
                    }

                    break;

                case 'null':
                    if (is_null($variable)) {
                        return $variable;
                    }

                    break;

                case 'datetime':
                    if ($variable instanceof DateTimeInterface) {
                        return $variable;
                    }

                    break;

                case 'object':
                    if (is_object($variable)) {
                        return $variable;
                    }

                    break;

                case 'enum':
                    if (is_enum($variable)) {
                        return $variable;
                    }

                    break;

                default:
                    // This should be an object
                    if ($variable instanceof $type) {
                        return $variable;
                    }

                    break;
            }
        }

        if ($exception) {
            if (is_object($variable)) {
                throw DatatypeNotPermittedException::new(tr('Specified variable value ":variable" is an object of the class ":class" but it should be one of ":types"', [
                    ':variable' => $variable,
                    ':class'    => get_class($variable),
                    ':types'    => $types,
                ]))->addData([
                    'variable' => $variable,
                    'default'  => $default
                ]);
            }

            throw DatatypeNotPermittedException::new(tr('Specified variable value ":variable" has datatype ":has" but it should be one of ":types"', [
                ':variable' => $variable,
                ':has'      => gettype($variable),
                ':types'    => $types,
            ]))->addData([
                'variable' => $variable,
                'default'  => $default
            ]);
        }

        // Don't throw an exception, return null instead.
        return null;
    }

    // The previous isset would have actually set the variable with null, unset it to ensure it won't exist
    unset($variable);

    if ($default === null) {
        return null;
    }

    // Return the default variable after validating datatype. This WILL throw an exception, no matter what, if the data
    // type doesn't match
    return isset_get_typed($types, $default);
}


/**
 * Return the value (with corrected datatype) if it actually exists with the correct datatype, or $default instead.
 *
 * If the variable partially matches a datatype, like the string "15" for datatype integer, the variable will be
 * accepted and corrected
 *
 * @note The variable is specified by reference, allowing non set variables to be used when calling this function, but
 *       this causes it to disallow static values or function outputs to be passed
 *
 * @note IMPORTANT! After calling this function, $variable will exist in the scope of the calling function!
 *
 * @param array|string $types         If the data exists, it must have one of these data types. Can be specified as
 *                                    array or | separated string
 * @param array            $source    The source array from which we want to get the value
 * @param string|float|int $key       The key indicating what value we want to have returned
 * @param mixed            $default   (optional) The value to return in case the specified $variable did not exist or
 *                                    was NULL.
 * @param bool             $exception If true, will throw an exception instead of returning $default if the specified
 *                                    value does not match the specified types
 *
 * @return mixed
 * @throws DatatypeNotPermittedException
 */
function get_safe_typed(array|string $types, array $source, string|float|int $key, mixed $default = null, bool $exception = true): mixed
{
    // The variable exists
    if (array_key_exists($key, $source) and ($source[$key] !== null)) {
        $variable = &$source[$key];

        // Ensure datatype
        foreach (Arrays::force($types, '|') as $type) {
            switch ($type) {
                case 'scalar':
                    if (is_scalar($variable)) {
                        return $variable;
                    }

                    break;

                case 'string':
                    if (is_string($variable)) {
                        return $variable;
                    }

                    if (is_object($variable) and ($variable instanceof Stringable)) {
                        // This is fine, this object has __toString() implemented
                        return (string) $variable;
                    }

                    break;

                case 'int':
                    // no break
                case 'integer':
                    if (is_integer($variable)) {
                        return $variable;
                    }

                    if (is_numeric($variable)) {
                        // This is a number stored as a string, if it's an integer, then type cast it
                        if ((int) $variable == $variable) {
                            return (int) $variable;
                        }
                    }

                    break;

                case 'double':
                    // no break
                case 'float':
                    if (is_float($variable)) {
                        return $variable;
                    }

                    if (is_numeric($variable)) {
                        if (!is_integer($variable)) {
                            // This is a float number stored as a string, convert it to integer
                            return (float) $variable;
                        }
                    }

                    break;

                case 'bool':
                    // no break
                case 'boolean':
                    if (is_bool($variable)) {
                        return $variable;
                    }

                    if (is_integer($variable)) {
                        if ($variable === 1) {
                            return true;
                        }

                        if ($variable === 0) {
                            return false;
                        }
                    }

                    if (is_string($variable)) {
                        $variable = strtolower(trim($variable));

                        if (($variable === 'true') or ($variable === '1')) {
                            return true;
                        }

                        if (($variable === 'false') or ($variable === '0')) {
                            return false;
                        }
                    }

                    break;

                case 'array':
                    if (is_array($variable)) {
                        return $variable;
                    }

                    break;

                case 'resource':
                    if (is_resource($variable)) {
                        return $variable;
                    }

                    break;

                case 'function':
                    // no break
                case 'callable':
                    // no break
                case 'closure':
                    if (is_callable($variable)) {
                        return $variable;
                    }

                    break;

                case 'null':
                    if (is_null($variable)) {
                        return $variable;
                    }

                    break;

                case 'object':
                    if (is_object($variable)) {
                        return $variable;
                    }

                    break;

                case 'enum':
                    if (is_enum($variable)) {
                        return $variable;
                    }

                    break;

                default:
                    // This should be an object
                    if ($variable instanceof $type) {
                        return $variable;
                    }

                    break;
            }
        }

        if ($exception) {
            throw DatatypeNotPermittedException::new(tr('Specified variable ":key" with value ":variable" has datatype ":has" but it should be one of ":types"', [
                ':key'      => $key,
                ':variable' => $variable,
                ':has'      => get_class_or_datatype($variable),
                ':types'    => $types,
            ]))->addData([
                'variable' => $variable
            ]);
        }

        // Don't throw an exception, return null instead.
        return null;
    }

    if ($default === null) {
        return null;
    }

    try {
        // Return the default variable after validating datatype. This WILL throw an exception, no matter what, if the data
        // type doesn't match
        return isset_get_typed($types, $default);

    } catch (DatatypeNotPermittedException $e) {
        throw DatatypeNotPermittedException::new(tr('Specified default for variable ":key" with value ":variable" has datatype ":has" but it should be one of ":types"', [
            ':key'      => $key,
            ':variable' => $default,
            ':has'      => get_class_or_datatype($default),
            ':types'    => $types,
        ]), $e)->addData([
            'variable' => $default
        ]);
    }
}


/**
 * Returns true if the specified value matches one or multiple of the specified datatypes or classes
 *
 * @note The variable is specified by reference, allowing non set variables to be used when calling this function, but
 *       this causes it to disallow static values or function outputs to be passed
 *
 * @note IMPORTANT! After calling this function, $variable will exist in the scope of the calling function!
 *
 * @param array|string $types     If the data exists, it must have one of these data types. Can be specified as array or
 *                                | separated string
 * @param mixed        $variable  The variable to test
 *
 * @return bool
 * @throws DatatypeNotPermittedException
 */
function is_datatype_or_class(array|string $types, mixed &$variable): bool
{
    // Ensure datatype
    foreach (Arrays::force($types, '|') as $type) {
        switch ($type) {
            case 'scalar':
                if (is_scalar($variable)) {
                    return $variable;
                }

                break;

            case 'string':
                if (is_string($variable)) {
                    return true;
                }

                if ($variable instanceof Stringable) {
                    // This is fine, this object has __toString() implemented
                    return true;
                }

                break;

            case 'int':
                // no break
            case 'integer':
                if (is_integer($variable)) {
                    return true;
                }

                if (is_numeric($variable)) {
                    // This is a number stored as a string, if it's an integer, then type cast it
                    if ((int) $variable == $variable) {
                        return true;
                    }
                }

                break;

            case 'double':
                // no break
            case 'float':
                if (is_float($variable)) {
                    return true;
                }

                if (is_numeric($variable)) {
                    if (!is_integer($variable)) {
                        // This is a float number stored as a string, convert it to integer
                        return true;
                    }
                }

                break;

            case 'bool':
                // no break
            case 'boolean':
                if (is_bool($variable)) {
                    return true;
                }

                if (is_integer($variable)) {
                    if ($variable === 1) {
                        return true;
                    }

                    if ($variable === 0) {
                        return true;
                    }
                }

                if (is_string($variable)) {
                    $variable = strtolower(trim($variable));

                    if (($variable === 'true') or ($variable === '1')) {
                        return true;
                    }

                    if (($variable === 'false') or ($variable === '0')) {
                        return true;
                    }
                }

                break;

            case 'array':
                if (is_array($variable)) {
                    return true;
                }

                break;

            case 'resource':
                if (is_resource($variable)) {
                    return true;
                }

                break;

            case 'function':
                // no break
            case 'callable':
                // no break
            case 'closure':
                if (is_callable($variable)) {
                    return true;
                }

                break;

            case 'null':
                if (is_null($variable)) {
                    return true;
                }

                break;

            case 'datetime':
                if ($variable instanceof DateTimeInterface) {
                    return true;
                }

                break;

            case 'object':
                if (is_object($variable)) {
                    return true;
                }

                break;

            case 'enum':
                if (is_enum($variable)) {
                    return true;
                }

                break;

            case 'mixed':
                // This is always ok
                return true;

            default:
                // This should be an object of the specified type
                if ($variable instanceof $type) {
                    return true;
                }

                break;
        }
    }

    // No datatype was matched
    return false;
}


/**
 * Returns true if the specified variable has the specified datatype, throws OutOfBounds exception otherwise
 *
 * @param mixed       $variable
 * @param string      $type
 * @param string|null $error_message
 *
 * @return bool
 */
function check_datatype(mixed $variable, string $type, ?string $error_message = null): bool
{
    if (gettype($variable) === $type) {
        return true;
    }

    if (empty($error_message)) {
        $error_message = tr('Specified variable has datatype ":has" but it should be ":type"', [
            ':has'  => gettype($variable),
            ':type' => $type
        ]);
    }

    throw new OutOfBoundsException($error_message);
}


/**
 * Ensures the specified variable exists. If the variable already exists with a non NULL value, it will not be touched.
 * If the variable does not exist, or has a NULL value, it will be set to the $initialization variable
 *
 * @param mixed $variable
 * @param mixed $initialize The value to initialize the variable with
 *
 * @return mixed the value of the variable. Either the value of the existing variable, or the value of the $initialize
 *               variable, if the variable did not exist, or was NULL
 */
function ensure_variable(mixed &$variable, mixed $initialize): mixed
{
    if (isset($variable)) {
        $variable = $initialize;

    } elseif ($variable === null) {
        $variable = $initialize;
    }

    return $variable;
}


/**
 * Force the specified number to be a natural number.
 *
 * This function will ensure that the specified $source variable is returned as an integer. If a float value was
 * specified, the value will be rounded up to the nearest integer value
 *
 * @param mixed $source  The source variable to convert
 * @param mixed $default [optional] The value to return in case the specified $variable did not exist or was NULL.*
 * @param mixed $start   [optional] The value to return in case the specified $variable did not exist or was NULL.*
 *
 * @return int
 */
function force_natural(mixed $source, int $default = 1, int $start = 1): int
{
    if (!is_numeric($source)) {
        // This isn't even a number
        return $default;
    }

    if ($source < $start) {
        // Natural numbers have to be > 1 (by default, $start might be adjusted where needed)
        return $default;
    }

    if (is_int($source)) {
        // This is a nice integer
        return $source;
    }

    // Natural numbers must be integer numbers. Round to the nearest integer
    return (int) round($source);
}


/**
 * Returns true if the specified number is a natural number.
 *
 * A natural number here is defined as one of the set of positive whole numbers; a positive integer and the number 1 and
 * any other number obtained by adding 1 to it repeatedly. For ease of use, the number one can be adjusted if needed.
 *
 * @param mixed $source
 * @param int   $start
 *
 * @return bool
 */
function is_natural(mixed $source, int $start = 1): bool
{
    if (!is_numeric($source)) {
        return false;
    }

    if ($source < $start) {
        return false;
    }

    return is_numeric_integer($source);
}


/**
 * Returns true if the specified number (may be any datatype) is content wise an integer
 *
 * @param mixed $source
 *
 * @return bool
 */
function is_numeric_integer(mixed $source): bool
{
    return $source == (int) $source;
}


/**
 * Converts <br> to \n
 *
 * @note This is the opposite of PHP's nl2br() and adds the missing function
 *
 * @param string $source
 * @param string $nl
 *
 * @return string
 */
function br2nl(string $source, string $nl = "\n"): string
{
    $source = preg_replace("/(\r\n|\n|\r)/u", '' , $source);
    $source = preg_replace("/<br *\/?>/iu"  , $nl, $source);

    return $source;
}


/**
 * Return the first non-empty argument
 *
 * @params mixed $value
 * @params mixed $value ...
 *
 * @return mixed The first value that is not empty, or NULL if all arguments were NULL
 */
function not_empty(): mixed
{
    foreach (func_get_args() as $argument) {
        if ($argument) {
            return $argument;
        }
    }

    return null;
}


/**
 * Return the first non null argument
 *
 * @params mixed $value
 * @params mixed $value ...
 *
 * @return mixed The first value that is not NULL, or NULL if all arguments were NULL
 */
function not_null(): mixed
{
    foreach (func_get_args() as $argument) {
        if ($argument === null) {
            continue;
        }

        return $argument;
    }

    return null;
}


/**
 * Return a randomly picked argument
 *
 * @param mixed ...$arguments
 *
 * @return mixed
 */
function pick_random_argument(mixed ...$arguments): mixed
{
    return Arrays::getRandomValue($arguments);
}


/**
 * Return randomly picked arguments
 *
 * @param int   $count
 * @param mixed ...$arguments
 *
 * @return string|array
 * @throws \Exception
 */
function pick_random_multiple(int $count, mixed ...$arguments): string|array
{
    if (!$count) {
        // Get a random count
        $count = random_int(1, count($arguments));
    }

    if (($count < 1) or ($count > count($arguments))) {
        // Invalid count specified
        throw new OutOfBoundsException(tr('Invalid count ":count" specified for ":args" arguments', [
            ':count' => $count,
            ':args'  => count($arguments),
        ]));
    }

    // Return multiple arguments in an array
    $return = [];

    for ($i = 0; $i < $count; $i++) {
        $return[] = $arguments[$key = Arrays::getRandomValue($arguments)];
        unset($arguments[$key]);
    }

    return $return;
}


/**
 * Shortcut to the Debug::show() call
 *
 * @param mixed $source
 * @param bool  $sort
 * @param int   $trace_offset
 * @param bool  $quiet
 * @param bool  $var_dump
 *
 * @return mixed
 */
function show(mixed $source = null, bool $sort = true, int $trace_offset = 1, bool $quiet = false, bool $var_dump = false): mixed
{
    if (Debug::isEnabled()) {
        if (Core::inStartupState() and config()->getBoolean('debug.startup', false)) {
            // Startup debugging may not have all libraries loaded required for Debug::show(), use show_system() instead
            return show_system($source, false);
        }

        if (Core::isStateShutdown() and config()->getBoolean('debug.shutdown', false)) {
            return Debug::show($source, $sort, $trace_offset, $quiet, var_dump: $var_dump);
        }

        return Debug::show($source, $sort, $trace_offset, $quiet, var_dump: $var_dump);

    } else {
        Log::warning(ts('Ignoring show() call at ":location" because debug mode is not enabled', [
            ':location' => Strings::from(FunctionCall::new(1)->getLocation(), DIRECTORY_ROOT)
        ]));
    }

    return null;
}


/**
 * Shortcut to the Debug::show() call, but displaying the data in hex
 *
 * @param mixed $source
 * @param bool  $sort
 * @param int   $trace_offset
 * @param bool  $quiet
 *
 * @return mixed
 */
function showhex(mixed $source = null, bool $sort = true, int $trace_offset = 1, bool $quiet = false): mixed
{
    if (Debug::isEnabled()) {
        return show(bin2hex($source), $sort, $trace_offset);

    } else {
        Log::warning(ts('Ignoring showhex() call at ":location" because debug mode is not enabled', [
            ':location' => Strings::from(FunctionCall::new(1)->getLocation(), DIRECTORY_ROOT)
        ]));
    }

    return null;
}


/**
 * Shortcut to the Debug::show() call, but displaying the backtrace
 *
 * @param int  $count
 * @param int  $trace_offset
 * @param bool $quiet
 *
 * @return mixed
 */
function showbacktrace(int $count = 0, int $trace_offset = 2, bool $quiet = false): mixed
{
    if (Debug::isEnabled()) {
        $backtrace = Debug::getBacktrace();
        $backtrace = Debug::formatBackTrace($backtrace);

        if ($count) {
            $backtrace = Arrays::limit($backtrace, $count);
        }

        return show($backtrace, true, $trace_offset, $quiet);

    } else {
        Log::warning(ts('Ignoring showbacktrace() call at ":location" because debug mode is not enabled', [
            ':location' => Strings::from(FunctionCall::new(1)->getLocation(), DIRECTORY_ROOT)
        ]));
    }

    return null;
}


/**
 * Shortcut to the Debug::show() call
 *
 * @param mixed $source
 * @param bool  $sort
 * @param int   $trace_offset
 * @param bool  $quiet
 * @param bool  $var_dump
 *
 * @return void
 */
#[NoReturn] function showdie(mixed $source = null, bool $sort = true, int $trace_offset = 2, bool $quiet = false, bool $var_dump = false): void
{
    if (Debug::isEnabled()) {
        if (Core::inStartupState() and config()->getBoolean('debug.startup', false)) {
            // Startup debugging may not have all libraries loaded required for Debug::show(), use show_system() instead
            show_system($source);
        }

        if (Core::isStateShutdown() and config()->getBoolean('debug.shutdown', false)) {
            Debug::showdie($source, $sort, $trace_offset, $quiet, $var_dump);
        }

        Debug::showdie($source, $sort, $trace_offset, $quiet, $var_dump);

    } else {
        Log::warning(ts('Ignoring showdie() call at ":location" because debug mode is not enabled', [
            ':location' => Strings::from(FunctionCall::new(1)->getLocation(), DIRECTORY_ROOT)
        ]));
    }
}


/**
 * Return $source if $source is not considered "empty", NULL otherwise
 *
 * Return null if the specified variable is considered "empty", like 0, "", array(), etc.
 *
 * @see get_false()
 * @see get_empty()
 * @param mixed $source The value to be tested. If this value doesn't evaluate to empty, it will be returned
 *
 * @return mixed Either $source or null, depending on if $source is empty or not
 * @note    This function is a wrapper for get_empty($source, null);
 * @see     get_empty()
 * @version 2.6.27: Added documentation
 * @example
 * code
 * $result = get_null(false);
 * showdie($result);
 * /code
 *
 * This would return
 * code
 * null
 * /code
 */
function get_null(mixed $source): mixed
{
    if (empty($source)) {
        return null;
    }

    return $source;
}


/**
 * Return $source if $source is not considered "empty", FALSE otherwise.
 *
 * Return false if the specified variable is considered "empty", like 0, "", array(), etc.
 *
 * @see get_null()
 * @param mixed $source The value to be tested. If this value doesn't evaluate to empty, it will be returned
 *
 * @return mixed Either $source or null, depending on if $source is empty or not
 */
function get_false(mixed $source): mixed
{
    if (empty($source)) {
        return false;
    }

    return $source;
}


/**
 * Returns NOT $source, unless $source is a NULL, which returns NULL.
 *
 * @param bool|null $source
 *
 * @return bool|null
 */
function null_not(?bool $source): ?bool
{
    if ($source === null) {
        return null;
    }

    return !$source;
}


/**
 * Returns if the specified variable (string or not) is actually an integer, or not
 *
 * @param mixed    $source
 * @param int|null $larger_than
 *
 * @return bool
 */
function is_really_integer(mixed $source, ?int $larger_than = null): bool
{
    if ($source != (int) $source) {
        return false;
    }

    if ($larger_than === null) {
        return true;
    }

    // The number must be larger than...
    return $source > $larger_than;
}


/**
 * Returns if the specified variable (string or not) is actually an integer, or not
 *
 * @param mixed $source
 * @param bool  $allow_zero
 *
 * @return bool
 */
function is_really_natural(mixed $source, bool $allow_zero = false): bool
{
    return is_really_integer($source, $allow_zero ? 0 : 1);
}


/**
 * Ensures the specified source either is NULL or INT value. Non NULL/INT values will cause an exception
 *
 * @param mixed $source
 * @param bool  $allow_null
 *
 * @return int|null
 */
function get_integer(mixed $source, bool $allow_null = true): ?int
{
    if (is_integer($source)) {
        // Well, that was easy!
        return $source;
    }

    if (!is_string($source)) {
        throw new OutOfBoundsException(tr('Specified data ":source" is not an integer value', [
            ':source' => $source,
        ]));
    }

    if ($source === '') {
        if ($allow_null) {
            // Interpret this as a NULL value
            return null;
        }

        return 0;

    }

    $old_source = $source;
    $source     = (int) $source;

    if ($old_source != $source) {
        throw new OutOfBoundsException(tr('Specified data ":source" is not an integer value', [
            ':source' => $old_source,
        ]));
    }

    return $source;
}


/**
 * Return the value quoted if non-numeric string
 *
 * @param string|int $value
 *
 * @return string|int
 */
function quote(string|int $value): string|int
{
    if (!is_numeric($value) and is_string($value)) {
        return '"' . $value . '"';
    }

    return $value;
}


/**
 * Returns either the specified value if it exists in the array, or the default vaule if it does not
 *
 * @param string|int $value
 * @param array      $array
 * @param mixed      $default
 *
 * @return mixed
 */
function ensure_value(string|int $value, array $array, mixed $default): mixed
{
    if (in_array($value, $array, true)) {
        return $value;
    }

    return $default;
}


/**
 * Execute the specified callback function with the specified $params only if the callback has been set with an
 * executable function
 *
 * @param callable|null $callback
 * @param array|null    $params
 *
 * @return string|null The results from the callback function, or null if no callback function was specified
 * @version 2.0.6: Added documentation
 */
function execute_callback(?callable $callback, ?array $params = null): ?string
{
    if (is_callable($callback)) {
        return $callback($params);
    }

    return null;
}


/**
 * Execute the current Request target and return the output (if any)
 *
 * @note This function is used to execute commands and web pages to give them their own empty function scope
 * @note Any information echo-ed by the targets will be stored in nested buffers and returned by Request::execute() or
 *       -If it's the first target being executed- flushed to the client (web) or console (cli)
 *
 * @return string| null
 */
function execute(): ?string
{
    try {
        Core::setScriptState();
        $result = include(Request::getTargetObject());

    } catch (Throwable $e) {
        if (!($e instanceof PhoException) or !$e->isWarning()) {
            Log::error(tr('Command ":command" failed with exception: :exception', [
                ':command'   => Request::getTargetObject(),
                ':exception' => $e->getMessage(),
            ]));
        }

        throw $e;
    }

    try {
        if ($result and (is_string($result) or ($result instanceof RenderInterface))) {
            echo $result;
        }

        return get_null((string) ob_get_clean());

    } catch (Throwable $e) {
        if (!($e instanceof PhoException) or !$e->isWarning()) {
            Log::error(tr('Command ":command" output failed to be pushed to output buffer: :exception', [
                ':command'   => Request::getTargetObject(),
                ':exception' => $e->getMessage(),
            ]));
        }

        throw $e;
    }
}


/**
 * Executes the specified hook file
 *
 * @note This function is used to execute hooks to give them their own empty function scope
 *
 * @param string        $__file
 * @param HookInterface $o_hook
 *
 * @return mixed
 */
function execute_hook(string $__file, HookInterface $o_hook): mixed
{
    $return = include($__file);

    if ($return === 1) {
        return null;
    }

    return $return;
}


/**
 * Returns the system cache object
 *
 * @param EnumCacheGroups|string $connector
 * @param bool                   $allow_alternate_connector
 *
 * @return CacheInterface
 */
function cache(EnumCacheGroups|string $connector, bool $allow_alternate_connector = true): CacheInterface
{
    return Databases::getCache($connector, $allow_alternate_connector);
}


/**
 * Returns the system SQL database object
 *
 * @param ConnectorInterface|string|null $connector
 * @param bool                           $use_database
 * @param bool                           $connect
 *
 * @return SqlInterface
 */
function sql(ConnectorInterface|string|null $connector = 'system', bool $use_database = true, bool $connect = true): SqlInterface
{
    return Databases::getSql($connector, $use_database, $connect);
}


/**
 * Returns the system SQL database object
 *
 * @param ConnectorInterface|string|null $connector
 * @param bool                           $connect
 *
 * @return MemcachedInterface
 */
function memcached(ConnectorInterface|string|null $connector, bool $connect = true): MemcachedInterface
{
    return Databases::getMemcached($connector, $connect);
}


/**
 * Returns the system SQL database object
 *
 * @note This is a wrapper for memcached() with a shorter name
 * @param ConnectorInterface|string|null $connector
 * @param bool                           $connect
 *
 * @return MemcachedInterface
 */
function mc(ConnectorInterface|string|null $connector, bool $connect = true): MemcachedInterface
{
    return Databases::getMemcached($connector, $connect);
}


/**
 * Returns the system SQL database object
 *
 * @param string|null $instance_name
 * @param bool        $connect
 *
 * @return MongoDb
 */
function mongo(?string $instance_name = null, bool $connect = true): MongoDb
{
    return Databases::getMongo($instance_name, $connect);
}


/**
 * Returns the system SQL database object
 *
 * @param ConnectorInterface|string|null $connector
 * @param bool                           $connect
 *
 * @return Redis
 */
function redis(ConnectorInterface|string|null $connector = 'system-redis', bool $connect = true): Redis
{
    return Databases::getRedis($connector, $connect);
}


/**
 * Returns the system SQL database object
 *
 * @param string|null $instance_name
 *
 * @return NullDb
 */
function null(?string $instance_name = null): NullDb
{
    return Databases::getNullDb($instance_name);
}


/**
 * Returns the file database object
 *
 * @param string|null $instance_name
 *
 * @return FileDb
 */
function filedb(?string $instance_name = null): FileDb
{
    return Databases::getFileDb($instance_name);
}


/**
 * Returns true if the specified class uses the specified trait
 *
 * @param string        $trait
 * @param object|string $class
 *
 * @return bool
 */
function has_trait(string $trait, object|string $class): bool
{
    while ($class) {
        $traits = class_uses($class);

        if (in_array($trait, $traits, true)) {
            return true;
        }

        // Check parent class
        $class = get_parent_class($class);
    }

    return false;
}


/**
 * Show command that requires no configuration and can be used at startup times. USE WITH CARE!
 *
 * @param mixed|null $source
 * @param bool       $die
 * @param bool       $sort
 *
 * @return mixed
 */
function show_system(mixed $source = null, bool $die = true, bool $sort = true): mixed
{
    $do = false;

    if (!Core::userScriptRunning()) {
        $do = true;

    } elseif (Core::isStateShutdown() and config()->getBoolean('debug.shutdown', false)) {
        $do = true;

    } elseif (Core::inStartupState() and config()->getBoolean('debug.startup', false)) {
        $do = true;
    }

    if ($sort) {
        if (is_array($source)) {
            ksort($source);
        }
    }

    if ($do) {
        if (php_sapi_name() !== 'cli') {
            // Only add this on browsers
            echo '<pre>' . PHP_EOL;
        }

        echo 'message-' . Numbers::getRandomInt(1, 1_000_000) . PHP_EOL . '"';
        print_r($source);
        echo '"' . PHP_EOL;

        if ($die) {
            exit('die-' . Numbers::getRandomInt(1, 1_000_000) . PHP_EOL);
        }
    }

    return $source;
}


/**
 * Returns true if the specified function was called in the current backtrace
 *
 * @param string $function
 *
 * @return bool
 */
function function_was_called(string $function): bool
{
    // Clean requested function
    $function = trim($function);

    if (str_ends_with($function, '()')) {
        $function = substr($function, 0, -2);
    }

    // Divide into class and function
    $class    = Strings::until($function, '::', needle_required: true);
    $class    = strtolower(trim($class));
    $function = Strings::from($function, '::');
    $function = strtolower(trim($function));

    // Scan trace for class and function match
    foreach (debug_backtrace() as $trace) {
        $trace['function'] = strtolower(trim((string) $trace['function']));
        $trace['class']    = strtolower(trim((string) array_get_safe($trace, 'class')));
        $trace['class']    = Strings::fromReverse($trace['class'], '\\');

        if ($trace['function'] === $function) {
            if ($trace['class'] === $class) {
                return true;
            }
        }
    }

    return false;
}


/**
 * Returns true if the specified value is in between the specified range
 *
 * @param float|int $value
 * @param float|int $begin
 * @param float|int $end
 * @param bool      $allow_same
 *
 * @return bool
 */
function in_range(float|int $value, float|int $begin, float|int $end, bool $allow_same = true): bool
{
    if ($allow_same) {
        return ($value >= $begin) and ($value <= $end);
    }

    return ($value > $begin) and ($value < $end);
}


/**
 * Returns a DateTime object for NOW
 *
 * @param PhoDateTimeZoneInterface|string|null $timezone
 *
 * @return PhoDateTimeInterface
 */
function now(PhoDateTimeZoneInterface|string|null $timezone = 'system'): PhoDateTimeInterface
{
    return new \Phoundation\Date\PhoDateTime('now', $timezone);
}


/**
 * Renders the given content to HTML and returns it
 *
 * @param RenderInterface|callable|string|float|int|null $content
 *
 * @return string|null
 */
function render(RenderInterface|callable|string|float|int|null $content): ?string
{
    if (is_callable($content)) {
        return render($content());
    }

    if ($content instanceof RenderInterface) {
        return render($content->render());
    }

    return (string) $content;
}


/**
 * Retuns the datatype of the value or, if the value is an object, the class of the specified object
 *
 * @param mixed $value
 *
 * @return string
 */
function get_class_or_datatype(mixed $value): string
{
    $type = gettype($value);

    if ($type === 'object') {
        return get_class($value);
    }

    return $type;
}


/**
 * Retuns the datatype of the value or, if the value is an object, the class of the specified object
 *
 * @param mixed  $value
 * @param string $class_or_datatype
 *
 * @return bool
 */
function has_class_or_datatype(mixed $value, string $class_or_datatype): bool
{
    return get_class_or_datatype($value) === $class_or_datatype;
}



/**
 * Returns the index for the specified key in the specified array
 *
 * @param int|string $needle
 * @param array      $haystack
 * @param bool       $strict
 *
 * @return int|false
 */
function array_key_index(int|string $needle, array $haystack, bool $strict = true): int|false
{
    return array_search($needle, array_keys($haystack), $strict);
}


/**
 * Inverse version of strpos(), will return the position where the specified character does NOT occur
 *
 * @param string $source
 * @param string $char
 * @param int    $offset
 *
 * @todo Make no_strrpos(), no_stripos(), no_strripos() versions of this
 *
 * @return int|false
 */
function no_strpos(string $source, string $char, int $offset = 0): int|false
{
    for (; $offset < strlen($source); $offset++) {
        if ($source[$offset] != $char) {
            return $offset;
        }
    }

    return false;
}


/**
 * Returns the first value from the specified array
 *
 * @param array $source
 * @return mixed
 */
function array_value_first(array $source): mixed
{
    if ($source) {
        return $source[array_key_first($source)];
    }

    throw new OutOfBoundsException(tr('Cannot get first value of source array, the array is empty'));
}


/**
 * Returns the last value from the specified array
 *
 * @param array $source
 * @return mixed
 */
function array_value_last(array $source): mixed
{
    if ($source) {
        return $source[array_key_last($source)];
    }

    throw new OutOfBoundsException(tr('Cannot get last value of source array, the array is empty'));
}


/**
 * Returns an integer or float number from whatever was specified
 *
 * @param mixed $source
 * @param bool  $allow_null
 *
 * @return float|int|null
 */
function get_numeric(mixed $source, bool $allow_null = true): float|int|null
{
    if (is_numeric($source)) {
        // It's a number!
        if (is_integer($source)) {
            return $source;
        }

        if (is_float($source)) {
            return $source;
        }

        if (is_numeric_integer($source)) {
            return (int) $source;
        }

        return (float) $source;
    }

    if ($source) {
        if(is_bool($source)) {
            return 1;
        }

        if (is_object($source)) {
            // We can possibly fetch numeric data from objects!
            if ($source instanceof FloatableInterface) {
                return $source->__toFloat();
            }

            if ($source instanceof IntegerableInterface) {
                return $source->__toInteger();
            }

            if ($source instanceof Stringable) {
                // Fetch string value and try again
                return get_numeric($source->__toString());
            }
        }

    } else {
        if ($source === null) {
            if ($allow_null) {
                return null;
            }

            // NULL is not allowed, return 0 instead (below)
        }
    }

    // Arrays, resources, objects we don't understand, it's all is zero
    return 0;
}


/**
 * Strips the extension from the given file name
 *
 * @param string|null $filename
 * @param bool        $all_extensions
 *
 * @return string|null
 */
function strip_extension(?string $filename, bool $all_extensions = false): ?string
{
    if (empty($filename) or ($filename === '.') or ($filename === '..')) {
        return null;
    }

    if ($all_extensions) {
        return $filename[0] . Strings::until(substr($filename, 1), '.');
    }

    return $filename[0] . Strings::untilReverse(substr($filename, 1), '.');
}


/**
 * Returns true if the given path start with a /
 *
 * @param string $path
 *
 * @return bool
 */
function is_absolute_path(string $path): bool
{
    return starts_with($path, '/');
}


/**
 * Will implode all given entries to a string, quoting each item individually before imploding
 *
 * @param array $source
 * @param string $separator
 * @return string
 */
function implode_with_quotes(array $source, string $separator = ','): string
{
    foreach ($source as &$value) {
        $value = '"' . $value . '"';
    }

    unset($value);
    return implode($separator, $source);
}


/**
 * Returns true if the specified source contains HTML
 *
 * @param Stringable|string $source
 * @param array|null        $tags
 *
 * @return bool
 */
function containsHtml(Stringable|string $source, ?array $tags = null): bool
{
    $tags = $tags ?? [
        'div',
        'p',
        'span',
        'b',
        'a',
        'strong',
        'center',
        'br',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'hr',
        'script',
        'meta',
        'link'
    ];

    $pattern = '#</?([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>#';

    if (preg_match_all($pattern, (string)$source, $matches)) {
        foreach ($matches[1] as $tag) {
            if (in_array(strtolower($tag), $tags, true)) {
                return true;
            }
        }
    }

    return false;
}


/**
 * Adds an <hr> tag to the specified content IF content is not empty
 *
 * @param Stringable|string|null $content
 * @param bool                   $before
 *
 * @return string|null
 */
function hr(Stringable|string|null $content, bool $before = true): ?string
{
    if (empty($content)) {
        return $content;
    }

    if ($before) {
        return '<hr>' . $content;
    }

    return $content . '<hr>';
}


/**
 * Returns true if the specified datatype is an object or class datatype, false if it is a standard PHP datatype
 *
 * Data type "object" and any datatype that is a class path will always return true
 *
 * @param string $datatype
 *
 * @return bool
 */
function datatype_is_class(string $datatype): bool
{
    return match ($datatype) {
        'unknown type',
        'resource',
        'resource (closed)',
        'array',
        'string',
        'double',
        'integer',
        'boolean',
        'NULL'  => false,
        default => true
    };
}


/**
 * Returns a ConfigInterface object for the specified section and environment
 *
 * @param string|null $section
 * @param string|null $environment
 *
 * @return ConfigInterface
 */
function config(?string $section = null, ?string $environment = null): ConfigInterface
{
    return Config::fromSection($section, $environment);
}


/**
 * Returns true if the specified source is a valid email address
 *
 * @param Stringable|string $source
 *
 * @return bool
 */
if (!function_exists('is_email')) {
    function is_email(Stringable|string $source): bool
    {
        try {
            Validate::new($source)
                    ->isEmail();
            return true;

        } catch (ValidationFailedException) {
            // Yeah, this is not an email address
        }

        return false;
    }
}


/**
 * Returns a LogInterface object that will log to the specified file
 *
 * @param string $file
 *
 * @return LogInterface
 */
function logmsg(string $file = 'syslog'): LogInterface
{
    return Log::toFile($file);
}


/**
 * Returns the number of references to the specified variable
 *
 * @see https://www.php.net/manual/en/language.references.php#99644 <<< Taken from here
 * @see https://www.php.net/manual/en/language.references.php
 *
 * @param mixed $variable
 *
 * @return int
 */
function get_reference_count(mixed $variable): int
{
    ob_start();
    debug_zval_dump($variable);

    $dump    = ob_get_clean();
    $matches = [];

    preg_match('/refcount\(([0-9]+)/', $dump, $matches);
    $count = $matches[1];

    //3 references are added, including when calling debug_zval_dump()
    return $count - 3;
}


/**
 * Wrappers for PHP yaml_emit(), yaml_parse() if the PHP YAML extension is not installed
 */
if (!function_exists('yaml_emit')) {
    function yaml_emit($data, $encoding = YAML_ANY_ENCODING, $linebreak = YAML_ANY_BREAK, array $callbacks = []): ?string
    {
        return Yml::dump($data);
    }
}

if (!function_exists('yaml_parse')) {
    function yaml_parse($input, $pos = 0, &$ndocs = null, array $callbacks = []): ?array
    {
        return Yml::parse($input);
    }
}

if (!function_exists('yaml_parse_file')) {
    function yaml_parse_file($filename, $pos = 0, &$ndocs = null, array $callbacks = []): ?array
    {
        return Yml::parseFile($filename);
    }
}
