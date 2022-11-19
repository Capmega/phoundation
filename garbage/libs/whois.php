<?php
/*
 * Whois library
 *
 * This library contains functions to execute whois functionalities
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */


/*
 * Empty function
 */
function whois($domain) {
    try {

    }catch(Exception $e) {
        throw new CoreException('whois(): Failed', $e);
    }
}
?>