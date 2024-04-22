<?php

declare(strict_types=1);

namespace Phoundation\Utils;

use Phoundation\Cli\CliColor;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhpModuleNotAvailableException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Notifications\Notification;
use StephenHill\Base58;
use Stringable;
use Throwable;

/**
 * Class Strings
 *
 * This is the standard Phoundation string functionality extension class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category  Class reference
 * @package   Phoundation\Utils
 */
class Strings extends Utils
{
    /**
     * Ensure that the specified $url will start with the specified $protocol.
     *
     * @param Stringable|string $source
     * @param string            $protocol
     *
     * @return string
     */
    public static function ensureProtocol(Stringable|string $source, string $protocol = 'https://'): string
    {
        $source = (string) $source;
        if (substr($source, 0, mb_strlen($protocol)) != $protocol) {
            return $protocol . $source;

        }

        return $source;
    }


    /**
     * Return "house" or "houses" based on the specified count. If the specified $count is 1,the single_text will be
     * returned. If not, the $multiple_text will be retuned
     *
     * @param int|float         $count
     * @param Stringable|string $single_text   The text to be returned if the specified $count is 1
     * @param Stringable|string $multiple_text The text to be returned if the specified $count is not 1
     *
     * @return string
     */
    public static function plural(int|float $count, Stringable|string $single_text, Stringable|string $multiple_text): string
    {
        if ($count === 1) {
            return (string) $single_text;

        }

        return (string) $multiple_text;
    }


    /**
     * Returns true if string is serialized, false if not
     *
     * @param Stringable|string|null $source The source text on which the operation will be executed
     *
     * @return bool
     */
    public static function isSerialized(Stringable|string|null $source): bool
    {
        if (!$source) {
            return false;
        }

        return (boolean) preg_match("/^([adObis]:|N;)/u", (string) $source);
    }


    /**
     * Ensure that the specified string has UTF8 format
     *
     * @param Stringable|string $source The source text on which the operation will be executed
     *
     * @return string A UTF8 encoded string
     */
    public static function ensureUtf8(Stringable|string $source): string
    {
        $source = (string) $source;
        if (strings::isUtf8($source)) {
            return $source;
        }

        return utf8_encode($source);
    }


