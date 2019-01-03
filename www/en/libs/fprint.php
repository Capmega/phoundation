<?php
/*
 * Fprint library
 *
 * This is a front-end library for the fprintd deamon
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package ssh
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the ssh library configuration
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @return void
 */
function fprint_library_init(){
    try{
        load_libs('linux');
        load_config('fprint');

    }catch(Exception $e){
        throw new bException('fprint_library_init(): Failed', $e);
    }
}



/*
 * Register the fingerprint with the fprintd deamon
 *
 * This function will start the finger print scanner and register a fingerprint for the specified user. A fingerprint requires at least 5 tests, so the user will have to place his / her finger 5 times on the finger print scanner for the function to return a result.
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package fprint
 * @version 1.24.0: Added documentation
 * @note This function is blocking until the finger print scanner returns results
 * @note This function will not return a result unless an error was encountered, in which case an exception will be thrown
 *
 * @param natural $users_id The database `id` for the user that needs to be enrolled
 * @param string $finger One from auto, left-thumb, left-index-finger, left-middle-finger, left-ring-finger, left-little-finger, right-thumb, right-index-finger, right-middle-finger, right-ring-finger, right-little-finger.
 * @return void
 */
function fprint_enroll($users_id, $finger = 'auto'){
    global $_CONFIG;

    try{
        $device = fprint_select_device();
        $finger = fprint_verify_finger($finger);
        fprint_kill($device['server']);

        $results = servers_exec($device['server'], 'sudo timeout '.$_CONFIG['fprint']['timeouts']['enroll'].' fprintd-enroll '.($finger ? '-f '.$finger.' ' : '').$users_id);
        $result  = array_pop($results);

        if($result == 'Enroll result: enroll-completed'){
            sql_query('UPDATE `users` SET `fingerprint` = UTC_TIMESTAMP WHERE `id` = :id', array(':id' => $users_id));
            return true;
        }

        throw new bException(tr('fprint_enroll(): Enroll failed with ":error"', array(':error' => $result)), 'failed');

    }catch(Exception $e){
        fprint_handle_exception($e, $users_id);
        throw new bException('fprint_enroll(): Failed', $e);
    }
}



/*
 * Verify a fingerprint for the specified user.
 *
 * This function will start the finger print scanner and verify the finger print for the specified user.
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package fprint
 * @version 1.24.0: Added documentation
 *
 * @param mixed $user The user that needs to have his / her finger print verified
 * @param string $finger One from auto, left-thumb, left-index-finger, left-middle-finger, left-ring-finger, left-little-finger, right-thumb, right-index-finger, right-middle-finger, right-ring-finger, right-little-finger.
 * @return boolean True if the finger print matches for the specified user, false if not
 */
function fprint_verify($user, $finger = 'auto'){
    global $_CONFIG;

    try{
        load_libs('user');
        $dbuser = user_get($user);

        if(!$dbuser){
            throw new bException(tr('fprint_verify(): Specified user ":user" does not exist', array(':user' => $user)), 'not-exist');
        }

        if(!$dbuser['fingerprint']){
            throw new bException(tr('fprint_verify(): User ":user" has no fingerprint registered', array(':user' => name($dbuser))), 'warning/empty');
        }

        $finger = fprint_verify_finger($finger);
        $device = fprint_select_device();

        fprint_kill($device['server']);

        $results = servers_exec($device['server'], 'sudo timeout '.$_CONFIG['fprint']['timeouts']['verify'].' fprintd-verify '.($finger ? '-f '.$finger.' ' : '').$user);
        $result  = array_pop($results);

        log_file(tr('Started fprintd-verify process for user ":user"', array(':user' => $user)), 'fprint');
        log_file($results, 'fprint');

        if($result == 'Verify result: verify-match (done)'){
            return true;
        }

        return false;

    }catch(Exception $e){
        fprint_handle_exception($e, $user);
        throw new bException('fprint_verify(): Failed', $e);
    }
}



/*
 * List available users registered in the fprint database
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package fprint
 * @version 1.24.0: Added documentation
 *
 * @return array the found users that have their finger print registered
 */
function fprint_list_users(){
    try{
        $device  = fprint_select_device();
        $results = linux_scandir($device['server'], '/var/lib/fprint');

        return $results;

    }catch(Exception $e){
        throw new bException('fprint_list_users(): Failed', $e);
    }
}



/*
 * List available users registered in the fprint database
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package fprint
 * @version 1.24.0: Added documentation
 *
 * @return array the users registered in the fprint database
 */
