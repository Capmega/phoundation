<?php
/*
 * Devices library
 *
 * This library manages registered hardware devices like scanners, fingerprint
 * scanners, webcams, etc. The devices can be connected by USB, or PCI, on this
 * local machine or on remote servers
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <license@capmega.com>
 * @category Function reference
 * @package devices
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 2.2.0: Added function and documentation
 *
 * @return void
 */
function devices_library_init(){
    try{
        load_libs('linux');
        load_config('devices');

    }catch(Exception $e){
        throw new CoreException('devices_library_init(): Failed', $e);
    }
}



/*
 * Merge specified POST data with the specified device data, and ensure that customer, provider, inventory, category, company, brach, department and employee data are all valid
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 2.2.0: Added function and documentation
 *
 * @param params $device
 * @param params $post
 * @return params The specified device parameter array, validated and sanitized
 */
function devices_merge($device, $post, $server = null){
    try{
        $device = sql_merge($device, $post);
        $device = devices_validate($device, $server, true);
//showdie('TEST DEVICES_MERGE(), DEVICES_VALIDATE(), MAKE ALL companies_get_*() use sql_simple_get() and all companies_list_*() use sql_simple_list()');
        return $device;

    }catch(Exception $e){
        throw new CoreException('devices_merge(): Failed', $e);
    }
}



/*
 * Insert a device into the devices table
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
        $device = devices_validate($device, $server, false);

        /*
         * Ensure the device does not exist yet
         */
        $exists = devices_get($device['seostring'], $device['servers_id']);

        if($exists){
            /*
             * Device already exists
             */
            $device['_exists'] = true;

            switch($exists['status']){
                case 'not-exists':
                    log_console(tr('Not inserting ":device" on server ":server", it is already registered with id ":id". Enabling existing device instead.', array(':device' => $exists['description'], ':server' => $exists['domain'], ':id' => $exists['id'])), 'VERBOSE/yellow');
                    devices_set_status(null, $exists['id']);
                    return $exists;

                case null:
                    log_console(tr('Not inserting ":device" on server ":server", it is already registered.', array(':device' => $exists['description'], ':server' => $exists['domain'])), 'VERBOSE/yellow');
                    return $exists;

                default:
                    log_console(tr('Not inserting ":device" on server ":server", it is already registered, though with status ":status".', array(':device' => $exists['description'], ':server' => $exists['domain'], ':status' => $exists['status'])), 'VERBOSE/yellow');
                    return $exists;
            }
        }

        sql_query('INSERT INTO `devices` (`createdby`, `meta_id`, `servers_id`, `categories_id`, `companies_id`, `branches_id`, `departments_id`, `employees_id`, `customers_id`, `providers_id`, `inventories_id`, `type`, `manufacturer`, `model`, `vendor`, `vendor_string`, `product`, `product_string`, `seo_product_string`, `libusb`, `bus`, `device`, `string`, `seostring`, `default`, `name`, `seoname`, `description`)
                   VALUES                (:createdby , :meta_id , :servers_id , :categories_id , :companies_id , :branches_id , :departments_id , :employees_id , :customers_id , :providers_id , :inventories_id , :type , :manufacturer , :model , :vendor , :vendor_string , :product , :product_string , :seo_product_string , :libusb , :bus , :device , :string , :seostring , :default , :name , :seoname , :description )',

                   array(':createdby'          => isset_get($_SESSION['user']['id']),
                         ':meta_id'            => meta_action(),
                         ':servers_id'         => $device['servers_id'],
                         ':categories_id'      => $device['categories_id'],
                         ':companies_id'       => $device['companies_id'],
                         ':branches_id'        => $device['branches_id'],
                         ':departments_id'     => $device['departments_id'],
                         ':employees_id'       => $device['employees_id'],
                         ':customers_id'       => $device['customers_id'],
                         ':providers_id'       => $device['providers_id'],
                         ':inventories_id'     => $device['inventories_id'],
                         ':type'               => $device['type'],
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
                         ':name'               => $device['name'],
                         ':seoname'            => $device['seoname'],
                         ':description'        => $device['description']));

        $device['id'] = sql_insert_id();
        devices_insert_options($device['id'], $device['options']);

        log_console(tr('Inserted ":device" on server ":server"', array(':device' => $device['string'], ':server' => $device['domain'])), 'VERYVERBOSE/green');
        return $device;

    }catch(Exception $e){
        throw new CoreException('devices_insert(): Failed', $e);
    }
}



