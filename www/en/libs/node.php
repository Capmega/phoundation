<?php
/*
 * NodeJS library
 *
 * This library contains various NodeJS functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 */



/*
 * Check if node is installed and available
 */
function node_check(){
    try{
        try{
            $result = file_which('nodejs');

        }catch(Exception $e){
            /*
             * No "nodejs"? Maybe just "node" ?
             */
            $result = file_which('node');
        }

        log_console(tr('node_check(): Using NodeJS ":result"', array(':result' => $result)), 'green');

        return $result;

    }catch(Exception $e){
        if($e->getCode() == 1){
            throw new BException('node_check(): Failed to find a node installation on this computer for this user. On Ubuntu, install node with "sudo apt-get install nodejs"', 'node_not_installed');
        }

        if($e->getCode() == 'node_modules_path_not_found'){
            throw $e;
        }

        throw new BException('node_check(): Failed', $e);
    }
}



/*
 * Check if node is installed and available
 */
function node_check_modules(){
    try{
        log_console('node_check_modules(): Checking node_modules availability', 'white');

        /*
         * Find node_modules path
         */
        if(!$home = getenv('HOME')){
            throw new BException('node_check_modules(): Environment variable "HOME" not found, failed to locate users home directory', 'environment_variable_not_found');
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
            throw new BException('node_check_modules(): node_modules path not found', 'path_not_found');
        }

        log_console(tr('node_check_modules(): Using node_modules ":path"', array(':path' => $home)), 'green');
        return slash($found);

    }catch(Exception $e){
        if($e->getCode() == 1){
            throw new BException('node_check_modules(): Failed to find a node installation on this computer for this user', 'not_installed');
        }

        if($e->getCode() == 'path_not_found'){
            throw $e;
        }

        throw new BException('node_check_modules(): Failed', $e);
    }
}



/*
 * Check if npm is installed and available
 */
function node_check_npm(){
    try{
        $result = file_which('npm');
        log_console(tr('node_check_npm(): Using npm ":result"', array(':result' => $result)), 'green');
        return $result;

    }catch(Exception $e){
        if($e->getCode() == 1){
            throw new BException('node_check_npm(): Failed to find an npm installation on this computer for this user. On Ubuntu, install with "sudo apt-get install npm"', 'npm_not_installed');
        }

        throw new BException('node_check_npm(): Failed', $e);
    }
}
?>
