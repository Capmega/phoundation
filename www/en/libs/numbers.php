<?php
/*
 * Numbers library
 *
 * This library contains various functions to manage numbers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 */



/*
 *
 */
function range_limit($number, $max, $min = null) {
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



 /*
 * Format integer into Kilo / Mega / Giga Byte
 */
function bytes($value, $unit = 'auto', $precision = 2) {
    return bytes_convert($value, $unit, $precision, true);
}



/*
 * Convert specified amount explicitly to specified multiplier
 */
function bytes_convert($amount, $unit = 'auto', $precision = 2, $add_suffix = false) {
    try{
        /*
         * Possibly shift parameters
         */
        if (is_bool($precision)) {
            $precision  = 0;
            $add_suffix = $precision;
        }

        if (!$amount) {
            $amount = 0;
        }

        $amount = str_replace(',', '', $amount);

        if (!is_numeric($amount)) {
            /*
             * Calculate back to bytes
             */
            if (!preg_match('/(\d+(?:\.\d+)?)(\w{1,3})/', $amount, $matches))  {
                throw new CoreException('bytes_convert(): Specified amount "'.$amount.'" is not a valid byte amount. Format should be either n, or nKB, nKiB, etc');
            }

            switch(strtolower($matches[2])) {
                case 'b':
                    /*
                     * Just bytes
                     */
                    $amount = $matches[1];
                    break;

                case 'kb':
                    /*
                     * Kilo bytes
                     */
                    $amount = $matches[1] * 1000;
                    break;

                case 'kib':
                    /*
                     * Kibi bytes
                     */
                    $amount = $matches[1] * 1024;
                    break;

                case 'mb':
                    /*
                     * Mega bytes
                     */
                    $amount = $matches[1] * 1000000;
                    break;

                case 'mib':
                    /*
                     * Mibi bytes
                     */
                    $amount = $matches[1] * 1048576;
                    break;

                case 'gb':
                    /*
                     * Giga bytes
                     */
                    $amount = $matches[1] * 1000000 * 1000;
                    break;

                case 'gib':
                    /*
                     * Gibi bytes
                     */
                    $amount = $matches[1] * 1048576 * 1024;
                    break;

                case 'tb':
                    /*
                     * Tera bytes
                     */
                    $amount = $matches[1] * 1000000 * 1000000;
                    break;

                case 'tib':
                    /*
                     * Tibi bytes
                     */
                    $amount = $matches[1] * 1048576 * 1048576;
                    break;

                default:
                    throw new CoreException('bytes_convert(): Specified suffix "'.$suffix.'" on amount "'.$amount.'" is not a valid. Should be one of b, or KB, KiB, mb, mib, etc');
            }
        }

        /*
         * We can only have an integer amount of bytes
         */
        $amount = ceil($amount);

        if (strtolower($unit) == 'auto') {
            /*
             * Auto determine what unit to use
             */
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

        /*
         * Convert to requested unit
         */
        switch(strtolower($unit)) {
            case 'b':
                /*
                 * Just bytes
                 */
                break;

            case 'kb':
                /*
                 * Kilo bytes
                 */
                $amount = $amount / 1000;
                break;

            case 'kib':
                /*
                 * Kibi bytes
                 */
                $amount = $amount / 1024;
                break;

            case 'mb':
                /*
                 * Mega bytes
                 */
                $amount = $amount / 1000000;
                break;

            case 'mib':
                /*
                 * Mibi bytes
                 */
                $amount = $amount / 1048576;
                break;

            case 'gb':
                /*
                 * Giga bytes
                 */
                $amount = $amount / 1000000 / 1000;
                break;

            case 'gib':
                /*
                 * Gibi bytes
                 */
                $amount = $amount / 1048576 / 1024;
                break;

            case 'tb':
                /*
                 * Tera bytes
                 */
                $amount = $amount / 1000000 / 1000000;
                break;

            case 'tib':
                /*
                 * Tibi bytes
                 */
                $amount = $amount / 1048576 / 1048576;
                break;

            default:
                throw new CoreException('bytes_convert(): Specified unit "'.$unit.'" is not a valid. Should be one of b, or KB, KiB, mb, mib, etc');
        }

        $amount = number_format(round($amount, $precision), $precision);

        if (!$add_suffix) {
            return $amount;
        }

        /*
         * Return amount with correct suffix.
         */
        switch(strlen($unit)) {
            case 1:
                return $amount.'b';

            case 2:
                return $amount.strtoupper($unit);

            case 3:
                return $amount.strtoupper($unit[0]).strtolower($unit[1]).strtoupper($unit[2]);
        }

    }catch(Exception $e) {
        throw new CoreException(tr('bytes_convert(): Failed'), $e);
    }
}



/*
 *
 */
function human_readable($number, $thousand = 1000, $decimals = 0) {
    try{
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

    }catch(Exception $e) {
        throw new CoreException('human_readable(): Failed', $e);
    }
}



/*
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
 * @param numeric One or multiple float numbers
 * @return string The step that can be used in the html <input type="number">
 */
function numbers_get_step() {
    try{
        /*
         * Remove the $count argument from the list
         * Get default value from the list
         */
        $args   = func_get_args();
        $retval = 0;

        foreach($args as $key => $value) {
            /*
             * Validate we have numeric values
             */
            if (!is_numeric($value)) {
                if (!is_scalar($value)) {
                    throw new CoreException(tr('numbers_get_step(): Variable ":key" is not a numeric scalar value, it is an ":type"', array(':key' => $key, ':type' => gettype($value))), 'invalid');
                }

                throw new CoreException(tr('numbers_get_step(): Variable ":key" has value ":value" which is not numeric', array(':key' => $key, ':value' => $value)), 'invalid');
            }

            /*
             * Cleanup the number
             */
            if ($value) {
                $value = str_replace(',', '.', $value);
                $value = number_format($value, 10, '.', '');
                $value = abs($value);
                $value = trim($value, '0');

            } else {
                $value = '0';
            }

            /*
             * Get the amount of decimals behind the .
             */
            $decimals = substr(strrchr($value, '.'), 1);
            $decimals = strlen($decimals);

            /*
             * Remember the highest amount of decimals
             */
            if ($decimals > $retval) {
                $retval = $decimals;
            }
        }

        /*
         * Return the found step
         */
        if ($retval) {
            return '0.'.str_repeat('0', $retval - 1).'1';
        }

        return '1';


    }catch(Exception $e) {
        throw new CoreException('numbers_get_step(): Failed', $e);
    }
}
?>
