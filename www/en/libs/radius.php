<?php
/*
 * Radius library
 *
 * This is a library for free radius server
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package radius
 */
load_libs('servers');

/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package radius
 * @version 2.0.7: Added function and documentation
 *
 * @return void
 */
function radius_library_init() {
    global $_CONFIG;

    try {
        load_config('radius');

        switch ($_CONFIG['radius']['provider']) {
            case 'freeradius':
                load_libs('freeradius');
                break;

            default:
                throw new CoreException(tr('radius_library_init(): Specified provider ":provider" does not exist', array(':provider' => $_CONFIG['radius']['provider'])), 'not-exist');
        }

    }catch(Exception $e) {
        throw new CoreException('radius_library_init(): Failed', $e);
    }
}



/*
 * Add new device
 *
 * This will add a new device in local and remote radius server
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package radius
 * @version 2.0.7: created function and documentation
 * @example add new device
 * code
 * radius_insert_device(array('users_id'    => 1,
 *                            'type'        => 'phone',
 *                            'brand'       => 'Apple',
 *                            'model'       => 'Iphone X',
 *                            'mac_address' => 'B0:E5:ED:7B:E9:62',
 *                            'description' => 'this is a phone for one user'));
 * /code
 *
 * @param params $params A parameters array
 * @param natural $params[users_id]
 * @param [phone,tablet] $params[type]
 * @param string $params[brand]
 * @param string $params[model]
 * @param string $params[mac_address]
 * @param string $params[description]
 * @return params the new device added with sql_id
 */
function radius_insert_device($device) {
    try {
        $device = radius_validate_device($device);

        sql_query('INSERT INTO `radius_devices` (`created_by`, `users_id`, `meta_id`, `type`, `brand`, `model`, `mac_address`,`description`)
                   VALUES                       (:created_by , :users_id , :meta_id , :type , :brand , :model , :mac_address ,:description )',

                   array(':created_by'   => isset_get($_SESSION['user']['id']),
                         ':users_id'    => $device['users_id'],
                         ':meta_id'     => meta_action(),
                         ':type'        => $device['type'],
                         ':brand'       => $device['brand'],
                         ':model'       => $device['model'],
                         ':mac_address' => $device['mac_address'],
                         ':description' => $device['description']));

         $device['id'] = sql_insert_id();

         radius_insert_device_server($device);

         return $device;

    }catch(Exception $e) {
        throw new CoreException('radius_insert_device(): Failed', $e);
    }
}



/*
 * Update device
 *
 * this will update device in local and remote radius server
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package radius
 * @version 2.0.7: created function and documentation
 * @example add new device
 * code
 * radius_update_device(array('id'          => 1,
 *                            'users_id'    => 1,
 *                            'type'        => 'phone',
 *                            'brand'       => 'Apple',
 *                            'model'       => 'Iphone X',
 *                            'mac_address' => 'B0:E5:ED:7B:E9:62',
 *                            'description' => 'this is a phone for one user'));
 * /code
 *
 * @param params $params A parameters array
 * @param string $params[id]
 * @param string $params[users_id]
 * @param string $params[type]
 * @param string $params[brand]
 * @param string $params[model]
 * @param string $params[mac_address]
 * @param string $params[description]
 * @return array updated device
 */
function radius_update_device($device) {
    try {
        $device     = radius_validate_device($device);
        $old_device = radius_get_device($device['id']);

        meta_action($device['meta_id'], 'update');

        sql_query('UPDATE `radius_devices`

                   SET    `users_id`    = :users_id,
                          `type`        = :type,
                          `brand`       = :brand,
                          `model`       = :model,
                          `mac_address` = :mac_address,
                          `description` = :description

                   WHERE  `id`          = :id',

                   array(':id'          => $device['id'],
                         ':users_id'    => $device['users_id'],
                         ':type'        => $device['type'],
                         ':brand'       => $device['brand'],
                         ':model'       => $device['model'],
                         ':mac_address' => $device['mac_address'],
                         ':description' => $device['description']));

         radius_update_device_server($old_device, $device);

         return $device;

    }catch(Exception $e) {
        throw new CoreException('radius_update_device(): Failed', $e);
    }
}



/*
 * Restart radius server
 *
 * this will restart free radius server
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package radius
 * @version 2.4.42: created function and documentation
 * @return void
 */
function radius_restart() {
    global $_CONFIG;

    try {
        load_libs('linux');
        linux_service($_CONFIG['radius']['server'], 'radius', 'restart');

    }catch(Exception $e) {
        throw new CoreException('radius_restart(): Failed', $e);
    }
}



