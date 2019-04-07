<?php
/*
 * email-servers library
 *
 * This library contains functions to manage email servers through SQL queries
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the ssh library configuration
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return void
 */
function email_servers_library_init(){
    try{
        load_libs('linux');

    }catch(Exception $e){
        throw new BException('email_servers_library_init(): Failed', $e);
    }
}



/*
 * Validate an email server
 *
 * This function will validate all relevant fields in the specified $email_server array
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available categories
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package categories
 *
 * @param params $params The parameters required
 * @params natural id The database table id for the specified email server, if not new
 * @params string server_seodomain
 * @params string domain
 * @params string description
 * @return The specified email server, validated
 */
function email_servers_validate($email_server){
    try{
        load_libs('validate,seo,domains,servers');

        $v = new ValidateForm($email_server, 'id,domain,server_seodomain,description');
        $v->isNotEmpty($email_server['server_seodomain'], tr('Please specify a server'));
        $v->isNotEmpty($email_server['domain'], tr('Please specify a domain'));

        /*
         * Validate the server
         */
        $server = servers_get($email_server['server_seodomain'], false, true, true);

        if(!$server){
            $v->setError(tr('The specified server ":server" does not exist', array(':server' => $email_server['server_seodomain'])));
        }

        $email_server['servers_id'] = $server['id'];

        /*
         * Validate the domain
         */
        $email_server['domains_id'] = domains_ensure($email_server['domain']);

        /*
         * Validate the rest
         */
        if($email_server['description']){
            $v->hasMinChars($email_server['description'], 16, tr('Please specify at least 16 characters for a description'));
            $v->hasMaxChars($email_server['description'], 2048, tr('Please specify no more than 2047 characters for a description'));

        }else{
            $email_server['description'] = null;
        }

        $v->isValid();

        $exists = sql_get('SELECT `id` FROM `email_servers` WHERE `domain` = :domain AND `id` != :id LIMIT 1', true, array(':domain' => $email_server['domain'], ':id' => isset_get($email_server['id'], 0)), 'core');

        if($exists){
            $v->setError(tr('The domain ":domain" is already registered', array(':domain' => $email_server['domain'])));
        }

        $email_server['seodomain'] = seo_unique($email_server['domain'], 'email_servers', $email_server['id'], 'seodomain');

        $v->isValid();

        return $email_server;

    }catch(Exception $e){
        throw new BException(tr('email_servers_validate(): Failed'), $e);
    }
}



/*
 * Validate an email domain
 *
 * This function will validate all relevant fields in the specified $domain array
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package categories
 *
 * @param array $params The parameters required
 * @return string HTML for a categories select box within the specified parameters
 */
function email_servers_validate_domain($domain){
    try{
        load_libs('validate,seo,customers');

        $v = new ValidateForm($domain, 'id,name,seocustomer,description');
        $v->isNotEmpty($domain['name'], tr('Please specify a domain name'));
        $v->hasMaxChars($domain['name'], 64, tr('Please specify a domain of less than 64 characters'));
        $v->isFilter($domain['name'], FILTER_VALIDATE_DOMAIN, tr('Please specify a valid domain'));

        if($domain['seocustomer']){
            $domain['customer'] = customers_get(array('columns' => 'seoname',
                                                      'filters' => array('seoname' => $domain['seocustomer'])));

        }else{
            $domain['customer'] = null;
        }

        $v->isValid();

        $exists = sql_get('SELECT `id` FROM `domains` WHERE `name` = :name LIMIT 1', true, array(':name' => $domain['name']));

        if($exists){
            $v->setError(tr('The domain ":name" is already registered on this email server'. array(':name' => $domain['name'])));
        }

        if($email_server['description']){
            $v->hasMinChars($email_server['description'], 16, tr('Please specify at least 16 characters for a description'));
            $v->hasMaxChars($email_server['description'], 2048, tr('Please specify no more than 2047 characters for a description'));

        }else{
            $email_server['description'] = null;
        }

        $v->isValid();

        $domain['seoname'] = seo_unique($domain['name'], 'domains', $domain['id']);

        return $domain;

    }catch(Exception $e){
        if($e->getCode() == '1049'){
            load_libs('servers');

            $servers  = servers_list_domains($domain['server']);
            $server   = servers_get($domain['server']);
            $domain = not_empty($servers[$domain['server']], $domain['server']);

            throw new BException(tr('email_servers_validate_domain(): Specified email server ":server" (server domain ":domain") does not have a "mail" database', array(':server' => $domain, ':domain' => $server['domain'])), 'not-exists');
        }

        throw new BException(tr('email_servers_validate_domain(): Failed'), $e);
    }
}



