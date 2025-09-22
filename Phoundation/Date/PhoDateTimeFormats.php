<?php

/**
 * Class PhoDateFormats
 *
 * PHP / JavaScript date format handling
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Date
 * @see https://momentjs.com/docs/#/displaying/format/ for JavaScript date/time formatting options
 * @see https://www.php.net/manual/en/datetime.format.php for PHP date/time formatting options
 */


declare(strict_types=1);

namespace Phoundation\Date;

use Phoundation\Accounts\Config\Exception\ConfigurationInvalidException;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Date\Enums\EnumDateTimeWidth;
use Phoundation\Date\Exception\UnsupportedDateFormatException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnsupportedException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Stringable;


class PhoDateTimeFormats
{
    /**
     * The default date formats
     *
     * @var array $defaults
     *
     * @todo Make these defaults configurable, requires object to be non static
     */
    protected static array $defaults = [
        'date'     => 'Y / m / d',
        'time'     => 'H : i : s',
        'datetime' => 'Y / m / d>>DATETIMESEPARATOR<<H : i : s',
    ];


    /**
     * Returns the default PHP date format string
     *
     * @param EnumDateTimeWidth $width
     *
     * @return string
     */
    public static function getDefaultDateFormatPhp(EnumDateTimeWidth $width = EnumDateTimeWidth::wide): string
    {
        return static::getSupportedPhp($width)->get('date');
    }


    /**
     * Returns the default JavaScript date format string
     *
     * @param bool              $lowercase
     * @param EnumDateTimeWidth $width
     *
     * @return string
     */
    public static function getDefaultDateFormatJavaScript(bool $lowercase = false, EnumDateTimeWidth $width = EnumDateTimeWidth::wide): string
    {
        $default = static::getDefaultDateFormatPhp($width);
        $default = static::convertPhpToJs($default);

        if ($lowercase) {
            return strtolower($default);
        }

        return $default;
    }


    /**
     * Returns the default PHP time format string
     *
     * @param EnumDateTimeWidth $width
     *
     * @return string
     */
    public static function getDefaultTimeFormatPhp(EnumDateTimeWidth $width = EnumDateTimeWidth::wide): string
    {
        return static::getSupportedPhp($width)->get('time');
    }


    /**
     * Returns the default JavaScript time format string
     *
     * @param bool              $lowercase
     * @param EnumDateTimeWidth $width
     *
     * @return string
     */
    public static function getDefaultTimeFormatJavaScript(bool $lowercase = false, EnumDateTimeWidth $width = EnumDateTimeWidth::wide): string
    {
        $default = static::getDefaultTimeFormatPhp($width);
        $default = static::convertPhpToJs($default);

        if ($lowercase) {
            return strtolower($default);
        }

        return $default;
    }


    /**
     * Returns the default PHP date format string
     *
     * @param EnumDateTimeWidth $width
     *
     * @return string
     */
    public static function getDefaultDateTimeFormatPhp(EnumDateTimeWidth $width = EnumDateTimeWidth::wide): string
    {
        return static::getSupportedPhp()->get('datetime');
    }


    /**
     * Returns the default JavaScript date format string
     *
     * @param bool              $lowercase
     * @param EnumDateTimeWidth $width
     *
     * @return string
     */
    public static function getDefaultDateTimeFormatJavaScript(bool $lowercase = false, EnumDateTimeWidth $width = EnumDateTimeWidth::wide): string
    {
        $default = static::getDefaultDateTimeFormatPhp($width);
        $default = static::convertPhpToJs($default);

        if ($lowercase) {
            return strtolower($default);
        }

        return $default;
    }