function fprint_list($users){
    try{
        $device  = fprint_select_device();
        $results = servers_exec($device['server'], 'sudo fprintd-list'.str_force($users, ' '));

        return $results;

    }catch(Exception $e){
        throw new bException('fprint_list(): Failed', $e);
    }
}



/*
 * Delete fingerprint for the specified user
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package fprint
 * @version 1.24.0: Added documentation
 *
 * @param mixed $user The user for which the finger print must be deleted
 * @return void
 */
function fprint_delete($user){
    try{
        $device = fprint_select_device();

        if(!linux_file_exists($device['server'], '/var/lib/fprint/'.$user)){
            throw new bException(tr('fprint_delete(): Specified user ":user" does not exist', array(':user' => $user)), 'not-exist');
        }

        /*
         * Delete the directory for this user completely
         */
        linux_file_delete($device['server'], '/var/lib/fprint/'.$user, false, true);

    }catch(Exception $e){
        throw new bException('fprint_delete(): Failed', $e);
    }
}



/*
 * Kill the fprint process
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package fprint
 * @version 1.24.0: Added documentation
 *
 * @return void
 */
function fprint_kill(){
    try{
        $device = fprint_select_device();
        return linux_pkill($device['server'], 'fprintd', 15, true);

    }catch(Exception $e){
        throw new bException('fprint_kill(): Failed', $e);
    }
}



/*
 * Returns the process id for the fprint process
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package fprint
 * @version 1.24.0: Added documentation
 *
 * @return natural
 */
function fprint_process(){
    try{
        $device = fprint_select_device();
        return linux_pgrep($device['server'], 'fprintd');

    }catch(Exception $e){
        throw new bException('fprint_kill(): Failed', $e);
    }
}



/*
 * Validate the specified finger type
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package fprint
 * @version 1.24.0: Added documentation
 *
 * @param string $finger One from auto, left-thumb, left-index-finger, left-middle-finger, left-ring-finger, left-little-finger, right-thumb, right-index-finger, right-middle-finger, right-ring-finger, right-little-finger.
 * @return string The specified finger, validated
 */
function fprint_verify_finger($finger){
    try{
        switch($finger){
            case 'auto':
                return '';

            case 'left-thumb':
                // FALLTHROUGH
            case 'left-index-finger':
                // FALLTHROUGH
            case 'left-middle-finger':
                // FALLTHROUGH
            case 'left-ring-finger':
                // FALLTHROUGH
            case 'left-little-finger':
                // FALLTHROUGH
            case 'right-thumb':
                // FALLTHROUGH
            case 'right-index-finger':
                // FALLTHROUGH
            case 'right-middle-finger':
                // FALLTHROUGH
            case 'right-ring-finger':
                // FALLTHROUGH
            case 'right-little-finger':
                return $finger;

            default:
                throw new bException('fprint_verify_finger(): Unknown finger ":finger" specified. Please specify one of "left-thumb, left-index-finger, left-middle-finger, left-ring-finger, left-little-finger, right-thumb, right-index-finger, right-middle-finger, right-ring-finger, right-little-finger"', 'unknown');
        }

        return $finger;

    }catch(Exception $e){
        throw new bException('fprint_verify_finger(): Failed', $e);
    }
}



/*
 * Try to handle fprint exceptions
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package fprint
 * @version 1.24.0: Added documentation
 *
 * @param bException $e The exception that has to be handled
 * @param mixed $user The user that was being processed
 * @return void
 */
function fprint_handle_exception($e, $user){
    try{
         $data = $e->getData();

        if($data){
            $data = array_pop($data);

            if(strstr($data, 'Failed to discover prints') !== false){
                /*
                 * Only counds for verify!
                 * Do NOT send previous exception, generate a new one, its just a simple warning!
                 */
                throw new bException(tr('fprint_handle_exception(): Finger print data missing for user ":user"', array(':user' => name($user))), 'warning/not-exist');
            }

            if(strstr($data, 'No devices available') !== false){
                /*
                 * Do NOT send previous exception, generate a new one, its just a simple warning!
                 */
                throw new bException(tr('fprint_handle_exception(): No finger print scanner devices found'), 'warning/no-devices');
            }
        }

        if($e->getCode() == 124){
            throw new bException(tr('fprint_handle_exception(): finger print scan timed out'), 'warning/timeout');
        }

    }catch(Exception $e){
        throw new bException('fprint_handle_exception(): Failed', $e);
    }
}



