<?php
/*
 * PHP Node library
 *
 * This library contains all required functions to work with node
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package node
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @version 2.6.14: Added function and documentation
 * @category Function reference
 * @package node
 *
 * @return void
 */
function node_library_init(){
    try{
        ensure_installed(array('name'     => 'node',
                               'callback' => 'node_setup',
                               'which'    => array('nodejs')));

        ensure_installed(array('name'     => 'node',
                               'callback' => 'node_setup_npm',
                               'which'    => array('npm')));

    }catch(Exception $e){
        throw new BException('node_library_init(): Failed', $e);
    }
}



/*
 * Automatically install node
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package node
 * @see node_library_init()
 * @version 2.6.14: Added function and documentation
 * @note This function typically gets executed automatically by the node_library_init() through the ensure_installed() call, and does not need to be run manually
 *
 * @return void
 */
function node_setup(){
    try{
        load_libs('linux');
        linux_install_package(null, 'nodejs');

    }catch(Exception $e){
        throw new BException('node_setup(): Failed', $e);
    }
}



/*
 * Automatically install the node npm library
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package node
 * @see node_library_init()
 * @version 2.6.14: Added function and documentation
 * @note This function typically gets executed automatically by the node_library_init() through the ensure_installed() call, and does not need to be run manually
 *
 * @return void
 */
function node_setup_npm(){
    try{
        load_libs('linux');
        linux_install_package(null, 'npm');

    }catch(Exception $e){
        throw new BException('node_setup_npm(): Failed', $e);
    }
}



/*
 * Check if node is installed and available
 */
function node_find(){
    global $core;

    try{
        try{
            $core->register['node'] = file_which('nodejs');

        }catch(Exception $e){
            /*
             * No "nodejs"? Maybe just "node" ?
             */
            $core->register['node'] = file_which('node');
        }

        log_console(tr('Using node ":result"', array(':result' => $core->register['node'])), 'green');

    }catch(Exception $e){
        if($e->getCode() == 1){
            throw new BException('node_find(): Failed to find a node installation on this computer for this user. On Ubuntu, install node with "sudo apt-get install nodejs"', 'node_not_installed');
        }

        if($e->getCode() == 'node_modules_path_not_found'){
            throw $e;
        }

        throw new BException('node_find(): Failed', $e);
    }
}



/*
 * Check if node is installed and available
 */
function node_find_modules(){
    global $core;

    try{
        log_console('node_find_modules(): Checking node_modules availability', 'white');

        /*
         * Find node_modules path
         */
        if(!$home = getenv('HOME')){
            throw new BException('node_find_modules(): Environment variable "HOME" not found, failed to locate users home directory', 'environment_variable_not_found');
        }

        $home  = slash($home);
        $found = false;

        /*
         * Search for node_modules path
         */
        foreach(array($home, ROOT, getcwd()) as $path){
            if($found){
                break;
            }

            foreach(array('node_modules', '.node_modules') as $subpath){
                if(file_exists(slash($path).$subpath)){
                    $found = slash($path).$subpath;
                    break;
                }
            }
        }

        if(!$found){
            throw new BException('node_find_modules(): node_modules path not found', 'path_not_found');
        }

        log_console(tr('node_find_modules(): Using node_modules ":path"', array(':path' => $home)), 'green');
        $core->register['node_modules'] = slash($found);

    }catch(Exception $e){
        if($e->getCode() == 1){
            throw new BException('node_find_modules(): Failed to find a node installation on this computer for this user', 'not_installed');
        }

        if($e->getCode() == 'path_not_found'){
            throw $e;
        }

        throw new BException('node_find_modules(): Failed', $e);
    }
}



/*
 * Check if npm is installed and available
 */
function node_find_npm(){
    global $core;

    try{
        $core->register['npm'] = file_which('npm');
        log_console(tr('Using npm ":result"', array(':result' => $core->register['npm'])), 'green');

    }catch(Exception $e){
        if($e->getCode() == 1){
            throw new BException('node_find_npm(): Failed to find an npm installation on this computer for this user. On Ubuntu, install with "sudo apt-get install npm"', 'npm_not_installed');
        }

        throw new BException('node_find_npm(): Failed', $e);
    }
}



/*
 * OBSOLETE WRAPPER FUNCTIONS
 */
function node_check(){
    try{
        node_find();

    }catch(Exception $e){
        throw new BException('node_check(): Failed', $e);
    }
}
function node_check_npm(){
    try{
        node_find_npm();

    }catch(Exception $e){
        throw new BException('node_check_npm(): Failed', $e);
    }
}
