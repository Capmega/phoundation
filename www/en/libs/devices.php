<?php
/*
 * Devices library
 *
 * This library manages registered hardware devices like scanners, fingerprint
 * scanners, webcams, etc. The devices can be connected by USB, or PCI, on this
 * local machine or on remote servers
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package devices
 */



/*
 * Insert a device into the devices table
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 1.25.0: Added function and documentation
 *
 * @param
 * @param
 * @return
 */
function devices_insert($device, $server = null){
    try{
        $device = devices_validate($device, $server);

        /*
         * Ensure the device does not exist yet
         */
        $exists = devices_get($device['string'], $device['servers_id']);

        if($exists){
            /*
             * This device product already exist on the specified server on the specified bus:device
             */
            $device['id']      = $exists['id'];
            $device['meta_id'] = $exists['meta_id'];

            return devices_update($device);
        }

        sql_query('INSERT INTO `devices` (`createdby`, `meta_id`, `servers_id`, `categories_id`, `companies_id`, `branches_id`, `departments_id`, `manufacturer`, `model`, `vendor`, `vendor_string`, `product`, `product_string`, `seo_product_string`, `libusb`, `bus`, `device`, `string`, `seostring`, `default`, `description`)
                   VALUES                (:createdby , :meta_id , :servers_id , :categories_id , :companies_id , :branches_id , :departments_id , :manufacturer , :model , :vendor , :vendor_string , :product , :product_string , :seo_product_string , :libusb , :bus , :device , :string , :seostring , :default , :description )',

                   array(':createdby'          => isset_get($_SESSION['user']['id']),
                         ':meta_id'            => meta_action(),
                         ':servers_id'         => $device['servers_id'],
                         ':categories_id'      => $device['categories_id'],
                         ':companies_id'       => $device['companies_id'],
                         ':branches_id'        => $device['branches_id'],
                         ':departments_id'     => $device['departments_id'],
                         ':manufacturer'       => $device['manufacturer'],
                         ':model'              => $device['model'],
                         ':vendor'             => $device['vendor'],
                         ':vendor_string'      => $device['vendor_string'],
                         ':product'            => $device['product'],
                         ':product_string'     => $device['product_string'],
                         ':seo_product_string' => $device['seo_product_string'],
                         ':libusb'             => $device['libusb'],
                         ':bus'                => $device['bus'],
                         ':device'             => $device['device'],
                         ':string'             => $device['string'],
                         ':seostring'          => $device['seostring'],
                         ':default'            => $device['default'],
                         ':description'        => $device['description']));

        $device['id'] = sql_insert_id();

        return $device;

    }catch(Exception $e){
        throw new BException('devices_insert(): Failed', $e);
    }
}



/*
 * Update a device in the devices table
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 1.25.0: Added function and documentation
 *
 * @param
 * @param
 * @return
 */
function devices_update($device, $server = null){
    try{
        $device = devices_validate($device, $server);
        meta_action($device['meta_id'], 'update');

        sql_query('UPDATE `devices`

                   SET    `categories_id`  = :categories_id,
                          `companies_id`   = :companies_id,
                          `branches_id`    = :branches_id,
                          `departments_id` = :departments_id,
                          `description`    = :description

                   WHERE  `id`             = :id',

                   array(':id'             => $device['id'],
                         ':categories_id'  => $device['categories_id'],
                         ':companies_id'   => $device['companies_id'],
                         ':branches_id'    => $device['branches_id'],
                         ':departments_id' => $device['departments_id'],
                         ':description'    => $device['description']));

        return $device;

    }catch(Exception $e){
        throw new BException('devices_insert(): Failed', $e);
    }
}



/*
 * Add options for a device
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 1.25.0: Added function and documentation
 *
 * @param
 * @param
 * @return
 */