/*
 * Validate device
 *
 * this will validate device and return this validated and clean
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package radius
 * @version 2.0.7: created function and documentation
 * @param array $device
 * @return array the specified $device array validated and clean
 */
function radius_validate_device($device) {
    global $_CONFIG;

    try {
        load_libs('validate');

        $v = new validateForm($device, 'mac_address');

        $v->isNotEmpty ($device['mac_address'], tr('Please provide the mac address of your device'));
        $v->hasMinChars($device['mac_address'], 14, tr('Please ensure that the mac address has a minimum of 14 characters'));
        $v->isRegex($device['mac_address'], '/^[a-zA-Z0-9:]{14,}$/', tr('Please provide a valid mac address with the format xx:xx:xx:xx:xx'));
        $v->isNotEmpty ($device['brand'], tr('Please provide the brand of your device'));
        $v->isNotEmpty ($device['model'], tr('Please provide the model of your device'));
        $v->isNotEmpty ($device['users_id'], tr('Please provide the users_id of your device'));
        $v->isNotEmpty ($device['type'], tr('Please provide the type of your device'));
        $v->inArray($device['type'], array('phone', 'proyector', 'tablet', 'laptop'), $message = tr('The prodiver type :type is invalid', array(':type' => $device['type'])));

        /*
         * Validate users_id
         */
        $user = sql_get('SELECT `id` FROM `users` WHERE `id` = :id', true, array(':id' => $device['users_id']));

        if (!$user) {
            $v->setError(tr('The specified user does not exist'));
        }

        /*
         * Validate mac address
         */
        $user = sql_get('SELECT `id`

                         FROM `radius_devices`

                         WHERE `mac_address` = :mac_address

                         AND   `id` != :id', true,

                         array(':mac_address' => $device['mac_address'],
                               ':id' => isset_get($device['id'], 0)));

        if ($user) {
            $v->setError(tr('The specified mac_address is already in devices'));
        }

        switch ($_CONFIG['radius']['mac']) {
            case 'uppercase':
                $device['mac_address'] = strtoupper($device['mac_address']);
                break;
            case 'lowercase':
                $device['mac_address'] = strtolower($device['mac_address']);
                break;

            default:
                throw new CoreException('invalid configuration $_CONFIG[\'radius\'][\'mac\'] : Failed', $_CONFIG['radius']['mac']);
                break;
        }

        $v->isValid();

        return $device;

    }catch(Exception $e) {
        throw new CoreException('radius_validate_device(): Failed', $e);
    }
}



/*
 * Radius type select
 *
 * this return a html <select> with all posbles options to use for "devices types"
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package radius
 * @see html_select
 * @version 2.0.7: created function and documentation
 * @example create a html select with all devices types
 * code
 * radius_type_select(array('selected' => $select_type));
 * /code
 *
 * @param array  $params
 * @param string $params['selected'] Is the current selected type
 * @return string HTML for a type of devices select box
 */
function radius_type_select($params = null) {
    try {
        Arrays::ensure($params);
        array_default($params, 'name' , 'type');
        array_default($params, 'empty', tr('No device types available'));
        array_default($params, 'none' , tr('Select a device type'));

        $params['resource'] = array('laptop'    => tr('Laptop'),
                                    'phone'     => tr('Phone'),
                                    'projector' => tr('Projector'),
                                    'tablet'    => tr('Tablet'));

        return html_select($params);

    }catch(Exception $e) {
        throw new CoreException('radius_type_select(): Failed', $e);
    }
}



/*
 * Radius_users_select
 *
 * this return a html <select> with all posbles options to use for users to assing
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package radius
 * @see html_select
 * @version 2.0.7: created function and documentation
 * @example create a html select with all users
 * code
 * radius_users_select(array('selected' => $select_user));
 * /code
 *
 * @param array  $params
 * @param string $params['selected'] Is the current selected user
 */
function radius_users_select($params = null) {
    try {
        Arrays::ensure($params);
        array_default($params, 'name'        , 'users_id');
        array_default($params, 'class'       , 'form-control');
        array_default($params, 'selected'    , null);
        array_default($params, 'autosubmit'  , false);
        array_default($params, 'empty'       , tr('No users available'));
        array_default($params, 'none'        , tr('Select a user'));

        $params['resource'] = sql_list('SELECT `id`, `name` FROM `users` WHERE `status` IS NULL');

        return html_select($params);

    }catch(Exception $e) {
        throw new CoreException('radius_users_select(): Failed', $e);
    }
}



/*
 * Radius get device
 *
 * Retuns all info for one device
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package radius
 * @version 2.0.7: created function and documentation
 * @example get device
 * code
 * radius_get_device($id);
 * /code
 *
 * @param integer $id
 * @return array with device selected
 */
