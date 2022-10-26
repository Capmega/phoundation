<?php

namespace Phoundation\Core;

use Phoundation\Core\Exception\CoreException;
use Phoundation\Exception\OutOfBoundsException;



/**
 * Class Numbers
 *
 * This is the standard Phoundation string functionality extension class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Class reference
 * @package Core
 */
class Numbers
{
    /**
     * Ensure that the specified number is within the specified range
     *
     * @param string|float|int $number
     * @param string|float|int $max
     * @param string|float|int|null $min
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
     * Convert specified amount explicitly to specified multiplier
     *
     * @param string|float|int $amount
     * @param string $unit
     * @param int $precision
     * @param bool $add_suffix
     * @return string
     * @throws OutOfBoundsException
     */
    public static function bytes(string|float|int $amount, string $unit = 'AUTO', int $precision = 2, bool $add_suffix = true): string
    {
        if (!$amount) {
            $amount = 0;
        }

        $amount = str_replace(',', '', $amount);

        if (!is_numeric($amount)) {
            // Calculate back to bytes
            if (!preg_match('/(\d+(?:\.\d+)?)(\w{1,3})/', $amount, $matches))  {
                throw new CoreException(tr('Specified amount ":amount" is not a valid byte amount. Format should be either n, or nKB, nKiB, etc', [':amount' => $amount]));
            }

            switch (strtolower($matches[2])) {
                case 'b':
                    // Just bytes
                    $amount = $matches[1];
                    break;

                case 'kb':
                    // Kilobytes
                    $amount = $matches[1] * 1000;
                    break;

                case 'kib':
                    // Kibibytes
                    $amount = $matches[1] * 1024;
                    break;

                case 'mb':
                    // Megabytes
                    $amount = $matches[1] * 1000000;
                    break;

                case 'mib':
                    // Mibibytes
                    $amount = $matches[1] * 1048576;
                    break;

                case 'gb':
                    // Gigabytes
                    $amount = $matches[1] * 1000000 * 1000;
                    break;

                case 'gib':
                    // Gibibytes
                    $amount = $matches[1] * 1048576 * 1024;
                    break;

                case 'tb':
                    // Terabytes
                    $amount = $matches[1] * 1000000 * 1000000;
                    break;

                case 'tib':
                    // Tibibytes
                    $amount = $matches[1] * 1048576 * 1048576;
                    break;

                default:
                    throw new OutOfBoundsException(tr('Specified suffix ":suffix" on amount ":amount" is not a valid. Should be one of b, or KB, KiB, mb, mib, etc', [':suffix' => strtolower($matches[2]), ':amount' => $amount]));
            }
        }

        // We can only have an integer amount of bytes
        $amount = ceil($amount);

        if ($unit === 'auto') {
            // Auto determine what unit to use
            if ($amount > 1000000) {
                if ($amount > (1000000 * 1000)) {
                    if ($amount > (1000000 * 1000000)) {
                        $unit = 'tib';

                    } else {
                        $unit = 'gib';
                    }

                } else {
                    $unit = 'mib';
                }

            } else {
                $unit = 'kib';
            }
        } elseif ($unit === 'AUTO') {
            // Auto determine what unit to use
            if ($amount > 1048576) {
                if ($amount > (1048576 * 1024)) {
                    if ($amount > (1048576 * 1048576)) {
                        $unit = 'tb';

                    } else {
                        $unit = 'gb';
                    }

                } else {
                    $unit = 'mb';
                }

            } else {
                $unit = 'kb';
            }
        }

        // Convert to requested unit
        switch (strtolower($unit)) {
            case 'b':
                // Just bytes
                break;

            case 'kb':
                // Kilobytes
                $amount = $amount / 1000;
                break;

            case 'kib':
                // Kibibytes
                $amount = $amount / 1024;
                break;

            case 'mb':
                // Megabytes
                $amount = $amount / 1000000;
                break;

            case 'mib':
                // Mibibytes
                $amount = $amount / 1048576;
                break;

            case 'gb':
                // Gigabytes
                $amount = $amount / 1000000 / 1000;
                break;

            case 'gib':
                // Gibibytes
                $amount = $amount / 1048576 / 1024;
                break;

            case 'tb':
                // Terabytes
                $amount = $amount / 1000000 / 1000000;
                break;

            case 'tib':
                // Tibibytes
                $amount = $amount / 1048576 / 1048576;
                break;

            default:
                throw new OutOfBoundsException(tr('Specified unit ":unit" is not a valid. Should be one of b, or KB, KiB, mb, mib, etc', [':unit' => $unit]));
        }

        $amount = number_format(round($amount, $precision), $precision);

        if (!$add_suffix) {
            return $amount;
        }

        // Return amount with correct suffix.
        switch (strlen($unit)) {
            case 1:
                return $amount.'b';

            case 2:
                return $amount . strtoupper($unit);

            case 3:
                return $amount . strtoupper($unit[0]) . strtolower($unit[1]) . strtoupper($unit[2]);
        }

        throw new OutOfBoundsException(tr('Unknown selected unit ":unit", ensure that only correct abbreviations like b, B, KB, KiB, GiB, etc are used', [':unit' => $unit]));
    }



    /**
     * Make the specified number humand readable
     *
     * @param string|float|int $number
     * @param int $thousand
     * @param int $decimals
     * @return string
     */
    function humanReadable(string|float|int $number, int $thousand = 1000, int $decimals = 0): string
    {
        if ($number > pow($thousand, 5)) {
            return number_format($number / pow($thousand, 5), $decimals).'P';
        }

        if ($number > pow($thousand, 4)) {
            return number_format($number / pow($thousand, 4), $decimals).'T';
        }

        if ($number > pow($thousand, 3)) {
            return number_format($number / pow($thousand, 3), $decimals).'G';
        }

        if ($number > pow($thousand, 2)) {
            return number_format($number / pow($thousand, 2), $decimals).'M';
        }

        if ($number > pow($thousand, 1)) {
            return number_format($number / pow($thousand, 1), $decimals).'K';
        }

        return number_format($number, $decimals);
    }



    /**
     * Return the "step" for use in HTML <input type="number"> tags from the specified list of numbers
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package numbers
     * @version 2.2.7: Added function and documentation
     * @example
     * code
     * $result = numbers_get_step(1, 15, .1, 0.009);
     * showdie($result);
     * /code
     *
     * This would return
     * code
     * 0.001
     * /code
     *
     * @return string The step that can be used in the html <input type="number">
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
                    throw new CoreException(tr('Variable ":key" is not a numeric scalar value, it is an ":type"', [':key' => $key, ':type' => gettype($value)]));
                }

                throw new CoreException(tr('Variable ":key" has value ":value" which is not numeric', [':key' => $key, ':value' => $value]));
            }

            // Cleanup the number
            if ($value) {
                $value = str_replace(',', '.', $value);
                $value = number_format($value, 10, '.', '');
                $value = abs($value);
                $value = trim($value, '0');

            } else {
                $value = '0';
            }

            // Get the amount of decimals behind the .
            $decimals = substr(strrchr($value, '.'), 1);
            $decimals = strlen($decimals);

            // Remember the highest amount of decimals
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
}