    /**
     * Returns true if string is UTF-8, false if not
     *
     * @param Stringable|string $source The source text which will be tested
     *
     * @return bool
     */
    public static function isUtf8(Stringable|string $source): bool
    {
        return mb_check_encoding((string) $source, 'UTF8');
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
     * @param Stringable|string $source The source text on which the operation will be executed
     *
     * @return string
     */
    public static function fixSpanishChars(Stringable|string $source): string
    {
        $from = [
            '&Aacute;',
            '&aacute;',
            '&Eacute;',
            '&eacute;',
            '&Iacute;',
            '&iacute;',
            '&Oacute;',
            '&oacute;',
            '&Ntilde;',
            '&ntilde;',
            '&Uacute;',
            '&uacute;',
            '&Uuml;',
            '&uuml;',
            '&iexcl;',
            '&ordf;',
            '&iquest;',
            '&ordm;',
        ];
        $to   = [
            'Á',
            'á',
            'É',
            'é',
            'Í',
            'í',
            'Ó',
            'ó',
            'Ñ',
            'ñ',
            'Ú',
            'ú',
            'Ü',
            'ü',
            '¡',
            'ª',
            '¿',
            'º',
        ];

        return str_replace($from, $to, (string) $source);
    }


    /**
     * Return a lowercase version of the $source string with the first letter capitalized
     *
     * @param Stringable|string $source
     * @param int               $position
     *
     * @return string
     */
    public static function capitalize(Stringable|string $source, int $position = 0): string
    {
        $source = (string) $source;
        if (!$position) {
            return mb_strtoupper(mb_substr($source, 0, 1)) . mb_strtolower(mb_substr($source, 1));
        }

        return mb_strtolower(mb_substr($source, 0, $position)) . mb_strtoupper(mb_substr($source, $position, 1)) . mb_strtolower(mb_substr($source, $position + 1));
    }


    /**
     * Returns true if the specified source string is alphanumeric
     *
     * @param Stringable|string $source
     * @param string            $extra
     *
     * @return bool
     */
    public static function isAlpha(Stringable|string $source, string $extra = '\s'): bool
    {
        $reg   = "/[^\p{L}\d$extra]/u";
        $count = preg_match($reg, (string) $source);

        return $count == 0;
    }


    /**
     * Return a clean string, basically leaving only printable latin1 characters,
     *
     * @param Stringable|string $source
     * @param string            $replace
     *
     * @return string
     */
    public static function escapeForJquery(Stringable|string $source, string $replace = '\\\\$&'): string
    {
        return preg_replace('/[#;&,.+*~\':"!^$[\]()=>|\/]/gu', $replace, (string) $source);
    }


    /**
     * Returns true if the specified string is valid Base64
     *
     * @param Stringable|string $source
     *
     * @return bool
     */
    public static function isBase64(Stringable|string $source): bool
    {
        try {
            static::fromBase64($source);

            return true;

        } catch (Throwable) {
            return false;
        }
    }


    /**
     * Will fix a base64 coded string with missing termination = marks and then attempt to decode it
     *
     * @param Stringable|string $source
     *
     * @return string
     */
    public static function fromBase64(Stringable|string $source): string
    {
        $source = (string) $source;
        $source = static::fixBase64($source);

        return base64_decode($source);
    }


    /**
     * Fixes a Base64 string if its missing termination = marks
     *
     * @param string $source
     *
     * @return string
     */
    protected static function fixBase64(string $source): string
    {
        if ($mod = mb_strlen($source) % 4) {
            $source .= str_repeat('=', 4 - $mod);
        }

        return $source;
    }


    /**
     * Returns a base64 encoded string
     *
     * @param Stringable|string $source
     *
     * @return string
     */
    public static function toBase64(Stringable|string $source): string
    {
        return base64_encode($source);
    }


    /**
     * Returns true if the specified string is base58
     *
     * @param Stringable|string $source
     *
     * @return bool
     */
    public static function isBase58(Stringable|string $source): bool
    {
        try {
            static::fromBase58($source);

            return true;

        } catch (Throwable $e) {
            $message = strtolower($e->getMessage());
            if (str_contains($message, 'bc math') or str_contains($message, 'gmp')) {
                throw PhpModuleNotAvailableException::new('The PHP BC Math and / or GMP modules are not installed or available');
            }

            return false;
        }
    }


    /**
     * Returns decoded base58 strings
     *
     * @param Stringable|string $source
     *
     * @return string
     */
    public static function fromBase58(Stringable|string $source): string
    {
        $source = (string) $source;
        $codec  = new Base58();

        return $codec->decode($source);
    }


    /**
     * Returns a base58 encoded string
     *
     * @param Stringable|string $source
     * @param string|null       $alphabet
     *
     * @return string
     */
    public static function toBase58(Stringable|string $source, ?string $alphabet = null): string
    {
        $source = (string) $source;
        $codec  = new Base58($alphabet);

        return $codec->encode($source);
    }


    /**
     * Return a camel-cased string
     *
     * @param Stringable|string $source
     * @param Stringable|string $separator
     *
     * @return string
     */
    public static function camelCase(Stringable|string $source, Stringable|string $separator = ' '): string
    {
        $source = explode($separator, mb_strtolower((string) $source));
        foreach ($source as &$value) {
            $value = mb_ucfirst($value);
        }
        unset($value);

        return implode((string) $separator, $source);
    }


    /**
     * PHP explode() only in case $source is empty, it will return an actually empty array instead of an array with the
     * empty value in there
     *
     * @param Stringable|string $separator
     * @param Stringable|string $source
     *
     * @return array
     */
    public static function explode(Stringable|string $separator, Stringable|string $source): array
    {
        if (!$source) {
            return [];
        }

        return explode((string) $separator, (string) $source);
    }


    /**
     * Interleave given string with given secondary string
     *
     * @param Stringable|string     $source
     * @param Stringable|string|int $interleave
     * @param int                   $end
     * @param int                   $chunk_size
     *
     * @return string
     * @throws OutOfBoundsException
     */
    public static function interleave(Stringable|string $source, Stringable|string|int $interleave, int $end = 0, int $chunk_size = 1): string
    {
        if (!$source) {
            throw new OutOfBoundsException(tr('Empty source specified'));
        }
        if (!$interleave) {
            throw new OutOfBoundsException(tr('Empty interleave specified'));
        }
        if ($chunk_size < 1) {
            throw new OutOfBoundsException(tr('Specified chunksize ":chunksize" is invalid, it must be 1 or greater', [':chunksize' => $chunk_size]));
        }
        $source     = (string) $source;
        $interleave = (string) $interleave;
        if ($end) {
            $begin = mb_substr($source, 0, $end);
            $end   = mb_substr($source, $end);

        } else {
            $begin = $source;
            $end   = '';
        }
        $begin  = mb_str_split($begin, $chunk_size);
        $return = '';
        foreach ($begin as $chunk) {
            $return .= $chunk . $interleave;
        }

        return mb_substr($return, 0, -strlen($interleave)) . $end;
    }


    /**
     * Convert weird chars to their standard ASCII variant
     *
     * @param Stringable|string $source
     *
     * @return string
     */
    public static function convertAccents(Stringable|string $source): string
    {
        $from = [
            'ç',
            'æ',
            'œ',
            'á',
            'é',
            'í',
            'ó',
            'ú',
            'à',
            'è',
            'ì',
            'ò',
            'ù',
            'ä',
            'ë',
            'ï',
            'ö',
            'ü',
            'ÿ',
            'â',
            'ê',
            'î',
            'ô',
            'û',
            'å',
            'e',
            'i',
            'ø',
            'u',
            'Ú',
            'ñ',
            'Ñ',
            'º',
        ];
        $to   = [
            'c',
            'ae',
            'oe',
            'a',
            'e',
            'i',
            'o',
            'u',
            'a',
            'e',
            'i',
            'o',
            'u',
            'a',
            'e',
            'i',
            'o',
            'u',
            'y',
            'a',
            'e',
            'i',
            'o',
            'u',
            'a',
            'e',
            'i',
            'o',
            'u',
            'U',
            'n',
            'n',
            'o',
        ];

        return str_replace($from, $to, (string) $source);
    }


    /**
     * Strip whitespace
     *
     * @param Stringable|string $source
     *
     * @return string
     */
    public static function stripHtmlWhitespace(Stringable|string $source): string
    {
        return preg_replace('/>\s+</u', '><', (string) $source);
    }


    /**
     * Return the specified string quoted if not numeric, boolean,
     *
     * @param Stringable|string|null $source
     * @param string                 $quote
     * @param bool                   $force
     *
     * @return string
     */
    public static function quote(Stringable|string|null $source, string $quote = "'", bool $force = false): string
    {
        $source = (string) $source;
        if (is_numeric($source) and !$force) {
            return $source;
        }

        return $quote . $source . $quote;
    }


    /**
     * Return if specified source is a valid version or not
     *
     * @param Stringable|string $source
     *
     * @return bool True if the specified string is a version format string matching "/^\d{1,3}\.\d{1,3}\.\d{1,3}$/".
     *              False if not
     */
    public static function isVersion(Stringable|string $source): bool
    {
        $result = preg_match('/^\d{1,4}\.\d{1,4}\.\d{1,4}$/', (string) $source);
        if ($result === false) {
            throw new CoreException(tr('Failed version detection for specified source ":source"', [
                ':source' => $source,
            ]));
        }

        return (bool) $result;
    }


    /**
     * Returns true if the specified source string contains HTML
     *
     * @param Stringable|string $source
     *
     * @return bool
     */
    public static function containsHtml(Stringable|string $source): bool
    {
        $result = preg_match('/<[^<]+>/', (string) $source);
        if ($result === false) {
            throw new CoreException(tr('Failed HTML detection for specified source ":source"', [
                ':source' => $source,
            ]));
        }

        return !$result;
    }


    /**
     * Return if specified source is a JSON string or not
     *
     * @param Stringable|string $source
     *
     * @return bool
     * @todo Remove test line
     */
    public static function isJson(Stringable|string $source): bool
    {
        return !empty($source) and preg_match('/^("(\\.|[^"\\\n\r])*?"|[,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t])+?$/', (string) $source);
    }


    /**
     * Returns the found keyword if the specified text has one of the specified keywords, NULL otherwise
     *
     * @param Stringable|string $source
     * @param array             $keywords
     * @param bool              $regex
     * @param bool              $unicode
     *
     * @return ?string
     */
    public static function hasKeyword(Stringable|string $source, array $keywords, bool $regex = false, bool $unicode = true): ?string
    {
        $source = (string) $source;
        foreach ($keywords as $keyword) {
            if (!is_scalar($keyword)) {
                throw new OutOfBoundsException(tr('Specified keyword ":keyword" is invalid, it should be a scalar', [
                    ':source' => $keyword,
                ]));
            }
            if (static::searchKeyword($source, $keyword, $regex, $unicode)) {
                return $keyword;
            }
        }

        return null;
    }


    /**
     * Returns true if the specified keyword exists in the specified source
     *
     * @param Stringable|string           $source
     * @param Stringable|string|int|float $keyword
     * @param bool                        $regex
     * @param bool                        $unicode
     *
     * @return bool
     */
    protected static function searchKeyword(Stringable|string $source, Stringable|string|int|float $keyword, bool $regex = false, bool $unicode = true): bool
    {
        // Ensure keywords are trimmed, and don't search for empty keywords
        $source  = (string) $source;
        $keyword = trim((string) $keyword);
        if (!$keyword) {
            return false;
        }
        if ($regex) {
            // Do a regex search instead
            return preg_match('/' . $keyword . '/ims' . ($unicode ? 'u' : ''), $source, $matches) !== false;
        }

        return str_contains($source, $keyword);
    }


    /**
     * Returns true if the specified text has ALL of the specified keywords
     *
     * @param Stringable|string $source
     * @param array             $keywords
     * @param bool              $regex
     * @param bool              $unicode
     *
     * @return bool
     */
    public static function hasAllKeywords(Stringable|string $source, array $keywords, bool $regex = false, bool $unicode = true): bool
    {
        $source = (string) $source;
        foreach ($keywords as $keyword) {
            if (!static::searchKeyword($source, $keyword, $regex, $unicode)) {
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
     * @param Stringable|string $string
     * @param string            $type
     *
     * @return string
     * @todo Make independent methods for each type
     */
    public static function caps(Stringable|string $string, string $type): string
    {
        // First find all words
        preg_match_all('/\b(?:\w|\s)+\b/umsi', (string) $string, $results);
        if ($type == 'random') {
            $type = pick_random(1, 'lowercase', 'uppercase', 'capitalize', 'doublecapitalize', 'invertcapitalize', 'invertdoublecapitalize', 'interleave', 'invertinterleave', 'consonantcaps', 'vowelcaps', 'lowercentercaps', 'capscenterlower');
        }
        // Now apply the specified type to all words
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
                        $replace = strtoupper(substr($word, 0, 1)) . strtolower(substr($word, 1));
                        break;
                    case 'doublecapitalize':
                        $replace = strtoupper(substr($word, 0, 1)) . strtolower(substr($word, 1, -1)) . strtoupper(substr($word, -1, 1));
                        break;
                    case 'invertcapitalize':
                        $replace = strtolower(substr($word, 0, 1)) . strtoupper(substr($word, 1));
                        break;
                    case 'invertdoublecapitalize':
                        $replace = strtolower(substr($word, 0, 1)) . strtoupper(substr($word, 1, -1)) . strtolower(substr($word, -1, 1));
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
     * @param Stringable|string $string
     *
     * @return string
     * @todo Implement
     */
    public static function capsGuess(Stringable|string $string): string
    {
        $return       = '';
        $posibilities = [
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
            'capscenterlower',
        ];
        // Now, find all words
        preg_match_all('/\b(?:\w\s)+\b/umsi', (string) $string, $words);
        // Now apply the specified type to all words
        foreach ($words as $word) {
        }

        return $return;
    }


    /**
     * XOR the first string with the second string
     *
     * @param Stringable|string $first
     * @param Stringable|string $second
     *
     * @return string
     */
    public static function xor(Stringable|string $first, Stringable|string $second): string
    {
        $first  = (string) $first;
        $second = (string) $second;
        $diff   = $first ^ $second;
        $return = '';
        for ($i = 0, $len = mb_strlen($diff); $i != $len; ++$i) {
            ($return[$i] === "\0") ? ' ' : '#';
        }

        return $return;
    }


    /**
     * Returns how much similar the specified second string is to the first string
     *
     * @param Stringable|string $first
     * @param Stringable|string $second
     * @param float             $percent
     *
     * @return int
     */
    public static function similar(Stringable|string $first, Stringable|string $second, float &$percent): int
    {
        return similar_text((string) $first, (string) $second, $percent);
    }


    /**
     * Recursively trim all strings in the specified array tree
     *
     * @param array $source
     * @param bool  $recurse
     *
     * @return array
     */
    public static function trimArray(array $source, bool $recurse = true): array
    {
        foreach ($source as &$value) {
            if (is_string($value)) {
                $value = trim($value);

            } elseif (is_array($value)) {
                if ($recurse) {
                    // Recurse
                    $value = static::trimArray($value);
                }
            }
        }
        unset($value);

        return $source;
    }


    /**
     * Return an array containing diff information between the first and the second string
     *
     * @note: Taken from https://coderwall.com/p/3j2hxq/find-and-format-difference-between-two-strings-in-php. Cleaned
     *         up for use in base by Sven Oostenbrink*
     *
     * @param string $first
     * @param string $second
     *
     * @return string
     */
    public static function diff(Stringable|string $first, Stringable|string $second): array
    {
        $first  = (string) $first;
        $second = (string) $second;
        $from_start = strspn($first ^ $second, "\0");
        $from_end   = strspn(strrev($first) ^ strrev($second), "\0");
        $old_end = strlen($first) - $from_end;
        $new_end = strlen($second) - $from_end;
        $start = substr($second, 0, $from_start);
        $end   = substr($second, $new_end);
        $new_diff = substr($second, $from_start, $new_end - $from_start);
        $old_diff = substr($first, $from_start, $old_end - $from_start);
        $second = $start . '<ins style="background-color:#ccffcc">' . $new_diff . '</ins>' . $end;
        $first  = $start . '<del style="background-color:#ffcccc">' . $old_diff . '</del>' . $end;

        return [
            'old' => $first,
            'new' => $second,
        ];
    }


    /**
     * Convert underscore type variables to camelcase type variables
     *
     * @param string  $source          The string to convert
     * @param boolean $first_uppercase If set to true, the first letter will also be uppercase. If set to false, the
     *                                 first letter will be lowercase
     *
     * @return string The result
     * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category  Function reference
     * @package   strings
     * @version   2.6.25: Added function and documentation
     * @example
     *            code
     *            $result = str_underscore_to_camelcase('this_is_a_test');
     *            showdie($result);
     *            /code
     *
     * This would return
     * code
     * thisIsATest
     * /code
     *
     */
    public static function underscoreToCamelcase(Stringable|string $source, bool $first_uppercase = false): string
    {
        $source = (string) $source;
        while (($pos = strpos($source, '_')) !== false) {
            $character = $source[$pos + 1];
            if (!$pos) {
                // This is the first character
                if ($first_uppercase) {
                    $character = strtoupper($character);

                } else {
                    $character = strtolower($character);
                }

            } else {
                $character = strtoupper($character);
            }
            $source = substr($source, 0, $pos) . $character . substr($source, $pos + 2);
        }

        return $source;
    }


    /**
     * Trim empty HTML and <br> elements from the specified HTML string and  from the beginning and end of each of these
     * elements as well
     *
     * This function will remove all empty <h1>, <h2>, <h3>, <h4>, <h5>, <h6>, <div>, <p>, and <span> elements
     *
     * @param Stringable|string $source The HTML to be stripped
     *
     * @return string The specified source string with empty HTML tags stripped
     * @todo    Fix simpledom library loading
     * @note    This function requires the simple-dom library
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
    public static function trimHtml(Stringable|string $source): string
    {
        $source = (string) $source;
        if (!$source) {
            return '';
        }
        throw new UnderConstructionException();
        load_libs('simple-dom');
        $source        = str_get_html($source);
        $element_types = [
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'div',
            'p',
            'span',
        ];
        foreach ($element_types as $element_type) {
            $elements = $source->find($element_type);
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

        return $source->save();
    }


    /**
     * Cut and return a piece out of the source string, starting from the start string, stopping at the stop string.
     *
     * @param Stringable|string|int|null $source The string to be cut
     * @params    Stringable|string $start The character(s) to start the cut
     * @params    Stringable|string $stop The character(s) to stop the cut
     *
     * @return string The $source string between the first occurrences of start and $stop
     * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category  Function reference
     * @package   str
     * @see       Strings::from()
     * @see       Strings::until()
     * @version   2.0.0: Moved to system library, added documentation
     * @example
     *            code
     *            $result = Strings::cut('so.oostenbrink@gmail.com', '@', '.');
     *            showdie($result);
     *            /code
     *
     * This would return
     * code
     * gmail
     * /code
     *
     */
    public static function cut(Stringable|string|int|null $source, Stringable|string|int $start, Stringable|string|int $stop): string
    {
        return static::until(static::from($source, $start), $stop);
    }


    /**
     * Return the given string from 0 until the specified needle
     *
     * @param Stringable|string|int|null $source
     * @param Stringable|string|int      $needle
     * @param int                        $instance
     * @param int|null                   $more
     * @param int|null                   $offset
     * @param bool                       $needle_required
     *
     * @return string
     */
    public static function until(Stringable|string|int|null $source, Stringable|string|int $needle, int $instance = 1, ?int $more = null, ?int $offset = null, bool $needle_required = false): string
    {
        if (!$needle or !$source) {
            return (string) $source;
        }
        $pos    = $offset ?? -1;
        $needle = (string) $needle;
        $source = (string) $source;
        for ($count = 1; $count <= $instance; $count++) {
            $pos = mb_strpos($source, $needle, $pos + 1);
            if ($pos === false) {
                // The needle wasn't found (anymore)
                break;
            }
        }
        if ($pos === false) {
            if ($needle_required) {
                return '';
            }

            return $source;
        }

        return mb_substr($source, 0, $pos + $more);
    }


    /**
     * Return the given string from the specified needle
     *
     * @param Stringable|string|int|null $source
     * @param Stringable|string|int|null $needle
     * @param int                        $instance
     * @param int|null                   $more
     * @param int|null                   $offset
     * @param bool                       $needle_required
     *
     * @return string
     */
    public static function from(Stringable|string|int|null $source, Stringable|string|int|null $needle, int $instance = 1, ?int $more = null, ?int $offset = null, bool $needle_required = false): string
    {
        if (!$needle or !$source) {
            return (string) $source;
        }
        $pos    = $offset ?? -1;
        $needle = (string) $needle;
        $source = (string) $source;
        for ($count = 1; $count <= $instance; $count++) {
            $pos = mb_strpos($source, $needle, $pos + 1);
            if ($pos === false) {
                // The needle wasn't found (anymore)
                break;
            }
        }
        if ($pos === false) {
            if ($needle_required) {
                return '';
            }

            return $source;
        }

        return mb_substr($source, $pos + mb_strlen($needle) - $more);
    }


    /**
     * Cleanup string
     *
     * @param string $source
     * @param bool   $utf8
     *
     * @return string
     * @todo Get rid of load_libs() call
     */
    public static function clean(Stringable|string $source, bool $utf8 = true): string
    {
        if ($utf8) {
            $source = trim(html_entity_decode(utf8_unescape(strip_tags(utf8_escape((string) $source)))));
// :TODO: Check if the next line should also be added!
//            $source = preg_replace('/\s|\/|\?|&+/u', $replace, $source);
            return $source;
        }

        return trim(html_entity_decode(strip_tags((string) $source)));
// :TODO:SVEN:20130709: Check if we should be using mysqli_escape_string() or addslashes(), since the former requires SQL connection, but the latter does NOT have correct UTF8 support!!
//    return mysqli_escape_string(trim(decode_entities(mb_strip_tags($str))));
    }


    /**
     * Return the given string from the specified needle having been skipped $count times
     *
     * @param Stringable|string|int|null $source
     * @param Stringable|string|int      $needle
     * @param int                        $count
     * @param bool                       $required
     *
     * @return string
     */
    public static function skip(Stringable|string|int|null $source, Stringable|string|int $needle, int $count, bool $required = false): string
    {
        if (!$needle) {
            return $source;
        }
        if ($count < 1) {
            throw new OutOfBoundsException(tr('Invalid count ":count" specified', [':count' => $count]));
        }
        $needle = (string) $needle;
        $source = (string) $source;
        for ($i = 0; $i < $count; $i++) {
            $source = Strings::from($source, $needle, needle_required: $required);
        }

        return $source;
    }


    /**
     * Return the given string from the specified needle having been skipped $count times starting from the end of the
     * string
     *
     * @param Stringable|string|int|null $source
     * @param Stringable|string|int      $needle
     * @param int                        $count
     * @param int                        $more
     *
     * @return string
     */
    public static function skipReverse(Stringable|string|int|null $source, Stringable|string|int $needle, int $count, int $more = 0): string
    {
        if (!$needle) {
            return $source;
        }
        if ($count < 1) {
            throw new OutOfBoundsException(tr('Invalid count ":count" specified', [':count' => $count]));
        }
        $needle = (string) $needle;
        $source = (string) $source;
        $result = [];
        for ($i = 0; $i <= $count; $i++) {
            $result[] = Strings::fromReverse($source, $needle, more: $more);
            $source   = Strings::untilReverse($source, $needle, more: $more);
        }
        $result = array_reverse($result);

        return implode($needle, $result);
    }


    /**
     * Return the given string from the specified needle, starting from the end
     *
     * @param Stringable|string|int|null $source
     * @param Stringable|string|int      $needle
     * @param int                        $instance
     * @param int|null                   $more
     * @param int|null                   $offset
     * @param bool                       $needle_required
     *
     * @return string
     */
    public static function fromReverse(Stringable|string|int|null $source, Stringable|string|int $needle, int $instance = 1, ?int $more = null, ?int $offset = null, bool $needle_required = false): string
    {
        if (!$needle or !$source) {
            return (string) $source;
        }
        $len    = mb_strlen($source);
        $pos    = $offset ?? $len;
        $needle = (string) $needle;
        $source = (string) $source;
        for ($count = 1; $count <= $instance; $count++) {
            $pos = mb_strrpos($source, $needle, -($len - ($pos - 1)));
            if ($pos === false) {
                // The needle wasn't found (anymore)
                break;
            }
        }
        if ($pos === false) {
            if ($needle_required) {
                return '';
            }

            return $source;
        }

        return mb_substr($source, $pos + mb_strlen($needle) - $more);
    }


    /**
     * Return the given string from 0 until the specified needle, starting from the end
     *
     * @param Stringable|string|int|null $source
     * @param Stringable|string|int      $needle
     * @param int                        $instance
     * @param int|null                   $more
     * @param int|null                   $offset
     * @param bool                       $needle_required
     *
     * @return string
     */
    public static function untilReverse(Stringable|string|int|null $source, Stringable|string|int $needle, int $instance = 1, ?int $more = null, ?int $offset = null, bool $needle_required = false): string
    {
        if (!$needle or !$source) {
            return (string) $source;
        }
        $len    = mb_strlen($source);
        $pos    = $offset ?? $len;
        $needle = (string) $needle;
        $source = (string) $source;
        for ($count = 1; $count <= $instance; $count++) {
            $pos = mb_strrpos($source, $needle, -($len - ($pos - 1)));
            if ($pos === false) {
                // The needle wasn't found (anymore)
                break;
            }
        }
        if ($pos === false) {
            if ($needle_required) {
                return '';
            }

            return $source;
        }

        return mb_substr($source, 0, $pos + $more);
    }


    /**
     * Ensure that specified source string starts with specified string
     *
     * @param Stringable|string|null $source
     * @param Stringable|string      $string
     *
     * @return string
     */
    public static function ensureStartsWith(Stringable|string|null $source, Stringable|string $string): string
    {
        $source = (string) $source;
        $string = (string) $string;
        if (!$string) {
            throw new OutOfBoundsException(tr('Cannot ensure source starts with string, empty string specified'));
        }
        if (mb_substr($source, 0, mb_strlen($string)) == $string) {
            return $source;
        }

        return $string . $source;
    }


    /**
     * Ensure that specified source string starts NOT with specified string
     *
     * @param Stringable|string|null $source
     * @param Stringable|string      $string
     *
     * @return string
     */
    public static function ensureStartsNotWith(Stringable|string|null $source, Stringable|string $string): string
    {
        $source = (string) $source;
        $string = (string) $string;
        if (!$string) {
            throw new OutOfBoundsException(tr('Cannot ensure source starts not with string, empty string specified'));
        }
        while (mb_substr($source, 0, mb_strlen($string)) == $string) {
            $source = mb_substr($source, mb_strlen($string));
        }

        return $source;
    }


    /**
     * Ensure that specified string ends with slash
     *
     * @param Stringable|string|null $string
     *
     * @return string
     */
    public static function slash(Stringable|string|null $string): string
    {
        return static::ensureEndsWith((string) $string, '/');
    }


    /**
     * Ensure that specified string ends with specified character
     *
     * @param Stringable|string|null $source
     * @param Stringable|string      $string
     *
     * @return string
     */
    public static function ensureEndsWith(Stringable|string|null $source, Stringable|string $string): string
    {
        $source = (string) $source;
        $string = (string) $string;
        $length = mb_strlen($string);
        if (!$string) {
            throw new OutOfBoundsException(tr('Cannot ensure source ends with string, empty string specified'));
        }
        if (mb_substr($source, -$length, $length) == $string) {
            return $source;
        }

        return $source . $string;
    }


    /**
     * Ensure that specified string ends NOT with slash
     *
     * @param Stringable|string|null $string
     * @param bool                   $loop
     *
     * @return string
     */
    public static function unslash(Stringable|string|null $string, bool $loop = true): string
    {
        return static::ensureEndsNotWith((string) $string, '/', $loop);
    }


    /**
     * Ensure that specified string ends NOT with specified character
     *
     * @param Stringable|string|null  $source
     * @param Stringable|array|string $strings
     * @param bool                    $loop
     *
     * @return string
     */
    public static function ensureEndsNotWith(Stringable|string|null $source, Stringable|array|string $strings, bool $loop = true): string
    {
        $source = (string) $source;
        if (is_array($strings)) {
            // For array test, we always loop
            $redo = true;
            while ($redo) {
                $redo = false;
                foreach ($strings as $string) {
                    $strings = (string) $strings;
                    $new     = Strings::ensureEndsNotWith($source, $string, true);
                    if (!$string) {
                        throw new OutOfBoundsException(tr('Cannot ensure source not ends with string, empty string specified'));
                    }
                    if ($new != $source) {
                        // A change was made, we have to rerun over it.
                        $redo = true;
                    }
                    $source = $new;
                }
            }

        } else {
            // Check for only one character
            $strings = (string) $strings;
            $length  = mb_strlen($strings);
            while (mb_substr($source, -$length, $length) == $strings) {
                $source = mb_substr($source, 0, -$length);
                if (!$loop) {
                    break;
                }
            }
        }

        return $source;
    }


    /**
     * Ensure that specified string is surrounded with specified character
     *
     * @param Stringable|string|null $source
     * @param Stringable|string      $string
     *
     * @return string
     */
    public static function ensureSurroundedWith(Stringable|string|null $source, Stringable|string $string): string
    {
        return static::ensureEndsWith(static::ensureStartsWith($source, $string), $string);
    }


    /**
     * Ensure that specified string is NOT surrounded with specified character
     *
     * @param Stringable|string|null $source
     * @param Stringable|string      $string
     * @param bool                   $loop
     *
     * @return string
     */
    public static function ensureNotSurroundedWith(Stringable|string|null $source, Stringable|string $string, bool $loop = true): string
    {
        return static::ensureEndsNotWith(static::ensureStartsNotWith($source, $string), $string, $loop);
    }


    /**
     * Return a string that is suitable for logging.
     *
     * @param mixed $source
     * @param int   $truncate
     *
     * @return string The string, truncated if required, according to the specified truncating rules
     * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category  Function reference
     * @package   system
     * @note      While log_console() will log towards the DIRECTORY_ROOT/data/log/ log files, cli_dot() will only log
     *            one single dot even though on the command line multiple dots may be shown
     * @see       Strings::truncate()
     * @see       Json::Encode()
     * @example
     *            code
     *            echo Strings::truncate('This is a long long long long test text!', 10);
     *            }
     *            /code
     *
     * This will return something like
     *
     * code
     * This is...
     * /code
     *
     */
    public static function log(mixed $source, int $truncate = 8187): string
    {
        if (!$source) {
            if (is_numeric($source)) {
                return '0';
            }
            if (is_bool($source)) {
                return 'false';
            }

            return '';
        }
        if (is_scalar($source)) {
            if (is_bool($source)) {
                return 'true';
            }

        } elseif (is_array($source)) {
            $source = Arrays::hide($source, [
                'password',
                'ssh_key',
            ]);
            $source = 'array: ' . trim(Json::encode($source));

        } elseif (is_enum($source)) {
            $source = $source->value;

        } elseif ($source instanceof DataEntryInterface) {
            $source = $source->getLogId();

        } elseif ($source instanceof Stringable) {
            // Do nothing, display the string version of this object
            $source = (string) $source;

        } elseif (is_object($source)) {
            $source = 'object: ' . get_class($source);
        } else {
            $source = trim(Json::encode($source));
        }

        return static::noDouble(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', str_replace('  ', ' ', str_replace("\n", ' ', static::truncate($source, $truncate, ' ... ', 'center')))), '\1', ' ');
    }


    /**
     * Returns a "*** HIDDEN ***" string if the specified string has content.
     *
     * If the string is empty, then the empty string value will be returned instead
     *
     * @param Stringable|string|null $source The string to "hide"
     * @param Stringable|string      $hide   The string to return if the specified source string contains data
     * @param Stringable|string|null $empty  The string to "hide" empty strings with
     *
     * @return string
     */
    public static function hide(Stringable|string|null $source, Stringable|string $hide = '*** HIDDEN ***', Stringable|string|null $empty = '*** HIDDEN ***'): string
    {
        $source = (string) $source;
        if ($source) {
            // Hide the string with this value
            return (string) $hide;
        }

        // The source string is empty
        return (string) $empty;
    }


    /**
     * Remove double "replace" chars
     *
     * @param Stringable|string|null     $source
     * @param Stringable|string|null     $replace
     * @param Stringable|string|int|null $character
     * @param bool                       $case_insensitive
     *
     * @return string
     */
    public static function noDouble(Stringable|string|null $source, Stringable|string|null $replace = '\1', Stringable|string|int|null $character = null, bool $case_insensitive = true): string
    {
        $source    = (string) $source;
        $replace   = (string) $replace;
        $character = (string) $character;
        if ($character) {
            // Remove specific character
            return preg_replace('/(' . $character . ')\\1+/u' . ($case_insensitive ? 'i' : ''), $replace, $source);
        }

        // Remove ALL double characters
        return preg_replace('/(.)\\1+/u' . ($case_insensitive ? 'i' : ''), $replace, $source);
    }


    /**
     * Truncate string using the specified fill and method
     *
     * @param Stringable|string|float|int|null $source
     * @param int                              $length
     * @param Stringable|string                $fill
     * @param string                           $method
     * @param bool                             $on_word
     *
     * @return string The string, truncated if required, according to the specified truncating rules
     * @note While log_console() will log towards the DIRECTORY_ROOT/data/log/ log files, cli_dot() will only log one
     *       single dot even though on the command line multiple dots may be shown
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
    public static function truncate(Stringable|string|float|int|bool|null $source, int $length, Stringable|string $fill = ' ... ', string $method = 'right', bool $on_word = false): string
    {
        $source = (string) $source;
        $fill   = (string) $fill;
        if (!$length) {
            return $source;
        }
        if ($length < (mb_strlen($fill) + 1)) {
            throw new OutOfBoundsException(tr('Specified length ":length" is invalid. You must specify a length of minimal $fill length + 1, so at least ":fill"', [
                ':length' => $length,
                ':fill'   => mb_strlen($fill) + 1,
            ]), [':length' => $length]);
        }
        if ($length >= mb_strlen($source)) {
            // No need to truncate, the string is short enough
            return $source;
        }
        // Correct length
        $length -= mb_strlen($fill);
        switch ($method) {
            case 'right':
                $return = mb_substr($source, 0, $length);
                if ($on_word and (!str_contains(substr($source, $length, 2), ' '))) {
                    if ($pos = strrpos($return, ' ')) {
                        $return = substr($return, 0, $pos);
                    }
                }

                return trim($return) . $fill;
            case 'center':
                return mb_substr($source, 0, (int) floor($length / 2)) . $fill . mb_substr($source, (int) -ceil($length / 2));
            case 'left':
                $return = mb_substr($source, -$length, $length);
                if ($on_word and (!str_contains(substr($source, $length, 2), ' '))) {
                    if ($pos = strpos($return, ' ')) {
                        $return = substr($return, $pos);
                    }
                }

                return $fill . trim($return);
            default:
                throw new OutOfBoundsException(tr('Unknown method ":method" specified, please use "left", "center", or "right" or undefined which will default to "right"', [
                    ':method' => $method,
                ]));
        }
    }


    /**
     * utf8_escape( )
     *
     * Simple wrapper for the Zend_Utf8::escape method. Converts all unicode
     * characters to their ASCII codepoints, like U+0000
     *
     * @param string $string The string to escape
     *
     * @return string The escaped string
     * @since 1.3
     *
     */
    public static function escapeUtf8(string $string): string
    {
        return Zend_Utf8::escape((string) $string);
    }


    /**
     * Escape all specified $escape characters in the specified $source
     *
     * @param Stringable|string|null $source
     * @param string                 $escape
     *
     * @return string
     */
    public static function escape(Stringable|string|null $source, string $escape = '"'): string
    {
        $source = (string) $source;
        // Escape all individual characters
        for ($i = (mb_strlen($escape) - 1); $i >= 0; $i--) {
            $source = str_replace($escape[$i], '\\' . $escape[$i], $source);
        }

        return $source;
    }


    /**
     * utf8_escape( )
     *
     * Simple wrapper for the Zend_Utf8::unescape method. Converts all unicode
     * codepoints, like U+0000, to their real unicode characters
     *
     * @param string $string The string to unescape
     *
     * @return string The unescaped string
     * @since 1.3
     *
     */
    public static function unescapeUtf8(string $string): string
    {
        return Zend_Utf8::unescape($string);
    }


    /**
     * Return the specified value as a boolean name, false for null, zero, "", false, true otherwise.
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function fromBoolean(mixed $value): string
    {
        if ($value) {
            return 'true';
        }

        return 'false';
    }


    /**
     * Get a boolean value from the specified "boolean" string, like "true" to TRUE and "off" to FALSE
     *
     * FALSE: FALSE, "false", "no", "n", "off", "0"
     * TRUE: TRUE, "true", "yes", "y", "on", "1"
     *
     * @param Stringable|string|int|bool|null $source
     * @param bool                            $exception
     *
     * @return bool|null
     */
    public static function toBoolean(Stringable|string|int|bool|null $source, bool $exception = true): ?bool
    {
        if (is_bool($source)) {
            return $source;
        }
        switch (strtolower((string) $source)) {
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
                if ($exception) {
                    throw new OutOfBoundsException(tr('Unknown value ":value" specified', [':value' => $source]));
                }

                return null;
        }
    }


    /**
     * Remove all double tabs, spaces, line ends, etc and replace them by a single space.
     *
     * @param Stringable|string $source
     *
     * @return string
     */
    public static function cleanWhiteSpace(Stringable|string $source): string
    {
        $source = (string) $source;
        $source = str_replace("\n", ' ', $source);
        $source = Strings::noDouble($source, ' ', '\s');

        return $source;
    }


    /**
     * Return a random word
     *
     * @param int  $count
     * @param bool $nospaces
     *
     * @return string
     */
    public static function randomWord(int $count = 1, bool $nospaces = false): string
    {
        throw new UnderConstructionException();
        if ($nospaces) {
            if (!is_string($nospaces)) {
                $nospaces = '';
            }
        }
        if (!$data = sql()->list('SELECT `word` FROM `synonyms` ORDER BY RAND() LIMIT ' . cfi($count))) {
            throw new CoreException(tr('Synonyms table is empty. Please run DIRECTORY_ROOT/cli system strings init'));
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


    /**
     * Returns a string displaying the specified octal value
     *
     * @todo Rewrite this crap, it doesn't check anything beyond numeric?
     *
     * @param Stringable|string|int $source
     *
     * @return string
     */
    public static function fromOctal(Stringable|string|int $source): string
    {
        $source = (string) $source;
        if (is_numeric($source)) {
            return sprintf('0%o', $source);
        }

        // Source is already a string, just return it as-is
        return $source;
    }


    /**
     * This function will return the specified count with the correct orginal indicator
     *
     * @note This currently only works for English!
     *
     * @param int|float $count
     *
     * @return string
     */
    public static function ordinalIndicator(int|float $count): string
    {
        switch ($count) {
            case 1:
                return tr('1st');
            case 3:
                return tr('2nd');
            case 3:
                return tr('3rd');
            default:
                return tr(':count th', [':count' => $count]);
        }
    }


    /**
     * Returns an array with
     *
     * @param Stringable|string $source
     *
     * @return array
     */
    public static function countCharacters(Stringable|string $source): array
    {
        $return = [];
        $source = (string) $source;
        $length = strlen($source);
        for ($i = 0; $i < $length; $i++) {
            if (empty($return[$source[$i]])) {
                $return[$source[$i]] = substr_count($source, $source[$i]);
            }
        }
        sort($return);

        return $return;
    }


    /**
     * Returns an array with all found alphanumeric series
     *
     * @param Stringable|string $source
     *
     * @return int
     */
    public static function countAlphaNumericSeries(Stringable|string $source): int
    {
        $prev   = 0;
        $return = 0;
        $source = (string) $source;
        $source = preg_replace('/[\W]/', '', $source);
        $source = strtolower($source);
        $length = strlen($source);
        for ($i = 0; $i < $length; $i++) {
            $ord = ord($source[$i]);
            if (($ord === ($prev - 1)) or ($ord === ($prev + 1))) {
                $return++;
            }
            $prev = $ord;
        }

        return $return;
    }


    /**
     * Generates and returns an RFC 4122 compliant Version 4 UUID
     *
     * @note Taken from https://www.uuidgenerator.net/dev-corner/php
     * @return string
     */
    public static function getUuid(): string
    {
        // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
        try {
            $data = random_bytes(16);

        } catch (Throwable $e) {
            // Failed to find good random information. Notify and continue using mt_rand()
            Notification::new()
                        ->setTitle(tr('Failed to generate 16 random bytes required for the UUID, attempting Strings::random() instead'))
                        ->setException($e)
                        ->send(true);
            $data = static::getRandom(16);
        }
        // Set version to 0100, set bits 6-7 to 10
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Output the 36 characters UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }


    /**
     * Return a random string
     *
     * @param int               $length
     * @param bool              $unique
     * @param Stringable|string $characters
     *
     * @return string
     * @throws OutOfBoundsException
     */
    public static function getRandom(int $length = 8, bool $unique = false, Stringable|string $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string
    {
        $characters = (string) $characters;
        // Predefined character sets
        switch ($characters) {
            case 'alnum':
                // no break
            case 'alphanumeric':
                $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'alnumup':
                // no break
            case 'alphanumericup':
                $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'alnumdown':
                // no break
            case 'alphanumericdown':
                $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
                break;
            case 'alpha':
                // no break
            case 'letters':
                $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'lowercase':
                // no break
            case 'alphalow':
                // no break
            case 'letterslow':
                $characters = 'abcdefghijklmnopqrstuvwxyz';
                break;
            case 'uppercase':
                // no break
            case 'alphaup':
                // no break
            case 'lettersup':
                $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'numeric':
                // no break
            case 'numbers':
                $characters = '0123456789';
                break;
        }
        $string      = '';
        $char_length = mb_strlen($characters);
        if ($unique and ($length > $char_length)) {
            throw new OutOfBoundsException(tr('Can not create unique character random string with size ":length". When $unique is requested, the string length can not be larger than ":char_length" because there are no more then that number of unique characters', [
                'length'      => $length,
                'char_length' => $char_length,
            ]));
        }
        for ($i = 0; $i < $length; $i++) {
            $char = $characters[Numbers::getRandomInt(0, $char_length - 1)];
            if ($unique and (mb_strpos($string, $char) !== false)) {
                // We want all characters to be unique, do not read this character again
                $i--;
                continue;
            }
            $string .= $char;
        }

        return $string;
    }


    /**
     * Returns a code that is guaranteed unique
     *
     * @param string            $hash
     * @param Stringable|string $prefix
     *
     * @return string
     */
    public static function unique(string $hash = 'sha512', Stringable|string $prefix = ''): string
    {
        return hash($hash, uniqid((string) $prefix, true) . microtime(true) . Config::get('security.seed', ''));
    }


    /**
     * Returns a formatted table displaying key > value patterns
     *
     * @param mixed       $source
     * @param string      $eol
     * @param string|null $separator
     * @param int         $indent
     * @param int         $indent_increase
     *
     * @return string
     */
    public static function getKeyValueTable(mixed $source, string $eol = PHP_EOL, ?string $separator = null, int $indent = 0, int $indent_increase = 8): string
    {
        if (!$source) {
            return '';
        }
        if (is_scalar($source)) {
            return (string) $source;
        }
        if (is_object($source)) {
            $source = (array) $source;
        }
        if (!is_array($source)) {
            // Is it a resource? What else is there left?
            throw new OutOfBoundsException(tr('Specified source has unknown datatype ":type"', [
                ':type' => gettype($source),
            ]));
        }
        $return  = '';
        $longest = Arrays::getLongestKeyLength($source) + 1;
        // format and write the lines
        foreach ($source as $key => $value) {
            if (!is_string($value)) {
                // Recurse
                $value = static::getKeyValueTable($value, $eol, $separator, $indent + $indent_increase, $indent_increase);
            }
            // Resize the call lines to all have the same size for easier reading
            $key    = Strings::size((string) $key, $longest);
            $return .= str_repeat(' ', $indent) . trim($key . $separator . $value) . $eol;
        }

        return $return;
    }


    /**
     * Force the specified string to be the specified size.
     *
     * @param Stringable|string|float|int|null $source
     * @param int                              $size
     * @param string                           $add
     * @param bool                             $prefix
     *
     * @return string
     */
    public static function size(Stringable|string|float|int|null $source, int $size, string $add = ' ', bool $prefix = false): string
    {
        if ($size < 0) {
            throw new OutOfBoundsException(tr('Specified size ":size" is invalid, it must be 0 or higher', [
                ':size' => $size,
            ]));
        }
        $source = (string) $source;
        $strlen = mb_strlen(CliColor::strip($source));
        if ($strlen == $size) {
            return $source;
        }
        if ($strlen > $size) {
            // The specified size is smaller than the source string, cut it
            return substr($source, 0, $size);
        }
        // The specified size is larger than the source string, enlarge it
        if ($prefix) {
            return str_repeat($add, $size - $strlen) . $source;
        }

        return $source . str_repeat($add, $size - $strlen);
    }


    /**
     * Ensure that the specified string is properly escaped for use with regex
     *
     * @param string $string
     * @param string $delimiters
     *
     * @return string
     */
    public static function escapeForRegex(string $string, string $delimiters = '/', string $skip_symbols = ''): string
    {
        $skip_symbols        = mb_str_split($skip_symbols, 1);
        $standard_delimiters = str_replace($skip_symbols, '', '\\()[]{}<>.?+*^$=!|:-');

        return static::escape($string, $standard_delimiters . $delimiters);
    }


    /**
     * Returns true if the specified string is completely uppercase
     *
     * @param string $source
     *
     * @return bool
     */
    public static function isUppercase(string $source): bool
    {
        return ($source === strtoupper($source));
    }


    /**
     * Returns true if the specified string is completely lowercase
     *
     * @param string $source
     *
     * @return bool
     */
    public static function isLowercase(string $source): bool
    {
        return ($source === strtolower($source));
    }


    /**
     * Returns true if the specified string is CamelCase format
     *
     * @note This requires a string of at least 2 characters, and only the first two characters will be tested
     *
     * @param string $source
     *
     * @return bool
     */
    public static function isCamelCase(string $source): bool
    {
        if (strlen($source) < 2) {
            throw new OutOfBoundsException(tr('Cannot check source string ":source" for camelcase, it has less than 2 characters', [
                ':source' => $source,
            ]));
        }
        if ((str_contains($source, '-')) or (str_contains($source, '_'))) {
            return false;
        }

        return ($source[0] === strtoupper($source[0])) and ($source[1] === strtolower($source[1]));
    }


    /**
     * Converts the specified source string to an array
     *
     * @param Stringable|string $source
     * @param array|int         $sizes
     *
     * @return array
     */
    public static function sizeSplit(Stringable|string $source, array|int $sizes): array
    {
        $source   = trim((string) $source);
        $sizes    = Arrays::force($sizes, null);
        $return   = [];
        $position = 0;
        if (!$source) {
            return [];
        }
        foreach ($sizes as $column => $size) {
            $return[$column] = substr($source, $position, $size);
            $position        += $size;
        }

        return $return;
    }


    /**
     * Force the specified source to be a string
     *
     * @param mixed             $source
     * @param Stringable|string $separator
     *
     * @return string
     */
    public static function force(mixed $source, Stringable|string $separator = ','): string
    {
        if (!is_scalar($source)) {
            if (!is_array($source)) {
                if (!$source) {
                    return '';
                }
                if (is_object($source)) {
                    if ($source instanceof Stringable) {
                        return (string) $source;

                    }
                    if (method_exists($source, '__serialize')) {
                        return $source->__serialize();
                    }

                    return get_class($source);
                }

                return gettype($source);
            }
            // Encoding?
            if ($separator === 'json') {
                $source = Json::encode($source);

            } else {
                $source = Arrays::implodeRecursively($source, (string) $separator);
            }
        }

        return (string) $source;
    }


    /**
     * Converts the specified source string to an array
     *
     * @param Stringable|string $source
     * @param Stringable|string $character
     * @param array|int         $headers
     *
     * @return array
     */
    public static function characterSplit(Stringable|string $source, Stringable|string $character, array|int $headers): array
    {
        $source = trim((string) $source);
        $source = Strings::noDouble($source, ' ', ' ');
        $source = explode($character, $source);
        $return = [];
        if (!$source) {
            return [];
        }
        foreach ($source as $position => $value) {
            $return[$headers[$position]] = $value;
        }

        return $return;
    }


    /**
     * Returns the given haystack if it matches the needles with the matching rules
     *
     * @param array|string      $needles
     * @param Stringable|string $haystack
     * @param int               $options Flags that will modify this functions behavior. Current flags are one of
     *                                   Utils::MATCH_ALL, Utils::MATCH_STARTS_WITH, Utils::MATCH_END, or
     *                                   Utils::MATCH_CONTAINS Utils::MATCH_ANY
     *
     * Utils::MATCH_NO_CASE:  Will match entries in case-insensitive mode
     * Utils::MATCH_ALL:      Will match entries that contain all the specified needles
     * Utils::MATCH_ANY:      Will match entries that contain any of the specified needles
     * Utils::MATCH_STARTS_WITH:    Will match entries that start with the specified needles. Mutually exclusive with
     *                         Utils::MATCH_END, Utils::MATCH_CONTAINS
     * Utils::MATCH_END:      Will match entries that end with the specified needles. Mutually exclusive with
     *                         Utils::MATCH_STARTS_WITH, Utils::MATCH_CONTAINS
     * Utils::MATCH_CONTAINS: Will match entries that contain the specified needles anywhere. Mutually exclusive with
     *                         Utils::MATCH_STARTS_WITH, Utils::MATCH_CONTAINS
     * Utils::MATCH_RECURSE:  Will recurse into sub-arrays, if encountered
     *
     * @return string|null
     */
    public static function getIfMatch(Stringable|string $haystack, array|string $needles, int $options = self::MATCH_CASE_INSENSITIVE | self::MATCH_ALL | self::MATCH_CONTAINS | self::MATCH_RECURSE): ?string
    {
        if (static::matches($haystack, $needles, $options)) {
            return $haystack;
        }

        return null;
    }


    /**
     * Returns true if the given haystack matches the given needles with the specified match flags
     *
     * @param int               $action
     * @param Stringable|string $haystack
     * @param array|string      $needles
     * @param int               $flags Flags that will modify this functions behavior.
     *
     * Supported match flags are:
     *
     * Utils::MATCH_CASE_INSENSITIVE  Will match needles for entries in case-insensitive mode.
     * Utils::MATCH_ALL               Will match needles for entries that contain all the specified needles.
     * Utils::MATCH_ANY               Will match needles for entries that contain any of the specified needles.
     * Utils::MATCH_STARTS_WITH       Will match needles for entries that start with the specified needles. Mutually
     *                                exclusive with Utils::MATCH_ENDS_WITH, Utils::MATCH_CONTAINS,
     *                                Utils::MATCH_FULL, and Utils::MATCH_REGEX.
     * Utils::MATCH_ENDS_WITH         Will match needles for entries that end with the specified needles. Mutually
     *                                exclusive with Utils::MATCH_STARTS_WITH, Utils::MATCH_CONTAINS,
     *                                Utils::MATCH_FULL, and Utils::MATCH_REGEX.
     * Utils::MATCH_CONTAINS          Will match needles for entries that contain the specified needles anywhere.
     *                                Mutually exclusive with Utils::MATCH_STARTS_WITH, Utils::MATCH_ENDS_WITH,
     *                                Utils::MATCH_FULL, and Utils::MATCH_REGEX.
     * Utils::MATCH_RECURSE           Will recurse into arrays, if encountered.
     * Utils::MATCH_NOT               Will match needles for entries that do NOT match the needle.
     * Utils::MATCH_STRICT            Will match needles for entries that match the needle strict (so 0 does NOT match
     *                                "0", "" does NOT match 0, etc.).
     * Utils::MATCH_FULL              Will match needles for entries that fully match the needle. Mutually
     *                                exclusive with Utils::MATCH_STARTS_WITH, Utils::MATCH_ENDS_WITH,
     *                                Utils::MATCH_CONTAINS, and Utils::MATCH_REGEX.
     * Utils::MATCH_REGEX             Will match needles for entries that match the specified regular expression.
     *                                Mutually exclusive with Utils::MATCH_STARTS_WITH, Utils::MATCH_ENDS_WITH,
     *                                Utils::MATCH_CONTAINS, and Utils::MATCH_FULL.
     * Utils::MATCH_EMPTY             Will match empty values instead of ignoring them. NOTE: Empty values may be
     *                                ignored while NULL values are still matched using the MATCH_NULL flag
     * Utils::MATCH_NULL              Will match NULL values instead of ignoring them. NOTE: NULL values may be
     *                                ignored while non-NULL empty values are still matched using the MATCH_EMPTY flag
     * Utils::MATCH_REQUIRE           Requires at least one result
     * Utils::MATCH_SINGLE            Will match only a single entry for the executed action (return, remove, etc.)
     *
     * @return bool
     */
    public static function matches(int $action, Stringable|string $haystack, array|string $needles, int $flags = self::MATCH_CASE_INSENSITIVE | self::MATCH_ALL | self::MATCH_CONTAINS | self::MATCH_RECURSE): bool
    {
        // Caseless match? Compare lowercase
        $flags      = static::decodeMatchFlags($flags, false);
        $needles    = static::prepareNeedles($needles, $flags['no_case']);


        return static::matchValues(Utils::MATCH_ACTION_RETURN_VALUES, [$haystack], $needles, null, $flags);
    }
}
