<?php
/*
 * systemd library
 *
 * This library contains functions to manage systemd
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package systemd
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
 * @package systemd
 * @version 2.0.5: Added function and documentation
 *
 * @return void
 */
function systemd_library_init(){
    try{
        ensure_installed(array('name'      => 'systemd',
                               'callback'  => 'systemd_install',
                               'checks'    => ROOT.'libs/external/systemd/systemd,'.ROOT.'libs/external/systemd/foobar',
                               'functions' => 'systemd,foobar',
                               'which'     => 'systemd,foobar'));

    }catch(Exception $e){
        throw new CoreException('systemd_library_init(): Failed', $e);
    }
}



/*
 * Install the external systemd library
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @version 2.0.5: Added function and documentation
 * @package systemd
 *
 * @param
 * @return
 */
function systemd_install($params){
    try{
        load_libs('apt');
        apt_install('systemd');

        load_libs('apt');
        apt_install('systemd');

    }catch(Exception $e){
        throw new CoreException('systemd_install(): Failed', $e);
    }
}



/*
 * SUB HEADER TEXT
 *
 * PARAGRAPH
 *
 * PARAGRAPH
 *
 * PARAGRAPH
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package systemd
 * @see systemd_install()
 * @see date_convert() Used to convert the sitemap entry dates
 * @table: `systemd`
 * @note: This is a note
 * @version 2.0.5: Added function and documentation
 * @example [Title]
 * code
 * $result = systemd_function(array('foo' => 'bar'));
 * showdie($result);
 * /code
 *
 * This would return
 * code
 * Foo...bar
 * /code
 *
 * @param params $params A parameters array
 * @param string $params[foo]
 * @param string $params[bar]
 * @return string The result
 */
function systemd_function($params){
    try{

    }catch(Exception $e){
        throw new CoreException('systemd_function(): Failed', $e);
    }
}
?>
