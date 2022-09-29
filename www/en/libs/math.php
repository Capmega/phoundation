<?php
/*
 * Math library
 *
 * This library contains some basic math functions that are missing from PHP
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */



/*
 * Returns the average value of all specified numbers
 *
 * NOTE: Any non string values will be skipped
 *
 * Example:
 * array_pluck(array('foo', 'bar', 'Frack!', 'test'), '/^F/i');
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package array
 *
 * @param array $source The array to check
 * @return boolean Returns true if the specified array contains duplicate values, false otherwise
 */
function math_average(...$items) {
    try{
        if (count($items)) {
            return array_sum($items) / count($items);
        }

        return 0;

    }catch(Exception $e) {
        throw new CoreException('math_average(): Failed', $e);
    }
}
?>
