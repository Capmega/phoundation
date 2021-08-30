<?php

namespace Phoundation\Core\Json;

use Exception;
use Phoundation\Core\CoreException\CoreException;

/**
 * Class Strings
 *
 * This is the standard Phoundation string functionality extension class
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2021 <license@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 */
class Strings
{
    /*
     * Fix urls that dont start with http://
     */
    public static function ensureUrl($url, $protocol = 'http://') {
        try{
            if (substr($url, 0, mb_strlen($protocol)) != $protocol) {
                return $protocol.$url;

            } else {
                return $url;
            }

        }catch(Exception $e) {
            throw new CoreException(tr('str_ensure_url(): Failed'), $e);
        }
    }



    /*
     * Return "casa" or "casas" based on number
     */
    public static function plural($count, $single_text, $multiple_text) {
        try{
            if ($count == 1) {
                return $single_text;

            }

            return $multiple_text;

        }catch(Exception $e) {
            throw new CoreException(tr('str_plural(): Failed'), $e);
        }
    }



    /*
     * Returns true if string is serialized, false if not
     */
    public static function isSerialized($data) {
        try{
            return (boolean) preg_match( "/^([adObis]:|N;)/u", $data );

        }catch(Exception $e) {
            throw new CoreException(tr('str_is_serialized(): Failed'), $e);
        }
    }



    /*
     * Fix urls that dont start with http://
     */
    public static function ensureUtf8($string) {
        try{
            if (strings::isUtf8($string)) {
                return $string;
            }

            return utf8_encode($string);

        }catch(Exception $e) {
            throw new CoreException(tr('str_ensure_utf8(): Failed'), $e);
        }
    }



