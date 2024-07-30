<?php

/**
 * Class DateFormats
 *
 * PHP / Javascript date format handling
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Date
 */

declare(strict_types=1);

namespace Phoundation\Date;

use MongoDB\Exception\UnsupportedException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\SessionConfig;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Exception\ConfigurationInvalidException;
use Phoundation\Utils\Strings;

class DateFormats
{
    /**
     * The default date formats
     *
     * @var array $defaults
     */
    protected static array $defaults = [
        'd-m-Y',
        'Y-m-d',
    ];


    /**
     * Returns the default Javascript date format string
     *
     * @return string
     */
    public static function getDefaultJavascript(): string
    {
        $default = static::getDefaultPhp();
        $default = static::convertPhpToJs($default);

        return $default;
    }


    /**
     * Returns the default PHP date format string
     *
     * @return string
     */
    public static function getDefaultPhp(): string
    {
        $default = static::getSupportedPhp()->getFirstValue();

        if (!is_string($default)) {
            throw new ConfigurationInvalidException(tr('The default configuration value ":value" for the path "locale.formats.date" must be a string', [
                ':value' => $default
            ]));
        }

        return $default;
    }


    /**
     * Returns the supported Javascript date format strings
     *
     * @return IteratorInterface
     */
    public static function getSupportedJavascript(): IteratorInterface
    {
        $supported = static::getSupportedPhp();

        foreach ($supported as &$date) {
            $date = DateFormats::convertPhpToJs($date);
        }

        return $supported;
    }


    /**
     * Returns the supported PHP date format strings
     *
     * @return IteratorInterface
     */
    public static function getSupportedPhp(): IteratorInterface
    {
        return SessionConfig::getIterator('locale.formats.date', static::$defaults);
    }


    /**
     * Returns the date time format from PHP to JS
     *
     * @param string $php_format
     *
     * @return string
     */
    public static function convertPhpToJs(string $php_format): string
    {
        Log::warning(tr('Converting PHP date formats to Javascript it still only partially supported, use with care and check the code in DateFormats->convertPhpToJs()!'));

        $js_format = $php_format;
        $lookup    = [
            'n'             => ['js' => 'M'],
            'n'             => [
                'js'       => 'Mo',
                'callback' => function (&$value) {
                    $value = $value . Strings::ordinalIndicator($value);
                },
            ],
            'm'             => ['js' => 'MM'],
            'M'             => ['js' => 'MMM'],
            'F'             => ['js' => 'MMMM'],
            null            => ['js' => 'Q'],
            null            => ['js' => 'Qo'],
            'j'             => ['js' => 'D'],
            'jS'            => ['js' => 'Do'],
            'd'             => ['js' => 'DD'],
            null            => ['js' => 'DDD'],
            null            => ['js' => 'DDDo'],
            null            => ['js' => 'DDDD'],
            null            => ['js' => 'd'],
            null            => ['js' => 'do'],
            null            => ['js' => 'dd'],
            null            => ['js' => 'ddd'],
            'l'             => ['js' => 'dddd'],
            null            => ['js' => 'e'],
            null            => ['js' => 'E'],
            null            => ['js' => 'w'],
            null            => ['js' => 'wo'],
            null            => ['js' => 'ww'],
            null            => ['js' => 'W'],
            null            => ['js' => 'Wo'],
            null            => ['js' => 'WW'],
            null            => ['js' => 'YY'],
            'Y'             => ['js' => 'YYYY'],
            null            => ['js' => 'YYYYYY'],
            null            => ['js' => 'Y'],
            null            => ['js' => 'y'],
            null            => ['js' => 'N'],
            null            => ['js' => 'NN'],
            null            => ['js' => 'NNN'],
            null            => ['js' => 'NNNN'],
            null            => ['js' => 'NNNNN'],
            null            => ['js' => 'gg'],
            null            => ['js' => 'gggg'],
            null            => ['js' => 'GG'],
            null            => ['js' => 'GGGG'],
            null            => ['js' => 'A'],
            null            => ['js' => 'a'],
            'G'             => ['js' => 'H'],
            'H'             => ['js' => 'HH'],
            'g'             => ['js' => 'h'],
            'h'             => ['js' => 'hh'],
            null            => ['js' => 'k'],
            null            => ['js' => 'kk'],
            null            => ['js' => 'm'],
            'i'             => ['js' => 'mm'],
            null            => ['js' => 's'],
            's'             => ['js' => 'ss'],
            null            => ['js' => 'S'],
            null            => ['js' => 'SS'],
            null            => ['js' => 'SSS'],
            null            => ['js' => 'SSSS'],
            null            => ['js' => 'SSSSS'],
            null            => ['js' => 'SSSSSS'],
            null            => ['js' => 'SSSSSSS'],
            null            => ['js' => 'SSSSSSSS'],
            null            => ['js' => 'SSSSSSSSS'],
            null            => ['js' => 'z'],
            null            => ['js' => 'zz'],
            null            => ['js' => 'Z'],
            null            => ['js' => 'ZZ'],
            null            => ['js' => 'X'],
            null            => ['js' => 'x'],
        ];

        // Get all javascript matches
        preg_match_all('/([a-z])+/i', $js_format, $matches);

        if (empty($matches)) {
            throw new OutOfBoundsException(tr('Failed to convert Javascript date time format string ":format" to PHP', [
                ':format' => $js_format,
            ]));
        }

        $matches = $matches[0];
        $matches = Arrays::sortByValueLength($matches);

        foreach ($matches as $match) {
            if (!array_key_exists($match, $lookup)) {
                throw new OutOfBoundsException(tr('Unknown Javascript date time format string identifier ":identifier" encountered in Javascript date time format string ":format"', [
                    ':identifier' => $match,
                    ':format'     => $js_format,
                ]));
            }

            if ($lookup[$match] === null) {
                throw new UnsupportedException(tr('Javascript date time format string identifier ":identifier" encountered in Javascript date time format string ":format" is currently not supported', [
                    ':identifier' => $match,
                    ':format'     => $js_format,
                ]));
            }

            $php_format = str_replace($match, $lookup[$match]['js'], $php_format);
        }

        return $php_format;
    }