    /**
     * Returns the supported JavaScript date format strings
     *
     * @note These supported formats come from PhoDateTimeFormats::
     *
     * @note These
     *
     * @note This method will cache both compact and full format lists internally
     *
     * @param EnumDateTimeWidth $width
     *
     * @return IteratorInterface
     */
    public static function getSupportedJavaScript(EnumDateTimeWidth $width = EnumDateTimeWidth::default): IteratorInterface
    {
        static $return_wide, $return_normal, $return_compact;

        switch (static::resolveDefaultWidth($width)) {
            case EnumDateTimeWidth::wide:
                if (empty($return_wide)) {
                    $return_wide = static::getSupportedPhp($width);

                    foreach ($return_wide as &$date) {
                        $date = PhoDateTimeFormats::convertPhpToJs($date);
                    }

                    $return_wide = static::getCleanDates($width);
                }

                return $return_wide;

            case EnumDateTimeWidth::compact:
                if (empty($return_compact)) {
                    $return_compact = static::getSupportedPhp($width);

                    foreach ($return_compact as &$date) {
                        $date = PhoDateTimeFormats::convertPhpToJs($date);
                    }

                    $return_compact = static::getCleanDates($width);
                }

                return $return_compact;

            case EnumDateTimeWidth::normal:
                // no break

            default:
                if (empty($return_normal)) {
                    $return_normal = static::getSupportedPhp($width);

                    foreach ($return_normal as &$date) {
                        $date = PhoDateTimeFormats::convertPhpToJs($date);
                    }

                    $return_normal = static::getCleanDates($width);
                }

                return $return_normal;
        }
    }


    /**
     * Returns the supported PHP date format strings
     *
     * @note These supported formats come from configuration path "locale.formats.date" and default to the list specified in PhoDateTimeFormats::$defaults
     *
     * @note This method will cache both compact and full format lists internally
     *
     * @param EnumDateTimeWidth $width
     *
     * @return IteratorInterface
     */
    public static function getSupportedPhp(EnumDateTimeWidth $width = EnumDateTimeWidth::default): IteratorInterface
    {
        static $return_wide, $return_normal, $return_compact;

        switch (static::resolveDefaultWidth($width)) {
            case EnumDateTimeWidth::wide:
                if (empty($return_wide)) {
                    $return_wide = static::getCleanDates($width);
                }

                return $return_wide;

            case EnumDateTimeWidth::compact:
                if (empty($return_compact)) {
                    $return_compact = static::getCleanDates($width);
                }

                return $return_compact;

            case EnumDateTimeWidth::normal:
                // no break;

            default:
                if (empty($return_normal)) {
                    $return_normal = static::getCleanDates($width);
                }

                return $return_normal;
        }
    }


    /**
     * Returns a list of clean dates
     *
     * @param EnumDateTimeWidth $width
     *
     * @return IteratorInterface
     */
    protected static function getCleanDates(EnumDateTimeWidth $width): IteratorInterface
    {
        $return = config()->getArray('locale.formats.date', static::$defaults);

        foreach ($return as &$date) {
            $date = static::cleanDateFormat($date, $width);
        }

        return new Iterator($return);
    }


    /**
     * Checks and formats the given date
     *
     * @note Will throw a ConfigurationInvalidException exception if the specified date is not a string
     *
     * @param string            $date
     * @param EnumDateTimeWidth $width
     *
     * @return string
     */
    public static function cleanDateFormat(string $date, EnumDateTimeWidth $width): string
    {
        switch (static::resolveDefaultWidth($width)) {
            case EnumDateTimeWidth::normal:
                $date = str_replace(' ', '', $date);
                // no break

            case EnumDateTimeWidth::wide:
                $date = str_replace('>>DATETIMESEPARATOR<<', PhoDateTimeFormats::getConfiguredSeparator(), $date);
                break;

            case EnumDateTimeWidth::compact:
                $date = str_replace(' ', '', $date);
                $date = str_replace('>>DATETIMESEPARATOR<<', PhoDateTimeFormats::getConfiguredSeparator(), $date);
                $date = str_replace([' ', '/', '\\', ':', '-', '_'], '', $date);
                break;
        }

        return $date;
    }


    /**
     * Returns the correct default width for EnumDateTimeWidth::default
     *
     * @param EnumDateTimeWidth|null $width
     *
     * @return EnumDateTimeWidth
     */
    public static function resolveDefaultWidth(?EnumDateTimeWidth $width = null): EnumDateTimeWidth
    {
        if ($width === EnumDateTimeWidth::default) {
            return PhoDateTimeFormats::getConfiguredWidth();
        }

        return $width;
    }


    /**
     * Returns the configured default separator
     *
     * @param string $default
     *
     * @return string
     */
    public static function getConfiguredSeparator(string $default = ' '): string
    {
        static $return;

        if (!isset($return)) {
            $return = config()->getString('locale.dates.formats.separator', $default);
        }

        return $return;
    }