/*
 * Update a device in the devices table
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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

        sql_query(' UPDATE `devices`

                   SET    `name`           = :name,
                          `seoname`        = :seoname,
                          `categories_id`  = :categories_id,
                          `companies_id`   = :companies_id,
                          `branches_id`    = :branches_id,
                          `departments_id` = :departments_id,
                          `employees_id`   = :employees_id,
                          `customers_id`   = :customers_id,
                          `providers_id`   = :providers_id,
                          `inventories_id` = :inventories_id,
                          `description`    = :description

                   WHERE  `id`             = :id',

                   array(':id'             => $device['id'],
                         ':categories_id'  => $device['categories_id'],
                         ':companies_id'   => $device['companies_id'],
                         ':branches_id'    => $device['branches_id'],
                         ':departments_id' => $device['departments_id'],
                         ':employees_id'   => $device['employees_id'],
                         ':customers_id'   => $device['customers_id'],
                         ':providers_id'   => $device['providers_id'],
                         ':inventories_id' => $device['inventories_id'],
                         ':name'           => $device['name'],
                         ':seoname'        => $device['seoname'],
                         ':description'    => $device['description']));

        return $device;

    }catch(Exception $e){
        throw new CoreException('devices_update(): Failed', $e);
    }
}



/*
 * Validate the specified device
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 1.25.0: Added function and documentation
 *
 * @param params $device
 * @param params $server
 * @return params
 */