    /**
     * Returns the date time format from JS to PHP
     *
     * @param string $js_format
     *
     * @return string
     * @throws OutOfBoundsException|UnsupportedException
     */
    public static function convertJsToPhp(string $js_format): string
    {
        Log::warning(tr('Converting Javascript date formats to PHP it still only partially supported, use with care and check the code in DateFormats->convertJsToPhp()!'));

        $php_format = $js_format;
        $lookup     = [
            'M'         => ['php' => 'n'],
            'Mo'        => [
                'php'      => 'n',
                'callback' => function (&$value) {
                    $value = $value . Strings::ordinalIndicator($value);
                },
            ],
            'MM'        => ['php' => 'm'],
            'MMM'       => ['php' => 'M'],
            'MMMM'      => ['php' => 'F'],
            'Q'         => null,
            'Qo'        => null,
            'D'         => ['php' => 'j'],
            'Do'        => ['php' => 'jS'],
            'DD'        => ['php' => 'd'],
            'DDD'       => null,
            'DDDo'      => null,
            'DDDD'      => null,
            'd'         => null,
            'do'        => null,
            'dd'        => null,
            'ddd'       => null,
            'dddd'      => ['php' => 'l'],
            'e'         => null,
            'E'         => null,
            'w'         => null,
            'wo'        => null,
            'ww'        => null,
            'W'         => null,
            'Wo'        => null,
            'WW'        => null,
            'YY'        => null,
            'YYYY'      => ['php' => 'Y'],
            'YYYYYY'    => null,
            'Y'         => null,
            'y'         => null,
            'N'         => null,
            'NN'        => null,
            'NNN'       => null,
            'NNNN'      => null,
            'NNNNN'     => null,
            'gg'        => null,
            'gggg'      => null,
            'GG'        => null,
            'GGGG'      => null,
            'A'         => null,
            'a'         => null,
            'H'         => ['php' => 'G'],
            'HH'        => ['php' => 'H'],
            'h'         => ['php' => 'g'],
            'hh'        => ['php' => 'h'],
            'k'         => null,
            'kk'        => null,
            'm'         => null,
            'mm'        => ['php' => 'i'],
            's'         => null,
            'ss'        => ['php' => 's'],
            'S'         => null,
            'SS'        => null,
            'SSS'       => null,
            'SSSS'      => null,
            'SSSSS'     => null,
            'SSSSSS'    => null,
            'SSSSSSS'   => null,
            'SSSSSSSS'  => null,
            'SSSSSSSSS' => null,
            'z'         => null,
            'zz'        => null,
            'Z'         => null,
            'ZZ'        => null,
            'X'         => null,
            'x'         => null,
        ];

        // Get all javascript matches
        preg_match_all('/([a-z])+/i', $js_format, $matches);

        if (empty($matches)) {
            throw new OutOfBoundsException(tr('Failed to convert Javascript date time format string ":format" to PHP', [
                ':format' => $js_format,
            ]));
        }

        $matches = $matches[0];
        $matches = Arrays::sortByValueLength($matches);

        foreach ($matches as $match) {
            if (!array_key_exists($match, $lookup)) {
                throw new OutOfBoundsException(tr('Unknown Javascript date time format string identifier ":identifier" encountered in Javascript date time format string ":format"', [
                    ':identifier' => $match,
                    ':format'     => $js_format,
                ]));
            }

            if ($lookup[$match] === null) {
                throw new UnsupportedException(tr('Javascript date time format string identifier ":identifier" encountered in Javascript date time format string ":format" is currently not supported', [
                    ':identifier' => $match,
                    ':format'     => $js_format,
                ]));
            }

            $php_format = str_replace($match, $lookup[$match]['php'], $php_format);
        }

        return $php_format;
    }


    /**
     * Ensures that the date only uses $replace as element separators, will replace " ", "-", "_", "/", "\"
     *
     * Note: $replace defaults t0 '-'
     *
     * @param string $date
     * @param string $date_replace
     * @param string $time_replace
     * @return string
     */
    public static function normalizeDate(string $date, string $date_replace = '-', string $time_replace = ':'): string
    {
        // Do we have a datetime or date? Try matching something like DD-MM-YYYY HH:MM:II (and maybe microseconds)
        if (preg_match_all('/^(\d+[^\d]\d+[^\d]\d+)[^\d](\d{2}[^\d]\d{2}[^\d]\d{2})([^\d]\d+)?/', $date, $matches)) {
            // This is a datetime
            return str_replace([' ', '-', '_', '/', '\\'], $date_replace, $matches[1][0]) . ' ' . str_replace([' ', '-', '_', '/', '\\'], $time_replace, $matches[2][0]) . $matches[3][0];
        }

        // Do we have a datetime or date? Try matching something like DD-MM-YYYY
        if (preg_match('/^(\d+[^\d]\d+[^\d]\d+)/', $date, $matches)) {
            // This is a date
            return str_replace([' ', '-', '_', '/', '\\'], $date_replace, $matches[1][0]);
        }

        // This is a human-readable date like "tomorrow", or "-3 days", do not touch!
        return $date;
    }
}