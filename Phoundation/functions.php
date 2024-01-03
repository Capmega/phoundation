<?php

declare(strict_types=1);

use CNZ\Helpers\Yml;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Databases;
use Phoundation\Databases\Mc;
use Phoundation\Databases\Mongo;
use Phoundation\Databases\NullDb;
use Phoundation\Databases\Redis;
use Phoundation\Databases\Sql\Interfaces\SqlInterface;
use Phoundation\Date\Interfaces\DateTimeZoneInterface;
use Phoundation\Date\Interfaces\DateTimeInterface;
use Phoundation\Developer\Debug;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Page;


/**
 * functions file functions.php
 *
 * This is the core functions library file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package functions
 */


/**
 * Returns true if the specified string is a version, or false if it is not
 *
 * @version 2.5.46: Added function and documentation
 * @param string $version The version to be validated
 * @return boolean True if the specified $version is an N.N.N version string
 */
function is_version(string $version): bool
{
    $return = preg_match('/\d{1,4}\.\d{1,4}\.\d{1,4}/', $version);

    if ($return === false) {
        throw new Exception(tr('Failed to determine if ":version" is a valid version or not', [
            ':version' => $version
        ]));
    }

    return (bool) $return;
}


/**
 * Sleep for the specified number of milliseconds
 *
 * @param int $milliseconds
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
 * @param string $text
 * @param array|null $replace
 * @param bool $clean
 * @return string
 */
function tr(string $text, ?array $replace = null, bool $clean = true): string
{
    try {
        if ($replace) {
            if ($clean) {
                foreach ($replace as &$value) {
                    $value = Strings::log($value);
                }
            }

            unset($value);

            $text = str_replace(array_keys($replace), array_values($replace), $text, $count);

            // Only on non-production machines, crash when not all entries were replaced as an extra check.
            if (!Debug::production()) {
                if ($count < count($replace)) {
                    foreach ($replace as $value) {
                        if (str_contains($value, ':')) {
                            // One of the $replace values contains :blah. This will cause the detector to fire off
                            // incorrectly. Ignore this.
                            return $text;
                        }
                    }

                    throw new CoreException('tr(): Not all specified keywords were found in text');
                }

                // Do NOT check for :value here since the given text itself may contain :value (ie, in prepared
                // statements!)
            }

            return $text;
        }

        return $text;

    } catch (Exception $e) {
        // Do NOT use tr() here for obvious endless loop reasons!
        throw new CoreException('tr(): Failed with text "' . Strings::log($text) . '". Very likely issue with $replace not containing all keywords, or one of the $replace values is non-scalar', $e);
    }
}


/**
 * Will return $return if the specified item id is in the specified source.
 *
 * @param array $source
 * @param string|int $key
 * @return bool
 */
function in_source(array $source, string|int $key): bool
{
    if (isset_get($source[$key])) {
        return true;
    }

    return false;
}


/**
 * Returns true if the specified source is an enum
 *
 * @param mixed $source
 * @return bool
 */
function is_enum(mixed $source) {
    return (is_object($source) and ($source instanceof UnitEnum));
}


/**
 * Returns true if the specified needle is in the given Enum haystack
 *
 * @note Internally this function will convert the enum to an array and then use in_array()
 *
 * @param mixed $needle
 * @param UnitEnum $haystack
 * @param bool $strict
 * @return bool
 */
function in_enum(mixed $needle, UnitEnum $haystack, bool $strict = false): bool
{
    $haystack = Arrays::fromEnum($haystack);
    return in_array($needle, $haystack, $strict);
}


/**
 * Return the value if it actually exists, or NULL instead.
 *
 * If (for example) a non-existing key from an array was specified, NULL will be returned instead of causing a variable
 *
 * @note IMPORTANT! After calling this function, $var will exist in the scope of the calling function!
 * @param mixed $variable The variable to test
 * @param mixed $default (optional) The value to return in case the specified $variable did not exist or was NULL.*
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
 * Return the array key value if it exists, or the default
 *
 * If (for example) a non-existing key from an array was specified, NULL will be returned instead of causing a variable
 *
 * @note IMPORTANT! After calling this function, $var will exist in the scope of the calling function!
 * @param array $source The source array to test
 * @param string|float|int|null $key The key to return
 * @param string|float|int $default (optional) The value to return in case the specified $variable did not exist or was NULL.*
 * @return mixed
 */