function devices_validate($device, $server = null, $update = true){
    try{
        load_libs('validate,seo,categories,companies,servers,customers,providers,inventories');
        $v = new ValidateForm($device, 'name,type,manufacturer,model,vendor,vendor_string,product,product_string,libusb,bus,device,string,default,category,company,branch,department,employee,customer,provider,inventory,server,description,options');

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

        $v->isNotEmpty($device['type'], tr('Please specify a device type'));

        /*
         * Validate server
         */
        if(!$update){
            if($server){
                $dbserver = servers_get($server);

                if(!$dbserver){
                    $v->setError(tr('Specified server ":server" does not exist', array(':server' => $server)));
                }

                $device['servers_id'] = $dbserver['id'];
                $device['domain']     = $dbserver['domain'];
                $device['seodomain']  = $dbserver['seodomain'];

            }elseif($device['server']){
                $server = servers_get($device['server']);

                if(!$device['servers_id']){
                    $v->setError(tr('Specified server ":server" does not exist', array(':server' => $device['server'])));
                }

                $device['servers_id'] = $server['id'];
                $device['domain']     = $server['domain'];
                $device['seodomain']  = $server['seodomain'];

            }else{
                $device['servers_id'] = null;
                $device['domain']     = '-';
                $device['seodomain']  = '-';
            }
        }

        /*
         * Name
         */
        if(empty($device['name'])){
            $device['name'] = null;

        }else{
            $v->hasMinChars($device['name'],  2, tr('Please specify a device name of 2 characters or more'));
            $v->hasMaxChars($device['name'], 64, tr('Please specify a device name of maximum 64 characters'));

            $device['name'] = cfm($device['name']);
        }

        /*
         * Description
         */
        if(empty($device['description'])){
            $device['description'] = '';

        }else{
            $v->hasMaxChars($device['description'], 2047, tr('Please specifiy a maximum of 2047 characters for the description'));

            $device['description'] = cfm($device['description']);
        }

        /*
         * Validate category
         */
        if($device['category']){
           $category = categories_get($device['category'], 'id');

            if(!$category){
                $v->setError(tr('Specified category ":category" does not exist', array(':category' => $device['category'])));
            }

            $device['categories_id'] = $category['id'];
            $device['category']      = $category['name'];
            $device['seocategory']   = $category['seoname'];

        }else{
            $device['categories_id'] = null;
            $device['category']      = null;
            $device['seocategory']   = null;
        }

        /*
         * Validate customer
         */
        if($device['customer']){
            $customer = customers_get(array('columns' => 'id,name,seoname',
                                            'filters' => array('seoname' => $device['customer'])));

            if(!$customer){
                $v->setError(tr('Specified customer ":customer" does not exist', array(':customer' => $device['customer'])));
            }

            $device['customers_id'] = $customer['id'];
            $device['customer']     = $customer['name'];
            $device['seocustomer']  = $customer['seoname'];

        }else{
            $device['customers_id'] = null;
            $device['customer']     = null;
            $device['seocustomer']  = null;
        }

        /*
         * Validate provider
         */
        if($device['provider']){
           $provider = providers_get($device['provider'], 'id,name,seoname');

            if(!$provider){
                $v->setError(tr('Specified provider ":provider" does not exist', array(':provider' => $device['provider'])));
            }

            $device['providers_id'] = $provider['id'];
            $device['provider']     = $provider['name'];
            $device['seoprovider']  = $provider['seoname'];

       }else{
            $device['providers_id'] = null;
            $device['provider']     = null;
            $device['seoprovider']  = null;
        }

        /*
         * Validate inventory
         */
        if($device['inventory']){
           $inventory = inventories_get($device['inventory'], 'id,name,seoname');

            if(!$inventory){
                $v->setError(tr('Specified inventory key ":inventory" does not exist', array(':inventory' => $device['inventory'])));
            }

            $device['inventories_id'] = $inventory['id'];
            $device['inventory']      = $inventory['name'];
            $device['seoinventory']   = $inventory['seoname'];

        }else{
            $device['inventories_id'] = null;
            $device['inventory']      = null;
            $device['seoinventory']   = null;
        }

        /*
         * Validate company / branch / department / employee
         */
        if($device['company']){
            $company = companies_get($device['company'], 'id,name,seoname');

            if(!$company){
                $v->setError(tr('Specified company ":company" does not exist', array(':company' => $device['company'])));

                $device['companies_id']   = null;
                $device['company']        = null;
                $device['seocompany']     = null;
                $device['branches_id']    = null;
                $device['departments_id'] = null;

            }else{
                $device['companies_id'] = $company['id'];
                $device['company']      = $company['name'];
                $device['seocompany']   = $company['seoname'];

                /*
                 * Validate branch
                 */
                if($device['branch']){
                    $branch = companies_get_branch($device['companies_id'], $device['branch'], 'id,name,seoname');

                    if(!$branch){
                        $v->setError(tr('Specified branch ":branch" does not exist in company ":company"', array(':company' => $device['company'], ':branch' => $device['branch'])));

                        $device['branches_id']    = null;
                        $device['branch']         = null;
                        $device['seobranch']      = null;
                        $device['departments_id'] = null;
                        $device['employees_id']   = null;

                    }else{
                        $device['branches_id']    = $branch['id'];
                        $device['branch']         = $branch['name'];
                        $device['seobranch']      = $branch['seoname'];

                        /*
                         * Validate department
                         */
                        if($device['department']){
                            $department = companies_get_department($device['companies_id'], $device['branches_id'], $device['department'], 'id,name,seoname');

                            if(!$department){
                                $v->setError(tr('Specified department ":department" does not exist in company ":company"', array(':company' => $device['company'], ':department' => $device['department'])));

                                $device['departments_id'] = null;
                                $device['department']     = null;
                                $device['seodepartment']  = null;
                                $device['employees_id']   = null;

                            }else{
                                $device['departments_id'] = $department['id'];
                                $device['department']     = $department['name'];
                                $device['seodepartment']  = $department['seoname'];

                                /*
                                 * Validate employee
                                 */
                                if($device['employee']){
                                    $employee = companies_get_employee(array('columns' => 'id,name,seoname',
                                                                             'filters' => array('employees.companies_id'   => $device['companies_id'],
                                                                                                'employees.branches_id'    => $device['branches_id'],
                                                                                                'employees.departments_id' => $device['departments_id'],
                                                                                                'employees.seoname'        => $device['employee'])));

                                    if(!$employee){
                                        $v->setError(tr('Specified employee ":employee" does not exist in company ":company"', array(':company' => $device['company'], ':employee' => $device['employee'])));

                                        $device['employees_id'] = null;
                                        $device['employee']     = null;
                                        $device['seoemployee']  = null;

                                    }else{
                                        $device['employees_id'] = $employee['id'];
                                        $device['employee']     = $employee['name'];
                                        $device['seoemployee']  = $employee['seoname'];
                                    }

                                }else{
                                    $device['employees_id'] = null;
                                    $device['employee']     = null;
                                    $device['seoemployee']  = null;
                                }
                            }

                        }else{
                            $device['departments_id'] = null;
                            $device['department']     = null;
                            $device['seodepartment']  = null;
                            $device['employees_id']   = null;
                            $device['employee']       = null;
                            $device['seoemployee']    = null;
                        }
                    }
                }else{
                    $device['branches_id']    = null;
                    $device['branch']         = null;
                    $device['seobranch']      = null;
                    $device['departments_id'] = null;
                    $device['department']     = null;
                    $device['seodepartment']  = null;
                    $device['employees_id']   = null;
                    $device['employee']       = null;
                    $device['seoemployee']    = null;
                }
            }

        }else{
            /*
             * None of it!
             */
            if($device['branch']){
                $v->setError(tr('No company specified for branch ":branch"', array(':branch' => $device['branch'])));
            }

            if($device['department']){
                $v->setError(tr('No company specified for department ":department"', array(':department' => $device['department'])));
            }

            if($device['employee']){
                $v->setError(tr('No company specified for employee ":employee"', array(':employee' => $device['employee'])));
            }

            $device['companies_id']   = null;
            $device['company']        = null;
            $device['seocompany']     = null;
            $device['branches_id']    = null;
            $device['branch']         = null;
            $device['seobranch']      = null;
            $device['departments_id'] = null;
            $device['department']     = null;
            $device['seodepartment']  = null;
            $device['employees_id']   = null;
            $device['employee']       = null;
            $device['seoemployee']    = null;
        }

        $v->isValid();

        /*
         * Cleanup
         */
        $device['type']               = devices_validate_types($device['type']);
        $device['seostring']          = seo_string($device['string']);
        $device['seo_product_string'] = seo_string($device['product_string']);
        $device['description']        = str_replace('_', ' ', $device['description']);

        /*
         * Ensure no double default
         */
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

        $device['seoname'] = seo_unique($device['name'], 'devices', isset_get($device['id'], 0));

        return $device;

    }catch(Exception $e){
        throw new CoreException('devices_validate(): Failed', $e);
    }
}



