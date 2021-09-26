<?php

namespace Phoundation\Core;

use Exception;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Exception\OutOfBoundsException;
use Zend_Utf8;

/**
 * Class Strings
 *
 * This is the standard Phoundation string functionality extension class
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2021 <copyright@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 */
class Strings
{
    /**
     * Fix urls that dont start with http://
     *
     * @param string $url
     * @param string $protocol
     * @return string
     */
    public static function ensureUrl(string $url, string $protocol = 'https://'): string
    {
        if (substr($url, 0, mb_strlen($protocol)) != $protocol) {
            return $protocol.$url;

        }

        return $url;
    }



    /**
     * Return "casa" or "casas" based on number
     *
     * @param int $count
     * @param string $single_text
     * @param string $multiple_text
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
     * @param string $data
     * @return bool
     */
    public static function isSerialized(string $data): bool
    {
        if (!$data) {
            return false;
        }

        return (boolean) preg_match( "/^([adObis]:|N;)/u", $data);
    }



    /**
     * Ensure that the specified string has UTF8 format
     *
     * @param string $string
     * @return string
     */
    public static function ensureUtf8(string $string): string
    {
        if (strings::isUtf8($string)) {
            return $string;
        }

        return utf8_encode($string);
    }



    /**
     * Returns true if string is UTF-8, false if not
     *
     * @param string $source
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
     * @param string $source
     * @return string
     */
    public static function fixSpanishChars(string $source): string
    {
        $from = array('&Aacute;', '&aacute;', '&Eacute;', '&eacute;', '&Iacute;', '&iacute;', '&Oacute;', '&oacute;', '&Ntilde;', '&ntilde;', '&Uacute;', '&uacute;', '&Uuml;', '&uuml;','&iexcl;','&ordf;','&iquest;','&ordm;');
        $to   = array('Á'       , 'á'       , 'É'       , 'é'       , 'Í'       , 'í'       , 'Ó'       , 'ó'       , 'Ñ'       , 'ñ'       , 'Ú'       , 'ú'       , 'Ü'     , 'ü'     , '¡'     , 'ª'    , '¿'      , 'º'    );

        return str_replace($from, $to, $source);
    }



    /**
     * Return a lowercased string with the first letter capitalized
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
     * Is spanish alphanumeric
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
     * ???
     *
     * @param string $string
     * @return string
     */
    public static function stripFunction(string $string) : string
    {
        return trim(Strings::from($string, '():'));
    }


    /**
     * Will fix a base64 coded string with missing termination = marks before decoding it
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
     * PHP explode() only in case $source is empty, it will return an empty array instead of an array with the emtpy
     * value in there
     *
     * @param string $separator
     * @param string $source
     * @return string
     */
    public static function explode(string $separator, string $source): string
    {
        if (!$source) {
            return array();
        }

        return explode($separator, $source);
    }



    /**
     * Interleave given string with given secundary string
     *
     * @param string $source
     * @param int|string $interleave
     * @param int $end
     * @param int $chunk_size
     * @return string
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
     * @todo Isnt this the same as str_fix_spanish_chars() ??
     * @param string $source
     * @return string
     */
    public static function convertAccents(string $source): string
    {
        $from = explode(',', "ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,e,i,ø,u,Ú,ñ,Ñ,º");
        $to   = explode(',', "c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,e,i,o,u,U,n,n,o");

        return str_replace($from, $to, $source);
    }



    /**
     * Strip whitespace
     *
     * @param string $string
     * @return string
     */
    public static function stripHtmlWhitespace(string $string): string
    {
        return preg_replace('/>\s+</u', '><', $string);
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
     * @return bool True if the specified string is a version format string matching "/^\d{1,3}\.\d{1,3}\.\d{1,3}$/". False if not
     * @example showdie(str_is_version(phpversion())); This example should show a debug table with true
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
    public static function isHtml(string $source): bool
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
        // :TODO: Remove this test line
        // return !preg_match('/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/', preg_replace('/"(\\.|[^"\\])*"/g', '', $source));

            return !empty($source) && is_string($source) && preg_match('/^("(\\.|[^"\\\n\r])*?"|[,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t])+?$/', $source);
    }



    /**
     * Multibyte safe version of PHP trim()
     *
     * No, trim() is not MB save
     * Yes, this (so far) seems to be safe
     *
     * IMPORTANT! DO NOT USE AND COMMENT THE self::mbTrim() call in the mb.php libray!! Experiments have shown that that one
     * there is NOT MB SAFE!!
     *
     * @param string $source
     * @return string
     */
    public static function mbTrim(string $source): string
    {
        return preg_replace("/(^\s+)|(\s+$)/us", '', $source);
    }


    /**
     * Correctly converts <br> to \n
     *
     * @param string $source
     * @param string $nl
     * @return string
     */
    public static function br2nl(string $source, string $nl = "\n"): string
    {
        $source = preg_replace("/(\r\n|\n|\r)/u", '' , $source);
        $source = preg_replace("/<br *\/?>/iu"  , $nl, $source);

        return $source;
    }



