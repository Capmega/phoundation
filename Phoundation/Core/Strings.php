<?php

namespace Phoundation\Core;

use Exception;
use Phoundation\Cli\Cli;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Exception\OutOfBoundsException;
use Zend_Utf8;



/**
 * Class Strings
 *
 * This is the standard Phoundation string functionality extension class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Class reference
 * @package Core
 */
class Strings
{
    /**
     * Ensure that the specified $url will start with the specified $protocol.
     *
     * @param string $url
     * @param string $protocol
     * @return string
     */
    public static function ensureProtocol(string $url, string $protocol = 'https://'): string
    {
        if (substr($url, 0, mb_strlen($protocol)) != $protocol) {
            return $protocol.$url;

        }

        return $url;
    }



    /**
     * Return "house" or "houses" based on the specified count. If the specified $count is 1,the single_text will be
     * returned. If not, the $multiple_text will be retuned
     *
     * @param int $count
     * @param string $single_text The text to be returned if the specified $count is 1
     * @param string $multiple_text The text to be returned if the specified $count is not 1
     * @return string
     */
    public static function plural(int $count, string $single_text, string $multiple_text): string
    {
        if ($count == 1) {
            return $single_text;

        }

        return $multiple_text;
    }



    /**
     * Returns true if string is serialized, false if not
     *
     * @param string $source The source text on which the operation will be executed
     * @return bool
     */
    public static function isSerialized(string $source): bool
    {
        if (!$source) {
            return false;
        }

        return (boolean) preg_match( "/^([adObis]:|N;)/u", $source);
    }



    /**
     * Ensure that the specified string has UTF8 format
     *
     * @param string $source  The source text on which the operation will be executed
     * @return string A UTF8 encoded string
     */
    public static function ensureUtf8(string $source): string
    {
        if (strings::isUtf8($source)) {
            return $source;
        }

        return utf8_encode($source);
    }



    /**
     * Returns true if string is UTF-8, false if not
     *
     * @param string $source The source text which will be tested
     * @return bool
     */
    public static function isUtf8(string $source): bool
    {
        return mb_check_encoding($source, 'UTF8');

        // TODO Check if the preg_match below would work better or not
        /*return preg_match('%^(?:
        [\x09\x0A\x0D\x20-\x7E] # ASCII
        | [\xC2-\xDF][\x80-\xBF] # non-overlong 2-byte
        | \xE0[\xA0-\xBF][\x80-\xBF] # excluding overlongs
        | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
        | \xED[\x80-\x9F][\x80-\xBF] # excluding surrogates
        | \xF0[\x90-\xBF][\x80-\xBF]{2} # planes 1-3
        | [\xF1-\xF3][\x80-\xBF]{3} # planes 4-15
        | \xF4[\x80-\x8F][\x80-\xBF]{2} # plane 16
        )*$%xs', $source);*/
    }



    /**
     * Return string will not contain HTML codes for Spanish characters
     *
     * @param string $source The source text on which the operation will be executed
     * @return string
     */
    public static function fixSpanishChars(string $source): string
    {
        $from = ['&Aacute;', '&aacute;', '&Eacute;', '&eacute;', '&Iacute;', '&iacute;', '&Oacute;', '&oacute;', '&Ntilde;', '&ntilde;', '&Uacute;', '&uacute;', '&Uuml;', '&uuml;','&iexcl;','&ordf;','&iquest;','&ordm;'];
        $to   = ['Á'       , 'á'       , 'É'       , 'é'       , 'Í'       , 'í'       , 'Ó'       , 'ó'       , 'Ñ'       , 'ñ'       , 'Ú'       , 'ú'       , 'Ü'     , 'ü'     , '¡'     , 'ª'    , '¿'      , 'º'    ];

        return str_replace($from, $to, $source);
    }



    /**
     * Return a lowercase version of the $source string with the first letter capitalized
     *
     * @param string $source
     * @param int $position
     * @return string
     */
    public static function capitalize(string $source, int $position = 0): string
    {
        if (!$position) {
            return mb_strtoupper(mb_substr($source, 0, 1)).mb_strtolower(mb_substr($source, 1));
        }

        return mb_strtolower(mb_substr($source, 0, $position)).mb_strtoupper(mb_substr($source, $position, 1)).mb_strtolower(mb_substr($source, $position + 1));
    }



