<?php
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
use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Strings;



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
 * On non production systems, tr() will perform a check on both the $text and $replace data to ensure that all markers
 * have been replaced, and non were forgotten. If results were found, an exception will be thrown. This behaviour does
 * NOT apply to production systems.
 *
 * @param string $text
 * @param array|null $replace
 * @param boolean $verify
 * @return string
 */
function tr(string $text, ?array $replace = null, bool $verify = true): string
{
    try {
        if ($replace) {
            foreach ($replace as &$value) {
                $value = Strings::log($value);
            }

            unset($value);

            $text = str_replace(array_keys($replace), array_values($replace), $text, $count);

            /*
             * Only on non production machines, crash when not all entries were replaced as an extra check.
             */
            if (core::isProduction() and $verify) {
                if ($count != count($replace)) {
                    foreach ($replace as $value) {
                        if (str_contains($value, ':')) {
                            /*
                             * One of the $replace values contains :blah. This will cause the detector to fire off
                             * incorrectly. Ignore this.
                             */
                            return $text;
                        }
                    }

                    throw new CoreException('tr(): Not all specified keywords were found in text');
                }

                /*
                 * Do NOT check for :value here since the given text itself may contain :value (ie, in prepared statements!)
                 */
            }

            return $text;
        }

        return $text;

    } catch (Exception $e) {
        /*
         * Do NOT use tr() here for obvious endless loop reasons!
         */
        throw new CoreException('tr(): Failed with text "'.Strings::log($text) . '". Very likely issue with $replace not containing all keywords, or one of the $replace values is non-scalar', $e);
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
 * Return the value if it actually exists, or NULL instead.
 *
 * If (for example) a non-existing key from an array was specified, NULL will be returned instead of causing a variable
 *
 * @note IMPORTANT! After calling this function, $var will exist in the scope of the calling function!
 * @param mixed $variable The variable to test
 * @param mixed $return (optional) The value to return in case the specified $variable did not exist or was NULL.*
 * @return mixed
 */
function isset_get(mixed &$variable, mixed $return = null): mixed
{
    /*
     * The variable exists
     */
    if (isset($variable)) {
        return $variable;
    }

    /*
     * The previous isset would have actually set the variable with null, unset it to ensure it won't exist
     */
    unset($variable);

    return $return;
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
        /*
         * This isn't even a number
         */
        return $default;
    }

    if ($source < $start) {
        /*
         * Natural numbers have to be > 1 (by detault, $start might be adjusted where needed)
         */
        return $default;
    }

    if (!is_int($source)) {
        /*
         * This is a nice integer
         */
        return (integer) $source;
    }

    /*
     * Natural numbers must be integer numbers. Round to the nearest integer
     */
    return (integer) round($source);
}



/**
 * Returns true if the specified number is a natural number.
 *
 * A natural number here is defined as one of the set of positive whole numbers; a positive integer and the number 1 and
 * any other number obtained by adding 1 to it repeatedly. For ease of use, the number one can be adjusted if needed.
 *
 * @param $number
 * @param int $start
 * @return bool
 */
function is_natural($number, int $start = 1): bool
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
 * Returns true if the specified string is a version, or false if it is not
 *
 * @version 2.5.46: Added function and documentation
 * @param string $version The version to be validated
 * @return boolean True if the specified $version is an N.N.N version string
 */
function is_version(string $version): bool
{
    return preg_match('/\d+\.\d+\.\d+/', $version);
}



/**
 * Returns TRUE if the specified data entry is new.
 *
 * A data entry is considered new when the id is null, or _new
 *
 * @param array|object $entry The entry to check
 * @return boolean TRUE if the specified $entry is new
 * @version 2.5.46: Added function and documentation
 */
function is_new(array|object $entry): bool
{
    if (!is_array($entry)){
        throw new CoreException(tr('is_new(): Specified entry is not an array'), 'invalid');
    }

    if (isset_get($entry['status']) === '_new'){
        return true;
    }

    if (isset_get($entry['id']) === null){
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
public function br2nl(string $source, string $nl = "\n"): string
{
    $source = preg_replace("/(\r\n|\n|\r)/u", '' , $source);
    $source = preg_replace("/<br *\/?>/iu"  , $nl, $source);

    return $source;
}



/*
 * Return the first non empty argument
 */
function not_empty()
{
    foreach (func_get_args() as $argument) {
        if ($argument) {
            return $argument;
        }
    }

    return $argument;
}


/*
 * Return the first non null argument
 */
function not_null()
{
    foreach (func_get_args() as $argument) {
        if ($argument === null) continue;
        return $argument;
    }
}


/*
 * Return the first non empty argument
 */
function pick_random($count)
{
    try {
        $args = func_get_args();

        /*
         * Remove the $count argument from the list
         */
        array_shift($args);

        if (!$count) {
            /*
             * Get a random count
             */
            $count = mt_rand(1, count($args));
            $array = true;
        }

        if (($count < 1) or ($count > count($args))) {
            throw new OutOfBoundsException(tr('pick_random(): Invalid count ":count" specified for ":args" arguments', array(':count' => $count, ':args' => count($args))), 'invalid');

        } elseif ($count == 1) {
            if (empty($array)) {
                return $args[array_rand($args, $count)];
            }

            return array($args[array_rand($args, $count)]);

        } else {
            $retval = array();

            for ($i = 0; $i < $count; $i++) {
                $retval[] = $args[$key = array_rand($args)];
                unset($args[$key]);
            }

            return $retval;
        }

    } catch (Exception $e) {
        throw new OutOfBoundsException(tr('pick_random(): Failed'), $e);
    }
}



/*
 * Return $source if $source is not considered "empty". Return null if specified variable is considered "empty", like 0, "", array(), etc.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
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
 *
 * @param mixed $source The value to be tested. If this value doesn't evaluate to empty, it will be returned
 * @return mixed Either $source or null, depending on if $source is empty or not
 */
function get_null($source)
{
    try {
        return get_empty($source, null);

    } catch (Exception $e) {
        throw new OutOfBoundsException(tr('get_null(): Failed'), $e);
    }
}


/*
 * Return $source if $source is not considered "empty". Return $default if specified variable is considered "empty", like 0, "", array(), etc.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see get_null()
 * @version 2.6.27: Added function and documentation
 * @example
 * code
 * $result = get_empty(null, false);
 * showdie($result);
 * /code
 *
 * This would return
 * code
 * false
 * /code
 *
 * @param mixed $source The value to be tested. If this value doesn't evaluate to empty, it will be returned
 * @param mixed $default The value to be returned if $source evalidates to empty
 * @return mixed Either $source or $default, depending on if $source is empty or not
 */
function get_empty($source, $default)
{
    try {
        if ($source) {
            return $source;
        }

        return $default;

    } catch (Exception $e) {
        throw new OutOfBoundsException(tr('get_empty(): Failed'), $e);
    }
}


/*
 * Return the value quoted if non numeric string
 */
function quote($value)
{
    try {
        if (!is_numeric($value) and is_string($value)) {
            return '"' . $value . '"';
        }

        return $value;

    } catch (Exception $e) {
        throw new OutOfBoundsException(tr('quote(): Failed'), $e);
    }
}

/*
 *
 */
function ensure_value($value, $enum, $default)
{
    try {
        if (in_array($value, $enum)) {
            return $value;
        }

        return $default;

    } catch (Exception $e) {
        throw new OutOfBoundsException(tr('ensure_value(): Failed'), $e);
    }
}

