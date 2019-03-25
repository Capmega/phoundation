<?php
/*
 * Wget library
 *
 * This library us a front-end to the wget function
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package wget
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package wget
 * @version 2.4.11: Added function and documentation
 *
 * @return void
 */
function wget_library_init(){
    try{
        load_libs('cli');

        if(!cli_which('wget')){
            linux_install_package('wget');
        }

    }catch(Exception $e){
        throw new BException('wget_library_init(): Failed', $e);
    }
}



/*
 * wget command front-end function
 *
 * At minimum, this function requires $params[url], and $params[file]
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package wget
 * @version 2.4.22: Added function and documentation
 *
 * @param params $params The parameters for wget
 * @return string The result
 */
function wget($params){
    try{
        if(empty($params['url'])){
            throw new BException(tr('wget(): No url specified'), 'not-specified');
        }

        if(empty($params['file'])){
            throw new BException(tr('wget(): No file specified'), 'not-specified');
        }

        $results = safe_exec(array('commands' => array('wget' => array('-q', '-O', $params['file'], '-', $params['url']))));
        return $result;

    }catch(Exception $e){
        throw new BException('wget(): Failed', $e);
    }
}
?>