/*
 * Update the status for the specified device to the specified status value
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 1.25.0: Added function and documentation
 *
 * @param
 * @param
 * @return
 */
function devices_set_status($status, $device = null){
    try{
        if(!$device){
            /*
             * Update all devices
             */
            $update = sql_query('UPDATE `devices` SET `status` = :status', array(':status' => $status));

        }elseif(is_numeric($device)){
            /*
             * Update device by id
             */
            $update = sql_query('UPDATE `devices` SET `status` = :status WHERE `id` = :id', array(':status' => $status, ':id' => $device));

        }else{
            /*
             * Update device by seostring
             */
            $update = sql_query('UPDATE `devices` SET `status` = :status WHERE `seostring` = :seostring', array(':status' => $status, ':seostring' => $device));
        }

        return $update->rowCount();

    }catch(Exception $e){
        throw new CoreException('devices_set_status(): Failed', $e);
    }
}



/*
 * Add options for a device
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
        if(!$options){
            /*
             * This device has no options
             */
            return 0;
        }

        $count  = 0;
        $insert = sql_prepare('INSERT INTO `drivers_options` (`devices_id`, `status`, `key`, `value`, `default`)
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
        throw new CoreException('devices_insert_options(): Failed', $e);
    }
}



/*
 * Return the available registered drivers for the specified seo_product_string
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
        throw new CoreException('devices_validate_options(): Failed', $e);
    }
}



/*
 * Return the available registered drivers for the specified seo_product_string
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
            $options = sql_query('SELECT `key`, `value`, `default` FROM `drivers_options` WHERE `devices_id` = :devices_id', array(':devices_id' => $devices_id));

        }else{
            $retval  = array();
            $options = sql_query('SELECT `key`, `value`, `default` FROM `drivers_options` WHERE `devices_id` = :devices_id AND `status` IS NULL', array(':devices_id' => $devices_id));
        }

        if(!$options){
            throw new CoreException(tr('devices_list_options(): Speficied drivers id ":id" does not exist', array(':id' => $devices_id)), 'not-exists');
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
        throw new CoreException('devices_list_options(): Failed', $e);
    }
}



/*
 * Return the available registered drivers for the specified seo_product_string
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 1.25.0: Added function and documentation
 *
 * @param
 * @param
 * @return
 */
