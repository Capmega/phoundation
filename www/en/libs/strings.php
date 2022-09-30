<?php
/*
 * This is the standard PHP strings extension library
 *
 * Mostly written by Sven Oostenbrink ,some additions from stackoverflow
 *
 * With few exceptions at the end of this file, all functions have the str_ prefix
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */



/*
 * Fix urls that dont start with http://
 */
function str_ensure_url($url, $protocol = 'http://') {
    try {
        if (substr($url, 0, mb_strlen($protocol)) != $protocol) {
            return $protocol.$url;

        } else {
            return $url;
        }

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_ensure_url(): Failed'), $e);
    }
}



/*
 * Return "casa" or "casas" based on number
 */
function str_plural($count, $single_text, $multiple_text) {
    try {
        if ($count == 1) {
            return $single_text;

        }

        return $multiple_text;

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_plural(): Failed'), $e);
    }
}



/*
 * Returns true if string is serialized, false if not
 */
function str_is_serialized($data) {
    try {
        return (boolean) preg_match( "/^([adObis]:|N;)/u", $data );

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_is_serialized(): Failed'), $e);
    }
}



/*
 * Fix urls that dont start with http://
 */
function str_ensure_utf8($string) {
    try {
        if (str_is_utf8($string)) {
            return $string;
        }

        return utf8_encode($string);

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_ensure_utf8(): Failed'), $e);
    }
}



/*
 * Returns true if string is UTF-8, false if not
 */