    /**
     * Correctly converts <br> to \n
     *
     * @param string $source
     * @param string $nl
     * @return string
     */
    public static function nl2br(string $source, string $nl = "\n"): string
    {
// TODO Implement!

/*
//        $source = preg_replace("/(\r\n|\n|\r)/u", '' , $source);
//        $source = preg_replace("/<br *\/?>/iu"  , $nl, $source);
//
*/
        return $source;
    }



    /**
     * Returns true if the specified text has one (or all) of the specified keywords
     *
     * @param string $text
     * @param array $keywords
     * @param bool $has_all
     * @param bool $regex
     * @param bool $unicode
     * @return string
     */
    public static function hasKeywords(string $text, array $keywords, bool $has_all = false, bool $regex = false, bool $unicode = true): string
    {
        $count = 0;

        foreach ($keywords as $keyword) {
            /*
             * Ensure keywords are trimmed, and don't search for empty keywords
             */
            if (!trim($keyword)) {
                continue;
            }

            if ($regex) {
                if (preg_match('/' . $keyword.'/ims'.($unicode ? 'u' : ''), $text, $matches) !== false) {
                    if (!$has_all) {
                        /*
                         * We're only interrested in knowing if it has one of the specified keywords
                         */
                        return array_shift($matches);
                    }

                    $count++;
                }

            } elseif (stripos($text, $keyword) !== false) {
                if (!$has_all) {
                    /*
                     * We're only interrested in knowing if it has one of the specified keywords
                     */
                    return $keyword;
                }

                $count++;
            }
        }

        return $count == count($keywords);
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
     * @param string $string
     * @param string $type
     * @return string
     */
    public static function caps(string $string, string $type): string
    {
        try{
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
                    throw new CoreException(tr('str_force(): Specified source is neither scalar nor array but an object of class ":class"', array(':class' => get_class($source))), 'invalid');
                }

                throw new CoreException(tr('str_force(): Specified source is neither scalar nor array but an ":type"', array(':type' => gettype($source))), 'invalid');
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

        load_libs('cli');
        $strlen = mb_strlen(cli_strip_color($source));

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
     * @param string $string
     * @param string $escape
     * @return string
     */
    public static function escape(string $string, string $escape = '"'): string
    {
        for($i = (mb_strlen($escape) - 1); $i <= 0; $i++) {
            $string = str_replace($escape[$i], '\\' . $escape[$i], $string);
        }

        return $string;
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
                $value = self::mbTrim($value);

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
     * Returns a "*** HIDDEN ***" string if the specified string has content. If the string is empty, an "-" emtpy string will be retuned instead
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package strings
     *
     * @param string $string The string to "hide"
     * @param string $hide The string to return if the specified source string contains data
     * @param string $empty The string to "hide" empty strings with
     * @return string
     */
    public static function hide(string $string, string $hide = '*** HIDDEN ***', string $empty = '-'): string
    {
        try{
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

        } catch (Exception $e) {
            throw new CoreException('str_hide(): Failed', $e);
        }
    }



    /**
     * Taken from https://coderwall.com/p/3j2hxq/find-and-format-difference-between-two-strings-in-php
     * Cleaned up for use in base by Sven Oostenbrink
     *
     * @param string $old
     * @param string $new
     * @return string
     * @throws \BException
     */
    public static function diff(string $old, string $new): string
    {
        try{
            $from_start = strspn($old ^ $new, "\0");
            $from_end   = strspn(strrev($old) ^ strrev($new), "\0");

            $old_end    = strlen($old) - $from_end;
            $new_end    = strlen($new) - $from_end;

            $start      = substr($new, 0, $from_start);
            $end        = substr($new, $new_end);

            $new_diff   = substr($new, $from_start, $new_end - $from_start);
            $old_diff   = substr($old, $from_start, $old_end - $from_start);

            $new        = $start.'<ins style="background-color:#ccffcc">' . $new_diff.'</ins>' . $end;
            $old        = $start.'<del style="background-color:#ffcccc">' . $old_diff.'</del>' . $end;

            return array('old' => $old,
                         'new' => $new);

        } catch (Exception $e) {
            throw new CoreException(tr('str_diff(): Failed'), $e);
        }
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
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
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
     * Trim empty HTML elements from the specified HTML string, and <br> elements from the beginning and end of each of these elements as well
     *
     * This function will remove all empty <h1>, <h2>, <h3>, <h4>, <h5>, <h6>, <div>, <p>, and <span> elements
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package strings
     * @see simple-dom
     * @note This function requires the simple-dom library
     * @version 2.8.2: Added function and documentation
     * @example
     * code
     * $result = str_trim_html('<p></p><p>test!</p><p></p>');
     * showdie($result);
     * /code
     *
     * This would return
     * code
     * <p>test!</p>
     * /code
     *
     * @param string $html The HTML to be stripped
     * @return string The specified source string with empty HTML tags stripped
     * @todo Fix issues with simpledom library
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
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package str
     * @see str_from()
     * @see str_until()
     * @version 2.0.0: Moved to system library, added documentation
     * @example
     * code
     * $result = str_cut('support@capmega.com', '@', '.');
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

            $source = self::mbTrim(html_entity_decode(utf8_unescape(strip_tags(utf8_escape($source)))));
// :TODO: Check if the next line should also be added!
//            $source = preg_replace('/\s|\/|\?|&+/u', $replace, $source);

            return $source;
        }

        return self::mbTrim(html_entity_decode(strip_tags($source)));

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
     * @param string $strings
     * @param bool $loop
     * @return string
     */
    public static function endsNotWith(string $source, string $strings, bool $loop = true): string
    {
        if (is_array($strings)) {
            // For array test, we always loop
            $redo = true;

            while ($redo) {
                $redo = false;

                foreach ($strings as $string) {
                    $new = str_ends_not($source, $string, true);

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
        try{
            if ($character) {
                // Remove specific character
                return preg_replace('/(' . $character.')\\1+/u'.($case_insensitive ? 'i' : ''), $replace, $source);
            }

            // Remove ALL double characters
            return preg_replace('/(.)\\1+/u'.($case_insensitive ? 'i' : ''), $replace, $source);

        } catch (Exception $e) {
            throw new CoreException('str_nodouble(): Failed', $e);
        }
    }



    /**
     * Truncate string using the specified fill and method
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @note While log_console() will log towards the ROOT/data/log/ log files, cli_dot() will only log one single dot even though on the command line multiple dots may be shown
     * @see Strings::log()
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
     * @param string $source
     * @param int $length
     * @param string $fill
     * @param string $method
     * @param bool $on_word
     * @return string The string, truncated if required, according to the specified truncating rules
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

                return self::mbTrim($retval) . $fill;

            case 'center':
                return mb_substr($source, 0, floor($length / 2)) . $fill.mb_substr($source, -ceil($length / 2));

            case 'left':
                $retval = mb_substr($source, -$length, $length);

                if ($on_word and (!str_contains(substr($source, $length, 2), ' '))) {
                    if ($pos = strpos($retval, ' ')) {
                        $retval = substr($retval, $pos);
                    }
                }

                return $fill.self::mbTrim($retval);

            default:
                throw new CoreException(tr('str_truncate(): Unknown method ":method" specified, please use "left", "center", or "right" or undefined which will default to "right"', array(':method' => $method)), 'unknown');
        }
    }



    /**
     * Return a string that is suitable for logging.
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
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
                    if (str_contains('password', $key)) {
                        $value = '*** HIDDEN ***';
                        continue;
                    }

                    if (str_contains('ssh_key', $key)) {
                        $value = '*** HIDDEN ***';
                        continue;
                    }
                }

                unset($value);

                $source = self::mbTrim(JSON::encode($source));

            } elseif (is_object($source) and ($source instanceof CoreException)) {
                $source = $source->getCode() . ' / ' . $source->getMessage();

            } else {
                $source = self::mbTrim(JSON::encode($source));
            }
        }

        return self::noDouble(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', str_replace('  ', ' ', str_replace("\n", ' ', self::truncate($source, $truncate, ' ... ', 'center')))), '\1', ' ');
    }



    /**
     * Return a seo appropriate string for given source string
     *
     * @param string $source
     * @param string $replace
     * @return string
     * @todo Get rid of the load_libs() call somehow
     */
    function seo(string $source, string $replace = '-'): string
    {
        if (self::isUtf8($source)) {
            load_libs('mb');

            //clean up string
            $source = mb_strtolower(self::mbTrim(mb_strip_tags($source)));

            //convert spanish crap to english
            $source2 = self::convertAccents($source);

            //remove special chars
            $from = array("'", '"', '\\');
            $to = array('', '', '');
            $source3 = str_replace($from, $to, $source2);

            //remove double spaces
            $source = preg_replace('/\s\s+/', ' ', $source3);

            //Replace anything that is junk
            $last = preg_replace('/[^a-zA-Z0-9]/u', $replace, $source);

            //Remove double "replace" chars
            $last = preg_replace('/\\' . $replace . '\\' . $replace . '+/', '-', $last);

            return self::mbTrim($last, '-');

        } else {
            //clean up string
            $source = strtolower(trim(strip_tags($source)));
            //convert spanish crap to english
            $source2 = self::convertAccents($source);

            //remove special chars
            $from = array("'", '"', '\\');
            $to = array('', '', '');
            $source3 = str_replace($from, $to, $source2);

            //remove double spaces
            $source = preg_replace('/\s\s+/', ' ', $source3);

            //Replace anything that is junk
            $last = preg_replace('/[^a-zA-Z0-9]/', $replace, $source);

            //Remove double "replace" chars
            $last = preg_replace('/\\' . $replace . '\\' . $replace . '+/', '-', $last);

            return trim($last, '-');
        }
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
    function escapeUtf8(string $string): string
    {
        try{
            return Zend_Utf8::escape((string) $string);

        }catch(Exception $e){
            throw new BException('utf8_escape(): Failed for string "'.str_log($string).'"', $e);
        }
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
    function unescapeUtf8(string $string): string
    {
        return Zend_Utf8::unescape($string);
    }
}
