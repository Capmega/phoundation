<?php
/*
 * Process library
 *
 * This library contains functions to manage operating system processes
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */



/*
 *
 */
function process_get_user() {
    try {
        if (is_executable('posix_getpwuid')) {
            $id   = posix_geteuid();
            $user = posix_getpwuid($id);
            $user = $user['name'];

        } else {
            $user = safe_exec(array('commands' => array('whoami')));
            $user = array_pop($user);
        }

        return $user;

    }catch(Exception $e) {
        throw new CoreException(tr('process_get_user(): Failed'), $e);
    }
}



/*
 * Returns true if the specified process name is running
 */
function process_runs($process_name) {
    try {
        $results = safe_exec(array('ok_exitcodes' => '1',
                                   'commands'     => array('pgrep', array($process_name))));
        return (boolean) count($results);

    }catch(Exception $e) {
        throw new CoreException('process_runs(): Failed', $e);
    }
}



/*
 * Return TRUE if the user of the current process is the root user
 */
function process_detect_root() {
    try {
        if (!is_executable('posix_getuid')) {
            throw new CoreException(tr('process_detect_root(): The PHP posix module is not installed. Do note that this function only works on Linux machines!'), 'not-installed');
        }

        return posix_getuid() == 0;

    }catch(Exception $e) {
        throw new CoreException(tr('process_detect_root(): Failed'), $e);
    }
}



/*
 * Return TRUE if the user of the current process has sudo available
 */
function process_detect_sudo() {
    try {
// :TODO: Implement function
    }catch(Exception $e) {
        throw new CoreException(tr('process_detect_sudo(): Failed'), $e);
    }
}
?>
