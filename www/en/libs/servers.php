<?php
/*
 * Servers library
 *
 * This library contains functions to manage registered servers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the servers library configuration
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @return void
 */
function servers_library_init(){
    try{
        load_libs('ssh,domains');
        load_config('servers');

    }catch(Exception $e){
        throw new BException('servers_library_init(): Failed', $e);
    }
}



/*
 * Validate the specified server. In case $structure_only is specified, only the array keys will be ensured available. If $password_strength is specified true, the specified passwords will be tested for strength as well.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param array $server
 * @param boolean $structure_only
 * @param boolean $password_strength
 * @return array
 */
function servers_validate($server, $structure_only = false, $password_strength = false){
    global $_CONFIG;

    try{
        load_libs('validate,seo,customers,providers');

        $v = new ValidateForm($server, 'id,ipv4,ipv6,port,domain,domains,seoprovider,seocustomer,ssh_account,description,ssh_proxy,database_accounts_id,bill_duedate,cost,interval,allow_sshd_modification,register');

        if($structure_only){
            return $server;
        }

        /*
         * Check password
         */
        if($password_strength){
            $v->isPassword($server['db_password'], tr('Please specifiy a strong password'), '');
        }

        if($server['database_accounts_id']){
            $exists = sql_get('SELECT `id` FROM `database_accounts` WHERE `id` = :id', true, array(':id' => $server['database_accounts_id']), 'core');

            if(!$exists){
                $v->setError(tr('The specified database account does not exist'));
            }

        }else{
            $server['database_accounts_id'] = null;
        }

        /*
         * Domain
         */
        $v->isNotEmpty($server['domain'], tr('Please specifiy a domain'));
        $v->isDomain($server['domain'], tr('The domain ":domain" is invalid', array(':domain' => $server['domain'])));

        if(!empty($server['url']) and !FORCE){
            $v->setError(tr('Both domain ":domain" and URL ":url" specified, please specify one or the other', array(':domain' => $server['domain'], ':url' => $server['url'])));

        }elseif(!preg_match('/[a-z0-9][a-z0-9-.]+/', $server['domain'])){
            $v->setError(tr('Invalid server specified, be sure it contains only a-z, 0-9, . and -'));
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
         * IPv4 check
         */
        if($server['ipv4']){
            /*
             * IP was specified manually
             */
            $v->isFilter($server['ipv4'], FILTER_VALIDATE_IP, tr('Please specify a valid IP address'));

        }else{
            /*
             * IP not specified, try to lookup
             */
            $server['ipv4'] = gethostbynamel($server['domain']);

            if(!$server['ipv4']){
                $server['ipv4'] = null;

            }else{
                if(count($server['ipv4']) == 1){
                    $server['ipv4'] = array_shift($server['ipv4']);

                }else{
                    $v->isFilter($server['ipv4'], FILTER_VALIDATE_IP, tr('Failed to auto lookup IPv4, please specify a valid IP address'));
                }
            }
        }

        /*
         * Port check
         */
        if(empty($server['port'])){
            $server['port'] = ssh_get_port();
            log_console(tr('servers_validate(): No SSH port specified, using port ":port" as default', array(':port' => $server['port'])), 'yellow');
        }

        if(!is_numeric($server['port']) or ($server['port'] < 1) or ($server['port'] > 65535)){
            $v->setError(tr('Specified port ":port" is not valid', array(':port' => $server['port'])));
        }

        $server['allow_sshd_modification'] = (boolean) $server['allow_sshd_modification'];

        if($server['domains']){
            $server['domains'] = array_force($server['domains'], "\n");

            foreach($server['domains'] as &$domain){
                $domain = trim($domain);
                $v->isDomain($domain, tr('The domain ":domain" is invalid', array(':domain' => $domain)));

                $domain = domains_ensure($domain, 'domain');
            }

            $v->isValid();

            $server['domains'][] = domains_ensure($server['domain'], 'domain');
            $server['domains']   = array_unique($server['domains']);

        }else{
            /*
             * The current domain is all the domains registered for this server
             */
            $server['domains'] = array($server['domain']);
        }

        $v->isScalar($server['seoprovider'], tr('Please specify a valid provider')   , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isScalar($server['seocustomer'], tr('Please specify a valid customer')   , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isScalar($server['ssh_account'], tr('Please specify a valid SSH account'), VALIDATE_ALLOW_EMPTY_NULL);

        $v->isValid();

        /*
         * Validate provider, customer, and ssh account
         */
        if($server['seoprovider']){
            $server['providers_id'] = sql_get('SELECT `id` FROM `providers` WHERE `seoname` = :seoname AND `status` IS NULL', true, array(':seoname' => $server['seoprovider']), 'core');

            if(!$server['providers_id']){
                $v->setError(tr('Specified provider ":provider" does not exist', array(':provider' => $server['seoprovider'])));
            }

        }else{
            $server['providers_id'] = null;
            //$v->setError(tr('Please specify a provider'));
        }

        if($server['seocustomer']){
            $server['customers_id'] = sql_get('SELECT `id` FROM `customers` WHERE `seoname` = :seoname AND `status` IS NULL', true, array(':seoname' => $server['seocustomer']), 'core');

            if(!$server['customers_id']){
                $v->setError(tr('Specified customer ":customer" does not exist', array(':customer' => $server['seocustomer'])));
            }

        }else{
            $server['customers_id'] = null;
        }

        if($server['ssh_account']){
            $server['ssh_accounts_id'] = sql_get('SELECT `id` FROM `ssh_accounts` WHERE `seoname` = :seoname AND `status` IS NULL', true, array(':seoname' => $server['ssh_account']), 'core');

            if(!$server['ssh_accounts_id']){
                $v->setError(tr('Specified SSH account ":account" does not exist', array(':account' => $server['ssh_account'])));
            }

        }else{
            $server['ssh_accounts_id'] = null;
        }

        /*
         * Already exists?
         */
        $exists = sql_get('SELECT `id` FROM `servers` WHERE `domain` = :domain AND `id` != :id LIMIT 1', true, array(':domain' => $server['domain'], ':id' => isset_get($server['id'], 0)), 'core');

        if($exists){
            $v->setError(tr('A server with domain ":domain" already exists', array(':domain' => $server['domain'])));
        }

        $server['seodomain']    = seo_unique($server['domain'], 'servers', isset_get($server['id']), 'seodomain');
        $server['bill_duedate'] = date_convert($server['bill_duedate'], 'mysql');

        $v->isValid();

        return $server;

    }catch(Exception $e){
        throw new BException('servers_validate(): Failed', $e);
    }
}



/*
 * Inserts a new server in the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @provider Function reference
 * @package servers
 *
 * @param params $server The server data to be inserted into the database
 * @return params The validated server data, including server[id]
 */
function servers_insert($server){
    try{
        $server = servers_validate($server);

        sql_query('INSERT INTO `servers` (`createdby`, `meta_id`, `status`, `domain`, `seodomain`, `port`, `database_accounts_id`, `bill_duedate`, `cost`, `interval`, `providers_id`, `customers_id`, `ssh_accounts_id`, `allow_sshd_modification`, `description`, `ipv4`)
                   VALUES                (:createdby , :meta_id , :status , :domain , :seodomain , :port , :database_accounts_id , :bill_duedate , :cost , :interval , :providers_id , :customers_id , :ssh_accounts_id , :allow_sshd_modification , :description , :ipv4)',

                   array(':status'                  => ($server['ssh_accounts_id'] ? 'testing' : null),
                         ':createdby'               => isset_get($_SESSION['user']['id']),
                         ':meta_id'                 => meta_action(),
                         ':domain'                  => $server['domain'],
                         ':seodomain'               => $server['seodomain'],
                         ':port'                    => $server['port'],
                         ':database_accounts_id'    => $server['database_accounts_id'],
                         ':cost'                    => $server['cost'],
                         ':interval'                => $server['interval'],
                         ':bill_duedate'            => $server['bill_duedate'],
                         ':providers_id'            => $server['providers_id'],
                         ':customers_id'            => $server['customers_id'],
                         ':ssh_accounts_id'         => $server['ssh_accounts_id'],
                         ':allow_sshd_modification' => $server['allow_sshd_modification'],
                         ':description'             => $server['description'],
                         ':ipv4'                    => $server['ipv4']), 'core');

        $server['id'] = sql_insert_id('core');

        log_console(tr('Inserted server ":domain" with id ":id"', array(':domain' => $server['domain'], ':id' => $server['id'])), 'VERBOSE/green');

        if($server['register']){
            ssh_add_known_host($server['domain'], $server['port']);
        }

        servers_update_domains($server, $server['domains']);
        return $server;

    }catch(Exception $e){
        throw new BException('servers_insert(): Failed', $e);
    }
}



/*
 * Erase the specified server from the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @provider Function reference
 * @package servers
 *
 * @param mixed server
 * @return
 */
function servers_erase($server){
    try{
        $server = servers_get($server);

        ssh_remove_known_host($server['domain']);
        servers_update_domains($server);
        servers_remove_domain($server);
        servers_unregister_host($server);

        sql_query('DELETE FROM `servers` WHERE `id` = :id', array(':id' => $server['id']), 'core');

        return $server;

    }catch(Exception $e){
        throw new BException('servers_insert(): Failed', $e);
    }
}



/*
 * Updates the specified server in the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @provider Function reference
 * @package servers
 *
 * @param params $server The server data to be updated into the database
 * @return params The validated server data
 */
function servers_update($server){
    try{
        $server = servers_validate($server);
        meta_action($server['meta_id'], 'update');

        sql_query('UPDATE `servers`

                   SET    `status`                  = :status,
                          `domain`                  = :domain,
                          `seodomain`               = :seodomain,
                          `port`                    = :port,
                          `database_accounts_id`    = :database_accounts_id,
                          `cost`                    = :cost,
                          `interval`                = :interval,
                          `bill_duedate`            = :bill_duedate,
                          `providers_id`            = :providers_id,
                          `customers_id`            = :customers_id,
                          `ssh_accounts_id`         = :ssh_accounts_id,
                          `allow_sshd_modification` = :allow_sshd_modification,
                          `description`             = :description,
                          `ipv4`                    = :ipv4

                   WHERE  `id`                      = :id',

                   array(':id'                      =>  $server['id'],
                         ':status'                  => ($server['ssh_accounts_id'] ? 'testing' : null),
                         ':domain'                  =>  $server['domain'],
                         ':seodomain'               =>  $server['seodomain'],
                         ':port'                    =>  $server['port'],
                         ':database_accounts_id'    =>  $server['database_accounts_id'],
                         ':cost'                    =>  $server['cost'],
                         ':interval'                =>  $server['interval'],
                         ':bill_duedate'            =>  $server['bill_duedate'],
                         ':providers_id'            =>  $server['providers_id'],
                         ':customers_id'            =>  $server['customers_id'],
                         ':ssh_accounts_id'         =>  $server['ssh_accounts_id'],
                         ':allow_sshd_modification' =>  $server['allow_sshd_modification'],
                         ':description'             =>  $server['description'],
                         ':ipv4'                    =>  $server['ipv4']), 'core');

        log_console(tr('Updated server ":domain" with id ":id"', array(':domain' => $server['domain'], ':id' => $server['id'])), 'VERBOSE/green');
        servers_update_domains($server, $server['domains']);
        return $server;

    }catch(Exception $e){
        throw new BException('servers_update(): Failed', $e);
    }
}



/*
 * Returns an array with all servers that are like the specified domain
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @provider Function reference
 * @package servers
 *
 * @param string $domain The domain section that is being searched for
 * @return string The domain of the found server
 */
function servers_like($domain){
    try{
        if(!$domain){
            if(($domain === '') or ($domain === null)){
                /*
                 * "" server is the localdomain server
                 * null means no server
                 */
                return $domain;
            }
        }

        if(is_array($domain)){
            return $domain;
        }

        if(is_numeric($domain)){
            $server = sql_get('SELECT `domain`

                               FROM   `servers`

                               WHERE  `id` = :id',

                               true, array(':id' => $domain), 'core');

        }else{
            $server = sql_get('SELECT `domain`

                               FROM   `servers`

                               WHERE  `ipv4`      LIKE :ipv4
                               OR     `domain`    LIKE :domain
                               OR     `seodomain` LIKE :seodomain',

                               true, array(':ipv4'      => '%'.$domain.'%',
                                           ':domain'    => '%'.$domain.'%',
                                           ':seodomain' => '%'.$domain.'%'), 'core');
        }

        if($server === null){
            /*
             * Specified server not found in the default servers list, try domains list
             */
            $server = sql_get('SELECT `servers`.`domain`

                               FROM   `domains`

                               JOIN   `domains_servers`
                               ON    (`domains`.`domain` LIKE :domain OR `domains`.`seodomain` LIKE :seodomain)
                               AND    `domains_servers`.`domains_id` = `domains`.`id`

                               JOIN   `servers`
                               ON     `servers`.`id` = `domains_servers`.`servers_id`',

                               true, array(':domain'    => '%'.$domain.'%',
                                           ':seodomain' => '%'.$domain.'%'), 'core');

            if(!$server){
                throw new BException(tr('servers_like(): Specified server ":server" does not exist', array(':server' => $domain)), 'not-exists');
            }
        }

        return $server;

    }catch(Exception $e){
        throw new BException('servers_like(): Failed', $e);
    }
}



/*
 * Return HTML for a servers select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available servers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @provider Function reference
 * @package servers
 * @see html_select()
 *
 * @param array $params The parameters required
 * @param string $params[name]
 * @param class
 * @param extra
 * @param none
 * @param selected
 * @param parents_id
 * @param status
 * @param orderby
 * @param resource
 * @return string HTML for a servers select box within the specified parameters
 */
function servers_select($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'    , 'seoserver');
        array_default($params, 'class'   , 'form-control');
        array_default($params, 'selected', null);
        array_default($params, 'status'  , null);
        array_default($params, 'empty'   , tr('No servers available'));
        array_default($params, 'none'    , tr('Select a server'));
        array_default($params, 'orderby' , '`domain`');

        if($params['status'] !== false){
            $where[] = ' `status` '.sql_is($params['status'], ':status');
            $execute[':status'] = $params['status'];
        }

        if(empty($where)){
            $where = '';

        }else{
            $where = ' WHERE '.implode(' AND ', $where).' ';
        }

        $query              = 'SELECT `seodomain`, CONCAT(`domain`, " (", `ipv4`, ")") AS `name` FROM `servers` '.$where.' ORDER BY '.$params['orderby'];
        $params['resource'] = sql_query($query, $execute, 'core');
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new BException('servers_select(): Failed', $e);
    }
}



/*
 * Update the domains list for the specified server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param mixed $server The server for which the specified domains should be linked. May be specified by id, domain, seodomain, or servers array
 * @param array $domains The domains which will be linked to the specified server. May be specified by id, domain, seodomain, or domains array
 * @return The amount of domains added for the server
 */
function servers_update_domains($server, $domains = null){
    try{
        $servers_id = servers_get_id($server);

        sql_query('DELETE FROM `domains_servers` WHERE `servers_id` = :servers_id', array(':servers_id' => $servers_id), 'core');

        if(empty($domains)){
            return false;
        }

        $insert = sql_prepare('INSERT INTO `domains_servers` (`createdby`, `meta_id`, `domains_id`, `servers_id`)
                               VALUES                        (:createdby , :meta_id , :domains_id , :servers_id )', 'core');

        foreach($domains as $domain){
            /*
             * Get the $domains_id. If the domain doesn't exist, auto add it.
             */
            $domains_id = domains_get_id($domain);

            if(!$domains_id){
                $domain = domains_insert(array('domain'       => $server['domain'],
                                               'seodomain'    => $server['seodomain'],
                                               'customers_id' => $server['customers_id'],
                                               'providers_id' => $server['providers_id']));

                $domains_id = $domain['id'];
            }

            $insert->execute(array(':meta_id'    => meta_action(),
                                   ':createdby'  => isset_get($_SESSION['user']['id']),
                                   ':servers_id' => $servers_id,
                                   ':domains_id' => $domains_id));
        }

        return count($domains);

    }catch(Exception $e){
        throw new BException('servers_update_domains(): Failed', $e);
    }
}



/*
 * Add the specified domain to the specified server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param mixed $server The server to which the domain must be linked. May be specified by id, domain, seodomain, or servers array
 * @param mixed $domain The domain to which the server must be linked. May be specified by id, domain, seodomain, or domains array
 *
 * @return boolean True if domain was added, false if it already existed
 */
function servers_add_domain($server, $domain){
    try{
        $server = servers_get_id($server);
        $domain = domains_get_id($domain);
        $exists = sql_get('SELECT `id` FROM `domains_servers` WHERE `servers_id` = :servers_id AND `domains_id` = :domains_id', true, array(':servers_id' => $server, ':domains_id' => $domain), 'core');

        if($exists){
            return false;
        }

        sql_query('INSERT INTO `domains_servers` (`createdby`, `meta_id`, `servers_id`, `domains_id`)
                   VALUES                        (:createdby , :meta_id , :servers_id , :domains_id )',

                   array('createdby'   => isset_get($_SESSION['user']['id']),
                         'meta_id'     => meta_action(),
                         'servers_id'  => $server,
                         'domains_id'  => $domain), 'core');

        return true;

    }catch(Exception $e){
        throw new BException('servers_add_domain(): Failed', $e);
    }
}



/*
 * Remove the specified domain from either the specified servers_id or from all servers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param mixed $domain The domain to be linked to the server. May be specified by id, domain, or domains array
 * @param mixed $server The server to be linked to the domain. May be specified by id, domain, or servers array
 *
 * @return integer Amount of deleted domains
 */
function servers_remove_domain($server, $domain = null){
    try{
        if(!$domain){
            /*
             * Remove all domains
             */
            $domains = servers_list_domains($server);

            foreach($domains as $domain){
                servers_remove_domain($server, $domain);
            }

            return count($domains);
        }

        $server = servers_get_id($server);
        $domain = domains_get_id($domain);

        if($server){
            if($domain){
                $r = sql_query('DELETE FROM `domains_servers` WHERE `servers_id` = :servers_id AND `domains_id` = :domains_id', array(':domains_id' => $domain, ':servers_id' => $server), 'core');

            }else{
                $r = sql_query('DELETE FROM `domains_servers` WHERE `servers_id` = :servers_id', array(':servers_id' => $server), 'core');
            }

        }else{
            if($domain){
                $r = sql_query('DELETE FROM `domains_servers` WHERE `domains_id` = :domains_id', array(':domains_id' => $domain), 'core');

            }else{
                throw new BException(tr('servers_remove_domain(): Neither $domain not $server specified. At least one must be specified'), 'not-specified');
            }
        }

        return sql_insert_id('core');

    }catch(Exception $e){
        throw new BException('servers_remove_domain(): Failed', $e);
    }
}



/*
 * List all linked domains for the specified server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param mixed $server The server for which the domains must be returned. May be specified by id, domain, seodomain, or servers array
 * @return array The domains for the specified server
 */
function servers_list_domains($server){
    try{
        $server  = servers_get_id($server);
        $results = sql_list('SELECT   `domains`.`seodomain`,
                                      `domains`.`domain`

                             FROM     `domains_servers`

                             JOIN     `domains`
                             ON       `domains_servers`.`servers_id` = :servers_id
                             AND      `domains_servers`.`domains_id` = `domains`.`id`
                             AND      `domains`.`status`             IS NULL

                             ORDER BY `domains`.`domain` ASC',

                             array(':servers_id' => $server), false, 'core');

        return $results;

    }catch(Exception $e){
        throw new BException('servers_list_domains(): Failed', $e);
    }
}



/*
 * Execute the specified commands on the specified server using ssh_exec() and return the results.
 *
 * If server is specified as an array, servers_exec() will assume the server data is available and send it directly to ssh_exec(). If server is specified as a string or integer, servers_exec() will look up the server in the database by either servers_id or domain, and if found, use that server data to send the commands to ssh_exec()
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 * @see ssh_exec()
 * @see safe_exec()
 *
 * @param mixed  $server
 * @param mixed  $params[commands]
 * @param string $server[function]
 * @param mixed  $server[ok_exitcodes]
 * @param string $server[timeout]
 * @return array The results of the executed SSH commands in an array, each entry containing one line of the output
 * @see ssh_exec()
 */
function servers_exec($server, $params){
    try{
        $server = servers_like($server);
        $server = servers_get($server);

        if(!empty($server['domain'])){
            array_default($server, 'hostkey_check', true);

            if(empty($server['identity_file'])){
                if(empty($server['ssh_key'])){
                    if(empty($server['password'])){
                        throw new BException(tr('servers_exec(): The specified server ":server" has no identity file or SSH key available and no password was specified', array(':server' => $server['domain'])), 'missing-data');
                    }
                }

                /*
                 * Copy the ssh_key to a temporal identity_file
                 */
                $server['identity_file'] = servers_create_identity_file($server);
            }
        }

        /*
         * Execute command on remote server
         */
        return ssh_exec($server, $params);

    }catch(Exception $e){
        /*
         * We failed to execute the server command but try deleting the keyfile
         * anyway!
         */
        try{
            servers_remove_identity_file(isset_get($identity_file));

        }catch(Exception $f){
            /*
             * Cannot be deleted, just ignore and notify
             */
            notify($f, true, false);
        }

        throw new BException('servers_exec(): Failed', $e);
    }
}



/*
 * Add SSH fingerprint all domains / ports for the specified server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param mixed $server
 * @return array The database entry data for the requested domain
 */
function servers_exec_on_all($params){
    try{
        if(is_executable($params)){
            /*
             * Just the callback function was given
             */
            $params['callback'] = $params;
        }

        array_ensure($params);
        array_default($params, 'status', null);

        $server = sql_query('SELECT `servers`.`id`,
                                    `servers`.`createdon`,
                                    `servers`.`meta_id`,
                                    `servers`.`port`,
                                    `servers`.`cost`,
                                    `servers`.`status`,
                                    `servers`.`interval`,
                                    `servers`.`domain`,
                                    `servers`.`seodomain`,
                                    `servers`.`bill_duedate`,
                                    `servers`.`ssh_accounts_id`,
                                    `servers`.`database_accounts_id`,
                                    `servers`.`description`,
                                    `servers`.`ipv4`,
                                    `servers`.`ipv6`,
                                    `servers`.`allow_sshd_modification`,

                                    `ssh_accounts`.`username`,
                                    `ssh_accounts`.`ssh_key`,

                                    `createdby`.`name`  AS `createdby_name`,
                                    `createdby`.`email` AS `createdby_email`,

                                    `providers`.`name`       AS `provider`,
                                    `customers`.`name`       AS `customer`,
                                    `providers`.`seoname`    AS `seoprovider`,
                                    `customers`.`seoname`    AS `seocustomer`,
                                    `ssh_accounts`.`seoname` AS `ssh_account`

                          FROM      `servers`

                          LEFT JOIN `users` AS `createdby`
                          ON        `servers`.`createdby`            = `createdby`.`id`

                          LEFT JOIN `providers`
                          ON        `providers`.`id`                 = `servers`.`providers_id`

                          LEFT JOIN `customers`
                          ON        `customers`.`id`                 = `servers`.`customers_id`

                          LEFT JOIN `ssh_accounts`
                          ON        `ssh_accounts`.`id`              = `servers`.`ssh_accounts_id`

                          WHERE `servers`.`status` '.sql_is($params['status'], ':status'),

                          array(':status' => $params['status']), 'core');

        while($server = sql_fetch($servers)){
            $params['callback']($server);
        }

        return $servers->rowCount();

    }catch(Exception $e){
        throw new BException('servers_exec_on_all(): Failed', $e);
    }
}



/*
 * Add SSH fingerprint all domains / ports for the specified server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param mixed $server
 * @return array The database entry data for the requested domain
 */
function servers_register_host($server){
    try{
        $server  = servers_get($server);
        $domains = servers_list_domains($server);

        foreach($domains as $domain){
            $server  = servers_get($domain);
            $entries = ssh_add_known_host($server['domain'], $server['port']);

            if($entries){
                $retval = array_merge($entries, $entries);
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new BException('servers_register_host(): Failed', $e);
    }
}



/*
 * Remove the SSH fingerprint for all domains / ports for the specified server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param mixed $server
 * @return void
 */
function servers_unregister_host($server){
    try{
        $retval  = array();
        $server  = servers_get($server);
        $domains = servers_list_domains($server);

        foreach($domains as $domain){
            $server  = servers_get($domain);
            $entries = ssh_add_known_host($server['domain'], $server['port']);

            if($entries){
                $retval = array_merge($entries, $entries);
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new BException('servers_unregister_host(): Failed', $e);
    }
}



/*
 * List all registered servers that are available
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 * @see servers_get()
 * @version 2.4.0: Added function and documentation
 *
 * @return array The currently registered servers that are available
 */
function servers_list($as_resource = false){
    try{
        $query = 'SELECT   `id`,
                           `domain`,
                           `seodomain`

                  FROM     `servers`

                  WHERE    `status` IS NULL

                  ORDER BY `domain` = "" DESC,
                           `createdon`   ASC';

        if($as_resource){
            $retval = sql_query($query, null, 'core');

        }else{
            $retval = sql_list($query, null, 'core');
        }

        return $retval;

    }catch(Exception $e){
        throw new BException('servers_list(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param mixed $server
 * @param boolean $database
 * @param boolean $return_proxies
 * @param boolean $limited_columns
 * @return array The database entry data for the requested domain
 */
function servers_get($server, $database = false, $return_proxies = true, $limited_columns = false){
    try{
        if($server === null){
            /*
             * This means local server, no network connection needed
             */
            return null;
        }

        if(is_array($server)){
            /*
             * Specified host is an array, so it should already contain all
             * information
             *
             * Assume that if no domain is available in the server array,
             * that NO server should be used at all
             * Assume that if identity_file data is available, that we have a
             * complete one
             */
            if(empty($server['domain'])){
                return null;
            }

            if(!empty($server['id'])){
                return $server;
            }

        }elseif(!is_scalar($server)){
            throw new BException(tr('servers_get(): The specified server ":server" is invalid', array(':server' => $server)), 'invalid');

        }elseif(substr($server, 0, 1) === '+'){
            /*
             * Use persistent connections
             */
            $server  = substr($server, 1);
            $persist = true;
        }

        if($limited_columns){
            $query = 'SELECT `servers`.`id`,
                             `servers`.`domain`,
                             `servers`.`port`,
                             `servers`.`ipv4`,

                             `ssh_accounts`.`username`,
                             `ssh_accounts`.`ssh_key` ';

        }else{
            $query = 'SELECT `servers`.`id`,
                             `servers`.`createdon`,
                             `servers`.`meta_id`,
                             `servers`.`port`,
                             `servers`.`cost`,
                             `servers`.`status`,
                             `servers`.`interval`,
                             `servers`.`domain`,
                             `servers`.`seodomain`,
                             `servers`.`bill_duedate`,
                             `servers`.`ssh_accounts_id`,
                             `servers`.`database_accounts_id`,
                             `servers`.`description`,
                             `servers`.`ipv4`,
                             `servers`.`ipv6`,
                             `servers`.`allow_sshd_modification`,

                             `ssh_accounts`.`username`,
                             `ssh_accounts`.`ssh_key`,

                             `createdby`.`name`  AS `createdby_name`,
                             `createdby`.`email` AS `createdby_email`,

                             `providers`.`name`       AS `provider`,
                             `customers`.`name`       AS `customer`,
                             `providers`.`seoname`    AS `seoprovider`,
                             `customers`.`seoname`    AS `seocustomer`,
                             `ssh_accounts`.`seoname` AS `ssh_account`';
        }

        $from  = ' FROM      `servers`

                   LEFT JOIN `users` AS `createdby`
                   ON        `servers`.`createdby`            = `createdby`.`id`

                   LEFT JOIN `providers`
                   ON        `providers`.`id`                 = `servers`.`providers_id`

                   LEFT JOIN `customers`
                   ON        `customers`.`id`                 = `servers`.`customers_id`

                   LEFT JOIN `ssh_accounts`
                   ON        `ssh_accounts`.`id`              = `servers`.`ssh_accounts_id` ';

        if(is_numeric($server)){
            /*
             * Host specified by id
             */
            $where   = ' WHERE `servers`.`id` = :id';
            $execute = array(':id' => $server);

        }elseif(is_array($server)){
            /*
             * Server host specified by array containing domain
             */
            if(is_numeric($server['domain'])){
                /*
                 * Host specified by id
                 */
                $where   = ' WHERE `servers`.`id` = :id';
                $execute = array(':id' => $server['domain']);

            }elseif(is_scalar($server['domain'])){
                /*
                 * Host specified by domain
                 */
                $where   = ' WHERE `servers`.`domain` = :domain';

                $execute = array(':domain' => $server['domain']);

            }else{
                throw new BException(tr('servers_get(): Specified server array domain should be a natural numeric id or a domain, but is a ":type"', array(':type' => gettype($server['domain']))), 'invalid');
            }

        }elseif(is_string($server)){
            /*
             * Domain specified by name
             */
            $where   = ' WHERE `servers`.`domain`    = :domain
                         OR    `servers`.`seodomain` = :seodomain';

            $execute = array(':domain'    => $server,
                             ':seodomain' => $server);

        }else{
            throw new BException(tr('servers_get(): Invalid server or domain specified. Should be either a natural nuber, domain, or array containing domain information'), 'invalid');
        }

        if($database){
            $query .= ' ,
                        `database_accounts`.`username`      AS `db_username`,
                        `database_accounts`.`password`      AS `db_password`,
                        `database_accounts`.`root_password` AS `db_root_password`';

            $from  .= ' LEFT JOIN `database_accounts`
                        ON        `database_accounts`.`id` = `servers`.`database_accounts_id` ';
        }

        $dbserver = sql_get($query.$from.$where.' GROUP BY `servers`.`id`', null, $execute, 'core');

        if(!$dbserver){
            throw new BException(tr('servers_get(): Specified server ":server" does not exist', array(':server' => (is_array($server) ? $server['domain'] : $server))), 'not-exists');
        }

        if($return_proxies){
            $dbserver['proxies'] = array();

            $dbserver_proxy = servers_get_proxy($dbserver['id']);

            if($dbserver_proxy){
                $dbserver['proxies'][] = $dbserver_proxy;
                $proxy                 = $dbserver_proxy['proxies_id'];

                while($proxy){
                    $dbserver_proxy = servers_get_proxy($proxy);
                    $proxy          = false;

                    if(!empty($dbserver_proxy)){
                        $dbserver['proxies'][] = $dbserver_proxy;
                        $proxy                 = $dbserver_proxy['proxies_id'];
                    }
                }

                $dbserver['proxies'] = array_filter($dbserver['proxies']);
            }

            if(is_array($server)){
                $dbserver = array_merge($server, $dbserver);
            }
        }

        if(isset($persist)){
            $dbserver['persist'] = true;
        }

        return $dbserver;

    }catch(Exception $e){
        if($e->getCode() == 'multiple'){
            throw new BException(tr('servers_get(): Specified domain ":domain" matched multiple results, please specify a more exact domain', array(':domain' => (is_array($server) ? isset_get($server['domain']) : $server))), 'multiple');
        }

        throw new BException('servers_get(): Failed', $e);
    }
}



/*
 * Test SSH connection with the specified server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 * @exception BException/failed-connect when server connection test fails
 *
 * @param mixed $server The server to be tested. Specified either by only a domain string, or a server array
 * @return void If the server test was executed succesfully, nothing happens
 */
function servers_test($domain){
    try{
        sql_query('UPDATE `servers` SET `status` = "testing" WHERE `domain` = :domain', array(':domain' => $domain), 'core');

        $result = servers_exec($domain, array('commands' => array('echo', array('1'))));
        $result = array_pop($result);

        if($result != '1'){
            throw new BException(tr('servers_test(): Failed to SSH connect to ":server"', array(':server' => $user.'@'.$domain.':'.$port)), 'failed-connect');
        }

        sql_query('UPDATE `servers` SET `status` = NULL WHERE `domain` = :domain', array(':domain' => $domain), 'core');

    }catch(Exception $e){
        throw new BException('servers_test(): Failed', $e);
    }
}



/*
 * Returns an SSH key for the specified username, if available
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param string $username The SSH username for which an SSH key must be returned
 * @return string The SSH key for the specified username
 */
function servers_get_key($username){
    try{
        return sql_get('SELECT `ssh_key` FROM `ssh_accounts` WHERE `username` = :username', 'ssh_key', null, array(':username' => $username), 'core');

    }catch(Exception $e){
        throw new BException('servers_get_key(): Failed', $e);
    }
}



/*
 * Securely clear the private key from a servers array
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param array $server Server array containing the private key that will be deleted securely
 * return boolean true if key was cleared, false if the specified $server array did not contain "ss_key"
 */
function servers_clear_key(&$server){
    try{
        if(empty($server['ssh_key'])){
            return false;
        }

        if(function_exists('sodium_memzero')){
            sodium_memzero($server['ssh_key']);
            unset($server['ssh_key']);

        }else{
            $server['ssh_key'] = random_bytes(2048);
            unset($server['ssh_key']);
        }

        return true;

    }catch(Exception $e){
        throw new BException('servers_clear_key(): Failed', $e);
    }
}



/*
 * Create a safe SSH keyfile containing the specified SSH key
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param string $ssh_key The SSH key that must be placed in a keyfile
 * return string $identity_file The created keyfile
 */
function servers_create_identity_file($server){
    global $core;

    try{
        if(empty($server['ssh_key'])){
            throw new BException(tr('servers_create_identity_file(): Specified server does not contain an ssh_key'), 'not-specified');
        }

        /*
         * Ensure that ssh/keys directory exists and that its safe
         */
        file_ensure_path(ROOT.'data/ssh/keys', 0700);
        chmod(ROOT.'data/ssh', 0700);

        /*
         * Safely create SSH key file
         */
        $identity_file = ROOT.'data/ssh/keys/'.str_random(8);

        touch($identity_file);
        chmod($identity_file, 0600);
        file_put_contents($identity_file, $server['ssh_key'], FILE_APPEND);
        chmod($identity_file, 0400);
        servers_clear_key($server);

        $core->register('shutdown_servers_remove_identity_file', array($identity_file));

        return ROOT.'data/ssh/keys/'.substr($identity_file, -8, 8);

    }catch(Exception $e){
        throw new BException('servers_create_identity_file(): Failed', $e);
    }
}



/*
 * Delete the specified SSH key
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param string $identity_file The SSH key file that must be deleted
 * return boolean True if the specified keyfile was deleted, false if no keyfile was specified
 */
function servers_remove_identity_file($identity_file, $background = false){
    try{
        if(!$identity_file){
            return false;
        }

        $identity_file = ROOT.'data/ssh/keys/'.$identity_file;

        if(file_exists($identity_file)){
            if($background){
                safe_exec(array('background' => true,
                                'commands'   => array('sleep', array('5'),
                                                      'chmod', array('sudo' => true, '0660', $identity_file),
                                                      'rm'   , array('sudo' => true, '-rf' , $identity_file))));

            }else{
                chmod($identity_file, 0600);
                file_delete($identity_file, ROOT.'data/ssh/keys');
            }
        }

        return true;

    }catch(Exception $e){
        throw new BException('servers_remove_identity_file(): Failed', $e);
    }
}



/*
 * Detect the operating system on the specified host
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param  string $domain The name of the host where to detect the operating system
 * @return array            An array containing the operatings system type (linux, windows, macos, etc), group (ubuntu group, redhad group, debian group), name (ubuntu, mint, fedora, etc), and version (7.4, 16.04, etc)
 * @see servers_get_os()
 */
function servers_detect_os($domain){
    try{
        /*
         * Getting complete operating system distribution
         */
        $output_version = servers_exec($domain, array('commands' => array('cat', array('proc/version'))));

        if(empty($output_version)){
            throw new BException(tr('servers_detect_os(): No operating system found on /proc/version for domain ":domain"', array(':domain' => $domain)), 'unknown');
        }

        /*
         * Determine to which group belongs the operating system
         */
        preg_match('/(ubuntu |debian |red hat )/i', $output_version, $matches);

        if(empty($matches)){
            throw new BException(tr('servers_detect_os(): No group version found'), 'unknown');
        }

        $group = trim(strtolower($matches[0]));

        switch($group){
            case 'debian':
                $release = servers_exec($domain, array('commands' => array('cat', array('/etc/issue'))));
                break;

            case 'ubuntu':
                $release = servers_exec($domain, array('commands' => array('cat', array('/etc/issue'))));
                break;

            case 'red hat':
                $group   = 'redhat';
                $release = servers_exec($domain, array('commands' => array('cat', array('/etc/redhat-release'))));
                break;

            default:
                throw new BException(tr('servers_detect_os(): No os group valid :group', array(':group' => $matches[0])), 'invalid');
        }

        if(empty($release)){
            throw new BException(tr('servers_detect_os(): No data found on for os group ":group"', array(':group' => $matches[0])), 'not-exists');
        }

        $server_os['type']  = 'linux';
        $server_os['group'] = $group;

        /*
         * Getting operating systema name based on release file(/etc/issue or /etc/redhad-release)
         */
        preg_match('/((:?[kxl]|edu)?ubuntu|mint|debian|red hat enterprise|fedora|centos)/i', $release, $matches);

        if(!isset($matches[0])){
            throw new BException(tr('servers_detect_os(): No name found for os group ":group"', array(':group' => $matches[0])), 'not-exists');
        }

        $server_os['name'] = strtolower($matches[0]);

        /*
         * Getting complete version for the operating system
         */
        preg_match('/\d*\.?\d+/', $release, $version);

        if(!isset($version[0])){
            throw new BException(tr('servers_detect_os(): No version found for os ":os"', array(':os' => $server_os['name'])), 'not-exists');
        }

        $server_os['version'] = $version[0];

        return $server_os;

    }catch(Exception $e){
        throw new BException('servers_get_os(): Failed', $e);
    }
}



/*
 * Returns the public IP for the specified domain
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param string $domain
 * @return string $ip The IP for the specified domain
 */
function servers_get_public_ip($domain){
    try{
        $ip = servers_exec($domain, array('commands' => array('dig', array('+short', 'myip.opendns.com', '@resolver1.opendns.com'))));

        if(is_array($ip)){
            $ip = $ip[0];
        }

        return $ip;

    }catch(Exception $e){
        throw new BException('servers_get_public_ip(): Failed', $e);
    }
}



/*
 * Returns the proxy (if available) linked to the specified $servers_id. If the specified $servers_id has multiple linked proxy servers, a single random one will be chosen and returned
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param numeric $servers_id, id of the required server
 * @return array
 */
function servers_get_proxy($servers_id){
    try{
        $server = sql_get('SELECT    `servers`.`id`,
                                     `servers`.`domain`,
                                     `servers`.`port`,
                                     `servers`.`ipv4`,
                                     `servers_ssh_proxies`.`proxies_id`

                           FROM      `servers_ssh_proxies`

                           LEFT JOIN `servers`
                           ON        `servers`.`id`                     = `servers_ssh_proxies`.`proxies_id`

                           WHERE     `servers_ssh_proxies`.`servers_id` = :servers_id

                           ORDER BY  RAND()

                           LIMIT     1',

                           array(':servers_id' => $servers_id), null, 'core');

        return $server;

    }catch(Exception $e){
        throw new BException('servers_get_proxy(): Failed', $e);
    }
}



/*
 * Returns all the proxy servers (if available) linked to the specified $servers_id
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param numeric $servers_id, id of the required server
 * @return array
 */
function servers_list_proxies($servers_id){
    try{
        $servers = sql_list('SELECT    `servers`.`id`,
                                       `servers`.`domain`,
                                       `servers`.`port`,
                                       `servers`.`ipv4`,
                                       `servers_ssh_proxies`.`proxies_id`

                             FROM      `servers_ssh_proxies`

                             LEFT JOIN `servers`
                             ON        `servers`.`id`                     = `servers_ssh_proxies`.`proxies_id`

                             WHERE     `servers_ssh_proxies`.`servers_id` = :servers_id',

                             array(':servers_id' => $servers_id), false, 'core');

        return $servers;

    }catch(Exception $e){
        throw new BException('servers_list_proxies(): Failed', $e);
    }
}



/*
 * Add the specified proxy $proxies_id to the proxy chain for the specified $servers_id
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param integer $servers_id
 * @param integer $proxies_id
 * @return integer servers_ssh_proxies insert_id
 */
function servers_add_ssh_proxy($servers_id, $proxies_id){
    try{
        if(empty($servers_id)){
            throw new BException(tr('proxies_create_relation(): No servers id specified'), 'not-specified');
        }

        if(empty($proxies_id)){
            throw new BException(tr('proxies_create_relation(): No proxies id specified'), 'not-specified');
        }

        sql_query('INSERT INTO `servers_ssh_proxies` (`servers_id`, `proxies_id`)
                   VALUES                            (:servers_id , :proxies_id )',

                   array(':servers_id' => $servers_id,
                         ':proxies_id' => $proxies_id), 'core');

        return sql_insert_id('core');

    }catch(Exception $e){
		throw new BException('servers_add_ssh_proxy(): Failed', $e);
	}
}



/*
 * Updates relation in database base for specified server, in case relation does not exists, a new record is created
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param integer $servers_id
 * @param integer $old_proxies_id
 * @param integer $new_proxies_id
 * @return void
 */
function servers_update_ssh_proxy($servers_id, $old_proxies_id, $new_proxies_id){
    try{
        if(empty($servers_id)){
            throw new BException(tr('servers_update_ssh_proxy(): No servers id specified'), 'not-specified');
        }

        if(empty($old_proxies_id)){
            throw new BException(tr('servers_update_ssh_proxy(): No old proxies id specified'), 'not-specified');
        }

        if(empty($new_proxies_id)){
            throw new BException(tr('servers_update_ssh_proxy(): No new proxies id specified'), 'not-specified');
        }

        $id = sql_get('SELECT `id`

                       FROM   `servers_ssh_proxies`

                       WHERE  `servers_id` = :servers_id
                       AND    `proxies_id` = :proxies_id',

                       array(':servers_id' => $servers_id,
                             ':proxies_id' => $old_proxies_id), true, 'core');

        if($id){
            sql_query('UPDATE `servers_ssh_proxies`

                       SET    `proxies_id` = :proxies_id

                       WHERE  `id`         = :id',

                       array(':id'         => $id,
                             ':proxies_id' => $new_proxies_id), 'core');

        }else{
            /*
             * Record does not exist, creating a new one
             */
            load_libs('servers');
            servers_add_ssh_proxy($servers_id, $new_proxies_id);
        }

    }catch(Exception $e){
		throw new BException('servers_update_ssh_proxy(): Failed', $e);
	}
}



/*
 * Deletes from data base relation between two servers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param integer $servers_id
 * @param integer $proxies_id
 */
function servers_delete_ssh_proxy($servers_id, $proxies_id){
    try{
        sql_query('DELETE FROM `servers_ssh_proxies`

                   WHERE       `servers_id` = :servers_id
                   AND         `proxies_id` = :proxies_id',

                   array(':servers_id' => $servers_id,
                         ':proxies_id' => $proxies_id), 'core');

    }catch(Exception $e){
		throw new BException('servers_delete_ssh_proxy(): Failed', $e);
	}
}



/*
 * Returns the ID for the specified server data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param mixed $server
 * @param integer The servers_id
 */
function servers_get_id($server){
    try{
        if(!$server){
            return null;
        }

        if(is_array($server)){
            $server = $server['id'];

        }elseif(!is_numeric($server)){
            $server = servers_get($server);
            $server = $server['id'];
        }

        return $server;

    }catch(Exception $e){
		throw new BException('servers_get_id(): Failed', $e);
	}
}



/*
 * Scan the specified server for all the domains it might be processing
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param mixed $server
 * @return integer The amount of scanned servers
 */
function servers_scan_domains($server = null){
    try{
        if(!$server){
            /*
             * Scan ALL servers
             */
            $domains = sql_query('SELECT `domain` FROM `servers` WHERE `status` IS NULL', null, 'core');

            while($domain = sql_fetch($domains, true)){
                $count++;
                servers_scan_domains($domain);
            }

            return $count++;
        }

        /*
         * Scan the server
         */

        servers_update_domains($server['id'], $domains);
        return 1;

    }catch(Exception $e){
		throw new BException('servers_scan_domains(): Failed', $e);
	}
}



/*
 * Returns if the specified account has SSH access on the specified server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param mixed $server The server to be checked
 * @param mixed $account This is either an SSH accounts id or name, or if $password is specified, just a normal username on that server (for example; root)
 * @param mixed $password If specified, the specified account will not be taken from the `ssh_accounts` table, but will be regarded as a username on the server which will be used in combination with the specified password
 * @return boolean True if the specified account has access on the specified server
 */
function servers_check_ssh_access($server, $account, $password = null){
    try{
        $server['username'] = $account;

        if($password){
            $server['password'] = $password;
            $results = servers_exec($server, array('commands' => array('echo', array('1'))));

        }else{
            $account = ssh_get_account($account);

            if(!$account){
                throw new BException(tr('servers_check_ssh_access(): The specified account ":account" does not exist in the `ssh_accounts` table', array(':account' => $account)), 'not-exists');
            }

            $results = servers_exec($server, array('commands' => array('echo', array('1'))));
        }

showdie($results);

    }catch(Exception $e){
		throw new BException('servers_check_ssh_access(): Failed', $e);
	}
}
?>