function array_get_safe(array $source, string|float|int|null $key, mixed $default = null): mixed
{
    // Return the key if it exists
    if (array_key_exists($key, $source)) {
        return $source[$key];
    }

    // Return the default value
    return $default;
}


/**
 * Return the value if it actually exists with the correct datatype, or NULL instead.
 *
 * If (for example) a non-existing key from an array was specified, NULL will be returned instead of causing a variable
 *
 * @note IMPORTANT! After calling this function, $var will exist in the scope of the calling function!
 * @param array|string $types If the data exists, it must have one of these data types. Can be specified as array or |
 *                            separated string
 * @param mixed $variable     The variable to test
 * @param mixed $default      (optional) The value to return in case the specified $variable did not exist or was NULL.
 * @param bool $exception
 * @return mixed
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

                    break;

                case 'double':
                    // no break
                case 'float':
                    if (is_float($variable)) {
                        return $variable;
                    }

                    break;

                case 'bool':
                    // no break
                case 'boolean':
                    if (is_bool($variable)) {
                        return $variable;
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
                    // no-break
                case 'callable':
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
                    if ($variable instanceof \DateTimeInterface) {
                        return $variable;
                    }

                    break;

                case 'object':
                    if (is_object($variable)) {
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
            throw OutOfBoundsException::new(tr('isset_get_typed(): Specified variable ":variable" has datatype ":has" but it should be one of ":types"', [
                ':variable' => $variable,
                ':has'      => gettype($variable),
                ':types'    => $types,
            ]))->addData(['variable' => $variable]);
        }

        // Don't throw an exception, return null instead.
        return null;
    }

    // The previous isset would have actually set the variable with null, unset it to ensure it won't exist
    unset($variable);

    if ($default === null) {
        return null;
    }

    // Return the default variable after validating datatype
    return isset_get_typed($types, $default);
}


/**
 * Ensures the specified variable exists. If the variable already exists with a non NULL value, it will not be touched.
 * If the variable does not exist, or has a NULL value, it will be set to the $initialization variable
 *
 * @param mixed $variable
 * @param mixed $initialize The value to initialize the variable with
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
 * @param mixed $source The source variable to convert
 * @param mixed $default [optional] The value to return in case the specified $variable did not exist or was NULL.*
 * @param mixed $start [optional] The value to return in case the specified $variable did not exist or was NULL.*
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

    if (!is_int($source)) {
        // This is a nice integer
        return (integer) $source;
    }

    // Natural numbers must be integer numbers. Round to the nearest integer
    return (integer) round($source);
}


/**
 * Returns true if the specified number is a natural number.
 *
 * A natural number here is defined as one of the set of positive whole numbers; a positive integer and the number 1 and
 * any other number obtained by adding 1 to it repeatedly. For ease of use, the number one can be adjusted if needed.
 *
 * @param mixed $number
 * @param int $start
 * @return bool
 */
function is_natural(mixed $number, int $start = 1): bool
{
    if (!is_numeric($number)) {
        return false;
    }

    if ($number < $start) {
        return false;
    }

    if ($number != (integer) $number) {
        return false;
    }

    return true;
}


/**
 * Returns true if the specified number (may be any datatype) is content wise an integer
 *
 * @param mixed $source
 * @return bool
 */
function is_numeric_integer(mixed $source): bool
{
    return $source == (int) $source;
}


/**
 * Returns TRUE if the specified data entry is new.
 *
 * A data entry is considered new when the id is null, or _new
 *
 * @param DataEntryInterface|array $entry The entry to check
 * @return boolean TRUE if the specified $entry is new
 * @version 2.5.46: Added function and documentation
 */
function is_new(DataEntryInterface|array $entry): bool
{
    if (!is_array($entry)) {
        if (!is_object($entry)) {
            throw new CoreException(tr('Specified entry is not an array or object'));
        }

        return $entry->isNew();
    }

    if (isset_get($entry['status']) === '_new') {
        return true;
    }

    if (isset_get($entry['id']) === null) {
        return true;
    }

    return false;
}



/**
 * Correctly converts <br> to \n
 *
 * @param string $source
 * @param string $nl
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
        if ($argument === null) continue;
        return $argument;
    }

    return null;
}


/**
 * Return a randomly picked argument
 *
 * @param mixed ...$arguments
 * @return mixed
 */
function pick_random(mixed ...$arguments): mixed
{
    return Arrays::getRandomValue($arguments);
}


