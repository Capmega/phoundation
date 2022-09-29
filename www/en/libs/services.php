<?php
/*
 * Services library
 *
 * This library manages what services run on what servers that are registered in the servers library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package services
 * @see package servers
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
 * @package services
 *
 * @return void
 */
function services_library_init() {
    try{
        load_libs('servers');
        load_config('services');

    }catch(Exception $e) {
        throw new CoreException('services_library_init(): Failed', $e);
    }
}



/*
 * Scan what services are ran by the specified server, and register it in the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package services
 *
 * @param mixed $server:
 * @return natural The amount of scanned servers
 */
function services_scan($server = null) {
    try{
        if(!$server) {
            /*
             * Scan ALL servers
             */
            $domains = sql_query('SELECT `domain` FROM `servers` WHERE `status` IS NULL');

            while($domain = sql_fetch($domains, true)) {
                $count++;
                services_scan($domain);
            }

            return $count++;
        }

        /*
         * Scan the server
         */
        $server   = servers_get($server);
        $services = services_list();

        foreach($services as $service) {
            /*
             * Scan for csf
             */

            /*
             * Scan for apache
             */

            /*
             * Scan for nginx
             */

            /*
             * Scan for mysql
             */

            /*
             * Scan for postgresql
             */

            /*
             * Scan for (open)ldap
             */

            /*
             * Scan for (free)radius
             */

            /*
             * Scan for php
             */

            /*
             * Scan for memcached
             */

            /*
             * Scan for virtualizor
             */

            /*
             * Scan for toolkit
             */

            /*
             * Scan for phoundation based websites
             */

            /*
             * Scan for wordpress based sites
             */

            /*
             * Scan for serverangel
             */

            /*
             * Scan for nextcloud
             */
            $results = servers_exec();
        }

        services_update_server($server, $services);
        return 1;

    }catch(Exception $e) {
        throw new CoreException('services_scan(): Failed', $e);
    }
}



/*
 * Validate the specified service
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package services
 *
 * @param params $service
 * @return params The specified service array
 */
function services_validate($service) {
    try{
        load_libs('validate,seo');

        $v = new ValidateForm($service, 'name');

        $v->isNotEmpty($service['name'], tr('Please specifiy a name'));
        $v->hasMinChars($service['name'], 3, tr('Please specifiy a minimum of 3 characters for the name'));
        $v->hasMaxChars($service['name'], 32, tr('Please specifiy a maximum of 32 characters for the name'));

        /*
         * Description
         */
        if(empty($service['description'])) {
            $service['description'] = '';

        } else {
            $v->hasMinChars($service['description'],   16, tr('Please specifiy a minimum of 16 characters for the description'));
            $v->hasMaxChars($service['description'], 2047, tr('Please specifiy a maximum of 2047 characters for the description'));

            $service['description'] = cfm($service['description']);
        }

        $v->isValid();

        return $service;

    }catch(Exception $e) {
        throw new CoreException('services_validate(): Failed', $e);
    }
}



/*
 * Insert a new service in the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package services
 * @see services_update()
 * @version 1.27.0: Implemented function and added documentation
 *
 * @param params $service
 * @return params The specified service array
 */
function services_insert($service) {
    try{
        $service = services_validate($service);

        sql_query('INSERT INTO `services` (`createdby`, `meta_id`, `name`, `seoname`, `description`)
                   VALUES                 (:createdby , :meta_id , :name , :seoname , :description )',

                   array('createdby'   => isset_get($_SESSION['user']['id']),
                         'meta_id'     => meta_action(),
                         'name'        => $service['name'],
                         'seoname'     => $service['seoname'],
                         'description' => $service['description']));

        $servi['id'] = sql_insert_id();

        return $service;

    }catch(Exception $e) {
        throw new CoreException('services_insert(): Failed', $e);
    }
}



/*
 * Update an existing service in the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package services
 * @see services_insert()
 * @version 1.27.0: Implemented function and added documentation
 *
 * @param params $service
 * @return params The specified service array
 */
function services_update($service) {
    try{
        $service = services_validate($service);
        meta_action($service['meta_id'], 'update');

        sql_query('UPDATE `services`

                   SET    `name`        = :name,
                          `seoname`     = :seoname,
                          `description` = :description,

                   WHERE  `id`          = :id',

                   array(':id'          =>  $service['id'],
                         ':domain'      =>  $service['domain'],
                         ':seodomain'   =>  $service['seodomain'],
                         ':description' =>  $service['description']));

        return $service;

    }catch(Exception $e) {
        throw new CoreException('services_update(): Failed', $e);
    }
}



/*
 * Set the specified services for the specified server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package services
 *
 * @param params $server
 * @param array $services
 * @return natural The amount of services set for the specified server
 */
