<?php
/*
 * Sessions library
 *
 * This library contains various functions to manage and manipulate PHP sessions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */



/*
 * Returns an array with all currently active sessions
 */
function session_list() {
    try {
        $path   = ini_get( 'session.save_path');
        $return = array();

        foreach (scandir($path) as $file) {
            if (($file == '.') or ($file == '..') or ($file == 'modules')) {
                continue;
            }

            $return[] = substr($file, 5);
        }

        return $return;

    }catch(Exception $e) {
        throw new CoreException('session_list(): Failed', $e);
    }
}



/*
 * Change the current session to the session with the specified ID
 */
function session_take($session_id) {
    try {
        $path = ini_get( 'session.save_path');

        if (!file_exists(Strings::slash($path).'sess_'.$session_id)) {
            throw new CoreException('Specified session "'.Strings::Log($session_id).'" does not exist', 'not-exists');
        }

        session_id($session_id);

    }catch(Exception $e) {
        throw new CoreException('session_take(): Failed', $e);
    }
}
?>
