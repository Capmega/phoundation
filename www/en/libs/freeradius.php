<?php
/*
 * Freeradius library
 *
 * This library contains front-end functions to freeradius
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package freeradius
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
 * @package freeradius
 * @version 2.4: Added function and documentation
 *
 * @return void
 */
function freeradius_library_init(){
    try{
        ensure_installed(array('name'      => 'freeradius',
                               'callback'  => 'freeradius_install',
                               'checks'    => ROOT.'libs/external/freeradius/freeradius,'.ROOT.'libs/external/freeradius/foobar',
                               'functions' => 'freeradius,foobar',
                               'which'     => 'freeradius,foobar'));

    }catch(Exception $e){
        throw new BException('freeradius_library_init(): Failed', $e);
    }
}



/*
 * Install the external freeradius library
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @version 2.4.11: Added function and documentation
 * @package freeradius
 *
 * @param
 * @return
 */
function freeradius_install($params){
    try{
        load_libs('apt');
        apt_install('freeradius');

        load_libs('apt');
        apt_install('freeradius');

    }catch(Exception $e){
        throw new BException('freeradius_install(): Failed', $e);
    }
}



/*
 * Radius sync server
 *
 * this sync all devices from local to radius server
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package radius
 * @version 2.0.7: created function and documentation
 * @example sync remote radius server
 * code
 * radius_sync_server();
 * /code
 *
 * @return void
 */
function freeradius_sync_server($devices_local){
    try{

        $devices_remote = sql_list('SELECT `username` FROM `radcheck`', null, false, 'radius');

        foreach($devices_local as $device){
            if(in_array($device['mac_address'], $devices_remote)){
                if($device['status'] == 'deleted'){
                    radius_delete_device_server($device);
                }

            }else{
                if($device['status'] === null){
                    radius_insert_device_server($device);
                }
            }
        }

        $devices_local_mac  = sql_list('SELECT `mac_address` FROM `radius_devices`');

        foreach($devices_remote as $device){
            show($device);
            if(!in_array($device, $devices_local_mac)){
                radius_delete_device_server(array('mac_address' => $device, 'status' => 'DELETED'));
            }
        }

    }catch(Exception $e){
        throw new bException('radius_sync_server(): Failed', $e);
    }
}



/*
 * Radius update device server
 *
 * this will validate device and return this validated and clean
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package radius
 * @version 2.0.7: created function and documentation
 * @example update device
 * code
 * radius_update_device_server(array('mac_address' => 'B0:E5:ED:7B:E9:62'),
 *                             array('mac_address' => 'B0:E5:ED:7B:E9:62'));
 * /code
 *
 * @param array  $old_device
 * @param string $old_device['mac_address']
 * @param array  $device
 * @param string $device['mac_address']
 * @return void
 */
function freeradius_update_device_server($old_device, $device){
    try{
        sql_query('UPDATE `radcheck`

                   SET    `username` = :username,
                          `value`    = :value

                   WHERE `username`  = :username
                   OR    `value`     = :value',

                   array(':username'     => $device['mac_address'],
                         ':value'        => $device['mac_address'],
                         ':old_value'    => $old_device['mac_address'],
                         ':old_username' => $old_device['mac_address']), 'radius');

    }catch(Exception $e){
        throw new bException('radius_update_device_server(): Failed', $e);
    }
}



/*
 * Radius insert device server
 *
 * this will insert a new device in remote radius server
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package radius
 * @version 2.0.7: created function and documentation
 * @example insert new device
 * code
 * radius_insert_device_server(array('mac_address' => 'B0:E5:ED:7B:E9:62'));
 * /code
 *
 * @param array  $device
 * @param string $device['mac_address']
 * @return void
 */
function freeradius_insert_device_server($device){
    try {
        sql_query('INSERT INTO `radcheck` (`username`, `attribute`         , `op`, `value`)
                   VALUES                 (:username , "Cleartext-Password", ":=", :value);',

                   array(':value'    => $device['mac_address'],
                         ':username' => $device['mac_address']), 'radius');

    }catch(Exception $e){
        throw new bException('radius_insert_device_server(): Failed', $e);
    }

}



/*
 * Radius delete device server
 *
 * this will delete one device from remote radius server
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package radius
 * @version 2.0.7: created function and documentation
 * @example delete device
 * code
 * radius_delete_device_server(array('mac_address' => 'B0:E5:ED:7B:E9:62'));
 * /code
 *
 * @param array  $device
 * @param string $device['mac_address']
 * @return void
 */
function freeradius_delete_device_server($device){
    try {
        sql_query('DELETE FROM `radcheck`
                   WHERE       `username` = :username OR `value` = :value',

                   array(':value'    => $device['mac_address'],
                         ':username' => $device['mac_address']), 'radius');


    }catch(Exception $e){
        throw new bException('radius_delete_device_server(): Failed', $e);
    }

}



/*
 * Radius test device
 *
 * test if one device is valid or not
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package radius
 * @version 2.4.40: created function and documentation
 * @see radius_test_device()
 * @example test device
 * code
 * freeradius_test_device(array('mac_address' => 'B0:E5:ED:7B:E9:62'));
 * /code
 *
 * @param params $device
 * @param string $device[mac_address]
 * @return boolean True if the specified device MAC address works, false if not
 */
function freeradius_test_device($device){
    global $_CONFIG;

    try {
        load_libs('servers');
        $results = servers_exec($_CONFIG['radius']['server'], 'radtest '.$device['mac_address'].' '.$device['mac_address'].' 127.0.0.1 1812 '.$_CONFIG['radius']['secret']);
        $results = end($results);

        return !str_exists($results, 'Access-Reject');

    }catch(Exception $e){
        load_libs('linux');

        if(!linux_which($_CONFIG['radius']['server'], 'radtest')){
            throw new bException(tr('freeradius_test_device(): The program "radtest" is not installed on server""', array(':server' => $_CONFIG['radius']['server'])), $e);
        };

        throw new bException('freeradius_test_device(): Failed', $e);
    }
}
?>
