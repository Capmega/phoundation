<?php

/**
 * Class Numbers
 *
 * This is the standard Phoundation string functionality extension class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category  Class reference
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils;

use Exception;
use Phoundation\Core\Exception\NumbersException;
use Phoundation\Core\Log\Log;
use Phoundation\Exception\OutOfBoundsException;


class Numbers
{
    /**
     * Ensure that the specified number is within the specified range
     *
     * @param string|float|int      $number
     * @param string|float|int      $max
     * @param string|float|int|null $min
     *
     * @return string|float|int
     */
    public static function limitRange(string|float|int $number, string|float|int $max, string|float|int|null $min = null): string|float|int
    {
        if (is_numeric($max)) {
            if ($number > $max) {
                return $max;
            }
        }

        if (is_numeric($min)) {
            if ($number < $min) {
                return $min;
            }
        }

        return $number;
    }


    /**
     * Returns human-readable AND precise bytes
     *
     * @param string|float|int $amount
     * @param string           $unit
     * @param int              $precision
     * @param bool             $add_suffix
     *
     * @return string
     * @throws OutOfBoundsException
     */
    public static function getHumanReadableAndPreciseBytes(string|float|int $amount, string $unit = 'AUTO', int $precision = 2, bool $add_suffix = true): string
    {
        return static::getHumanReadableBytes($amount, $unit, $precision, $add_suffix) . ' / ' . $amount . ' bytes';
    }


    /**
     * Convert specified amount explicitly to specified multiplier
     *
     * @param string|float|int $amount
     * @param string           $unit
     * @param int              $precision
     * @param bool             $add_suffix
     *
     * @return string
     * @throws OutOfBoundsException
     */
    public static function getHumanReadableBytes(string|float|int $amount, string $unit = 'auto', int $precision = 2, bool $add_suffix = true): string
    {
        // We can only have an integer number of bytes
        $amount = Numbers::fromBytes($amount);

        if ($unit === 'auto') {
            // Auto determine what unit to use in 10^N bytes
            if ($amount > 1_000_000) {
                if ($amount > (1_000_000 * 1_000)) {
                    if ($amount > (1_000_000 * 1_000_000)) {
                        $unit = 'tib';

                    } else {
                        $unit = 'gib';
                    }

                } else {
                    $unit = 'mib';
                }

            } elseif ($amount < 1_000) {
                if (!$amount) {
                    return '0b';
                }

                $precision = 0;
                $unit      = 'b';

            } else {
                $unit = 'kib';
            }

        } elseif ($unit === 'AUTO') {
            // Auto determine what unit to use in 2^N bytes
            if ($amount > 1_048_576) {
                if ($amount > (1_048_576 * 1_024)) {
                    if ($amount > (1_048_576 * 1_048_576)) {
                        $unit = 'tb';

                    } else {
                        $unit = 'gb';
                    }

                } else {
                    $unit = 'mb';
                }

            } elseif ($amount < 1_000) {
                if (!$amount) {
                    return '0b';
                }

                $precision = 0;
                $unit      = 'b';

            } else {
                $unit = 'kb';
            }
        }

        // Convert to requested unit
        switch (strtolower($unit)) {
            case 'b':
                // Just bytes
                $precision = 0;
                break;

            case 'kb':
                // Kilobytes
                $amount = $amount / 1_000;
                break;

            case 'kib':
                // Kibibytes
                $amount = $amount / 1_024;
                break;

            case 'mb':
                // Megabytes
                $amount = $amount / 1_000_000;
                break;

            case 'mib':
                // Mibibytes
                $amount = $amount / 1_048_576;
                break;

            case 'gb':
                // Gigabytes
                $amount = $amount / 1_000_000 / 1_000;
                break;

            case 'gib':
                // Gibibytes
                $amount = $amount / 1_048_576 / 1_024;
                break;

            case 'tb':
                // Terabytes
                $amount = $amount / 1_000_000 / 1_000_000;
                break;

            case 'tib':
                // Tibibytes
                $amount = $amount / 1_048_576 / 1_048_576;
                break;

            default:
                throw new OutOfBoundsException(tr('Specified unit ":unit" is not a valid. Should be one of b, or KB, KiB, mb, mib, etc', [
                    ':unit' => $unit,
                ]));
        }

        $amount = number_format(round($amount, $precision), $precision);

        if (!$add_suffix) {
            return $amount;
        }

        // Return amount with correct suffix.
        switch (strlen($unit)) {
            case 1:
                return $amount . 'b';

            case 2:
                return $amount . strtoupper($unit);

            case 3:
                return $amount . strtoupper($unit[0]) . strtolower($unit[1]) . strtoupper($unit[2]);
        }

        throw new OutOfBoundsException(tr('Unknown selected unit ":unit", ensure that only correct abbreviations like b, B, KB, KiB, GiB, etc are used', [
            ':unit' => $unit,
        ]));
    }


    /**
     * Reads a byte string like "4MB" and returns the number of bytes
     *
     * @param string|float|int $bytes
     *
     * @return int
     * @throws OutOfBoundsException
     */
    public static function fromBytes(string|float|int $bytes): int
    {
        if (!$bytes) {
            $amount = '0';

        } else {
            $amount = str_replace(',', '', (string) $bytes);
        }

        if (!is_numeric($amount)) {
            // Calculate back to bytes
            if (!preg_match('/(\d+(?:\.\d+)?)(\w{1,3})/', $amount, $matches)) {
                throw new NumbersException(tr('Specified amount ":amount" is not a valid byte amount. Format should be either n, or nKB, nKiB, etc', [
                    ':amount' => $amount,
                ]));
            }

            $amount = match (strtolower($matches[2])) {
                'b'        => (float) $matches[1],
                'kb'       => (float) $matches[1] * 1_000,
                'k', 'kib' => (float) $matches[1] * 1_024,
                'mb'       => (float) $matches[1] * 1_000_000,
                'm', 'mib' => (float) $matches[1] * 1_048_576,
                'gb'       => (float) $matches[1] * 1_000_000 * 1_000,
                'g', 'gib' => (float) $matches[1] * 1_048_576 * 1_024,
                'tb'       => (float) $matches[1] * 1_000_000 * 1_000_000,
                't', 'tib' => (float) $matches[1] * 1_048_576 * 1_048_576,
                default    => throw new OutOfBoundsException(tr('Specified suffix ":suffix" on amount ":amount" is not a valid. Should be one of b, or KB, KiB, mb, mib, etc', [
                    ':suffix' => strtolower($matches[2]),
                    ':amount' => $amount,
                ])),
            };
        }

        // We can only have an integer number of bytes
        $amount = (int) ceil((float) $amount);

        if ($amount < 0) {
            throw new OutOfBoundsException(tr('Specified number of bytes ":bytes" is negative, must be positive', [
                ':bytes' => $bytes
            ]));
        }

        return $amount;
    }


    /**
     * Return the "step" for use in HTML <input type="number"> tags from the specified list of numbers
     *
     * @return string The step that can be used in the html <input type="number">
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
         * @package   numbers
     * @version   2.2.7: Added function and documentation
     * @example
     *            code
     *            $result = numbers_get_step(1, 15, .1, 0.009);
     *            showdie($result);
     *            /code
     *
     * This would return
     * code
     * 0.001
     * /code
     *
     * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     */
    public static function getStep(): string
    {
        // Remove the $count argument from the list Get default value from the list
        $args   = func_get_args();
        $return = 0;

        foreach ($args as $key => $value) {
            // Validate we have numeric values
            if (!is_numeric($value)) {
                if (!is_scalar($value)) {
                    throw new NumbersException(tr('Variable ":key" is not a numeric scalar value, it is an ":type"', [
                        ':key'  => $key,
                        ':type' => gettype($value),
                    ]));
                }

                throw new NumbersException(tr('Variable ":key" has value ":value" which is not numeric', [
                    ':key'   => $key,
                    ':value' => $value,
                ]));
            }

            // Cleanup the number
            if ($value) {
                $value = str_replace(',', '.', $value);
                $value = abs($value);
                $value = number_format($value, 10, '.', '');
                $value = trim($value, '0');

            } else {
                $value = '0';
            }

            // Get the number of decimals behind the .
            $decimals = substr(strrchr($value, '.'), 1);
            $decimals = strlen($decimals);

            // Remember the highest number of decimals
            if ($decimals > $return) {
                $return = $decimals;
            }
        }

        // Return the found step
        if ($return) {
            return '0.' . str_repeat('0', $return - 1) . '1';
        }

        return '1';
    }


    /**
     * Returns a random float number between 0 and 1
     *
     * @param int       $min
     * @param int|float $max
     *
     * @return float
     */
    public static function getRandomFloat(int $min = 0, int $max = PHP_FLOAT_MAX): float
    {
        try {
            return random_int($min, $max) / $max;

        } catch (Exception $e) {
            // random_int() crashed for ... reasons? Fall back on mt_rand()
            Log::warning(tr('Failed to get result from random_int(), attempting mt_rand()'));
            Log::error($e);

            return mt_rand($min, $max) / $max;
        }
    }


    /**
     * Returns a random float number between $min and $max
     *
     * @param int $min
     * @param int $max
     *
     * @return int
     */
    public static function getRandomInt(int $min = 0, int $max = PHP_INT_MAX): int
    {
        try {
            return random_int($min, $max);

        } catch (Exception $e) {
            // random_int() crashed for ... reasons? Fall back on mt_rand()
            Log::warning(tr('Failed to get result from random_int(), continuing process normally but with mt_rand(). See exception below for more information.'));
            Log::error($e);

            return mt_rand($min, $max);
        }
    }


    /**
     * Returns the highest specified number
     *
     * @param float|int ...$numbers
     *
     * @return float|int
     */
    public static function getHighest(float|int ...$numbers): float|int
    {
        $highest = PHP_FLOAT_MIN;

        foreach ($numbers as $number) {
            if ($number > $highest) {
                $highest = $number;
            }
        }

        return $highest;
    }


    /**
     * Returns the lowest specified number
     *
     * @param float|int ...$numbers
     *
     * @return float|int
     */
    public static function getLowest(float|int ...$numbers): float|int
    {
        $lowest = PHP_FLOAT_MAX;

        foreach ($numbers as $number) {
            if ($number < $lowest) {
                $lowest = $number;
            }
        }

        return $lowest;
    }


    /**
     * Make the specified number human-readable
     *
     * @param string|float|int $number
     * @param int              $thousand
     * @param int              $decimals
     *
     * @return string
     */
    function humanReadable(string|float|int $number, int $thousand = 1000, int $decimals = 0): string
    {
        if ($number > pow($thousand, 5)) {
            return number_format($number / pow($thousand, 5), $decimals) . 'P';
        }

        if ($number > pow($thousand, 4)) {
            return number_format($number / pow($thousand, 4), $decimals) . 'T';
        }

        if ($number > pow($thousand, 3)) {
            return number_format($number / pow($thousand, 3), $decimals) . 'G';
        }

        if ($number > pow($thousand, 2)) {
            return number_format($number / pow($thousand, 2), $decimals) . 'M';
        }

        if ($number > pow($thousand, 1)) {
            return number_format($number / pow($thousand, 1), $decimals) . 'K';
        }

        return number_format($number, $decimals);
    }
}
