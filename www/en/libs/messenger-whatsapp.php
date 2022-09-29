<?php
/*
 * Whatsapp library
 *
 * This library contains  Whatsapp API functions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package whatsapp
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
 * @package
 *
 * @return void
 */
function whatsapp_library_init(){
    try{
        ensure_installed(array('name'     => 'whatsapp',
                               'callback' => 'whatsapp_install',
                               'checks'   => array(ROOT.'libs/external/whatsapp/')));

    }catch(Exception $e){
        throw new CoreException('whatsapp_library_init(): Failed', $e);
    }
}



?>
