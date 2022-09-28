<?php
/*
 * Template library
 *
 * This is a library template file
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package template
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package template
 * @version 2.0.5: Added function and documentation
 *
 * @return void
 */
function template_library_init(){
    try{
        ensure_installed(array('name'      => 'template',
                               'callback'  => 'template_install',
                               'checks'    => ROOT.'libs/external/template/template,'.ROOT.'libs/external/template/foobar',
                               'functions' => 'template,foobar',
                               'which'     => 'template,foobar'));

    }catch(Exception $e){
        throw new CoreException('template_library_init(): Failed', $e);
    }
}



/*
 * Install the external template library
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @version 2.0.5: Added function and documentation
 * @package template
 *
 * @param
 * @return
 */
function template_install($params){
    try{
        load_libs('apt');
        apt_install('template');

        load_libs('apt');
        apt_install('template');

    }catch(Exception $e){
        throw new CoreException('template_install(): Failed', $e);
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
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package template
 * @see template_install()
 * @see date_convert() Used to convert the sitemap entry dates
 * @table: `template`
 * @note: This is a note
 * @version 2.0.5: Added function and documentation
 * @example [Title]
 * code
 * $result = template_function(array('foo' => 'bar'));
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
function template_function($params){
    try{

    }catch(Exception $e){
        throw new CoreException('template_function(): Failed', $e);
    }
}
?>