/*
 * Detect fingerprint scanner on the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package fprint
 * @version 1.24.0: Added function and documentation
 *
 * @return
 */
function fprint_detect_software(){
    try{
        $device = fprint_select_device();

        if(!linux_file_exists($device['server'], '/var/lib/fprint')){
            throw new bException(tr('fprint_detect_software(): fprintd application data directory "/var/lib/fprint" not found, it it probably is not installed. Please fix this by executing "sudo apt-get install fprintd" on the command line'), 'install');
        }

    }catch(Exception $e){
        throw new bException('fprint_detect_software(): Failed', $e);
    }
}



/*
 * Detect fingerprint scanner on the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package fprint
 * @version 1.24.0: Added function and documentation
 *
 * @return
 */
function fprint_detect_device(){
    try{

    }catch(Exception $e){
        throw new bException('fprint_detect_device(): Failed', $e);
    }
}



/*
 * Select a finger print device for this user
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package fprint
 * @version 1.25.0: Added function and documentation
 * @note This function caches the result. After the first call, it will keep returning the same device and server data for all subsequent calls.
 *
 * @param mixed $category id or seoname of category
 * @return array The selected finger print scanner device with server data included
 */
function fprint_select_device($category = null){
    static $device;

    try{
        if(!$device){
            load_libs('devices');
            $device = devices_select('fingerprint-reader', $category);

            if(!$device){
                throw new bException(t('fprint_select_device(): No fingerprint reader device found'), 'not-exist');
            }

            $device['persist'] = true;
        }

        return $device;

    }catch(Exception $e){
        throw new bException('fprint_select_device(): Failed', $e);
    }
}



/*
 * Test the finger print device for this user by turning it on for one second and turning it off again
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package fprint
 * @version 1.25.0: Added function and documentation
 *
 * @return
 */
function fprint_test_device(){
   try{
        $device = fprint_select_device();

        fprint_kill($device['server']);
        servers_exec($device['server'], 'sudo timeout 1 fprintd-enroll test', false, null, 124);

        return $device;

    }catch(Exception $e){
        $results = $e->getData();
        $results = array_force($results);
        $result  = array_pop($results);

        if(strstr($result, 'No devices available')){
            throw new bException(tr('fprint_test_device(): No fingerprint devices available'), 'warning/not-exist');
        }

        if(strstr($result, 'Could not attempt device open')){
            $device = array_shift($results);
            throw new bException(tr('fprint_test_device(): Failed to open fingerprint device ":device"', array(':device' => $device)), 'failed');
        }

        throw new bException('fprint_test_device(): Failed', $e);
    }
}



/*
 * Check the fingerprint scanner output result, and either cause an exception in case of problems, or return a success type message
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package fprint
 * @version 1.26.0: Added function and documentation
 *
 * @return
 */
function fprint_process_result(){
    if(!isset($_SESSION['fprint'])){
        return false;
    }

    $result = isset_get($_SESSION['fprint']['result']);
    $action = isset_get($_SESSION['fprint']['action']);

    unset($_SESSION['fprint']);

    switch($result){
        case '':
            /*
             * Nothing happened yet
             */
            return false;

        case 'authenticated':
            return $action;

        case 'not-authenticated':
            throw new bException(tr('fprint_process_result(): Finger print did not match'), 'warning/'.$result);

        case 'timeout':
            throw new bException(tr('fprint_process_result(): Finger print scan process timed out'), 'warning/'.$result);

        case 'no-devices':
            throw new bException(tr('fprint_process_result(): No finger print scan devices found'), 'warning/'.$result);

        case 'no-sudo':
            load_libs('process');
            throw new bException(tr('fprint_process_result(): Current process owner ":owner" cannot execute fprint with sudo without password', array(':owner' => process_get_user())), 'warning/'.$result);

        case 'not-exist':
            throw new bException(tr('fprint_process_result(): User ":user" has no fingerprints registered', array(':user' => name(isset_get($_SESSION['user'])))), 'warning/'.$result);

        case 'no-fprint-file':
            throw new bException(tr('fprint_process_result(): Fingerprint process failed'), 'warning/'.$result);

        case 'fingerprints-missing':
            throw new bException(tr('fprint_process_result(): Fingerprint files missing'), 'warning/'.$result);

        default:
            throw new bException(tr('fprint_process_result(): Finger print scan process failed'), $result);
    }
}
?>