function services_update_server($service) {
    try{

    }catch(Exception $e) {
        throw new CoreException('services_update_server(): Failed', $e);
    }
}



/*
 * Get and return all database information for the specified service
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package services
 * @see services_insert()
 * @version 1.27.0: Implemented function and added documentation
 *
 * @param params $service
 * @return params The specified service array
 */
function services_get($service, $column = null, $status = null) {
    try{
        if(is_numeric($service)) {
            $where[] = ' `services`.`id` = :id ';
            $execute[':id'] = $service;

        } else {
            $where[] = ' `services`.`seoname` = :seoname ';
            $execute[':seoname'] = $service;
        }

        if($status !== false) {
            $execute[':status'] = $status;
            $where[] = ' `services`.`status` '.sql_is($status, ':status');
        }

        $where   = ' WHERE '.implode(' AND ', $where).' ';

        if($column) {
            $retval = sql_get('SELECT `'.$column.'` FROM `services` '.$where, true, $execute, 'core');

        } else {
            $retval = sql_get('SELECT    `services`.`id`,
                                         `services`.`createdon`,
                                         `services`.`createdby`,
                                         `services`.`meta_id`,
                                         `services`.`status`,
                                         `services`.`name`,
                                         `services`.`seoname`,
                                         `services`.`description`,

                               FROM      `services` '.

                               $where,

                               $execute, null, 'core');
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('services_get(): Failed', $e);
    }
}



/*
 * Clear all services for the specified server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package services
 * @see services_get()
 * @version 1.27.0: Implemented function and added documentation
 *
 * @param mixed $server The server for which all services must be cleared. May be specified by id, domain, or server array
 * @return natural The amount of services that were cleared for the specified server
 */
function services_clear($server) {
    try{
        if($server) {
            $server = servers_get($server);
            $r      = sql_query('DELETE FROM `services_servers`
                                 WHERE       `servers_id` = :servers_id',

                                 array(':servers_id' => $server['id']));
        } else {
            $r      = sql_query('DELETE FROM `services_servers`');
        }

        return $r->rowCount();

    }catch(Exception $e) {
        throw new CoreException('services_clear(): Failed', $e);
    }
}



/*
 * Return HTML for a services select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available services
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @provider Function reference
 * @package services
 * @see html_select()
 *
 * @param array $params The parameters required
 * @param $params name
 * @param $params class
 * @param $params empty
 * @param $params none
 * @param $params selected
 * @param $params parents_id
 * @param $params status
 * @param $params orderby
 * @param $params resource
 * @return string HTML for a services select box within the specified parameters
 */
function services_select($params = null) {
    try{
        array_ensure($params);
        array_default($params, 'name'    , 'seoservice');
        array_default($params, 'class'   , 'form-control');
        array_default($params, 'selected', null);
        array_default($params, 'status'  , null);
        array_default($params, 'empty'   , tr('No services available'));
        array_default($params, 'none'    , tr('Select a service'));
        array_default($params, 'orderby' , '`name`');

        if($params['status'] !== false) {
            $where[] = ' `status` '.sql_is($params['status'], ':status');
            $execute[':status'] = $params['status'];
        }

        if(empty($where)) {
            $where = '';

        } else {
            $where = ' WHERE '.implode(' AND ', $where).' ';
        }

        $query              = 'SELECT `seoname`, `name` FROM `services` '.$where.' ORDER BY '.$params['orderby'];
        $params['resource'] = sql_query($query, $execute, 'core');
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('services_select(): Failed', $e);
    }
}



/*
 * Return an array or PDO resource with domains of all servers that supply the requested service
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @provider Function reference
 * @package services
 * @see services_insert()
 * @see services_list()
 *
 * @param string $service The service that must be filtered on
 * @param null string $domain A (part of a) domain name that should be matched as well
 * @return array An array with all domain names that matches the requested type (and optionally $domain)
 */
function services_list_servers($service, $domain = null, $return_array = false) {
    try{
        if($domain) {
            $where   = ' WHERE `services`.`seoname` = :seoname';
            $execute = array(':seoname' => $service);

        } else {
            $where   = ' WHERE `services`.`seoname` = :seoname
                         AND   `servers`.`domain`   = :domain';

            $execute = array(':seoname' => $service,
                             ':domain'  => $domain);
        }

        $retval = sql_query('SELECT   `servers`.`seodomain`,
                                      `servers`.`domain`

                             FROM     `services`

                             JOIN     `services_servers`
                             ON       `services_servers`.`servers_id` = `services`.`id`

                             JOIN     `servers`
                             ON       `servers`.`id`                  = `services_servers`.`servers_id`

                             '.$where.'

                             ORDER BY `servers`.`seodomain` ASC',

                             $execute);

        if($return_array) {
            return sql_list($retval);
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('services_list_servers(): Failed', $e);
    }
}
?>
