<?php
/*
 * Whois library
 *
 * This library contains functions to execute whois functionalities
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 */


/*
 * Empty function
 */
function whois($domain){
    try{

    }catch(Exception $e){
        throw new BException('whois(): Failed', $e);
    }
}
?>
