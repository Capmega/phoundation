<?php

/**
 * Class Json
 *
 * This class contains various JSON methods and can reply with JSON data structures to the client
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Exception\JsonException;


class Json
{
    /**
     * Encode the specified variable into a JSON string
     *
     * @param mixed $source
     * @param int   $options
     * @param int   $depth Until what depth will we recurse until an exception will be thrown
     *
     * @return string
     * @throws JsonException If JSON encoding failed
     */
    public static function encode(mixed $source, int $options = JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING, int $depth = 512): string
    {
        if ($source === null) {
            return '';
        }

        $return = json_encode($source, $options, $depth);

        if (json_last_error()) {
            throw new JsonException(tr('JSON encoding failed with :error', [':error' => json_last_error_msg()]));
        }

        return $return;
    }


    /**
     * Ensure that the specified source is encoded into a JSON string
     *
     * This method will assume that given strings are encoded JSON. Anything else will be encoded into a JSON string
     *
     * @param mixed $source
     * @param int   $options
     * @param int   $depth Until what depth will we recurse until an exception will be thrown
     *
     * @return string
     * @throws JsonException If JSON encoding failed
     */
    public static function ensureEncoded(mixed $source, int $options = 0, int $depth = 512): string
    {
        if (is_string($source)) {
            // Assume this is a JSON string
            return $source;
        }

        return static::encode($source, $options, $depth);
    }


    /**
     * Ensure the given variable is decoded.
     *
     * If it is a JSON string it will be decoded back into the original data. If it is not a string, this method will
     * assume it already was decoded. Can optionally decode into standard object classes or arrays [default]
     *
     * @param mixed $source
     * @param int   $options
     * @param int   $depth    Until what depth will we recurse until an exception will be thrown
     * @param bool  $as_array If $as_array is set true [default] then this method will always return an array. If not,
     *                        it will return a PHP JSON object
     *
     * @return mixed The decoded variable
     * @throws JsonException
     */
    public static function ensureDecoded(mixed $source, int $options = 0, int $depth = 512, bool $as_array = true): mixed
    {
        if ($source and is_string($source)) {
            return static::decode($source, $options, $depth, $as_array);
        }

        return $source;
    }


    /**
     * Decode the given JSON string back into the original data. Can optionally decode into standard object classes or
     * arrays [default]
     *
     * @param mixed $source
     * @param int   $options
     * @param int   $depth    Until what depth will we recurse until an exception will be thrown
     * @param bool  $as_array If $as_array is set true [default] then this method will always return an array. If not,
     *                        it will return a PHP JSON object
     *
     * @return mixed The decoded variable
     * @throws JsonException
     */
    public static function decode(?string $source, int $options = 0, int $depth = 512, bool $as_array = true): mixed
    {
        if ($source === null) {
            return null;
        }

        $return = json_decode($source, $as_array, $depth, $options);

        if (json_last_error()) {
            throw new JsonException(tr('JSON decoding failed with ":error"', [':error' => json_last_error_msg()]));
        }

        return $return;
    }


    /**
     * Returns the specified source, but limiting its maximum size to the specified $max_size.
     *
     * If it crosses this threshold, it will truncate entries in the $source array
     *
     * @param array|string $source
     * @param int          $max_size
     * @param string       $fill
     * @param string       $method
     * @param bool         $on_word
     * @param int          $options
     * @param int          $depth
     *
     * @return string
     */
    public static function encodeTruncateToMaxSize(array|string $source, int $max_size, string $fill = ' ... [TRUNCATED] ... ', string $method = 'right', bool $on_word = false, int $options = 0, int $depth = 512): string
    {
        if (is_string($source)) {
            if (strlen($source) <= $max_size) {
                // We're already done, no need for more!
                return $source;
            }

            $string = $source;
            $array  = static::decode($source, $options, $depth);

        } else {
            $array  = $source;
            $string = static::encode($source, $options, $depth);
        }

        if ($max_size < 64) {
            throw new OutOfBoundsException(tr('Cannot truncate JSON string to ":size" characters, the minimum is 64 characters', [
                ':size' => $max_size,
            ]));
        }

        while (strlen($string) > $max_size) {
            // Okay, we're over max size
            $keys    = count($source);
            $average = floor((strlen($string) / $keys) - ($keys * 8));

            if ($average < 1) {
                $average = 10;
            }

            // Truncate and re-encode the truncated array and check size again
            $array  = Arrays::truncate($array, $average, $fill, $method, $on_word);
            $string = Json::encode($array);
        }

        return $string;
    }
}
