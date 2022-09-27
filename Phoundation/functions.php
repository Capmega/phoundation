<?php
/**
 * functions file functions.php
 *
 * This is the core functions library file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
    try{
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
    if(!is_array($entry)){
        throw new CoreException(tr('is_new(): Specified entry is not an array'), 'invalid');
    }

    if(isset_get($entry['status']) === '_new'){
        return true;
    }

    if(isset_get($entry['id']) === null){
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