    /*
     * Returns true if string is UTF-8, false if not
     */
    public static function isUtf8($source) {
        try{
            return mb_check_encoding($source, 'UTF8');

        }catch(Exception $e) {
            throw new CoreException(tr('str_is_utf8(): Failed'), $e);
        }

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



    /*
     * Return string will not contain HTML codes for Spanish haracters
     */
    public static function fixSpanishChars($source) {
        try{
            $from = array('&Aacute;', '&aacute;', '&Eacute;', '&eacute;', '&Iacute;', '&iacute;', '&Oacute;', '&oacute;', '&Ntilde;', '&ntilde;', '&Uacute;', '&uacute;', '&Uuml;', '&uuml;','&iexcl;','&ordf;','&iquest;','&ordm;');
            $to   = array('Á'       , 'á'       , 'É'       , 'é'       , 'Í'       , 'í'       , 'Ó'       , 'ó'       , 'Ñ'       , 'ñ'       , 'Ú'       , 'ú'       , 'Ü'     , 'ü'     , '¡'     , 'ª'    , '¿'      , 'º'    );

            return str_replace($from, $to, $source);

        }catch(Exception $e) {
            throw new CoreException(tr('str_fix_spanish_chars(): Failed'), $e);
        }
    }



    /*
     * Return a lowercased string with the first letter capitalized
     */
    public static function capitalize($source, $position = 0) {
        try{
            if (!$position) {
                return mb_strtoupper(mb_substr($source, 0, 1)).mb_strtolower(mb_substr($source, 1));
            }

            return mb_strtolower(mb_substr($source, 0, $position)).mb_strtoupper(mb_substr($source, $position, 1)).mb_strtolower(mb_substr($source, $position + 1));

        }catch(Exception $e) {
            throw new CoreException(tr('str_capitalize(): Failed'), $e);
        }
    }



    /*
     * Return a random string
     */
    public static function random($length = 8, $unique = false, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
        try{
            $string     = '';
            $charlen    = mb_strlen($characters);

            if ($unique and ($length > $charlen)) {
                throw new CoreException('str_random(): Can not create unique character random string with size "'.str_log($length).'". When $unique is requested, the string length can not be larger than "'.str_log($charlen).'" because there are no more then that amount of unique characters', 'invalid');
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

        }catch(Exception $e) {
            throw new CoreException(tr('str_random(): Failed'), $e);
        }
    }



    /*
     * Is spanish alphanumeric
     */
    public static function isAlpha($s, $extra = '\s') {
        try{
            $reg   = "/[^\p{L}\d$extra]/u";
            $count = preg_match($reg, $s, $matches);

            return $count == 0;

        }catch(Exception $e) {
            throw new CoreException(tr('str_is_alpha(): Failed'), $e);
        }
    }



    /*
     * Return a clean string, basically leaving only printable latin1 characters,
     */
    // :DELETE: This is never used, where would it be used?
    public static function escapeForJquery($source, $replace = '') {
        try{
            return preg_replace('/[#;&,.+*~\':"!^$[\]()=>|\/]/gu', '\\\\$&', $source);

        }catch(Exception $e) {
            throw new CoreException(tr('str_escape_for_jquery(): Failed'), $e);
        }
    }



    /*
     *
     */
    public static function stripFunction($string) {
        try{
            return trim(Strings::from($string, '():'));

        }catch(Exception $e) {
            throw new CoreException(tr('str_strip_function(): Failed'), $e);
        }
    }



    /*
     * Will fix a base64 coded string with missing termination = marks before decoding it
     */
    public static function safeBase64Decode($source) {
        try{
            if ($mod = mb_strlen($source) % 4) {
                $source .= str_repeat('=', 4 - $mod);
            }

            return base64_decode($source);

        }catch(Exception $e) {
            throw new CoreException(tr('str_safe_base64_decode(): Failed'), $e);
        }
    }



    /*
     * Return a safe size string for displaying
     */
    // :DELETE: Isn't this str_log()?
    public static function safe($source, $maxsize = 50) {
        try{
            return Strings::truncate(Json::encode($source), $maxsize);

        }catch(Exception $e) {
            throw new CoreException(tr('str_safe(): Failed'), $e);
        }
    }



    /*
     * Return the entire string in HEX ASCII
     */
    public static function hex($source) {
        try{
            return bin2hex($source);

        }catch(Exception $e) {
            throw new CoreException(tr('str_hex(): Failed'), $e);
        }
    }



    /*
     * Return a camel cased string
     */
    public static function camelCase($source, $separator = ' ') {
        try{
            $source = explode($separator, mb_strtolower($source));

            foreach ($source as $key => &$value) {
                $value = mb_ucfirst($value);
            }

            unset($value);

            return implode($separator, $source);

        }catch(Exception $e) {
            throw new CoreException(tr('str_camelcase(): Failed'), $e);
        }
    }



    /*
     * Fix PHP explode
     */
    public static function explode($separator, $source) {
        try{
            if (!$source) {
                return array();
            }

            return explode($separator, $source);

        }catch(Exception $e) {
            throw new CoreException(tr('str_explode(): Failed'), $e);
        }
    }



    /*
     * Interleave given string with given secundary string
     *
     *
     *
     */
    public static function interleave($source, $interleave, $end = 0, $chunksize = 1) {
        try{
            if (!$source) {
                throw new CoreException('str_interleave(): Empty source specified', 'empty');
            }

            if (!$interleave) {
                throw new CoreException('str_interleave(): Empty interleave specified', 'empty');
            }

            if ($end) {
                $begin = mb_substr($source, 0, $end);
                $end   = mb_substr($source, $end);

            }else{
                $begin = $source;
                $end   = '';
            }

            $begin  = mb_str_split($begin, $chunksize);
            $retval = '';

            foreach ($begin as $chunk) {
                $retval .= $chunk.$interleave;
            }

            return mb_substr($retval, 0, -1).$end;

        }catch(Exception $e) {
            throw new CoreException(tr('str_interleave(): Failed'), $e);
        }
    }



    /*
     * Convert weird chars to their standard ASCII variant
     */
    // :TODO: Isnt this the same as str_fix_spanish_chars() ??
    public static function convertAccents($source) {
        try{
            $from = explode(',', "ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,e,i,ø,u,Ú,ñ,Ñ,º");
            $to   = explode(',', "c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,e,i,o,u,U,n,n,o");

            return str_replace($from, $to, $source);

        }catch(Exception $e) {
            throw new CoreException(tr('str_convert_accents(): Failed'), $e);
        }
    }



    /*
     * Strip whitespace
     */
    public static function stripHtmlWhitespace($string) {
        try{
            return preg_replace('/>\s+</u', '><', $string);

        }catch(Exception $e) {
            throw new CoreException(tr('str_strip_html_whitespace(): Failed'), $e);
        }
    }



    /*
     * Return the specified string quoted if not numeric, boolean,
     * @param string $string
     * @param string $quote What quote (or other symbol) to use for the quoting
     */
    public static function quote($string, $quote = "'") {
        try{
            if (is_numeric($string) or is_bool(is_numeric($string))) {
                return $string;
            }

            return $quote.$string.$quote;

        }catch(Exception $e) {
            throw new CoreException(tr('str_quote(): Failed'), $e);
        }
    }



    /*
     * Return if specified source is a valid version or not
     * @param string $source
     * @return boolean True if the specified string is a version format string matching "/^\d{1,3}\.\d{1,3}\.\d{1,3}$/". False if not
     * @example showdie(str_is_version(phpversion())); This example should show a debug table with true
     */
    public static function isVersion($source) {
        try{
            return preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}$/', $source);

        }catch(Exception $e) {
            throw new CoreException('str_is_version(): Failed', $e);
        }
    }



    /*
     * Returns true if the specified source string contains HTML
     */
    public static function isHtml($source) {
        try{
            return !preg_match('/<[^<]+>/', $source);

        }catch(Exception $e) {
            throw new CoreException(tr('str_is_html(): Failed'), $e);
        }
    }



    /*
     * Return if specified source is a JSON string or not
     */
    public static function isJson($source) {
        try{
    //        return !preg_match('/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/', preg_replace('/"(\\.|[^"\\])*"/g', '', $source));
            return !empty($source) && is_string($source) && preg_match('/^("(\\.|[^"\\\n\r])*?"|[,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t])+?$/', $source);

        }catch(Exception $e) {
            throw new CoreException(tr('str_is_json(): Failed'), $e);
        }
    }



    /*
     * HERE BE SOME NON str_........ functions that ARE string functions anyway!
     */

    /*
     * mb_trim
     *
     * No, trim() is not MB save
     * Yes, this (so far) seems to be safe
     *
     * IMPORTANT! COMMENT THE mb_trim() call in the mb.php libray!! The one there is NOT MB SAFE!!
     */
    function mbTrim($string) {
        try{
            return preg_replace("/(^\s+)|(\s+$)/us", "", $string);

        }catch(Exception $e) {
            throw new CoreException('mb_trim(): Failed', $e);
        }
    }



    ///*
    // * Proper unicode mb_str_split()
    // * Taken from http://php.net/manual/en/function.str-split.php
    // */
    //function mb_str_split($source, $l = 0) {
    //    try{
    //        if ($l > 0) {
    //            $retval = array();
    //            $length = mb_strlen($source, 'UTF-8');
    //
    //            for ($i = 0; $i < $length; $i += $l) {
    //                $retval[] = mb_substr($source, $i, $l, 'UTF-8');
    //            }
    //
    //            return $retval;
    //        }
    //
    //        return preg_split("//u", $source, -1, PREG_SPLIT_NO_EMPTY);
    //
    //    }catch(Exception $e) {
    //        throw new CoreException('mb_str_split(): Failed', $e);
    //    }
    //}



    /*
     * Correctly converts <br> to \n
     */
    function br2nl($string, $nl = "\n") {
        try{
            $string = preg_replace("/(\r\n|\n|\r)/u", '' , $string);
            $string = preg_replace("/<br *\/?>/iu"  , $nl, $string);

            return $string;

        }catch(Exception $e) {
            throw new CoreException(tr('br2nl(): Failed'), $e);
        }
    }



    /*
     * Returns true if the specified text has one (or all) of the specified keywords
     */
    public static function hasKeywords($text, $keywords, $has_all = false, $regex = false, $unicode = true) {
        try{
            if (!is_array($keywords)) {
                if (!is_string($keywords) and !is_numeric($keywords)) {
                    throw new CoreException('str_has_keywords(): Specified keywords are neither string or array', 'invalid');
                }

                if ($regex) {
                    $keywords = array($keywords);

                }else{
                    $keywords = explode(',', $keywords);
                }
            }

            $count = 0;

            foreach ($keywords as $keyword) {
                /*
                 * Ensure keywords are trimmed, and don't search for empty keywords
                 */
                if (!trim($keyword)) {
                    continue;
                }

                if ($regex) {
                    if (preg_match('/'.$keyword.'/ims'.($unicode ? 'u' : ''), $text, $matches) !== false) {
                        if (!$has_all) {
                            /*
                             * We're only interrested in knowing if it has one of the specified keywords
                             */
                            return array_shift($matches);
                        }

                        $count++;
                    }

                }else{
                    if (stripos($text, $keyword) !== false) {
                        if (!$has_all) {
                            /*
                             * We're only interrested in knowing if it has one of the specified keywords
                             */
                            return $keyword;
                        }

                        $count++;
                    }
                }
            }

            return $count == count($keywords);

        }catch(Exception $e) {
            throw new CoreException('str_has_keywords(): Failed', $e);
        }
    }



    /*
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
     */
    public static function caps($string, $type) {
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
                            $replace = $word;
                            break;

                        case 'invertinterleave':
                            $replace = $word;
                            break;

                        case 'consonantcaps':
                            $replace = $word;
                            break;

                        case 'vowelcaps':
                            $replace = $word;
                            break;

                        case 'lowercentercaps':
                            $replace = $word;
                            break;

                        case 'capscenterlower':
                            $replace = $word;
                            break;

                        default:
                            throw new CoreException('str_caps(): Unknown type "'.str_log($type).'" specified', 'unknowntype');
                    }

                    str_replace($word, $replace, $string);
                }
            }

            return $string;

        }catch(Exception $e) {
            throw new CoreException('str_caps(): Failed', $e);
        }
    }



    /*
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
     */
    public static function capsGuess($string) {
        try{
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

        }catch(Exception $e) {
            throw new CoreException('str_caps_guess_type(): Failed', $e);
        }
    }



    /*
     * Force the specified source to be a string
     */
    public static function force($source, $separator = ',') {
        try{
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

                }else{
                    $source = implode($separator, $source);
                }
            }

            return (string) $source;

        }catch(Exception $e) {
            throw new CoreException('str_force(): Failed', $e);
        }
    }



    /*
     * Force the specified string to be the specified size.
     */
    public static function size($source, $size, $add = ' ', $prefix = false) {
        try{
            load_libs('cli');
            $strlen = mb_strlen(cli_strip_color($source));

            if ($strlen == $size) {
                return $source;
            }

            if ($strlen > $size) {
                return substr($source, 0, $size);
            }

            if ($prefix) {
                return str_repeat($add, $size - $strlen).$source;
            }

            return $source.str_repeat($add, $size - $strlen);

        }catch(Exception $e) {
            throw new CoreException('str_size(): Failed', $e);
        }
    }



    /*
     *
     */
    public static function escape($string, $escape = '"') {
        try{
            for($i = (mb_strlen($escape) - 1); $i <= 0; $i++) {
                $string = str_replace($escape[$i], '\\'.$escape[$i], $string);
            }

            return $string;

        }catch(Exception $e) {
            throw new CoreException('str_escape(): Failed', $e);
        }
    }



    /*
     *
     */
    public static function xor($a, $b) {
        try{
            $diff   = $a ^ $b;
            $retval = '';

            for ($i = 0, $len = mb_strlen($diff); $i != $len; ++$i) {
                $retval[$i] === "\0" ? ' ' : '#';
            }

            return $retval;

        }catch(Exception $e) {
            throw new CoreException('str_xor(): Failed', $e);
        }
    }



    /*
     *
     */
    public static function similar($a, $b, $percent) {
        try{
            return similar_text($a, $b, $percent);

        }catch(Exception $e) {
            throw new CoreException(tr('str_similar(): Failed'), $e);
        }
    }



    /*
     * Recursively trim all strings in the specified array tree
     */
    public static function trimArray($source, $recurse = true) {
        try{
            foreach ($source as $key => &$value) {
                if (is_string($value)) {
                    $value = mb_trim($value);

                }elseif (is_array($value)) {
                    if ($recurse) {
                        $value = str_trim_array($value);
                    }
                }
            }

            return $source;

        }catch(Exception $e) {
            throw new CoreException('str_trim_array(): Failed', $e);
        }
    }



    /*
     * Returns a "*** HIDDEN ***" string if the specified string has content. If the string is empty, an "-" emtpy string will be retuned instead
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package strings
     *
     * @param string $string The string to "hide"
     * @param string $hidden The string to return if the specified source string contains data
     * @param string $string The string to "hide" empty strings with
     * @return natural If the specified port was not empty, it will be returned. If the specified port was empty, the default port configuration will be returned
     */
    public static function hide($string, $hide = '*** HIDDEN ***', $empty = '-') {
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
            return $hidden;

        }catch(Exception $e) {
            throw new CoreException('str_hide(): Failed', $e);
        }
    }



    // :DELETE: This is not working
    ///*
    // *
    // * Taken from https://github.com/paulgb/simplediff/blob/5bfe1d2a8f967c7901ace50f04ac2d9308ed3169/simplediff.php
    // * Originally written by https://github.com/paulgb
    // * Adapted by Sven Oostnbrink support@capmega.com for use in BASE project
    // */
    //public static function diff() {
    //    try{
    //        foreach ($old as $oindex => $ovalue) {
    //            $nkeys = array_keys($new, $ovalue);
    //
    //            foreach ($nkeys as $nindex) {
    //                $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ? $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
    //
    //                if ($matrix[$oindex][$nindex] > $maxlen) {
    //                    $maxlen = $matrix[$oindex][$nindex];
    //                    $omax   = $oindex + 1 - $maxlen;
    //                    $nmax   = $nindex + 1 - $maxlen;
    //                }
    //            }
    //        }
    //
    //        if ($maxlen == 0) return array(array('d' => $old, 'i' => $new));
    //
    //        return array_merge(diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)), array_slice($new, $nmax, $maxlen), diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
    //
    //    }catch(Exception $e) {
    //        throw new CoreException('str_diff(): Failed', $e);
    //    }
    //}



    /*
     * Taken from https://coderwall.com/p/3j2hxq/find-and-format-difference-between-two-strings-in-php
     * Cleaned up for use in base by Sven Oostenbrink
     */
    public static function diff($old, $new) {
        try{
            $from_start = strspn($old ^ $new, "\0");
            $from_end   = strspn(strrev($old) ^ strrev($new), "\0");

            $old_end    = strlen($old) - $from_end;
            $new_end    = strlen($new) - $from_end;

            $start      = substr($new, 0, $from_start);
            $end        = substr($new, $new_end);

            $new_diff   = substr($new, $from_start, $new_end - $from_start);
            $old_diff   = substr($old, $from_start, $old_end - $from_start);

            $new        = $start.'<ins style="background-color:#ccffcc">'.$new_diff.'</ins>'.$end;
            $old        = $start.'<del style="background-color:#ffcccc">'.$old_diff.'</del>'.$end;

            return array('old' => $old,
                         'new' => $new);

        }catch(Exception $e) {
            throw new CoreException(tr('str_diff(): Failed'), $e);
        }
    }



    /*
     *
     */
    public static function boolean($value) {
        try{
            if ($value) {
                return 'true';
            }

            return 'false';

        }catch(Exception $e) {
            throw new CoreException(tr('str_boolean(): Failed'), $e);
        }
    }



    /*
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
    public static function underscoreToCamelcase($string, $first_uppercase = false) {
        try{
            while(($pos = strpos($string, '_')) !== false) {
                $character = $string[$pos + 1];

                if (!$pos) {
                    /*
                     * This is the first character
                     */
                    if ($first_uppercase) {
                        $character = strtoupper($character);

                    }else{
                        $character = strtolower($character);
                    }

                }else{
                    $character = strtoupper($character);
                }

                $string = substr($string, 0, $pos).$character.substr($string, $pos + 2);
            }

            return $string;

        }catch(Exception $e) {
            throw new CoreException('str_underscore_to_camelcase(): Failed', $e);
        }
    }



    /*
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
     * @note: This function requires the simple-dom library
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
     */
    public static function trimHtml($html) {
        try{
            if (!$html) {
                return '';
            }

            if (!is_string($html)) {
                throw new CoreException(tr('str_trim_html(): Specified $html ":html" is not a string', array(':html' => $html)), 'invalid');
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

                    }else{
                        $element->innertext = $plaintext;
                    }
                }
            }

            return $html->save();

        }catch(Exception $e) {
            throw new CoreException(tr('str_trim_html(): Failed'), $e);
        }
    }



    /* From http://stackoverflow.com/questions/11151250/how-to-compare-two-very-large-strings, implement?
    $string1 = "This is a sample text to test a script to highlight the differences between 2 strings, so the second string will be slightly different";
    $string2 = "This is 2 s4mple text to test a scr1pt to highlight the differences between 2 strings, so the first string will be slightly different";
    for($i=0;$i<strlen($string1);$i++) {
        if ($string1[$i]!=$string2[$i]) {
            $string3[$i] = "<mark>{$string1[$i]}</mark>";
            $string4[$i] = "<mark>{$string2[$i]}</mark>";
        }
        else {
            $string3[$i] = "{$string1[$i]}";
            $string4[$i] = "{$string2[$i]}";
        }
    }
    $string3 = implode("",$string3);
    $string4 = implode("",$string4);

    echo "$string3". "<br />". $string4;*/



    /*
     * Obsolete functions
     * These functions only exist as wrappers for compatibility purposes
     */
    public static function autoQuote($string, $quote = "'") {
        return Strings::quote($string, $quote);
    }


    /*
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
     * @params string $start The character(s) to start the cut
     * @params string $stop The character(s) to stop the cut
     * @return string The $source string between the first occurrences of start and $stop
     */
    public static function cut($source, $start, $stop){
        try{
            return str_until(str_from($source, $start), $stop);

        }catch(Exception $e){
            throw new CoreException(tr('str_cut(): Failed'), $e);
        }
    }



    /*
     * Returns true if the specified $needle exists in the specified $haystack
     *
     * This is a simple wrapper function to strpos() which does not require testing for false, as the output is boolean. If the $needle exists in the $haystack, true will be returned, else false will be returned.
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package str
     * @see strpos()
     * @see strstr()
     * @version 1.26.1: Added function and documentation
     * @example
     * code
     * $result = str_exists('This function is completely foobar', 'foobar');
     * showdie($result);
     * /code
     *
     * This would return
     * code
     * true
     * /code
     *
     * @param string $haystack The source string in which this function needs to find $needle
     * @params string $needle The string that will be searched for in $haystack
     * @return boolean True if the $needle exists in $haystack, false otherwise
     */
    public static function exists($haystack, $needle){
        try{
            return (strpos($haystack, $needle) !== false);

        }catch(Exception $e){
            throw new CoreException(tr('str_exists(): Failed'), $e);
        }
    }



    /*
     * Cleanup string
     */
    public static function clean($source, $utf8 = true){
        try{
            if(!is_scalar($source)){
                if(!is_null($source)){
                    throw new CoreException(tr('str_clean(): Specified source ":source" from ":location" should be datatype "string" but has datatype ":datatype"', array(':source' => $source, ':datatype' => gettype($source), ':location' => current_file(1).'@'.current_line(1))), 'invalid');
                }
            }

            if($utf8){
                load_libs('utf8');

                $source = mb_trim(html_entity_decode(utf8_unescape(strip_tags(utf8_escape($source)))));
// :TODO: Check if the next line should also be added!
//            $source = preg_replace('/\s|\/|\?|&+/u', $replace, $source);

                return $source;
            }

            return mb_trim(html_entity_decode(strip_tags($source)));

        }catch(Exception $e){
            throw new CoreException(tr('str_clean(): Failed with string ":string"', array(':string' => $source)), $e);
        }
// :TODO:SVEN:20130709: Check if we should be using mysqli_escape_string() or addslashes(), since the former requires SQL connection, but the latter does NOT have correct UTF8 support!!
//    return mysqli_escape_string(trim(decode_entities(mb_strip_tags($str))));
    }



    /*
     * Return the given string from the specified needle
     */
    public static function from($source, $needle, $more = 0, $require = false){
        try{
            if(!$needle){
                throw new CoreException('str_from(): No needle specified', 'not-specified');
            }

            $pos = mb_strpos($source, $needle);

            if($pos === false){
                if($require){
                    return '';
                }

                return $source;
            }

            return mb_substr($source, $pos + mb_strlen($needle) - $more);

        }catch(Exception $e){
            throw new CoreException(tr('str_from(): Failed for string ":string"', array(':string' => $source)), $e);
        }
    }



    /*
     * Return the given string from 0 until the specified needle
     */
    public static function until($source, $needle, $more = 0, $start = 0, $require = false){
        try{
            if(!$needle){
                throw new CoreException('str_until(): No needle specified', 'not-specified');
            }

            $pos = mb_strpos($source, $needle);

            if($pos === false){
                if($require){
                    return '';
                }

                return $source;
            }

            return mb_substr($source, $start, $pos + $more);

        }catch(Exception $e){
            throw new CoreException(tr('str_until(): Failed for string ":string"', array(':string' => $source)), $e);
        }
    }



    /*
     * Return the given string from the specified needle, starting from the end
     */
    public static function rfrom($source, $needle, $more = 0){
        try{
            if(!$needle){
                throw new CoreException('str_rfrom(): No needle specified', 'not-specified');
            }

            $pos = mb_strrpos($source, $needle);

            if($pos === false) return $source;

            return mb_substr($source, $pos + mb_strlen($needle) - $more);

        }catch(Exception $e){
            throw new CoreException(tr('str_rfrom(): Failed for string ":string"', array(':string' => $source)), $e);
        }
    }



    /*
     * Return the given string from 0 until the specified needle, starting from the end
     */
    public static function runtil($source, $needle, $more = 0, $start = 0){
        try{
            if(!$needle){
                throw new CoreException('str_runtil(): No needle specified', 'not-specified');
            }

            $pos = mb_strrpos($source, $needle);

            if($pos === false) return $source;

            return mb_substr($source, $start, $pos + $more);

        }catch(Exception $e){
            throw new CoreException(tr('str_runtil(): Failed for string ":string"', array(':string' => $source)), $e);
        }
    }



    /*
     * Ensure that specified source string starts with specified string
     */
    public static function starts($source, $string){
        try{
            if(mb_substr($source, 0, mb_strlen($string)) == $string){
                return $source;
            }

            return $string.$source;

        }catch(Exception $e){
            throw new CoreException(tr('str_starts(): Failed for ":source"', array(':source' => $source)), $e);
        }
    }



    /*
     * Ensure that specified source string starts NOT with specified string
     */
    public static function starts_not($source, $string){
        try{
            while(mb_substr($source, 0, mb_strlen($string)) == $string){
                $source = mb_substr($source, mb_strlen($string));
            }

            return $source;

        }catch(Exception $e){
            throw new CoreException(tr('str_starts_not(): Failed for ":source"', array(':source' => $source)), $e);
        }
    }



    /*
     * Ensure that specified string ends with specified character
     */
    public static function ends($source, $string){
        try{
            $length = mb_strlen($string);

            if(mb_substr($source, -$length, $length) == $string){
                return $source;
            }

            return $source.$string;

        }catch(Exception $e){
            throw new CoreException('str_ends(): Failed', $e);
        }
    }



    /*
     * Ensure that specified string ends NOT with specified character
     */
    public static function ends_not($source, $strings, $loop = true){
        try{
            if(is_array($strings)){
                /*
                 * For array test, we always loop
                 */
                $redo = true;

                while($redo){
                    $redo = false;

                    foreach($strings as $string){
                        $new = str_ends_not($source, $string, true);

                        if($new != $source){
                            // A change was made, we have to rerun over it.
                            $redo = true;
                        }

                        $source = $new;
                    }
                }

            }else{
                /*
                 * Check for only one character
                 */
                $length = mb_strlen($strings);

                while(mb_substr($source, -$length, $length) == $strings){
                    $source = mb_substr($source, 0, -$length);
                    if(!$loop) break;
                }
            }

            return $source;

        }catch(Exception $e){
            throw new CoreException('str_ends_not(): Failed', $e);
        }
    }



    /*
     * Ensure that specified string ends with slash
     */
    function slash($string){
        try{
            return str_ends($string, '/');

        }catch(Exception $e){
            throw new CoreException('slash(): Failed', $e);
        }
    }



    /*
     * Ensure that specified string ends NOT with slash
     */
    function unslash($string, $loop = true){
        try{
            return str_ends_not($string, '/', $loop);

        }catch(Exception $e){
            throw new CoreException('unslash(): Failed', $e);
        }
    }



    /*
     * Remove double "replace" chars
     */
    public static function nodouble($source, $replace = '\1', $character = null, $case_insensitive = true){
        try{
            if($character){
                /*
                 * Remove specific character
                 */
                return preg_replace('/('.$character.')\\1+/u'.($case_insensitive ? 'i' : ''), $replace, $source);
            }

            /*
             * Remove ALL double characters
             */
            return preg_replace('/(.)\\1+/u'.($case_insensitive ? 'i' : ''), $replace, $source);

        }catch(Exception $e){
            throw new CoreException('str_nodouble(): Failed', $e);
        }
    }



    /*
     * Truncate string using the specified fill and method
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @note While log_console() will log towards the ROOT/data/log/ log files, cli_dot() will only log one single dot even though on the command line multiple dots may be shown
     * @see str_log()
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
     * @param natural $length
     * @param string $fill
     * @param string $method
     * @param booelan $on_word
     * @return string The string, truncated if required, according to the specified truncating rules
     */
    public static function truncate($source, $length, $fill = ' ... ', $method = 'right', $on_word = false){
        try{
            if(!$length or ($length < (mb_strlen($fill) + 1))){
                throw new CoreException('str_truncate(): No length or insufficient length specified. You must specify a length of minimal $fill length + 1', 'invalid');
            }

            if($length >= mb_strlen($source)){
                /*
                 * No need to truncate, the string is short enough
                 */
                return $source;
            }

            /*
             * Correct length
             */
            $length -= mb_strlen($fill);

            switch($method){
                case 'right':
                    $retval = mb_substr($source, 0, $length);
                    if($on_word and (strpos(substr($source, $length, 2), ' ') === false)){
                        if($pos = strrpos($retval, ' ')){
                            $retval = substr($retval, 0, $pos);
                        }
                    }

                    return trim($retval).$fill;

                case 'center':
                    return mb_substr($source, 0, floor($length / 2)).$fill.mb_substr($source, -ceil($length / 2));

                case 'left':
                    $retval = mb_substr($source, -$length, $length);

                    if($on_word and substr($retval)){
                        if($pos = strpos($retval, ' ')){
                            $retval = substr($retval, $pos);
                        }
                    }

                    return $fill.trim($retval);

                default:
                    throw new CoreException(tr('str_truncate(): Unknown method ":method" specified, please use "left", "center", or "right" or undefined which will default to "right"', array(':method' => $method)), 'unknown');
            }

        }catch(Exception $e){
            throw new CoreException(tr('str_truncate(): Failed for ":source"', array(':source' => $source)), $e);
        }
    }



    /*
     * Return a string that is suitable for logging.
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @note While log_console() will log towards the ROOT/data/log/ log files, cli_dot() will only log one single dot even though on the command line multiple dots may be shown
     * @see str_log()
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
     * @param string $source
     * @param natural $length
     * @param string $fill
     * @param string $method
     * @param booelan $on_word
     * @return string The string, truncated if required, according to the specified truncating rules
     */
    public static function log($source, $truncate = 8187, $separator = ', '){
        try{
            try{
                $json_encode = 'json_encode_custom';

            }catch(Exception $e){
                /*
                 * Fuck...
                 */
                $json_encode = 'json_encode';
            }

            if(!$source){
                if(is_numeric($source)){
                    return 0;
                }

                return '';
            }

            if(!is_scalar($source)){
                if(is_array($source)){
                    foreach($source as $key => &$value){
                        if(strstr($key, 'password')){
                            $value = '*** HIDDEN ***';
                            continue;
                        }

                        if(strstr($key, 'ssh_key')){
                            $value = '*** HIDDEN ***';
                            continue;
                        }
                    }

                    unset($value);

                    $source = mb_trim($json_encode($source));

                }elseif(is_object($source) and ($source instanceof CoreException)){
                    $source = $source->getCode().' / '.$source->getMessage();

                }else{
                    $source = mb_trim($json_encode($source));
                }
            }

            return str_nodouble(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', str_replace('  ', ' ', str_replace("\n", ' ', str_truncate($source, $truncate, ' ... ', 'center')))), '\1', ' ');

        }catch(Exception $e){
            if($e->getRealCode() === 'invalid'){
                notify($e->makeWarning(true));
                return "Data converted using print_r() instead of json_encode() because json_encode_custom() failed on this data: ".print_r($source, true);
            }

            throw new CoreException('str_log(): Failed', $e);
        }
    }
}