/**
 * Return randomly picked arguments
 *
 * @param int $count
 * @param mixed ...$arguments
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
            ':args' => count($arguments)
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
 * @param int $trace_offset
 * @param bool $quiet
 * @return mixed
 */
function show(mixed $source = null, int $trace_offset = 1, bool $quiet = false): mixed
{
    if (Debug::getEnabled()) {
        if (Core::scriptStarted()) {
            return Debug::show($source, $trace_offset, $quiet);
        }

        return show_system($source, false);
    }

    return null;
}


/**
 * Shortcut to the Debug::show() call, but displaying the data in hex
 *
 * @param mixed $source
 * @param int $trace_offset
 * @param bool $quiet
 * @return mixed
 */
function showhex(mixed $source = null, int $trace_offset = 1, bool $quiet = false): mixed
{
    if (Debug::getEnabled()) {
        $source = bin2hex($source);
        return show($source, $trace_offset);
    }

    return null;
}


/**
 * Shortcut to the Debug::show() call, but displaying the backtrace
 *
 * @param int $count
 * @param int $trace_offset
 * @param bool $quiet
 * @return mixed
 */
function showbacktrace(int $count = 0, int $trace_offset = 1, bool $quiet = false): mixed
{
    if (Debug::getEnabled()) {
        $backtrace = Debug::backtrace();
        $backtrace = Debug::formatBackTrace($backtrace);

        if ($count) {
            $backtrace = Arrays::limit($backtrace, $count);
        }

        return show($backtrace, $trace_offset, $quiet);
    }

    return null;
}


/**
 * Shortcut to the Debug::show() call
 *
 * @param mixed $source
 * @param int $trace_offset
 * @param bool $quiet
 * @return void
 */
#[NoReturn] function showdie(mixed $source = null, int $trace_offset = 2, bool $quiet = false): void
{
    if (Debug::getEnabled()) {
        if (Core::scriptStarted()) {
            Debug::showdie($source, $trace_offset, $quiet);
        }

        show_system($source);
    }
}


/**
 * Return $source if $source is not considered "empty".
 *
 * Return null if specified variable is considered "empty", like 0, "", array(), etc.
 *
 * @param mixed $source The value to be tested. If this value doesn't evaluate to empty, it will be returned
 * @return mixed Either $source or null, depending on if $source is empty or not
 * @see get_empty()
 * @note This function is a wrapper for get_empty($source, null);
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
 * Returns NOT $source, unless $source is a NULL, which returns NULL.
 *
 * @param bool|null $source
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
 * @param mixed $source
 * @param int|null $larger_than
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
 * @param bool $allow_zero
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
 * @param bool $allow_null
 * @return int|null
 */
function get_integer(mixed $source, bool $allow_null = true): ?int
{
    if (is_integer($source)) {
        // Well that was easy!
        return $source;
    }

    if (!is_string($source)) {
        throw new OutOfBoundsException(tr('Specified data ":source" is not an integer value', [
            ':source' => $source
        ]));
    }

    if ($source === '') {
        if ($allow_null) {
            // Interpret this as a NULL value
            return null;
        }

        return 0;

    } else {
        $old_source = $source;
        $source     = (int) $source;

        if ($old_source != $source) {
            throw new OutOfBoundsException(tr('Specified data ":source" is not an integer value', [
                ':source' => $old_source
            ]));
        }
    }

    return $source;
}


/**
 * Return the value quoted if non-numeric string
 *
 * @param string|int $value
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
 * @param array $array
 * @param mixed $default
 * @return mixed
 */
function ensure_value(string|int $value, array $array, mixed $default): mixed
{
    if (in_array($value, $array)) {
        return $value;
    }

    return $default;
}


/**
 * Execute the specified callback function with the specified $params only if the callback has been set with an
 * executable function
 *
 * @version 2.0.6: Added documentation
 * @param callable|null $callback
 * @param array|null $params
 * @return string|null The results from the callback function, or null if no callback function was specified
 */
function execute_callback(?callable $callback, ?array $params = null): ?string
{
    if (is_callable($callback)) {
        return $callback($params);
    }

    return null;
}


/**
 * Execute the specified script file
 *
 * @param string $__file
 * @return void
 * @throws Throwable
 */
function execute_script(string $__file): void
{
    try {
        include($__file);

    } catch (Throwable $e) {
        // Did this fail because the specified file does not exist?
        File::new($__file, DIRECTORY_SCRIPTS)->checkReadable('script', $e);
    }
}