/*
 * Validate an email account
 *
 * This function will validate all relevant fields in the specified $domain array
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package categories
 *
 * @param array $params The parameters required
 * @return string HTML for a categories select box within the specified parameters
 */
function email_servers_validate_account($domain){
    try{
        load_libs('validate,seo,customers');

        $v = new ValidateForm($domain, 'id,email,description');
        $v->isNotEmpty($domain['email'], tr('Please specify a domain name'));
        $v->hasMaxChars($domain['email'], 120, tr('Please specify a domain of less than 64 characters'));
        $v->isFilter($domain['email'], FILTER_VALIDATE_EMAIL, tr('Please specify a valid email address'));

        $v->isValid();

        $exists = sql_get('SELECT `id` FROM `domains` WHERE `name` = :name LIMIT 1', true, array(':name' => $domain['name']));

        if($exists){
            $v->setError(tr('The domain ":name" is already registered on this email server'. array(':name' => $domain['name'])));
        }

        if($email_server['description']){
            $v->hasMinChars($email_server['description'], 16, tr('Please specify at least 16 characters for a description'));
            $v->hasMaxChars($email_server['description'], 2048, tr('Please specify no more than 2047 characters for a description'));

        }else{
            $email_server['description'] = null;
        }

        $v->isValid();

        $domain['seoname'] = seo_unique($domain['name'], 'domains', $domain['id']);

        return $domain;

    }catch(Exception $e){
        if($e->getCode() == '1049'){
            load_libs('servers');

            $servers  = servers_list_domains($domain['server']);
            $server   = servers_get($domain['server']);
            $domain = not_empty($servers[$domain['server']], $domain['server']);

            throw new BException(tr('email_servers_validate_account(): Specified email server ":server" (server domain ":domain") does not have a "mail" database', array(':server' => $domain, ':domain' => $server['domain'])), 'not-exists');
        }

        throw new BException(tr('email_servers_validate_account(): Failed'), $e);
    }
}



/*
 * Return HTML for a email server select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available email servers
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email_servers
 *
 * @param array $params The parameters required
 * @param $params name
 * @param $params class
 * @param $params extra
 * @param $params tabindex
 * @param $params empty
 * @param $params none
 * @param $params selected
 * @param $params parents_id
 * @param $params status
 * @param $params orderby
 * @param $params resource
 * @return string HTML for a email_servers select box within the specified parameters
 */
function email_servers_select($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'    , 'seodomain');
        array_default($params, 'class'   , 'form-control');
        array_default($params, 'selected', null);
        array_default($params, 'status'  , null);
        array_default($params, 'empty'   , tr('No email servers available'));
        array_default($params, 'none'    , tr('Select an email server'));
        array_default($params, 'tabindex', 0);
        array_default($params, 'extra'   , 'tabindex="'.$params['tabindex'].'"');
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

        $query              = 'SELECT `seodomain`, `domain` FROM `email_servers` '.$where.' ORDER BY '.$params['orderby'];
        $params['resource'] = sql_query($query, $execute, 'core');
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new BException('email_servers_select(): Failed', $e);
    }
}



/*
 * Return HTML for a email server domain select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available email server domains
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email_servers
 *
 * @param array $params The parameters required
 * @param $params name
 * @param $params class
 * @param $params extra
 * @param $params tabindex
 * @param $params empty
 * @param $params none
 * @param $params selected
 * @param $params parents_id
 * @param $params status
 * @param $params orderby
 * @param $params resource
 * @return string HTML for a email_servers select box within the specified parameters
 */
function email_servers_select_domain($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'    , 'seodomain');
        array_default($params, 'class'   , 'form-control');
        array_default($params, 'selected', null);
        array_default($params, 'status'  , null);
        array_default($params, 'empty'   , tr('No domains available'));
        array_default($params, 'none'    , tr('Select a domain'));
        array_default($params, 'orderby' , '`name`');

        if($params['status'] !== false){
            $where[] = ' `status` '.sql_is($params['status'], ':status');
            $execute[':status'] = $params['status'];
        }

        if(empty($where)){
            $where = '';

        }else{
            $where = ' WHERE '.implode(' AND ', $where).' ';
        }

        $query              = 'SELECT `seoname`, `name` FROM `domains` '.$where.' ORDER BY '.$params['orderby'];
        $params['resource'] = sql_query($query, $execute);
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new BException('email_servers_select_domain(): Failed', $e);
    }
}



