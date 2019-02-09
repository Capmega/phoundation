<?php
/*
 * Sessions library
 *
 * This library contains various functions to manage and manipulate PHP sessions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 */



/*
 * Returns an array with all currently active sessions
 */
function session_list(){
    try{
        $path   = ini_get( 'session.save_path');
        $retval = array();

        foreach(scandir($path) as $file){
            if(($file == '.') or ($file == '..') or ($file == 'modules')){
                continue;
            }

            $retval[] = substr($file, 5);
        }

        return $retval;

    }catch(Exception $e){
        throw new BException('session_list(): Failed', $e);
    }
}



/*
 * Change the current session to the session with the specified ID
 */
function session_take($session_id){
    try{
        $path = ini_get( 'session.save_path');

        if(!file_exists(slash($path).'sess_'.$session_id)){
            throw new BException('Specified session "'.str_log($session_id).'" does not exist', 'not-exist');
        }

        session_id($session_id);

    }catch(Exception $e){
        throw new BException('session_take(): Failed', $e);
    }
}
?>
