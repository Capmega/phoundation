<?php
/*
 * Empty library
 *
 * This is an empty template library file
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package smart
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
 * @package smart
 *
 * @return void
 */
function smart_library_init(){
    try{
        ensure_installed(array('name'      => 'empty',
                               'project'   => 'emptyear',
                               'callback'  => 'empty_install',
                               'checks'    => array(ROOT.'libs/external/empty/')));

    }catch(Exception $e){
        throw new CoreException('smart_library_init(): Failed', $e);
    }
}



/*
 * Install the external empty library
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package smart
 *
 * @param
 * @return
 */
function smart_install(){
    try{
        load_libs('linux');
        linux_install_package(null, 'smartmontools');

    }catch(Exception $e){
        throw new CoreException('smart_install(): Failed', $e);
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
 * @package smart
 * @see empty_install()
 * @see date_convert() Used to convert the sitemap entry dates
 * @table: `empty`
 * @note: This is a note
 * @version 1.22.0: Added documentation
 * @example
 * code
 * $result = empty(array('foo' => 'bar'));
 * showdie($result);
 * /code
 *
 * This would return
 * code
 * Foo...bar
 * /code
 *
 * @param params $params A parameters array
 * @params string $params[foo]
 * @params string $params[bar]
 * @return string The result
 */
function empty_function($params){
    try{

    }catch(Exception $e){
        throw new CoreException('empty(): Failed', $e);
    }
}
?>
