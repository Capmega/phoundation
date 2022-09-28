<?php
/*
 * Phones library
 *
 * This library contains various functions related to telephony
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <license@capmega.com>
 * @category Function reference
 * @package phones
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package phones
 *
 * @return void
 */
function phones_library_init(){
    try{

    }catch(Exception $e){
        throw new CoreException('phones_library_init(): Failed', $e);
    }
}



/*
 * Remove any formatting from the specified phone number, leaving only optionally a + and numbers
 *
 * This function will remove all formatting information like spaces, ( ) brackets, dashes, etc from the specified phone number, leaving only the numbers, optionally prefixed by a +
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package phones
 * @see phones_format_number()
 * @version 1.27.0: Added function and documentation
 * @example
 * code
 * $result = phones_clean_number('+13483829384'));
 * showdie($result);
 * /code
 *
 * This would return
 * code
 * +1 (348) 382 9384
 * /code
 *
 * @param string $number The phone number that must have its formatting stripped
 * @return string The phone number clean without formatting
 */
function phones_clean_number($number){
    try{
        $number = str_replace(array(' ', '-', '(', ')'), '', $number);
        return $number;

    }catch(Exception $e){
        throw new CoreException('phones_clean_number(): Failed', $e);
    }
}



/*
 * Format the specified phone number for displaying
 *
 * This function will format the specified phone number for displaying purposes. Spaces will be added to make the phone number more readable
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package phones
 * @see phones_clean_number()
 * @version 1.27.0: Added function and documentation
 * @example
 * code
 * $result = phones_format_number('+13483829384');
 * showdie($result);
 * /code
 *
 * This would return
 * code
 * +1 (348) 382 9384
 * /code
 *
 * @param string $number The phone number that must be formatted
 * @return string The formatted phone number
 */
function phones_format_number($number){
    try{
        /*
         * First ensure that this phone number does not have any formatting at all
         */
        $number = phones_clean_number($number);
        $plus   = (substr($number, 0, 1) == '+');

        if($plus){
            $number = substr($number, 1);
        }

        $digits = strlen($number);

        switch($digits){
            case 0:
                // FALLTHROUGH
            case 1:
                // FALLTHROUGH
            case 2:
                // FALLTHROUGH
            case 3:
                // FALLTHROUGH
            case 4:
                break;

            case 5:
                $number = substr($number, 0, 2).'-'.substr($number, 2, 3);
                break;

            case 6:
                $number = substr($number, 0, 3).'-'.substr($number, 3, 3);
                break;

            case 7:
                $number = substr($number, 0, 3).'-'.substr($number, 3, 4);
                break;

            case 8:
                $number = substr($number, 0, 4).'-'.substr($number, 4, 4);
                break;

            case 9:
                $number = '('.substr($number, 0, 2).') '.substr($number, 2, 3).'-'.substr($number, 5, 4);
                break;

            case 10:
                $number = '('.substr($number, 0, 3).') '.substr($number, 3, 3).'-'.substr($number, 6, 4);
                break;

            default:
                $number = substr($number, 0, strlen($number) - 10).' ('.substr($number, -10, 3).') '.substr($number, 7, 3).'-'.substr($number, -4, 4);
                break;
        }

        if($plus){
            $number = '+'.$number;
        }

        return $number;

    }catch(Exception $e){
        throw new CoreException('phones_format_number(): Failed', $e);
    }
}
?>