/*
 * Return data for the specified email_server
 *
 * This function returns information for the specified email_server. The email_server can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email_servers
 *
 * @param mixed $email_server The requested email_server. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The email_server data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified email_server does not exist, NULL will be returned.
 */
function email_servers_get($email_server, $column = null, $status = null){
    try{
        if(is_numeric($email_server)){
            $where[] = ' `email_servers`.`id` = :id ';
            $execute[':id'] = $email_server;

        }else{
            $where[] = ' `email_servers`.`seodomain` = :seodomain ';
            $execute[':seodomain'] = $email_server;
        }

        if($status !== false){
            $execute[':status'] = $status;
            $where[] = ' `email_servers`.`status` '.sql_is($status, ':status');
        }

        $where   = ' WHERE '.implode(' AND ', $where).' ';

        if($column){
            $retval = sql_get('SELECT `'.$column.'` FROM `email_servers` '.$where, true, $execute);

        }else{
            $retval = sql_get('SELECT    `email_servers`.`id`,
                                         `email_servers`.`createdon`,
                                         `email_servers`.`createdby`,
                                         `email_servers`.`meta_id`,
                                         `email_servers`.`status`,
                                         `email_servers`.`servers_id`,
                                         `email_servers`.`domains_id`,
                                         `email_servers`.`domain`,
                                         `email_servers`.`seodomain`,
                                         `email_servers`.`smtp_port`,
                                         `email_servers`.`imap`,
                                         `email_servers`.`poll_interval`,
                                         `email_servers`.`header`,
                                         `email_servers`.`footer`,
                                         `email_servers`.`description`,

                                         `servers`.`domain`    AS `server_domain`,
                                         `servers`.`seodomain` AS `server_seodomain`

                               FROM      `email_servers`

                               LEFT JOIN `servers`
                               ON        `servers`.`id` = `email_servers`.`servers_id` '.$where, null, $execute, 'core');
        }

        return $retval;

    }catch(Exception $e){
        throw new BException('email_servers_get(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @param array $servers
 * @return void
 */
function email_servers_update_password($email, $password){
    try{
        sql_query('UPDATE `accounts`

                   SET    `password` = ENCRYPT(:password, CONCAT("$6$", SUBSTRING(SHA(RAND()), -16)))

                   WHERE  `email`    = :email',

                   array(':email'    => $email,
                         ':password' => $password));

    }catch(Exception $e){
        throw new BException('email_servers_update_password(): Failed', $e);
    }
}



/*
 * Return an array with the sizes for all mail boxes for the specified domain on the specified mail server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @param mixed $servers
 * @param mixed $servers
 * @return array An array with mailbox => bytes format
 */
function email_servers_list_mailbox_sizes($server, $domain){
    try{
        if(!filter_var($domain, FILTER_VALIDATE_DOMAIN)){
            throw new BException(tr('email_servers_list_mailbox_sizes(): Specified domain ":domain" is not a valid domain', array(':domain' => $domain)), 'invalid');
        }

        $total   = 0;
        $retval  = array();
        $results = linux_find($server, array('path'     => '/var/mail/vhosts/'.$domain,
                                             'sudo'     => true,
                                             'maxdepth' => 1,
                                             'type'     => 'd',
                                             'exec'     => array('du', array('-s', '{}'))));

        foreach($results as $result){
            $result = trim($result);
            $size   = (str_until($result, "\t") * 1024);
            $total += $size;

            $retval[str_rfrom($result, '/')] = $size;
        }

        ksort($retval);
        $retval['--total--'] = $total;

        return $retval;

    }catch(Exception $e){
        if(!linux_file_exists($server, '/var/mail/vhosts/'.$domain, true)){
            $e->setCode('not-exist');
            throw new BException(tr('email_servers_list_mailbox_sizes(): Specified domain ":domain" does not exists as a mail domain', array(':domain' => $domain)), $e);
        }

        throw new BException('email_servers_list_mailbox_sizes(): Failed', $e);
    }
}
?>