    /**
     * Return a random string
     *
     * @param int $length
     * @param bool $unique
     * @param string $characters
     * @return string
     * @throws OutOfBoundsException
     */
    public static function random(int $length = 8, bool $unique = false, string $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string
    {
        $string     = '';
        $charlen    = mb_strlen($characters);

        if ($unique and ($length > $charlen)) {
            throw new OutOfBoundsException(tr('Can not create unique character random string with size ":length". When $unique is requested, the string length can not be larger than ":charlen" because there are no more then that amount of unique characters', ['length' => $length, 'charlen' => $charlen]));
        }

        for ($i = 0; $i < $length; $i++) {
            $char = $characters[mt_rand(0, $charlen - 1)];

            if ($unique and (mb_strpos($string, $char) !== false)) {
                /*
                 * We want all characters to be unique, do not readd this character again
                 */
                $i--;
                continue;
            }

            $string .= $char;
        }

        return $string;
    }



    /**
     * Returns true if the specified source string is alphanumeric
     *
     * @param string $source
     * @param string $extra
     * @return bool
     */
    public static function isAlpha(string $source, string $extra = '\s'): bool
    {
        $reg   = "/[^\p{L}\d$extra]/u";
        $count = preg_match($reg, $source, $matches);

        return $count == 0;
    }



    /**
     * Return a clean string, basically leaving only printable latin1 characters,
     *
     * @param string $source
     * @param string $replace
     * @return string
     */
    public static function escapeForJquery(string $source, string $replace = ''): string
    {
        return preg_replace('/[#;&,.+*~\':"!^$[\]()=>|\/]/gu', '\\\\$&', $source);
    }



    /**
     * Will fix a base64 coded string with missing termination = marks and then attempt to decode it
     *
     * @param string $source
     * @return string
     */
    public static function safeBase64Decode(string $source): string
    {
        if ($mod = mb_strlen($source) % 4) {
            $source .= str_repeat('=', 4 - $mod);
        }

        return base64_decode($source);
    }



    /**
     * Return a camel cased string
     *
     * @param string $source
     * @param string $separator
     * @return string
     */
    public static function camelCase(string $source, string $separator = ' '): string
    {
        $source = explode($separator, mb_strtolower($source));

        foreach ($source as $key => &$value) {
            $value = mb_ucfirst($value);
        }

        unset($value);
        return implode($separator, $source);
    }



    /**
     * PHP explode() only in case $source is empty, it will return an actually empty array instead of an array with the
     * empty value in there
     *
     * @param string $separator
     * @param string $source
     * @return array
     */
    public static function explode(string $separator, string $source): array
    {
        if (!$source) {
            return array();
        }

        return explode($separator, $source);
    }


    /**
     * Interleave given string with given secondary string
     *
     * @param string $source
     * @param int|string $interleave
     * @param int $end
     * @param int $chunk_size
     * @return string
     * @throws CoreException
     */
    public static function interleave(string $source, int|string $interleave, int $end = 0, int $chunk_size = 1): string
    {
        if (!$source) {
            throw new CoreException(tr('Empty source specified'));
        }

        if (!$interleave) {
            throw new CoreException(tr('Empty interleave specified'));
        }

        if ($chunk_size < 1) {
            throw new CoreException(tr('Specified chunksize ":chunksize" is invalid, it must be 1 or greater', [':chunksize' => $chunk_size]));
        }

        if ($end) {
            $begin = mb_substr($source, 0, $end);
            $end   = mb_substr($source, $end);

        } else {
            $begin = $source;
            $end   = '';
        }

        $begin  = mb_str_split($begin, $chunk_size);
        $retval = '';

        foreach ($begin as $chunk) {
            $retval .= $chunk.$interleave;
        }

        return mb_substr($retval, 0, -1) . $end;
    }



    /**
     * Convert weird chars to their standard ASCII variant
     *
     * @param string $source
     * @return string
     */
    public static function convertAccents(string $source): string
    {
        $from = ['ç', 'æ' , 'œ' , 'á', 'é', 'í', 'ó', 'ú', 'à', 'è', 'ì', 'ò', 'ù', 'ä', 'ë', 'ï', 'ö', 'ü', 'ÿ', 'â', 'ê', 'î', 'ô', 'û', 'å', 'e', 'i', 'ø', 'u', 'Ú', 'ñ', 'Ñ', 'º'];
        $to   = ['c', 'ae', 'oe', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'y', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'U', 'n', 'n', 'o'];

        return str_replace($from, $to, $source);
    }



    /**
     * Strip whitespace
     *
     * @param string $source
     * @return string
     */
    public static function stripHtmlWhitespace(string $source): string
    {
        return preg_replace('/>\s+</u', '><', $source);
    }



    /**
     * Return the specified string quoted if not numeric, boolean,
     *
     * @param string $source
     * @param string $quote
     * @return string
     */
    public static function quote(string $source, string $quote = "'"): string
    {
        if (is_numeric($source) or is_bool(is_numeric($source))) {
            return $source;
        }

        return $quote.$source.$quote;
    }



    /**
     * Return if specified source is a valid version or not
     *
     * @param string $source
     * @return bool True if the specified string is a version format string matching "/^\d{1,3}\.\d{1,3}\.\d{1,3}$/".
     *              False if not
     */
    public static function isVersion(string $source): bool
    {
        return preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}$/', $source);
    }