function radius_get_device($id) {
    try {
        return sql_get('SELECT `id`,
                               `users_id`,
                               `meta_id`,
                               `type`,
                               `brand`,
                               `model`,
                               `mac_address`,
                               `description`,
                               `status`

                        FROM   `radius_devices`

                        WHERE  `id`          = :id
                        OR     `mac_address` = :mac_address',

                        array(':id'          => $id,
                              ':mac_address' => $id));

    }catch(Exception $e) {
        throw new CoreException('radius_users_select(): Failed', $e);
    }
}



/*
 * Radius get all devices by id's
 *
 * Retuns all selected devices serach by id
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package radius
 * @version 2.0.7: created function and documentation
 * @example get devices
 * code
 * radius_list_devices($in);
 * /code
 *
 * @param array $in
 * @return array all devices
 */
function radius_list_devices($id_list) {
    try {
        $in     = sql_in($id_list);
        $return = sql_list('SELECT *

                            FROM `radius_devices`

                            WHERE `id` IN ('.sql_in_columns($in).')', $in);

        return $return;

    }catch(Exception $e) {
        throw new CoreException('radius_list_devices(): Failed', $e);
    }
}



/*
 * Radius sync server
 *
 * this sync all devices from local to radius server
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function radius_sync_server() {
    global $_CONFIG;

    try {
        $devices_local  = sql_list('SELECT `id`, `mac_address`, `status` FROM `radius_devices`');

        switch ($_CONFIG['radius']['provider']) {
            case 'freeradius':
                return freeradius_sync_server($devices_local);

            default:
                throw new CoreException(tr('radius_insert_device_server(): Specified provider ":provider" does not exist', array(':provider' => $_CONFIG['radius']['provider'])), 'not-exist');
        }

    }catch(Exception $e) {
        throw new CoreException('radius_insert_device_server(): Failed', $e);
    }
}



/*
 * Radius update device server
 *
 * this will validate device and return this validated and clean
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function radius_update_device_server($old_device, $device) {
    global $_CONFIG;

    try {
        switch ($_CONFIG['radius']['provider']) {
            case 'freeradius':
                return freeradius_update_device_server($old_device, $device);

            default:
                throw new CoreException(tr('radius_insert_device_server(): Specified provider ":provider" does not exist', array(':provider' => $_CONFIG['radius']['provider'])), 'not-exist');
        }

    }catch(Exception $e) {
        throw new CoreException('radius_insert_device_server(): Failed', $e);
    }
}



/*
 * Radius insert device server
 *
 * this will insert a new device in remote radius server
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function radius_insert_device_server($device) {
    global $_CONFIG;

    try {
        switch ($_CONFIG['radius']['provider']) {
            case 'freeradius':
                return freeradius_insert_device_server($device);

            default:
                throw new CoreException(tr('radius_insert_device_server(): Specified provider ":provider" does not exist', array(':provider' => $_CONFIG['radius']['provider'])), 'not-exist');
        }

    }catch(Exception $e) {
        throw new CoreException('radius_insert_device_server(): Failed', $e);
    }
}



/*
 * Radius delete device server
 *
 * this will delete one device from remote radius server
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function radius_delete_device_server($device) {
    global $_CONFIG;

    try {
        switch ($_CONFIG['radius']['provider']) {
            case 'freeradius':
                return freeradius_delete_device_server($device);

            default:
                throw new CoreException(tr('radius_delete_device_server(): Specified provider ":provider" does not exist', array(':provider' => $_CONFIG['radius']['provider'])), 'not-exist');
        }

    }catch(Exception $e) {
        throw new CoreException('radius_delete_device_server(): Failed', $e);
    }
}



/*
 * Radius test device
 *
 * test if one device is valid or not
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package radius
 * @see freeradius_test_device()
 * @version 2.0.7: Created function and documentation
 * @version 2.4.40: Updated to be front-end function to freeradius_test_device()
 * @example test device
 * code
 * radius_test_device(array('mac_address' => 'B0:E5:ED:7B:E9:62'));
 * /code
 *
 * @param params $device
 * @param string $device[mac_address]
 * @return boolean True if the specified device MAC address works, false if not
 */
function radius_test_device($device) {
    global $_CONFIG;

    try {
        switch ($_CONFIG['radius']['provider']) {
            case 'freeradius':
                return freeradius_test_device($device);

            default:
                throw new CoreException(tr('radius_test_device(): Specified provider ":provider" does not exist', array(':provider' => $_CONFIG['radius']['provider'])), 'not-exist');
        }

    }catch(Exception $e) {
        throw new CoreException('radius_test_device(): Failed', $e);
    }
}
?>
