<?php
/*
 * OX library
 *
 * This library is a interface library with Open Exchange
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function ox_library_init(){
    try{

    }catch(Exception $e){
        throw new CoreException('ox_library_init(): Failed', $e);
    }
}
?>