/**
 * Execute the page and return the contents
 *
 * @param string $__file
 * @return string|null
 */
function execute_page(string $__file): ?string
{
    include($__file);
    $body = '';

    // Get all output buffers and restart buffer
    while (ob_get_level()) {
        $body .= ob_get_contents();
        ob_end_clean();
    }

    ob_start(chunk_size: 4096);

    // Merge the flash messages from sessions into page flash messages
    Page::getFlashMessages()->pullMessagesFrom(Session::getFlashMessages());

    return $body;
}


/**
 * ??? No idea what this is supposed to do or if its important. Figure it out later, I guess?
 *
 * @param mixed $variable
 * @param int $level
 * @return mixed
 */
function variable_zts_safe(mixed $variable, int $level = 0): mixed
{
    if (!defined('PHP_ZTS')) {
        return $variable;
    }

    if (++$level > 20) {
        // Recursion level reached, until here, no further!
        return '***  Resource limit reached! ***';
    }

    if (is_resource($variable)) {
        $variable = print_r($variable, true);
    }

    if (is_array($variable) or (is_object($variable) and (($variable instanceof Exception) or ($variable instanceof Error)))) {
        foreach ($variable as $key => &$value) {
            if ($key === 'object') {
                $value = print_r($value, true);

            } else {
                $value = variable_zts_safe($value, $level);
            }
        }

    } elseif (is_object($variable)) {
        $variable = print_r($variable, true);
    }

    unset($value);
    return $variable;
}


/**
 * Returns the system SQL database object
 *
 * @param ConnectorInterface|string $connector
 * @param bool $use_database
 * @return SqlInterface
 */
function sql(ConnectorInterface|string $connector = 'system', bool $use_database = true): SqlInterface
{
    return Databases::Sql($connector, $use_database);
}


/**
 * Returns the system SQL database object
 *
 * @param string|null $instance_name
 * @return Mc
 */
function mc(?string $instance_name = null): Mc
{
    return Databases::Mc($instance_name);
}


/**
 * Returns the system SQL database object
 *
 * @param string|null $instance_name
 * @return Mongo
 */
function mongo(?string $instance_name = null): Mongo
{
    return Databases::Mongo($instance_name);
}


/**
 * Returns the system SQL database object
 *
 * @param string|null $instance_name
 * @return Redis
 */
function redis(?string $instance_name = null): Redis
{
    return Databases::Redis($instance_name);
}


/**
 * Returns the system SQL database object
 *
 * @param string|null $instance_name
 * @return NullDb
 */
function null(?string $instance_name = null): NullDb
{
    return Databases::NullDb($instance_name);
}


/**
 * Returns true if the specified class has the specified trait
 *
 * @param string $trait
 * @param object|string $class
 * @return bool
 */
function has_trait(string $trait, object|string $class): bool
{
    while ($class) {
        $traits = class_uses($class);

        if (in_array($trait, $traits)) {
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
 * @param bool $die
 * @return mixed
 * @throws \Exception
 */
#[NoReturn] function show_system(mixed $source = null, bool $die = true): mixed
{
    if (php_sapi_name() !== 'cli') {
        // Only add this on browsers
        echo '<pre>' . PHP_EOL . '"';
    }

    echo 'message-' . random_int(1,10000) . PHP_EOL . '"';
    print_r($source);
    echo '"' . PHP_EOL;

    if ($die) {
        exit('die-'.random_int(1,10000) . PHP_EOL);
    }

    return $source;
}


/**
 * Returns true if the specified function was called
 *
 * @param string $function
 * @return bool
 */
function function_called(string $function): bool
{
    // Clean requested function
    $function = trim($function);

    if (str_ends_with($function, '()')) {
        $function = substr($function, 0, -2);
    }

    // Divide into class and function
    $class    = Strings::until($function, '::', require: true);
    $class    = strtolower(trim($class));
    $function = Strings::from($function, '::');
    $function = strtolower(trim($function));

    // Scan trace for class and function match
    foreach (debug_backtrace() as $trace) {
        $trace['function'] = strtolower(trim((string) $trace['function']));
        $trace['class']    = strtolower(trim((string) isset_get($trace['class'])));
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
 * Returns a DateTime object for NOW
 *
 * @param DateTimeZoneInterface|string|null $timezone
 * @return DateTimeInterface
 */
function now(DateTimeZoneInterface|string|null $timezone = 'system'): DateTimeInterface
{
    return new \Phoundation\Date\DateTime('now', $timezone);
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
