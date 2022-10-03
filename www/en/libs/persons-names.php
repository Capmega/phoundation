<?php
/*
 * persons_nams library
 *
 * This library contains functions to manage persons names
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */


/*
 *
 */
function persons_names_get($gender, $name_count, $lastname_count) {
    try {
        $retval = '';

        switch (strtolower($gender)) {
            case 'man':
                // no-break
            case 'boy':
                // no-break
            case 'male':
                $column = 'male';
                break;

            case 'woman':
                // no-break
            case 'girl':
                // no-break
            case 'female':
                $column = 'female';
                break;

            default:
                throw new CoreException('persons_names_get(): Unknown gender "'.Strings::Log($gender).'" specified', 'unknown');
        }

        $names     = sql_list('SELECT `'.$column.'` FROM `persons_names` LIMIT '.$lastname_count, $column);
        $lastnames = sql_list('SELECT `'.$column.'` FROM `persons_names` LIMIT '.$lastname_count, $column);

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('persons_names_get(): Failed', $e);
    }
}
?>
