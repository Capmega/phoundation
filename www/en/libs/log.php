<?php
/*
 * Log library
 *
 * This library contains functions to manage log files
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package log
 */



/*
 * Rotate the data/log log files
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package log
 * @version 2.4.13: Added function and documentation
 *
 * @return void()
 */
function log_rotate(){
    try{

    }catch(Exception $e){
        throw new BException('log_rotate(): Failed', $e);
    }
}
?>