function str_is_utf8($source) {
    try {
        return mb_check_encoding($source, 'UTF8');

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_is_utf8(): Failed'), $e);
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
function str_fix_spanish_chars($source) {
    try {
        $from = array('&Aacute;', '&aacute;', '&Eacute;', '&eacute;', '&Iacute;', '&iacute;', '&Oacute;', '&oacute;', '&Ntilde;', '&ntilde;', '&Uacute;', '&uacute;', '&Uuml;', '&uuml;','&iexcl;','&ordf;','&iquest;','&ordm;');
        $to   = array('Á'       , 'á'       , 'É'       , 'é'       , 'Í'       , 'í'       , 'Ó'       , 'ó'       , 'Ñ'       , 'ñ'       , 'Ú'       , 'ú'       , 'Ü'     , 'ü'     , '¡'     , 'ª'    , '¿'      , 'º'    );

        return str_replace($from, $to, $source);

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_fix_spanish_chars(): Failed'), $e);
    }
}



/*
 * Return a lowercased string with the first letter capitalized
 */
function str_capitalize($source, $position = 0) {
    try {
        if (!$position) {
            return mb_strtoupper(mb_substr($source, 0, 1)).mb_strtolower(mb_substr($source, 1));
        }

        return mb_strtolower(mb_substr($source, 0, $position)).mb_strtoupper(mb_substr($source, $position, 1)).mb_strtolower(mb_substr($source, $position + 1));

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_capitalize(): Failed'), $e);
    }
}



/*
 * Return a random string
 */
function str_random($length = 8, $unique = false, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
    try {
        $string     = '';
        $charlen    = mb_strlen($characters);

        if ($unique and ($length > $charlen)) {
            throw new OutOfBoundsException('str_random(): Can not create unique character random string with size "'.str_log($length).'". When $unique is requested, the string length can not be larger than "'.str_log($charlen).'" because there are no more then that amount of unique characters', 'invalid');
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
        throw new OutOfBoundsException(tr('str_random(): Failed'), $e);
    }
}



/*
 * Is spanish alphanumeric
 */
function str_is_alpha($s, $extra = '\s') {
    try {
        $reg   = "/[^\p{L}\d$extra]/u";
        $count = preg_match($reg, $s, $matches);

        return $count == 0;

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_is_alpha(): Failed'), $e);
    }
}



/*
 * Return a clean string, basically leaving only printable latin1 characters,
 */
// :DELETE: This is never used, where would it be used?
function str_escape_for_jquery($source, $replace = '') {
    try {
        return preg_replace('/[#;&,.+*~\':"!^$[\]()=>|\/]/gu', '\\\\$&', $source);

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_escape_for_jquery(): Failed'), $e);
    }
}



/*
 *
 */
function str_strip_function($string) {
    try {
        return trim(Strings::from($string, '():'));

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_strip_function(): Failed'), $e);
    }
}



/*
 * Will fix a base64 coded string with missing termination = marks before decoding it
 */
function str_safe_base64_decode($source) {
    try {
        if ($mod = mb_strlen($source) % 4) {
            $source .= str_repeat('=', 4 - $mod);
        }

        return base64_decode($source);

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_safe_base64_decode(): Failed'), $e);
    }
}



/*
 * Return a safe size string for displaying
 */
// :DELETE: Isn't this str_log()?
function str_safe($source, $maxsize = 50) {
    try {
        return str_truncate(json_encode_custom($source), $maxsize);

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_safe(): Failed'), $e);
    }
}



/*
 * Return the entire string in HEX ASCII
 */
function str_hex($source) {
    try {
        return bin2hex($source);

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_hex(): Failed'), $e);
    }
}



/*
 * Return a camel cased string
 */
function str_camelcase($source, $separator = ' ') {
    try {
        $source = explode($separator, mb_strtolower($source));

        foreach($source as $key => &$value) {
            $value = mb_ucfirst($value);
        }

        unset($value);

        return implode($separator, $source);

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_camelcase(): Failed'), $e);
    }
}



/*
 * Fix PHP explode
 */
function str_explode($separator, $source) {
    try {
        if (!$source) {
            return array();
        }

        return explode($separator, $source);

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_explode(): Failed'), $e);
    }
}



/*
 * Interleave given string with given secundary string
 *
 *
 *
 */
function str_interleave($source, $interleave, $end = 0, $chunksize = 1) {
    try {
        if (!$source) {
            throw new OutOfBoundsException('str_interleave(): Empty source specified', 'empty');
        }

        if (!$interleave) {
            throw new OutOfBoundsException('str_interleave(): Empty interleave specified', 'empty');
        }

        if ($end) {
            $begin = mb_substr($source, 0, $end);
            $end   = mb_substr($source, $end);

        } else {
            $begin = $source;
            $end   = '';
        }

        $begin  = mb_str_split($begin, $chunksize);
        $retval = '';

        foreach($begin as $chunk) {
            $retval .= $chunk.$interleave;
        }

        return mb_substr($retval, 0, -1).$end;

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_interleave(): Failed'), $e);
    }
}



/*
 * Convert weird chars to their standard ASCII variant
 */
// :TODO: Isnt this the same as str_fix_spanish_chars() ??
function str_convert_accents($source) {
    try {
        $from = explode(',', "ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,e,i,ø,u,Ú,ñ,Ñ,º");
        $to   = explode(',', "c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,e,i,o,u,U,n,n,o");

        return str_replace($from, $to, $source);

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_convert_accents(): Failed'), $e);
    }
}



/*
 * Strip whitespace
 */
function str_strip_html_whitespace($string) {
    try {
        return preg_replace('/>\s+</u', '><', $string);

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_strip_html_whitespace(): Failed'), $e);
    }
}



/*
 * Return the specified string quoted if not numeric, boolean,
 * @param string $string
 * @param string $quote What quote (or other symbol) to use for the quoting
 */
function str_quote($string, $quote = "'") {
    try {
        if (is_numeric($string) or is_bool(is_numeric($string))) {
            return $string;
        }

        return $quote.$string.$quote;

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_quote(): Failed'), $e);
    }
}



/*
 * Return if specified source is a valid version or not
 * @param string $source
 * @return boolean True if the specified string is a version format string matching "/^\d{1,3}\.\d{1,3}\.\d{1,3}$/". False if not
 * @example showdie(str_is_version(phpversion())); This example should show a debug table with true
 */
function str_is_version($source) {
    try {
        return preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}$/', $source);

    }catch(Exception $e) {
        throw new OutOfBoundsException('str_is_version(): Failed', $e);
    }
}



/*
 * Returns true if the specified source string contains HTML
 */
function str_is_html($source) {
    try {
        return !preg_match('/<[^<]+>/', $source);

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_is_html(): Failed'), $e);
    }
}



/*
 * Return if specified source is a JSON string or not
 */
function str_is_json($source) {
    try {
//        return !preg_match('/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/', preg_replace('/"(\\.|[^"\\])*"/g', '', $source));
        return !empty($source) && is_string($source) && preg_match('/^("(\\.|[^"\\\n\r])*?"|[,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t])+?$/', $source);

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_is_json(): Failed'), $e);
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
function mb_trim($string) {
    try {
        return preg_replace("/(^\s+)|(\s+$)/us", "", $string);

    }catch(Exception $e) {
        throw new OutOfBoundsException('mb_trim(): Failed', $e);
    }
}



///*
// * Proper unicode mb_str_split()
// * Taken from http://php.net/manual/en/function.str-split.php
// */
//function mb_str_split($source, $l = 0) {
//    try {
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
//        throw new OutOfBoundsException('mb_str_split(): Failed', $e);
//    }
//}



/*
 * Correctly converts <br> to \n
 */
function br2nl($string, $nl = "\n") {
    try {
        $string = preg_replace("/(\r\n|\n|\r)/u", '' , $string);
        $string = preg_replace("/<br *\/?>/iu"  , $nl, $string);

        return $string;

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('br2nl(): Failed'), $e);
    }
}



/*
 * Returns true if the specified text has one (or all) of the specified keywords
 */
function str_has_keywords($text, $keywords, $has_all = false, $regex = false, $unicode = true) {
    try {
        if (!is_array($keywords)) {
            if (!is_string($keywords) and !is_numeric($keywords)) {
                throw new OutOfBoundsException('str_has_keywords(): Specified keywords are neither string or array', 'invalid');
            }

            if ($regex) {
                $keywords = array($keywords);

            } else {
                $keywords = explode(',', $keywords);
            }
        }

        $count = 0;

        foreach($keywords as $keyword) {
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

            } else {
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
        throw new OutOfBoundsException('str_has_keywords(): Failed', $e);
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
function str_caps($string, $type) {
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
        foreach($results as $words) {
            foreach($words as $word) {
                /*
                 * Create the $replace string
                 */
                switch($type) {
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
                        throw new OutOfBoundsException('str_caps(): Unknown type "'.str_log($type).'" specified', 'unknowntype');
                }

                str_replace($word, $replace, $string);
            }
        }

        return $string;

    }catch(Exception $e) {
        throw new OutOfBoundsException('str_caps(): Failed', $e);
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
function str_caps_guess($string) {
    try {
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
        foreach($words as $word) {
        }

    }catch(Exception $e) {
        throw new OutOfBoundsException('str_caps_guess_type(): Failed', $e);
    }
}



/*
 * Force the specified source to be a string
 */
function str_force($source, $separator = ',') {
    try {
        if (!is_scalar($source)) {
            if (!is_array($source)) {
                if (!$source) {
                    return '';
                }

                if (is_object($source)) {
                    throw new OutOfBoundsException(tr('str_force(): Specified source is neither scalar nor array but an object of class ":class"', array(':class' => get_class($source))), 'invalid');
                }

                throw new OutOfBoundsException(tr('str_force(): Specified source is neither scalar nor array but an ":type"', array(':type' => gettype($source))), 'invalid');
            }

            /*
             * Encoding?
             */
            if ($separator === 'json') {
                $source = json_encode_custom($source);

            } else {
                $source = implode($separator, $source);
            }
        }

        return (string) $source;

    }catch(Exception $e) {
        throw new OutOfBoundsException('str_force(): Failed', $e);
    }
}



/*
 * Force the specified string to be the specified size.
 */
function str_size($source, $size, $add = ' ', $prefix = false) {
    try {
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
        throw new OutOfBoundsException('str_size(): Failed', $e);
    }
}



/*
 *
 */
function str_escape($string, $escape = '"') {
    try {
        for($i = (mb_strlen($escape) - 1); $i <= 0; $i++) {
            $string = str_replace($escape[$i], '\\'.$escape[$i], $string);
        }

        return $string;

    }catch(Exception $e) {
        throw new OutOfBoundsException('str_escape(): Failed', $e);
    }
}



/*
 *
 */
function str_xor($a, $b) {
    try {
        $diff   = $a ^ $b;
        $retval = '';

        for ($i = 0, $len = mb_strlen($diff); $i != $len; ++$i) {
            $retval[$i] === "\0" ? ' ' : '#';
        }

        return $retval;

    }catch(Exception $e) {
        throw new OutOfBoundsException('str_xor(): Failed', $e);
    }
}



/*
 *
 */
function str_similar($a, $b, $percent) {
    try {
        return similar_text($a, $b, $percent);

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_similar(): Failed'), $e);
    }
}



/*
 * Recursively trim all strings in the specified array tree
 */
function str_trim_array($source, $recurse = true) {
    try {
        foreach($source as $key => &$value) {
            if (is_string($value)) {
                $value = mb_trim($value);

            } elseif (is_array($value)) {
                if ($recurse) {
                    $value = str_trim_array($value);
                }
            }
        }

        return $source;

    }catch(Exception $e) {
        throw new OutOfBoundsException('str_trim_array(): Failed', $e);
    }
}



/*
 * Returns a "*** HIDDEN ***" string if the specified string has content. If the string is empty, an "-" emtpy string will be retuned instead
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package strings
 *
 * @param string $string The string to "hide"
 * @param string $hidden The string to return if the specified source string contains data
 * @param string $string The string to "hide" empty strings with
 * @return natural If the specified port was not empty, it will be returned. If the specified port was empty, the default port configuration will be returned
 */
function str_hide($string, $hide = '*** HIDDEN ***', $empty = '-') {
    try {
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
        throw new OutOfBoundsException('str_hide(): Failed', $e);
    }
}



// :DELETE: This is not working
///*
// *
// * Taken from https://github.com/paulgb/simplediff/blob/5bfe1d2a8f967c7901ace50f04ac2d9308ed3169/simplediff.php
// * Originally written by https://github.com/paulgb
// * Adapted by Sven Oostnbrink support@capmega.com for use in BASE project
// */
//function str_diff() {
//    try {
//        foreach($old as $oindex => $ovalue) {
//            $nkeys = array_keys($new, $ovalue);
//
//            foreach($nkeys as $nindex) {
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
//        throw new OutOfBoundsException('str_diff(): Failed', $e);
//    }
//}



/*
 * Taken from https://coderwall.com/p/3j2hxq/find-and-format-difference-between-two-strings-in-php
 * Cleaned up for use in base by Sven Oostenbrink
 */
function str_diff($old, $new) {
    try {
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
        throw new OutOfBoundsException(tr('str_diff(): Failed'), $e);
    }
}



/*
 *
 */
function str_boolean($value) {
    try {
        if ($value) {
            return 'true';
        }

        return 'false';

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_boolean(): Failed'), $e);
    }
}



/*
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
function str_underscore_to_camelcase($string, $first_uppercase = false) {
    try {
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

            $string = substr($string, 0, $pos).$character.substr($string, $pos + 2);
        }

        return $string;

    }catch(Exception $e) {
        throw new OutOfBoundsException('str_underscore_to_camelcase(): Failed', $e);
    }
}



/*
 * Trim empty HTML elements from the specified HTML string, and <br> elements from the beginning and end of each of these elements as well
 *
 * This function will remove all empty <h1>, <h2>, <h3>, <h4>, <h5>, <h6>, <div>, <p>, and <span> elements
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function str_trim_html($html) {
    try {
        if (!$html) {
            return '';
        }

        if (!is_string($html)) {
            throw new OutOfBoundsException(tr('str_trim_html(): Specified $html ":html" is not a string', array(':html' => $html)), 'invalid');
        }

        load_libs('simple-dom');

        $html          = str_get_html($html);
        $element_types = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'p', 'span');

        foreach($element_types as $element_type) {
            $elements = $html->find($element_type);

            foreach($elements as $element) {
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

    }catch(Exception $e) {
        throw new OutOfBoundsException(tr('str_trim_html(): Failed'), $e);
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
function str_auto_quote($string, $quote = "'") {
    return str_quote($string, $quote);
}