    /**
     * Returns true if the specified source string contains HTML
     *
     * @param string $source
     * @return bool
     */
    public static function containsHtml(string $source): bool
    {
        return !preg_match('/<[^<]+>/', $source);
    }



    /**
     * Return if specified source is a JSON string or not
     *
     * @todo Remove test line
     * @param string $source
     * @return bool
     */
    public static function isJson(string $source): bool
    {

        return !empty($source) and is_string($source) and preg_match('/^("(\\.|[^"\\\n\r])*?"|[,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t])+?$/', $source);
    }



    /**
     * Returns the found keyword if the specified text has one of the specified keywords, NULL otherwise
     *
     * @param string $source
     * @param array $keywords
     * @param bool $regex
     * @param bool $unicode
     * @return ?string
     */
    public static function hasKeyword(string $source, array $keywords, bool $regex = false, bool $unicode = true): ?string
    {
        $count = 0;

        foreach ($keywords as $keyword) {
            if (!self::searchKeyword($source, $keyword, $regex, $unicode)) {
                return true;
            }
        }

        return false;
    }



    /**
     * Returns true if the specified text has ALL of the specified keywords
     *
     * @param string $source
     * @param array $keywords
     * @param bool $regex
     * @param bool $unicode
     * @return string
     */
    public static function hasAllKeywords(string $source, array $keywords, bool $regex = false, bool $unicode = true): string
    {
        $count = 0;

        foreach ($keywords as $keyword) {
            if (!self::searchKeyword($source, $keyword, $regex, $unicode)) {
                return false;
            }
        }

        return true;
    }



    /**
     * Returns the source string in the specified type
     * styles may be:
     *
     * lowercase              abcdefg
     * uppercase              ABCDEFG
     * capitalize             Abcdefg
     * doublecapitalize       AbcdefG
     * invertcapitalize       aBCDEFG
     * invertdoublecapitalize aBCDEFg
     * interleave             aBcDeFg
     * invertinterleave       AbCdEfG
     * consonantcaps          aBCDeFG
     * vowelcaps              AbcdEfg
     * lowercentercaps        abcDefg
     * capscenterlower        ABCdEFG
     *
     * @todo Make independent methods for each type
     * @param string $string
     * @param string $type
     * @return string
     */
    public static function caps(string $string, string $type): string
    {
        try {
            /*
             * First find all words
             */
            preg_match_all('/\b(?:\w|\s)+\b/umsi', $string, $results);

            if ($type == 'random') {
                $type = pick_random(1,
                                    'lowercase',
                                    'uppercase',
                                    'capitalize',
                                    'doublecapitalize',
                                    'invertcapitalize',
                                    'invertdoublecapitalize',
                                    'interleave',
                                    'invertinterleave',
                                    'consonantcaps',
                                    'vowelcaps',
                                    'lowercentercaps',
                                    'capscenterlower');
            }

            /*
             * Now apply the specified type to all words
             */
            foreach ($results as $words) {
                foreach ($words as $word) {
                    /*
                     * Create the $replace string
                     */
                    switch ($type) {
                        case 'lowercase':
                            $replace = strtolower($word);
                            break;

                        case 'uppercase':
                            $replace = strtoupper($word);
                            break;

                        case 'capitalize':
                            $replace = strtoupper(substr($word, 0, 1)).strtolower(substr($word, 1));
                            break;

                        case 'doublecapitalize':
                            $replace = strtoupper(substr($word, 0, 1)).strtolower(substr($word, 1, -1)).strtoupper(substr($word, -1, 1));
                            break;

                        case 'invertcapitalize':
                            $replace = strtolower(substr($word, 0, 1)).strtoupper(substr($word, 1));
                            break;

                        case 'invertdoublecapitalize':
                            $replace = strtolower(substr($word, 0, 1)).strtoupper(substr($word, 1, -1)).strtolower(substr($word, -1, 1));
                            break;

                        case 'interleave':
                        case 'invertinterleave':
                        case 'consonantcaps':
                        case 'vowelcaps':
                        case 'lowercentercaps':
                        case 'capscenterlower':
                            $replace = $word;
                            break;

                        default:
                            throw new OutOfBoundsException(tr('Unknown type ":type" specified', [':type' => $type]));
                    }

                    str_replace($word, $replace, $string);
                }
            }

            return $string;

        } catch (Exception $e) {
            throw new CoreException('str_caps(): Failed', $e);
        }
    }