function devices_list_option_keys($devices_id, $inactive = false){
    try{
        if($inactive){
            $retval  = array();
            $options = sql_query('SELECT `key`, `value`, `default` FROM `drivers_options` WHERE `devices_id` = :devices_id', array(':devices_id' => $devices_id));

        }else{
            $retval  = array();
            $options = sql_query('SELECT `key`, `value`, `default` FROM `drivers_options` WHERE `devices_id` = :devices_id AND `status` IS NULL', array(':devices_id' => $devices_id));
        }

        if(!$options){
            if($inactive){
                $exists = sql_get('SELECT `id` FROM `devices` WHERE `id` = :id', true, array(':id' => $devices_id));

            }else{
                $exists = sql_get('SELECT `id` FROM `devices` WHERE `id` = :id AND `status` IS NULL', true, array(':id' => $devices_id));
            }

            if($exists){
                throw new CoreException(tr('devices_list_options(): Speficied devices id ":id" does not have any device options', array(':id' => $devices_id)), 'not-exists');
            }

            throw new CoreException(tr('devices_list_options(): Speficied devices id ":id" does not exist', array(':id' => $devices_id)), 'not-exists');
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
        throw new CoreException('devices_list_option_keys(): Failed', $e);
    }
}



/*
 * Return an array with the available option values for the specified option key
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 2.4.7: Added function and documentation
 *
 * @param
 * @return
 */
function devices_list_option_values($devices_id, $key){
    try{
        array_ensure($params, '');

        if(empty($devices_id)){
            throw new CoreException(tr('devices_list_options(): No devices_id specified'), 'not-specified');
        }

        if(empty($key)){
            throw new CoreException(tr('devices_list_options(): No key specified for devices id ":id"', array(':id' => $devices_id)), 'not-specified');
        }

        $retval = sql_query('SELECT `value`, `value`, `default` FROM `drivers_options` WHERE `devices_id` = :devices_id AND `key` = :key', array(':devices_id' => $devices_id, ':key' => $key));

        return $retval;

    }catch(Exception $e){
        throw new CoreException('devices_list_option_values(): Failed', $e);
    }
}



/*
 * Return an array with the available option values for the specified option key
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 2.4.7: Added function and documentation
 *
 * @param params $params
 * @return
 */
function devices_get_option_html_element($params){
    global $core;

    try{
        array_ensure($params, '');
        array_default($params, 'key'       , '');
        array_default($params, 'devices_id', '');
        array_default($params, 'name'      , $params['key']);
        array_default($params, 'class'     , 'form-control');

        load_libs('numbers');

        $params['resource'] = devices_list_option_values($params['devices_id'], $params['key']);

        switch($params['resource']->rowCount()){
            case 0:
                throw new CoreException(tr('devices_get_option_html_element(): Speficied devices id ":id" does not have the key ":key"', array(':id' => $params['devices_id'], ':key' => $params['key'])), 'not-exists');

            case 1:
                /*
                 * Single entry, returns an input element
                 */
                $data        = sql_fetch($params['resource']);
                $data['min'] = str_until($data['value'], '..');
                $data['max'] = str_from($data['value'] , '..');

                switch($params['key']){
                    case 'x':
                        // FALLTHROUGH
                    case 'y':
                        // FALLTHROUGH
                    case 'l':
                        // FALLTHROUGH
                    case 't':
                        $data['step'] = '0.01';
                        break;

                    default:
                        $data['step'] = numbers_get_step($data['min'], $data['max'], $data['default']);
                }

                return '<input type="number" name="'.$params['name'].'" id="'.$params['name'].'" min="'.$data['min'].'" max="'.$data['max'].'" step="'.$data['step'].'" value="'.$data['default'].'" tabindex="'.html_tabindex().'" class="'.$params['class'].'">';

            default:
                /*
                 * Multiple entries, return an HTML select
                 */
                return html_select($params);
        }

        return $retval;

    }catch(Exception $e){
        throw new CoreException('devices_get_option_html_element(): Failed', $e);
    }
}



/*
 * Return the available registered drivers for the specified seo_product_string
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function devices_list($type, $all = false, $default_only = false){
    try{
        $execute = array();

        if($type){
            $type = devices_validate_types($type);
            $where[] = ' `devices`.`type` = :type ';
            $execute[':type'] = $type;
        }

        if($default_only){
            $where[] = ' `devices`.`default` = 1 ';
        }

        if(!$all){
            $where[] = ' `devices`.`status` IS NULL ';
        }

        if($where){
            $where = ' WHERE '.implode(' AND ', $where);
        }

        $devices = sql_query('SELECT    `devices`.`id`,
                                        `devices`.`meta_id`,
                                        `devices`.`status`,
                                        `devices`.`servers_id`,
                                        `devices`.`seo_product_string`,
                                        `devices`.`manufacturer`,
                                        `devices`.`type`,
                                        `devices`.`model`,
                                        `devices`.`vendor`,
                                        `devices`.`vendor_string`,
                                        `devices`.`product`,
                                        `devices`.`product_string`,
                                        `devices`.`seo_product_string`,
                                        `devices`.`libusb`,
                                        `devices`.`bus`,
                                        `devices`.`device`,
                                        `devices`.`seostring`,
                                        `devices`.`string`,
                                        `devices`.`default`,
                                        `devices`.`description`,
                                        `devices`.`name`,
                                        `devices`.`seoname`,

                                        `servers`.`domain`,
                                        `servers`.`seodomain`

                              FROM      `devices`

                              LEFT JOIN `servers`
                              ON        `servers`.`id` = `devices`.`servers_id`'.$where,

                              $execute);

        return $devices;

    }catch(Exception $e){
        throw new CoreException('devices_list(): Failed', $e);
    }
}



/*
 * Return the device with the specified device string
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
        if(is_numeric($device)){
            if(!is_natural($device)){
                throw new CoreException(tr('devices_get(): Invalid device ":device" specified', array(':device' => $device)), 'invalid');
            }

            $where = ' WHERE `devices`.`id` = :id ';
            $execute[':id'] = $device;

        }elseif(is_string($device)){
            load_libs('servers');

            $server = servers_get($server);
            $where  = ' WHERE `devices`.`seostring` = :seostring AND `devices`.`servers_id` = :servers_id ';

            $execute[':seostring']  = $device;
            $execute[':servers_id'] = $server['id'];

        }else{
            if(!$device){
                throw new CoreException(tr('devices_get(): No device specified'), 'not-specified');
            }

            throw new CoreException(tr('devices_get(): Invalid device ":device" specified', array(':device' => $device)), 'invalid');
        }

        $device = sql_get('SELECT    `devices`.`id`,
                                     `devices`.`meta_id`,
                                     `devices`.`servers_id`,
                                     `devices`.`categories_id`,
                                     `devices`.`companies_id`,
                                     `devices`.`branches_id`,
                                     `devices`.`departments_id`,
                                     `devices`.`employees_id`,
                                     `devices`.`customers_id`,
                                     `devices`.`providers_id`,
                                     `devices`.`inventories_id`,
                                     `devices`.`status`,
                                     `devices`.`type`,
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
                                     `devices`.`seostring`,
                                     `devices`.`default`,
                                     `devices`.`description`,
                                     `devices`.`name`,
                                     `devices`.`seoname`,

                                     `servers`.`ipv4`,
                                     `servers`.`ipv6`,
                                     `servers`.`domain`,
                                     `servers`.`seodomain`,

                                     `categories`.`name`  AS `category`,
                                     `companies`.`name`   AS `company`,
                                     `branches`.`name`    AS `branch`,
                                     `departments`.`name` AS `department`,

                                     `categories`.`seoname`  AS `category`,
                                     `companies`.`seoname`   AS `company`,
                                     `branches`.`seoname`    AS `branch`,
                                     `departments`.`seoname` AS `department`

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
        throw new CoreException('devices_get(): Failed', $e);
    }
}



/*
 * Return the default device for the requested product
 *
 * This function returns a device of the specified seo_product_string which suits the current user and (if specified) category best
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
        array_ensure($params);
        array_default($params, 'name'       , 'seodevice');
        array_default($params, 'class'      , 'form-control');
        array_default($params, 'selected'   , null);
        array_default($params, 'seocategory', null);
        array_default($params, 'type'       , false);
        array_default($params, 'status'     , null);
        array_default($params, 'empty'      , tr('No devices available'));
        array_default($params, 'none'       , tr('Select a device'));
        array_default($params, 'orderby'    , '`name`');

        if($params['seocategory']){
            load_libs('categories');
            $params['categories_id'] = categories_get($params['seocategory'], 'id');

            if(!$params['categories_id']){
                throw new CoreException(tr('devices_select(): The reqested category ":category" does exist, but is deleted', array(':category' => $params['seocategory'])), 'deleted');
            }
        }

        if($params['type'] !== false){
            $where[] = ' `type` '.sql_is($params['type'], ':type');
            $execute[':type'] = $params['type'];
        }

        if($params['status'] !== false){
            $where[] = ' `status` '.sql_is($params['status'], ':status');
            $execute[':status'] = $params['status'];
        }

        if(empty($where)){
            $where = '';

        }else{
            $where = ' WHERE '.implode(' AND ', $where).' ';
        }

        $query              = 'SELECT `seostring`, `string` FROM `devices` '.$where.' ORDER BY '.$params['orderby'];
        $params['resource'] = sql_query($query, $execute, 'core');
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new CoreException('devices_select(): Failed', $e);
    }
}



/*
 * Clear all devices from the database
 *
 * This function will erase all devices from the `devices` table. If $type is specified, only the
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 2.4.15: Upgraded function and documentation
 *
 * @param null string $type The type of devices to erase. If not specified, all devices will be erased
 * @return natural The amount of erased devices
 */
function devices_clear($type = null){
    try{
        if($type){
            $erase = sql_query('DELETE FROM `devices` WHERE `type` = :type', array(':type' => $type));

        }else{
            $erase = sql_query('DELETE FROM `devices`');
        }

        return $erase->rowCount();

    }catch(Exception $e){
        throw new CoreException('devices_clear(): Failed', $e);
    }
}



/*
 * Scan one or multiple servers for connected hardware devices and return a list of all these devices
 *
 * The
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @version 1.25.0: Added function and documentation
 *
 * @param
 * @param
 * @return
 */
function devices_scan($types, $server = null, $sudo = false){
    global $_CONFIG;

    try{
        load_libs('servers');

        $sudo = ($sudo or $_CONFIG['devices']['sudo']);

        if($server === null){
            /*
             * Scan all registered servers
             */
            $retval  = array();
            $servers = servers_list(true);

            while($server = sql_fetch($servers)){
                try{
                    log_console(tr('Scanning server ":server" for devices', array(':server' => $server['domain'])), 'VERBOSE/cyan');
                    $devices = devices_scan($types, $server['id'], $sudo);

                    if($devices){
                        $retval[$server['id']] = $devices[$server['id']];
                    }

                }catch(Exception $e){
                    log_console(tr('Failed to scan server ":server" for devices because ":e"', array(':server' => $server['domain'], ':e' => $e)), 'yellow');
                }
            }

            return $retval;
        }

        if(str_exists($server, ',')){
            /*
             * A specific list of servers was specified
             */
            $servers = array_force($server);
            $retval  = array();

            foreach($servers as $server){
                log_console(tr('Scanning server ":server" for devices', array(':server' => $server['domain'])), 'VERBOSE/cyan');
                $devices = devices_scan($types, $server['id'], $sudo);

                if($devices){
                    $retval[$server['id']] = $devices[$server['id']];
                }
            }

            return $retval;
        }

        /*
         * Scan this specific server
         */
        $server            = servers_like($server);
        $server            = servers_get($server);
        $server['persist'] = true;
        $servers_id        = $server['id'];
        $retval            = array();
        $types             = array_force($types);
        $types             = devices_validate_types($types, true);

        foreach(array_force($types) as $type => $filter){
            log_console(tr('Scanning server ":server" for ":type" type devices', array(':server' => $server['domain'], ':type' => $type)), 'VERBOSE/cyan');

            switch($type){
                case 'usb':
                    // FALLTHROUGH
                case 'fingerprint-reader':
                    // FALLTHROUGH
                case 'barcode-scanner':
                    // FALLTHROUGH
                case 'webcam':
                    $devices = servers_exec($server, array('ok_exitcodes' => '0,1',
                                                           'commands'     => array('lsusb', array('sudo' => $sudo, 'connector' => '|'),
                                                                                   'grep' , array('-i', $filter))));
                    $entries = array();

                    foreach($devices as $device){
                        $found = preg_match_all('/Bus (\d+) Device (.+?): ID ([0-9a-f]{4}:[0-9a-f]{4}) (.+)/i', $device, $matches);

                        if(!$found){
                            continue;
                        }

                        log_console(tr('Found device ":device" on server ":server"', array(':device' => $device, ':server' => $server['domain'])), 'green');

                        $entry = array('manufacturer'   => null,
                                       'product'        => null,
                                       'product_string' => null,
                                       'vendor'         => null,
                                       'vendor_string'  => null,
                                       'type'           => $type,
                                       'raw'            => $matches[0][0],
                                       'device'         => $matches[2][0],
                                       'bus'            => $matches[1][0],
                                       'string'         => $matches[3][0],
                                       'description'    => $matches[4][0]);

                        $data = servers_exec($server, array('commands' => array('lsusb', array('sudo' => $sudo, '-vs', $entry['bus'].':'.$entry['device']))));

                        foreach($data as $line){
                            if(stristr($line, 'idProduct')){
                                $entry['product']        = str_from($line             , '0x');
                                $entry['product_string'] = str_from($entry['product'] , ' ');
                                $entry['product']        = str_until($entry['product'], ' ');
                            }

                            if(stristr($line, 'idVendor')){
                                $entry['vendor']        = str_from($line            , '0x');
                                $entry['vendor_string'] = str_from($entry['vendor'] , ' ');
                                $entry['vendor']        = str_until($entry['vendor'], ' ');
                            }

                            if(stristr($line, 'idManufacturer')){
                                $entry['manufacturer'] = str_from($line, 'idManufacturer');
                            }
                        }

                        $entries[] = $entry;
                    }

                    if($entries){
                        $retval[$servers_id] = array_merge(isset_get($retval[$servers_id], array()), $entries);
                    }

                    break;

                case 'document-scanner':
                    load_libs('scanimage');

                    /*
                     * Only scan for scanners if scanimage has been installed on
                     * the taget server
                     */
                    if(linux_which($server, 'scanimage')){
                        $devices = scanimage_detect_devices($server, $sudo);
                        $entries = array();

                        foreach($devices as $device){
                            log_console(tr('Found document-scanner device ":device" on server ":server"', array(':device' => $device['raw'], ':server' => $server['domain'])), 'green');
                        }

                        if($devices){
                            $retval[$servers_id] = array_merge(isset_get($retval[$servers_id], array()), $devices);
                        }
                    }

                    break;

                default:
                    throw new CoreException(tr('devices_scan(): Unknown device type ":type" specified', array(':type' => $types)), 'unknown');
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new CoreException('devices_scan(): Failed', $e);
    }
}



/*
 * Validate the given device type or devices type list and ensures it is supported
 *
 * Currently supported devices are "fingerprint-reader" and "scanner"
 *
 * If part of a device type is specified, this will match and the function will update the given device type part string to reflect the correct device type. If the specified part matches multiple supported devices, an exception will be thrown. If the given device type (part) does not match any supported devices, an exception will be thrown
 *
 * If in stead of one single device type a list of device types is specified in an array, the same rules apply, but on each entry in the array
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package devices
 * @see devices_scan()
 * @version 2.4.0: Added function and documentation
 * @example [Title]
 * code
 * $result = devices_validate_types(array('scan' => 'fing'));
 * showdie($result);
 * /code
 *
 * This would return
 * code
 * array('scanner', 'fingerprint-reader')
 * /code
 *
 * @param string|array $types The device type or multiple device types to validate
 * @return string|array The device types, validated and sanitized
 */
function devices_validate_types($types = null, $return_filters = false){
    static $supported = array('fingerprint-reader' => 'fingerprint',
                              'document-scanner'   => '',
                              'barcode-scanner'    => 'barcode',
                              'webcam'             => 'webcam');

    try{
        if($types){
            /*
             * Device types list specified. Compare them all to the supported types
             */
            if(is_array($types)){
                foreach($types as $key => &$type){
                    if(!is_string($type)){
                        throw new CoreException(tr('devices_validate_types(): Specified device type list is invalid. Key ":key" should be a string but is an ":type" instead', array(':key' => $key, ':type' => gettype($type))), 'invalid');
                    }

                    $retval[devices_validate_types($type)] = devices_validate_types($type, $return_filters);
                }

                unset($type);
                return $retval;
            }

            /*
             * Single type specified. Compare to the supported types.
             */
            if(is_string($types)){
                foreach($supported as $support => $filter){
                    if(str_exists($support, $types)){
                        if(isset($match)){
                            throw new CoreException(tr('devices_validate_types(): Specified device type ":type" matches multiple supported devices', array(':type' => $types)), 'multiple');
                        }

                        if($return_filters){
                            $match = $filter;

                        }else{
                            $match = $support;
                        }
                    }
                }

                if(!isset($match)){
                    throw new CoreException(tr('devices_validate_types(): Specified device type ":type" does not match any of the supported devices', array(':type' => $types)), 'not-exists');
                }

                return $match;
            }

            /*
             * Specified device type is neither string nor array
             */
            throw new CoreException(tr('devices_validate_types(): Invalid device type or list of device types specified. Expected a string or array, but got an ":type" instead', array(':type' => gettype($types))), 'invalid');
        }

        /*
         * No type list specified, return all supported devices
         */
        return $supported;

    }catch(Exception $e){
        throw new CoreException('devices_validate_types(): Failed', $e);
    }
}
?>