function devices_validate($device, $server){
    try{
        load_libs('validate,seo,categories,companies,servers');
        $v = new ValidateForm($device, 'manufacturer,model,vendor,vendor_string,product,product_string,libusb,bus,device,string,default,category,company,branch,department,server,description');

        $v->isAlphaNumeric($device['manufacturer'], tr('Please specify a valid device manufacturer'), VALIDATE_ALLOW_EMPTY_NULL|VALIDATE_IGNORE_DASH);
        $v->hasMinChars($device['manufacturer'],  2, tr('Please specify a device manufacturer of 2 characters or more'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($device['manufacturer'], 32, tr('Please specify a device manufacturer of maximum 32 characters'), VALIDATE_ALLOW_EMPTY_NULL);

        $v->isAlphaNumeric($device['model'], tr('Please specify a valid device model'), VALIDATE_ALLOW_EMPTY_NULL|VALIDATE_IGNORE_DASH);
        $v->hasMinChars($device['model'],  2, tr('Please specify a device model of 2 characters or more'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($device['model'], 32, tr('Please specify a device model of maximum 32 characters'), VALIDATE_ALLOW_EMPTY_NULL);

        $v->isHexadecimal($device['vendor'], tr('Please specify a valid hexadecimal device vendor string'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMinChars($device['vendor'], 4, tr('Please specify a device vendor of 4 characters'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($device['vendor'], 4, tr('Please specify a device vendor of 4 characters'), VALIDATE_ALLOW_EMPTY_NULL);

        $v->hasMinChars($device['vendor_string'],  2, tr('Please specify a device vendor string of minimal 2 characters'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($device['vendor_string'], 32, tr('Please specify a device vendor of maximum 32 characters'), VALIDATE_ALLOW_EMPTY_NULL);

        $v->isHexadecimal($device['product'], tr('Please specify a valid hexadecimal device vendor string'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMinChars($device['product'], 4, tr('Please specify a device vendor of 4 characters'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($device['product'], 4, tr('Please specify a device vendor of 4 characters'), VALIDATE_ALLOW_EMPTY_NULL);

        $v->hasMinChars($device['product_string'],  2, tr('Please specify a device product string of minimal 2 characters'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($device['product_string'], 32, tr('Please specify a device product of maximum 32 characters'), VALIDATE_ALLOW_EMPTY_NULL);

        $v->isNatural($device['bus']   , 1, tr('Please specify a valid , natural device bus number'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($device['device'], 1, tr('Please specify a valid, natural device number'), VALIDATE_ALLOW_EMPTY_NULL);

        $v->hasMinChars($device['libusb'], 7, tr('Please specify a libusb string of 7 characters'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($device['libusb'], 7, tr('Please specify a libusb string of 7 characters'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->isRegex($device['libusb'], '/\d{3}:\d{3}/', tr('Please specify a libusb string in the format nnn:nnn'), VALIDATE_ALLOW_EMPTY_NULL);

        $v->hasMinChars($device['string'],   2, tr('Please specify a device string of 2 characters or more'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($device['string'], 128, tr('Please specify a device string of maximum 128 characters'), VALIDATE_ALLOW_EMPTY_NULL);

        $device['seo_product_string'] = seo_string($device['product_string']);

        /*
         * Validate server
         */
        if($server){
            $server = servers_get($server);
            $device['servers_id'] = $server['id'];

        }elseif($device['server']){
           $device['servers_id'] = servers_get($device['server'], 'id');

            if(!$device['servers_id']){
                $v->setError(tr('Specified server ":server" does not exist', array(':server' => $device['server'])));
            }

        }else{
            $device['servers_id'] = null;
        }

        /*
         * Validate category
         */
        if($device['category']){
           $device['categories_id'] = categories_get($device['category'], 'id');

            if(!$device['categories_id']){
                $v->setError(tr('Specified category ":category" does not exist', array(':category' => $device['category'])));
            }

        }else{
            $device['categories_id'] = null;
        }

        /*
         * Description
         */
        if(empty($server['description'])){
            $server['description'] = '';

        }else{
            $v->hasMinChars($server['description'],   16, tr('Please specifiy a minimum of 16 characters for the description'));
            $v->hasMaxChars($server['description'], 2047, tr('Please specifiy a maximum of 2047 characters for the description'));

            $server['description'] = cfm($server['description']);
        }

        /*
         * Validate company / branch / department
         */
        if($device['company']){
            $device['companies_id'] = companies_get($device['company'], 'id');

            if(!$device['companies_id']){
                $v->setError(tr('Specified company ":company" does not exist', array(':company' => $device['company'])));

                $device['branches_id']    = null;
                $device['departments_id'] = null;

            }else{
                $device['branches_id'] = companies_get_branch($device['companies_id'], $device['branch'], 'id');

                if(!$device['branches_id']){
                    $v->setError(tr('Specified branch ":branch" does not exist in company ":company"', array(':company' => $device['company'], ':branch' => $device['branch'])));

                   $device['departments_id'] = null;

                }else{
                    $device['departments_id'] = companies_get_department($device['companies_id'], $device['department'], 'id');

                    if(!$device['departments_id']){
                        $v->setError(tr('Specified department ":department" does not exist in company ":company"', array(':company' => $device['company'], ':department' => $device['department'])));
                    }
                }
            }

        }else{
            $device['companies_id']   = null;
            $device['branches_id']    = null;
            $device['departments_id'] = null;

            if($device['branch']){
                $v->setError(tr('No company specified for branch ":branch"', array(':branch' => $device['branch'])));
            }

            if($device['department']){
                $v->setError(tr('No company specified for department ":department"', array(':department' => $device['department'])));
            }
        }

        if($device['default']){
            /*
             * Ensure that there is not another device already the default
             */
            $exists = sql_get('SELECT `string` FROM `devices` WHERE `seo_product_string` = :seo_product_string AND `default` IS NOT NULL AND `id` != :id', array(':seo_product_string' => $device['seo_product_string'], ':id' => $device['id']));

            if($exists){
                $v->setError(tr('Device ":device" already is the default for ":seo_product_string" devices', array(':device' => $exists, ':seo_product_string' => $device['seo_product_string'])));
            }

        }else{
            $device['default'] = !sql_get('SELECT COUNT(`id`) AS `count` FROM `devices` WHERE `seo_product_string` = :seo_product_string', true, array(':seo_product_string' => $device['seo_product_string']));

            if(!$device['default']){
                $device['default'] = null;
            }
        }

        $v->isValid();

        /*
         * Cleanup
         */
        $device['seostring']   = seo_unique($device['string'], 'devices', isset_get($device['id']), 'seostring');
        $device['description'] = str_replace('_', ' ', $device['description']);

        return $device;

    }catch(Exception $e){
        throw new BException('devices_validate(): Failed', $e);
    }
}



/*
 * Add options for a device
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 1.25.0: Added function and documentation
 *
 * @param
 * @param
 * @return
 */
function devices_set_status($device, $status){
    try{
        if(is_numeric($device)){
            $delete = sql_query('UPDATE `devices` SET `status` = :status WHERE `id` = :id'        , array(':id'     => $device, ':status' => $status));

        }else{
            $delete = sql_query('UPDATE `devices` SET `status` = :status WHERE `string` = :string', array(':string' => $device, ':status' => $status));
        }

        return $delete->rowCount();

    }catch(Exception $e){
        throw new BException('devices_set_status(): Failed', $e);
    }
}



/*
 * Add options for a device
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 1.25.0: Added function and documentation
 *
 * @param
 * @param
 * @param
 * @return
 */
function devices_insert_options($devices_id, $options){
    try{
        $count  = 0;
        $insert = sql_prepare('INSERT INTO `devices_options` (`devices_id`, `status`, `key`, `value`, `default`)
                               VALUES                        (:devices_id , :status , :key , :value , :default )');

        foreach($options as $key => $values){
            /*
             * Extract default values, if available
             */
            foreach($values['data'] as $value){
                $count++;

                if(strstr($value, '..')){
                    /*
                     * This is a single range entry
                     */
                    $default = $values['default'];

                }else{
                    $default = (($value == $values['default']) ? $value : null);
                }

                $insert->execute(array(':devices_id' => $devices_id,
                                       ':status'     => $values['status'],
                                       ':key'        => $key,
                                       ':value'      => $value,
                                       ':default'    => $default));
            }
        }

        return $count;

    }catch(Exception $e){
        throw new BException('devices_insert_options(): Failed', $e);
    }
}



/*
 * Return the available registered drivers for the specified seo_product_string
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 1.25.0: Added function and documentation
 *
 * @param
 * @param
 * @param
 * @return
 */
function devices_validate_options($option){
    try{
        load_libs('validate');
        $v = new ValidateForm($device, 'key,value,default');

        return $option;

    }catch(Exception $e){
        throw new BException('devices_validate_options(): Failed', $e);
    }
}



/*
 * Return the available registered drivers for the specified seo_product_string
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 1.25.0: Added function and documentation
 *
 * @param
 * @param
 * @return
 */
function devices_list_options($devices_id, $inactive = false){
    try{
        if($inactive){
            $retval  = array();
            $options = sql_query('SELECT `key`, `value`, `default` FROM `devices_options` WHERE `devices_id` = :devices_id', array(':devices_id' => $devices_id));

        }else{
            $retval  = array();
            $options = sql_query('SELECT `key`, `value`, `default` FROM `devices_options` WHERE `devices_id` = :devices_id AND `status` IS NULL', array(':devices_id' => $devices_id));
        }

        if(!$options){
            throw new BException(tr('devices_list_options(): Speficied drivers id ":id" does not exist', array(':id' => $devices_id)), 'not-exist');
        }

        foreach($options as $option){
            if(empty($retval[$option['key']])){
                $retval[$option['key']] = array();
            }

            $retval[$option['key']][$option['value']] = $option['value'];

            if($option['default']){
                $retval[$option['key']]['default'] = $option['default'];
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new BException('devices_list_options(): Failed', $e);
    }
}



/*
 * Return the available registered drivers for the specified seo_product_string
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 1.25.0: Added function and documentation
 *
 * @param
 * @param
 * @param
 * @return
 */
function devices_list($seo_product_string, $all = false, $default_only = false){
    try{
        if($default_only){
            $where = 'WHERE  `seo_product_string` = :seo_product_string
                      AND    `status`             IS NULL
                      AND    `default`            = 1';

        }elseif($all){
            $where = 'WHERE  `seo_product_string` = :seo_product_string';

        }else{
            $where = 'WHERE  `seo_product_string` = :seo_product_string
                      AND    `status` IS NULL';
        }

        $devices = sql_query('SELECT `id`,
                                     `meta_id`,
                                     `status`,
                                     `seo_product_string`,
                                     `manufacturer`,
                                     `model`,
                                     `vendor`,
                                     `vendor_string`,
                                     `product`,
                                     `product_string`,
                                     `seo_product_string`,
                                     `libusb`,
                                     `bus`,
                                     `device`,
                                     `string`,
                                     `default`,
                                     `description`

                              FROM   `devices`'.$where,

                              array(':seo_product_string' => $seo_product_string));

        return $devices;

    }catch(Exception $e){
        throw new BException('devices_list(): Failed', $e);
    }
}



/*
 * Return the device with the specified device string
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 1.25.0: Added function and documentation
 *
 * @param
 * @param
 * @return
 */
function devices_get($device, $server = null){
    try{
        if(is_natural($device)){
            $where = ' WHERE `devices`.`id` = :id ';
            $execute[':id'] = $device;

        }elseif(is_string($device)){
            load_libs('servers');

            $server = servers_get($server);
            $where  = ' WHERE `devices`.`string` = :string AND `devices`.`servers_id` = :servers_id ';

            $execute[':string']     = $device;
            $execute[':servers_id'] = $server['id'];

        }else{
            throw new BException(tr('devices_get(): Invalid device ":device" specified', array(':device' => $device)), 'invalid');
        }

        $device = sql_get('SELECT    `devices`.`id`,
                                     `devices`.`meta_id`,
                                     `devices`.`servers_id`,
                                     `devices`.`categories_id`,
                                     `devices`.`companies_id`,
                                     `devices`.`branches_id`,
                                     `devices`.`departments_id`,
                                     `devices`.`status`,
                                     `devices`.`manufacturer`,
                                     `devices`.`model`,
                                     `devices`.`vendor`,
                                     `devices`.`vendor_string`,
                                     `devices`.`product`,
                                     `devices`.`product_string`,
                                     `devices`.`seo_product_string`,
                                     `devices`.`libusb`,
                                     `devices`.`bus`,
                                     `devices`.`device`,
                                     `devices`.`string`,
                                     `devices`.`default`,
                                     `devices`.`description`,

                                     `servers`.`domain`,
                                     `servers`.`seodomain`,
                                     `categories`.`name`  AS `category`,
                                     `companies`.`name`   AS `company`,
                                     `branches`.`name`    AS `branch`,
                                     `departments`.`name` AS `department`

                           FROM      `devices`

                           LEFT JOIN `servers`
                           ON        `servers`.`id`     = `devices`.`servers_id`

                           LEFT JOIN `categories`
                           ON        `categories`.`id`  = `devices`.`categories_id`

                           LEFT JOIN `companies`
                           ON        `companies`.`id`   = `devices`.`companies_id`

                           LEFT JOIN `branches`
                           ON        `branches`.`id`    = `devices`.`branches_id`

                           LEFT JOIN `departments`
                           ON        `departments`.`id` = `devices`.`departments_id`'.$where,

                           $execute);

        return $device;

    }catch(Exception $e){
        throw new BException('devices_get(): Failed', $e);
    }
}



/*
 * Return the default device for the requested product
 *
 * This function returns a device of the specified seo_product_string which suits the current user and (if specified) category best
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 1.25.0: Added function and documentation
 *
 * @param string $product The seo_product_string that should be matched
 * @param mixed $category id or seoname of category to filter on
 * @return array The selected device
 */
function devices_select($product, $category = null){
    try{
        if($category){
            $categories_id = categories_get($category, 'id');
        }

        $device = sql_get('SELECT    `devices`.`id`,
                                     `devices`.`meta_id`,
                                     `devices`.`servers_id`,
                                     `devices`.`categories_id`,
                                     `devices`.`companies_id`,
                                     `devices`.`branches_id`,
                                     `devices`.`departments_id`,
                                     `devices`.`status`,
                                     `devices`.`manufacturer`,
                                     `devices`.`model`,
                                     `devices`.`vendor`,
                                     `devices`.`vendor_string`,
                                     `devices`.`product`,
                                     `devices`.`product_string`,
                                     `devices`.`seo_product_string`,
                                     `devices`.`libusb`,
                                     `devices`.`bus`,
                                     `devices`.`device`,
                                     `devices`.`string`,
                                     `devices`.`default`,
                                     `devices`.`description`,
                                     `categories`.`name`  AS `category`,
                                     `companies`.`name`   AS `company`,
                                     `branches`.`name`    AS `branch`,
                                     `departments`.`name` AS `department`

                           FROM      `devices`

                           LEFT JOIN `categories`
                           ON       (`devices`.`categories_id`  IS NULL OR `devices`.`categories_id`  = :categories_id)
                           AND       `devices`.`categories_id`  =`categories`.`id`

                           LEFT JOIN `companies`
                           ON       (`devices`.`companies_id`   IS NULL OR `devices`.`companies_id`   = :companies_id)
                           AND       `devices`.`companies_id`   = `companies`.`id`

                           LEFT JOIN `branches`
                           ON       (`devices`.`branches_id`    IS NULL OR `devices`.`branches_id`    = :branches_id)
                           AND       `devices`.`branches_id`    = `branches`.`id`

                           LEFT JOIN `departments`
                           ON       (`devices`.`departments_id` IS NULL OR `devices`.`departments_id` = :departments_id)
                           AND       `devices`.`departments_id` = `departments`.`id`

                           WHERE     `devices`.`seo_product_string` = :seo_product_string

                           ORDER BY  `default` DESC

                           LIMIT     1',

                           array(':categories_id'      => isset_get($categories_id),
                                 ':companies_id'       => isset_get($_SESSION['user']['companies_id']),
                                 ':branches_id'        => isset_get($_SESSION['user']['branches_id']),
                                 ':departments_id'     => isset_get($_SESSION['user']['departments_id']),
                                 ':seo_product_string' => $product));

        if($device and $device['servers_id']){
            /*
             * Get server data
             */
            load_libs('servers');
            $device['server'] = servers_get($device['servers_id']);
        }

        return $device;

    }catch(Exception $e){
        throw new BException('devices_select(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 1.25.0: Added function and documentation
 *
 * @param string $product The seo_product_string that should be matched
 * @return
 */
function devices_clear($product){
    try{
        $delete = sql_query('DELETE FROM `devices` WHERE `seo_product_string` = :seo_product_string', array(':seo_product_string' => $product));
        return $delete->rowCount();

    }catch(Exception $e){
        throw new BException('devices_clear(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 1.25.0: Added function and documentation
 *
 * @param
 * @param
 * @return
 */
function devices_scan_usb($filters, $server){
    try{
        load_libs('servers');

        $retval            = array();
        $server            = servers_get($server);
        $server['persist'] = true;
        $devices           = servers_exec($server, 'lsusb | grep -i "'.$filters.'"');

        foreach($devices as $device){
            $found = preg_match_all('/Bus (\d+) Device (.+?): ID ([0-9a-f]{4}:[0-9a-f]{4}) (.+)/i', $device, $matches);

            if(!$found){
                continue;
            }

            log_console(tr('Found device ":device"', array(':device' => $device)), 'VERBOSE');

            $entry = array('raw'         => $matches[0][0],
                           'device'      => $matches[2][0],
                           'bus'         => $matches[1][0],
                           'string'      => $matches[3][0],
                           'description' => $matches[4][0]);

            $data = servers_exec($server, 'lsusb -vs '.$entry['bus'].':'.$entry['device']);

            foreach($data as $line){
                if(stristr($line, 'idProduct')){
                    $entry['product']        = str_from($line, '0x');
                    $entry['product_string'] = str_from($entry['product'] , ' ');
                    $entry['product']        = str_until($entry['product'], ' ');
                }

                if(stristr($line, 'idVendor')){
                    $entry['vendor']        = str_from($line, '0x');
                    $entry['vendor_string'] = str_from($entry['vendor'] , ' ');
                    $entry['vendor']        = str_until($entry['vendor'], ' ');
                }

                if(stristr($line, 'idManufacturer')){
                    $entry['manufacturer'] = str_from($line, 'idManufacturer');
                }
            }

            $retval[] = $entry;
        }

        return $retval;

    }catch(Exception $e){
        throw new BException('devices_scan_usb(): Failed', $e);
    }
}



/*
 * Scan all servers for devices within the specified filters
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 1.27.1: Added function and documentation
 *
 * @param
 * @return
 */
function devices_scan_servers($filters){
    try{
        load_libs('servers');

    }catch(Exception $e){
        throw new BException('devices_scan_servers(): Failed', $e);
    }
}
?>