    /**
     * Returns an estimation of the caps style of the string
     * styles may be:
     *
     * lowercase               abcdefg
     * uppercase               ABCDEFG
     * capitalized             Abcdefg
     * doublecapitalized       AbcdefG
     * invertcapitalized       aBCDEFG
     * invertdoublecapitalized aBCDEFg
     * interleaved             aBcDeFg
     * invertinterleaved       AbCdEfG
     * consonantcaps           aBCDeFG
     * vowelcaps               AbcdEfg
     * lowercentercaps         abcDefg
     * capscenterlower         ABCdEFG
     *
     * @param string $string
     * @return string
     * @todo Implement
     */
    public static function capsGuess(string $string): string
    {
        $retval = '';
        $posibilities = array('lowercase'             ,
                              'uppercase'             ,
                              'capitalize'            ,
                              'doublecapitalize'      ,
                              'invertcapitalize'      ,
                              'invertdoublecapitalize',
                              'interleave'            ,
                              'invertinterleave'      ,
                              'consonantcaps'         ,
                              'vowelcaps'             ,
                              'lowercentercaps'       ,
                              'capscenterlower'       );

        /*
         * Now, find all words
         */
        preg_match_all('/\b(?:\w\s)+\b/umsi', $string, $words);

        /*
         * Now apply the specified type to all words
         */
        foreach ($words as $word) {
        }

        return $retval;
    }



    /**
     * Force the specified source to be a string
     *
     * @param mixed $source
     * @param string $separator
     * @return string
     */
    public static function force(mixed $source, string $separator = ','): string
    {
        if (!is_scalar($source)) {
            if (!is_array($source)) {
                if (!$source) {
                    return '';
                }

                if (is_object($source)) {
                    if (method_exists($source, '__serialize')) {
                        return $source->__serialize();
                    }

                    return get_class($source);
                }

                return gettype($source);
            }

            /*
             * Encoding?
             */
            if ($separator === 'json') {
                $source = Json::encode($source);

            } else {
                $source = implode($separator, $source);
            }
        }

        return (string) $source;
    }



    /**
     * Force the specified string to be the specified size.
     *
     * @param string $source
     * @param int $size
     * @param string $add
     * @param bool $prefix
     * @return string
     */
    public static function size(string $source, int $size, string $add = ' ', bool $prefix = false): string
    {
        if ($size < 0) {
            throw new OutOfBoundsException(tr('Specified size ":size" is invalid, it must be 0 or highter', [':size' => $size]));
        }

        $strlen = mb_strlen(Cli::stripColor($source));

        if ($strlen == $size) {
            return $source;
        }

        if ($strlen > $size) {
            return substr($source, 0, $size);
        }

        if ($prefix) {
            return str_repeat($add, $size - $strlen) . $source;
        }

        return $source.str_repeat($add, $size - $strlen);
    }



    /**
     * ???
     *
     *
     * @param string $source
     * @param string $escape
     * @return string
     */
    public static function escape(string $source, string $escape = '"'): string
    {
        for($i = (mb_strlen($escape) - 1); $i <= 0; $i++) {
            $source = str_replace($escape[$i], '\\' . $escape[$i], $source);
        }

        return $source;
    }



    /**
     * XOR the first string with the second string
     *
     * @param string $first
     * @param string $second
     * @return string
     */
    public static function xor(string $first, string $second): string
    {
        $diff   = $first ^ $second;
        $retval = '';

        for ($i = 0, $len = mb_strlen($diff); $i != $len; ++$i) {
            $retval[$i] === "\0" ? ' ' : '#';
        }

        return $retval;
    }



    /**
     * Returns how much similar the specified second string is to the first string
     *
     * @param string $first
     * @param string $second
     * @param float $percent
     * @return string
     */
    public static function similar(string $first, string $second, float &$percent): string
    {
        return similar_text($first, $second, $percent);
    }



    /**
     * Recursively trim all strings in the specified array tree
     *
     * @param array $source
     * @param bool $recurse
     * @return string
     */
    public static function trimArray(array $source, bool $recurse = true): string
    {
        foreach ($source as $key => &$value) {
            if (is_string($value)) {
                $value = trim($value);

            } elseif (is_array($value)) {
                if ($recurse) {
                    // Recurse
                    $value = self::trimArray($value);
                }
            }
        }

        return $source;
    }