    /**
     * Returns the configured default width for EnumDateTimeWidth::default
     *
     * @param string $default
     *
     * @return EnumDateTimeWidth
     */
    public static function getConfiguredWidth(string $default = EnumDateTimeWidth::normal->value): EnumDateTimeWidth
    {
        static $return;

        if (!isset($return)) {
            $return = EnumDateTimeWidth::from(config()->getString('locale.dates.formats.width', $default));
        }

        return $return;
    }


    /**
     * Returns the date time format from PHP to JS
     *
     * @param string $php_format
     *
     * @return string
     *
     * @todo This conversion method is incomplete! Complete it when possible
     *
     * @see https://www.php.net/manual/en/datetime.format.php for PHP date/time formatting options
     * @see https://blog.stevenlevithan.com/archives/javascript-date-format
     * @see https://momentjs.com/docs/#/displaying/format/ for JavaScript date/time formatting options
     */
    public static function convertPhpToJs(string $php_format): string
    {
        Log::warning(ts('Converting PHP date formats to Javascript it still only partially supported, use with care and check the code in DateFormats->convertPhpToJs()!'));

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
     * @see https://momentjs.com/docs/#/displaying/format/ for JavaScript date/time formatting options
     * @todo This conversion method is incomplete! Complete it when possible
     */
    public static function convertJsToPhp(string $js_format): string
    {
        Log::warning(ts('Converting Javascript date formats to PHP it still only partially supported, use with care and check the code in DateFormats->convertJsToPhp()!'));

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
     * Returns the date time format from JS to JS DatePicker Moment library format
     *
     * @param string $js_format
     *
     * @return string
     * @throws OutOfBoundsException|UnsupportedException
     * @todo This conversion method is incomplete! Complete it when possible
     */
    public static function convertJsToMoment(string $js_format): string
    {
        $out_format = $js_format;
        $lookup     = [
            'M'         => ['out' => 'M'],
            'MM'        => ['out' => 'MM'],
            'MMM'       => ['out' => 'MMM'],
            'MMMM'      => ['out' => 'MMMM'],
            'D'         => ['out' => 'D'],
            'DD'        => ['out' => 'DD'],
            'DDD'       => ['out' => 'ddd'],
            'DDDD'      => ['out' => 'dddd'],
            'YY'        => ['out' => 'yy'],
            'YYYY'      => ['out' => 'yyyy'],
            'H'         => ['out' => 'H'],
            'HH'        => ['out' => 'HH'],
            'h'         => ['out' => 'h'],
            'hh'        => ['out' => 'hh'],
            'm'         => ['out' => 'm'],
            'mm'        => ['out' => 'mm'],
            's'         => ['out' => 's'],
            'ss'        => ['out' => 'ss'],
            'T'         => ['out' => 'T'],
        ];

        // Get all javascript matches
        preg_match_all('/([a-z])+/i', $js_format, $matches);

        if (empty($matches)) {
            throw new OutOfBoundsException(tr('Failed to convert JavaScript date time format string ":format" to JavaScript DatePicker Moment format', [
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

            $out_format = str_replace($match, $lookup[$match]['out'], $out_format);
        }

        return $out_format;
    }


    /**
     * Ensures that the date only uses $replace as element separators, will replace " ", "-", "_", "/", "\"
     *
     * @param Stringable|string $date                  The date or datetime string to process
     * @param string            $date_separator        The character to use between date component sections
     * @param string            $time_separator        The character to use between time component sections
     * @param string            $datetime_separator    The character to use between a date component and a time component
     * @param string            $microsecond_separator The character to use between a time component and a micro-seconds component
     *
     * @return string
     */
    public static function normalizeDate(Stringable|string $date, string $date_separator = '-', string $time_separator = ':', string $datetime_separator = ' ', string $microsecond_separator = '.'): string
    {
        $date = trim((string) $date);

        // Do we have a datetime or date? Try matching something like DD-MM-YYYY HH:MM:II (and maybe microseconds)
        if (preg_match_all('/^(\d{1,4})\D+(\d{1,2})\D+(\d{1,4})\D+(\d{1,2})\D+(\d{1,2})(?:\D+(\d{1,2})(?:\D+(\d{1,6}))?)?$/', $date, $matches)) {
            if (array_get_safe(array_get_safe($matches, 7), 0)) {
                $microseconds = $microsecond_separator . $matches[7][0];
            }

            if (is_empty(array_get_safe(array_get_safe($matches, 6), 0))) {
                // Seconds was not specified, presume :00
                $matches[6] = [0 => '00'];
            }

            // This is a datetime
            return static::normalizeNumber($matches[1][0]) . $date_separator .
                   static::normalizeNumber($matches[2][0]) . $date_separator .
                   static::normalizeNumber($matches[3][0]) . $datetime_separator .
                   static::normalizeNumber($matches[4][0]) . $time_separator .
                   static::normalizeNumber($matches[5][0]) . $time_separator .
                   static::normalizeNumber($matches[6][0]) . isset_get($microseconds);
        }

        // Do we have a datetime or date? Try matching something like DD-MM-YYYY
        if (preg_match('/^(\d+)\D+(\d+)\D+(\d+)$/', $date, $matches)) {
            // This is a date
            return static::normalizeNumber($matches[1]) . $date_separator .
                   static::normalizeNumber($matches[2]) . $date_separator .
                   static::normalizeNumber($matches[3]);
        }

        // This is a human-readable date like "tomorrow", or "-3 days", don't touch!
        return $date;
    }


    /**
     * Normalizes the given date number by ensuring it will be either two or four digits
     *
     * @param string $number
     *
     * @return string
     */
    public static function normalizeNumber(string $number): string
    {
        switch (strlen($number)) {
            case 2:
                // no break

            case 4:
                return $number;

            case 1:
                // no break

            case 3:
                return '0' . $number;
        }

        throw new OutOfBoundsException(tr('Cannot normalize number ":number", it must be 1, 2, 3, or 4 digits long', [
            ':number' => $number
        ]));
    }


    /**
     * Ensures that the date format only uses $replace as element separators, will replace " ", "-", "_", "/", "\"
     *
     * @param Stringable|string $format              The date-format or datetime-format string to process
     * @param string            $date_replace        The character to use between date component sections
     * @param string            $time_replace        The character to use between time component sections
     * @param string            $date_time_replace   The character to use between a date component and a time component
     * @param string            $microsecond_replace The character to use between a time component and a microseconds
     *                                               component
     *
     *
     * @return string
     */
    public static function normalizeDateFormat(Stringable|string $format, string $date_replace = '-', string $time_replace = ':', string $date_time_replace = ' ', string $microsecond_replace = '.'): string
    {
        $format = trim((string) $format);
        $format = str_replace('>>DATETIMESEPARATOR<<', PhoDateTimeFormats::getConfiguredSeparator(), $format);

        // Do we have a datetime or date? Try matching something like DD-MM-YYYY HH:MM:II (and maybe microseconds)
        if (preg_match_all('/^([a-z]+)[^a-z]+([a-z]+)[^a-z]+([a-z]+)[^a-z]+([a-z]+)[^a-z]+([a-z]+)[^a-z]+([a-z]+)(?:[^a-z]+([a-z]+))?$/i', $format, $matches)) {
            if (array_get_safe(array_get_safe($matches, 7), 0)) {
                $microseconds = $microsecond_replace . $matches[7][0];
            }

            // This is a datetime
            return $matches[1][0] . $date_replace .
                   $matches[2][0] . $date_replace .
                   $matches[3][0] . $date_time_replace .
                   $matches[4][0] . $time_replace .
                   $matches[5][0] . $time_replace .
                   $matches[6][0] . isset_get($microseconds);
        }

        // Do we have a datetime or date? Try matching something like DD-MM-YYYY
        if (preg_match('/^([a-z]+)[^a-z]+([a-z]+)[^a-z]+([a-z]+)$/i', $format, $matches)) {
            // This is a date
            return $matches[1] . $date_replace .
                   $matches[2] . $date_replace .
                   $matches[3];
        }

        throw new UnsupportedDateFormatException(tr('Unsupported date or datetime format ":format" specified', [
            ':format' => $format
        ]));
    }


    /**
     * Returns either 12 or 24 depending on what the system or user configured
     *
     * @return int
     */
    public static function getUser1224(): int
    {
        $format = config()->getInteger('formats.date.force1224', 24, true);

        switch ($format) {
            case '12':
                // no break

            case '24':
                break;

            default:
                throw new OutOfBoundsException(tr('Invalid user 12/24 hour configuration ":format" encountered, it should be either 12 or 24', [
                    ':format' => $format,
                ]));
        }

        return $format;
    }
}