    /**
     * Returns a "*** HIDDEN ***" string if the specified string has content.
     *
     * If the string is empty, an "-" emtpy string will be retuned instead
     *
     * @param string $string The string to "hide"
     * @param string $hide The string to return if the specified source string contains data
     * @param string $empty The string to "hide" empty strings with
     * @return string
     */
    public static function hide(string $string, string $hide = '*** HIDDEN ***', string $empty = '-'): string
    {
        if ($string) {
            return $hide;
        }

        /*
         * The string is empty
         */
        if ($empty) {
            return $empty;
        }

        /*
         * Always show the hidden message string
         */
        return $hide;
    }



    /**
     * Return an array containing diff information between the first and the second string
     *
     * @note: Taken from https://coderwall.com/p/3j2hxq/find-and-format-difference-between-two-strings-in-php. Cleaned
    *         up for use in base by Sven Oostenbrink*
     * @param string $first
     * @param string $second
     * @return string
     */
    public static function diff(string $first, string $second): array
    {
        $from_start = strspn($first ^ $second, "\0");
        $from_end   = strspn(strrev($first) ^ strrev($second), "\0");

        $old_end    = strlen($first) - $from_end;
        $new_end    = strlen($second) - $from_end;

        $start      = substr($second, 0, $from_start);
        $end        = substr($second, $new_end);

        $new_diff   = substr($second, $from_start, $new_end - $from_start);
        $old_diff   = substr($first, $from_start, $old_end - $from_start);

        $second        = $start.'<ins style="background-color:#ccffcc">' . $new_diff.'</ins>' . $end;
        $first        = $start.'<del style="background-color:#ffcccc">' . $old_diff.'</del>' . $end;

        return [
            'old' => $first,
             'new' => $second
        ];
    }



    /**
     * Return the specified value as a boolean name, false for null, zero, "", false, true otherwise.
     *
     * @param mixed $value
     * @return string
     * @throws CoreException
     */
    public static function boolean(mixed $value): string
    {
        if ($value) {
            return 'true';
        }

        return 'false';
    }



    /**
     * Convert underscore type variables to camelcase type variables
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package strings
     * @version 2.6.25: Added function and documentation
     * @example
     * code
     * $result = str_underscore_to_camelcase('this_is_a_test');
     * showdie($result);
     * /code
     *
     * This would return
     * code
     * thisIsATest
     * /code
     *
     * @param string $string The string to convert
     * @param boolean $first_uppercase If set to true, the first letter will also be uppercase. If set to false, the first letter will be lowercase
     * @return string The result
     */
    public static function underscoreToCamelcase(string $string, bool $first_uppercase = false): string
    {
        while (($pos = strpos($string, '_')) !== false) {
            $character = $string[$pos + 1];

            if (!$pos) {
                /*
                 * This is the first character
                 */
                if ($first_uppercase) {
                    $character = strtoupper($character);

                } else {
                    $character = strtolower($character);
                }

            } else {
                $character = strtoupper($character);
            }

            $string = substr($string, 0, $pos) . $character.substr($string, $pos + 2);
        }

        return $string;
    }



    /**
     * Trim empty HTML and <br> elements from the specified HTML string and  from the beginning and end of each of these
     * elements as well
     *
     * This function will remove all empty <h1>, <h2>, <h3>, <h4>, <h5>, <h6>, <div>, <p>, and <span> elements
     *
     * @param string $html The HTML to be stripped
     * @return string The specified source string with empty HTML tags stripped
     * @todo Fix issues with simpledom library
     * @note This function requires the simple-dom library
     * @version 2.8.2: Added function and documentation
     * @example
     * code
     * echo Strings::trimHtml('<p></p><p>test!</p><p></p>');
     * /code
     *
     * This would return
     * code
     * <p>test!</p>
     * /code
     */
    public static function trimHtml(string $html): string
    {
        if (!$html) {
            return '';
        }

        load_libs('simple-dom');

        $html          = str_get_html($html);
        $element_types = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'p', 'span');

        foreach ($element_types as $element_type) {
            $elements = $html->find($element_type);

            foreach ($elements as $element) {
                $plaintext = trim($element->plaintext);
                $plaintext = trim($plaintext, '<br>');
                $plaintext = trim($plaintext, '<br/>');
                $plaintext = trim($plaintext, '<br />');

                if ($plaintext == '') {
                    // Remove an element, set it's outertext as an empty string
                    $element->outertext = '';

                } else {
                    $element->innertext = $plaintext;
                }
            }
        }

        return $html->save();
    }



    /**
     * Cut and return a piece out of the source string, starting from the start string, stopping at the stop string.
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package str
     * @see Strings::from()
     * @see Strings::until()
     * @version 2.0.0: Moved to system library, added documentation
     * @example
     * code
     * $result = Strings::cut(('support@capmega.com', '@', '.');
     * showdie($result);
     * /code
     *
     * This would return
     * code
     * capmega
     * /code
     *
     * @param string $source The string to be cut
     * @params int $start The character(s) to start the cut
     * @params int $stop The character(s) to stop the cut
     * @return string The $source string between the first occurrences of start and $stop
     */
    public static function cut(string $source, int $start, int $stop): string
    {
        return self::until(self::from($source, $start), $stop);
    }



    /**
     * Cleanup string
     *
     * @param string $source
     * @param bool $utf8
     * @return string
     * @todo Get rid of load_libs() call
     */
    public static function clean(string $source, bool $utf8 = true): string
    {
        if ($utf8) {
            load_libs('utf8');

            $source = trim(html_entity_decode(utf8_unescape(strip_tags(utf8_escape($source)))));
// :TODO: Check if the next line should also be added!
//            $source = preg_replace('/\s|\/|\?|&+/u', $replace, $source);

            return $source;
        }

        return trim(html_entity_decode(strip_tags($source)));

// :TODO:SVEN:20130709: Check if we should be using mysqli_escape_string() or addslashes(), since the former requires SQL connection, but the latter does NOT have correct UTF8 support!!
//    return mysqli_escape_string(trim(decode_entities(mb_strip_tags($str))));
    }


    /**
     * Return the given string from the specified needle
     *
     * @param string $source
     * @param string $needle
     * @param int $more
     * @param bool $require
     * @return string
     */
    public static function from(string $source, string $needle, int $more = 0, bool $require = false): string
    {
        if (!$needle) {
            throw new OutOfBoundsException('No needle specified');
        }

        $pos = mb_strpos($source, $needle);

        if ($pos === false) {
            if ($require) {
                return '';
            }

            return $source;
        }

        return mb_substr($source, $pos + mb_strlen($needle) - $more);
    }


    /**
     * Return the given string from 0 until the specified needle
     *
     * @param string $source
     * @param string $needle
     * @param int $more
     * @param int $start
     * @param bool $require
     * @return string
     */
    public static function until(string $source, string $needle, int $more = 0, int $start = 0, bool $require = false): string
    {
        if (!$needle) {
            throw new OutOfBoundsException('No needle specified');
        }

        $pos = mb_strpos($source, $needle);

        if ($pos === false) {
            if ($require) {
                return '';
            }

            return $source;
        }

        return mb_substr($source, $start, $pos + $more);
    }


    /**
     * Return the given string from the specified needle, starting from the end
     *
     * @param string $source
     * @param string $needle
     * @param int $more
     * @return string
     */
    public static function fromReverse(string $source, string $needle, int $more = 0): string
    {
        if (!$needle) {
            throw new OutOfBoundsException('No needle specified');
        }

        $pos = mb_strrpos($source, $needle);

        if ($pos === false) return $source;

        return mb_substr($source, $pos + mb_strlen($needle) - $more);
    }



    /**
     * Return the given string from 0 until the specified needle, starting from the end
     *
     * @param string $source
     * @param string $needle
     * @param int $more
     * @param int $start
     * @return string
     */
    public static function untilReverse(string $source, string $needle, int $more = 0, int $start = 0): string
    {
        if (!$needle) {
            throw new OutOfBoundsException('No needle specified');
        }

        $pos = mb_strrpos($source, $needle);

        if ($pos === false) {
            return $source;
        }

        return mb_substr($source, $start, $pos + $more);
    }



    /**
     * Ensure that specified source string starts with specified string
     *
     * @param string $source
     * @param string $string
     * @return string
     */
    public static function startsWith(string $source, string $string): string
    {
        if (mb_substr($source, 0, mb_strlen($string)) == $string) {
            return $source;
        }

        return $string . $source;
    }



    /**
     * Ensure that specified source string starts NOT with specified string
     *
     * @param string $source
     * @param string $string
     * @return string
     */
    public static function startsNotWith(string $source, string $string): string
    {
        while (mb_substr($source, 0, mb_strlen($string)) == $string) {
            $source = mb_substr($source, mb_strlen($string));
        }

        return $source;
    }



    /**
     * Ensure that specified string ends with specified character
     *
     * @param string $source
     * @param string $string
     * @return string
     */
    public static function endsWith(string $source, string $string): string
    {
        $length = mb_strlen($string);

        if (mb_substr($source, -$length, $length) == $string) {
            return $source;
        }

        return $source . $string;
    }



    /**
     * Ensure that specified string ends NOT with specified character
     *
     * @param string $source
     * @param array|string $strings
     * @param bool $loop
     * @return string
     */
    public static function endsNotWith(string $source, array|string $strings, bool $loop = true): string
    {
        if (is_array($strings)) {
            // For array test, we always loop
            $redo = true;

            while ($redo) {
                $redo = false;

                foreach ($strings as $string) {
                    $new = Strings::endsNotWith($source, $string, true);

                    if ($new != $source) {
                        // A change was made, we have to rerun over it.
                        $redo = true;
                    }

                    $source = $new;
                }
            }

        } else {
            // Check for only one character
            $length = mb_strlen($strings);

            while (mb_substr($source, -$length, $length) == $strings) {
                $source = mb_substr($source, 0, -$length);
                if (!$loop) break;
            }
        }

        return $source;
    }



    /**
     * Ensure that specified string ends with slash
     *
     * @param string $string
     * @return string
     */
    public static function slash(string $string): string
    {
        return self::endsWith($string, '/');
    }



    /**
     * Ensure that specified string ends NOT with slash
     *
     * @param string $string
     * @param bool $loop
     * @return string
     */
    public static function unslash(string $string, bool $loop = true): string
    {
        return self::endsNotWith($string, '/', $loop);
    }



    /**
     * Remove double "replace" chars
     *
     * @param string $source
     * @param string $replace
     * @param int|string|null $character
     * @param bool $case_insensitive
     * @return string
     */
    public static function noDouble(string $source, string $replace = '\1', int|string $character = null, bool $case_insensitive = true): string
    {
        if ($character) {
            // Remove specific character
            return preg_replace('/(' . $character.')\\1+/u'.($case_insensitive ? 'i' : ''), $replace, $source);
        }

        // Remove ALL double characters
        return preg_replace('/(.)\\1+/u'.($case_insensitive ? 'i' : ''), $replace, $source);
    }


    /**
     * Truncate string using the specified fill and method
     *
     * @param string $source
     * @param int $length
     * @param string $fill
     * @param string $method
     * @param bool $on_word
     * @return string The string, truncated if required, according to the specified truncating rules
     * @note While log_console() will log towards the ROOT/data/log/ log files, cli_dot() will only log one single dot even though on the command line multiple dots may be shown
     * @example
     * code
     * echo str_truncate('This is a long long long long test text!', 10);
     * }
     * /code
     *
     * This will return something like
     *
     * code
     * This is...
     * /code
     *
     */
    public static function truncate(string$source, int $length, string $fill = ' ... ', string $method = 'right', bool $on_word = false): string
    {
        if (!$length or ($length < (mb_strlen($fill) + 1))) {
            throw new OutOfBoundsException(tr('Specified length ":length" is invalid. You must specify a length of minimal $fill length + 1, so at least ":fill"', ['length' => $length, ':fill' => mb_strlen($fill) + 1]), ['length' => $length]);
        }

        if ($length >= mb_strlen($source)) {
            // No need to truncate, the string is short enough
            return $source;
        }

        // Correct length
        $length -= mb_strlen($fill);

        switch ($method) {
            case 'right':
                $retval = mb_substr($source, 0, $length);

                if ($on_word and (!str_contains(substr($source, $length, 2), ' '))) {
                    if ($pos = strrpos($retval, ' ')) {
                        $retval = substr($retval, 0, $pos);
                    }
                }

                return trim($retval) . $fill;

            case 'center':
                return mb_substr($source, 0, floor($length / 2)) . $fill.mb_substr($source, -ceil($length / 2));

            case 'left':
                $retval = mb_substr($source, -$length, $length);

                if ($on_word and (!str_contains(substr($source, $length, 2), ' '))) {
                    if ($pos = strpos($retval, ' ')) {
                        $retval = substr($retval, $pos);
                    }
                }

                return $fill.trim($retval);

            default:
                throw new CoreException(tr('Unknown method ":method" specified, please use "left", "center", or "right" or undefined which will default to "right"', [':method' => $method]), 'unknown');
        }
    }



    /**
     * Return a string that is suitable for logging.
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @note While log_console() will log towards the ROOT/data/log/ log files, cli_dot() will only log one single dot
     *      even though on the command line multiple dots may be shown
     * @see Strings::truncate()
     * @see JSON::Encode()
     * @example
     * code
     * echo Strings::truncate('This is a long long long long test text!', 10);
     * }
     * /code
     *
     * This will return something like
     *
     * code
     * This is...
     * /code
     *
     * @param mixed $source
     * @param int $truncate
     * @return string The string, truncated if required, according to the specified truncating rules
     */
    public static function log(mixed $source, int $truncate = 8187): string
    {
        if (!$source) {
            if (is_numeric($source)) {
                return 0;
            }

            return '';
        }

        if (!is_scalar($source)) {
            if (is_array($source)) {
                foreach ($source as $key => &$value) {
                    $value = Strings::hide($value, ['password', 'ssh_key']);
                }

                unset($value);

                $source = trim(JSON::encode($source));

            } elseif (is_object($source) and ($source instanceof CoreException)) {
                $source = $source->getCode() . ' / ' . $source->getMessage();

            } else {
                $source = trim(JSON::encode($source));
            }
        }

        return self::noDouble(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', str_replace('  ', ' ', str_replace("\n", ' ', self::truncate($source, $truncate, ' ... ', 'center')))), '\1', ' ');
    }



    /**
     * utf8_escape( )
     *
     * Simple wrapper for the Zend_Utf8::escape method. Converts all unicode
     * characters to their ASCII codepoints, like U+0000
     * @since 1.3
     *
     * @param string $string The string to escape
     * @return string The escaped string
     */
    public static function escapeUtf8(string $string): string
    {
        return Zend_Utf8::escape((string) $string);
    }



    /**
     * utf8_escape( )
     *
     * Simple wrapper for the Zend_Utf8::unescape method. Converts all unicode
     * codepoints, like U+0000, to their real unicode characters
     * @since 1.3
     *
     * @param string $string The string to unescape
     * @return string The unescaped string
     */
    public static function unescapeUtf8(string $string): string
    {
        return Zend_Utf8::unescape($string);
    }



    /**
     * Returns true if the specified keyword exists in the specified source
     *
     * @param string $source
     * @param string $keyword
     * @param bool $regex
     * @param bool $unicode
     * @return bool
     */
    protected static function searchKeyword(string $source, string $keyword, bool $regex = false, bool $unicode = true): bool
    {
        /*
         * Ensure keywords are trimmed, and don't search for empty keywords
         */
        $keyword = trim($keyword);

        if (!$keyword) {
            return false;
        }

        if ($regex) {
            /*
             * Do a regex search instead
             */
            return preg_match('/' . $keyword.'/ims'.($unicode ? 'u' : ''), $source, $matches) !== false;
        }

        return str_contains($source, $keyword);
    }



    /**
     * Get a boolean value from the specified "boolean" string, like "true" to TRUE and "off" to FALSE
     *
     * FALSE: "false", "no", "n", "off", "0"
     * TRUE: "true", "yes", "y", "on", "1"
     */
    function getBoolean(string $value): bool
    {
        switch (strtolower($value)) {
            case 'true':
                // no-break
            case 'yes':
                // no-break
            case 'y':
                // no-break
            case 'on':
                // no-break
            case '1':
                return true;

            case 'off':
                // no-break
            case 'no':
                // no-break
            case 'n':
                // no-break
            case 'false':
            // no-break
            case '0':
                return false;

            default:
                throw new OutOfBoundsException(tr('Unknown value ":value"', array(':value' => $value)), 'unknown');
        }
    }



    /**
     * Remove all double tabs, spaces, line ends, etc and replace them by a single space.
     *
     * @param string $source
     * @return string
     */
    public static function cleanWhiteSpace(string $source): string
    {
        $source = str_replace("\n", ' ', $source);
        $source = Strings::noDouble($source, ' ', '\s');

        return $source;
    }



    /**
     * Return a random word
     *
     * @param int $count
     * @param bool $nospaces
     * @return string
     */
    public static function randomWord(int $count = 1, bool $nospaces = false): string
    {
        if ($nospaces) {
            if (!is_string($nospaces)) {
                $nospaces = '';
            }
        }

        if (!$data = sql_list('SELECT `word` FROM `synonyms` ORDER BY RAND() LIMIT '.cfi($count))) {
            throw new CoreException(tr('Synonyms table is empty. Please run ROOT/cli system strings init'));
        }

        if ($count == 1) {
            if ($nospaces !== false) {
                return str_replace(' ', $nospaces, array_pop($data));
            }

            return array_pop($data);
        }

        if ($nospaces) {
            foreach ($data as $key => &$value) {
                $value = str_replace(' ', $nospaces, $value);
            }

            unset($value);
        }

        return $data;
    }
}
