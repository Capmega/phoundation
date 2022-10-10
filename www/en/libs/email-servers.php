<?php
/*
 * email-servers library
 *
 * This library contains functions to manage email servers through SQL queries
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the ssh library configuration
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return void
 */
function email_servers_library_init() {
    try {
        load_libs('linux');

    }catch(Exception $e) {
        throw new CoreException('email_servers_library_init(): Failed', $e);
    }
}



/*
 * Validate an email server
 *
 * This function will validate all relevant fields in the specified $email_server array
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available categories
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function email_servers_validate($email_server) {
    try {
        load_libs('validate,seo,domains,servers');

        $v = new ValidateForm($email_server, 'id,domain,server_seodomain,description');
        $v->isNotEmpty($email_server['server_seodomain'], tr('Please specify a server'));
        $v->isNotEmpty($email_server['domain'], tr('Please specify a domain'));

        /*
         * Validate the server
         */
        $server = servers_get($email_server['server_seodomain'], false, true, true);

        if (!$server) {
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
        if ($email_server['description']) {
            $v->hasMinChars($email_server['description'], 16, tr('Please specify at least 16 characters for a description'));
            $v->hasMaxChars($email_server['description'], 2048, tr('Please specify no more than 2047 characters for a description'));

        } else {
            $email_server['description'] = null;
        }

        $v->isValid();

        $exists = sql_get('SELECT `id` FROM `email_servers` WHERE `domain` = :domain AND `id` != :id LIMIT 1', true, array(':domain' => $email_server['domain'], ':id' => isset_get($email_server['id'], 0)), 'core');

        if ($exists) {
            $v->setError(tr('The domain ":domain" is already registered', array(':domain' => $email_server['domain'])));
        }

        $email_server['seodomain'] = seo_unique($email_server['domain'], 'email_servers', $email_server['id'], 'seodomain');

        $v->isValid();

        return $email_server;

    }catch(Exception $e) {
        throw new CoreException(tr('email_servers_validate(): Failed'), $e);
    }
}



/*
 * Insert the specified email server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 * @version
 *
 * @param params $server
 * @return params The specified email server, validated and sanitized
 */
function email_servers_insert($server) {
    try {
        $server = email_servers_validate($server);

        sql_query('INSERT INTO `email_servers` (`createdby`, `meta_id`, `domains_id`, `servers_id`, `domain`, `seodomain`, `description`)
                   VALUES                      (:createdby , :meta_id , :domains_id , :servers_id , :domain , :seodomain , :description )',

                   array(':createdby'     => $_SESSION['user']['id'],
                         ':meta_id'       => meta_action(),
                         ':domains_id'    => $server['domains_id'],
                         ':servers_id'    => $server['servers_id'],
                         ':domain'        => $server['domain'],
                         ':seodomain'     => $server['seodomain'],
                         ':description'   => $server['description']));

        $server['id'] = sql_insert_id();

        /*
         * Register this domain with this server
         */
        servers_add_domain($server['servers_id'], $server['domain']);

        return $server;

    }catch(Exception $e) {
        throw new CoreException('email_servers_insert(): Failed', $e);
    }
}



/*
 * Update the email server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 * @version
 *
 * @param params $server
 * @return params The specified email server, validated and sanitized
 */
function email_servers_update($server) {
    try {
        $server   = email_servers_validate($server);
        $dbserver = email_servers_get($server['id']);

        meta_action($server['meta_id'], 'update');

        $update = sql_query('UPDATE `email_servers`

                             SET    `domain`      = :domain,
                                    `domains_id`  = :domains_id,
                                    `servers_id`  = :servers_id,
                                    `domain`      = :domain,
                                    `seodomain`   = :seodomain,
                                    `description` = :description

                             WHERE  `id`          = :id',

                             array(':id'          => $server['id'],
                                   ':domains_id'  => $server['domains_id'],
                                   ':servers_id'  => $server['servers_id'],
                                   ':domain'      => $server['domain'],
                                   ':seodomain'   => $server['seodomain'],
                                   ':description' => $server['description']));

        $server['_updated'] = (boolean) $update->rowCount();

        /*
         * Register this domain with this server
         */
        servers_remove_domain($server['servers_id'], $dbserver['domain']);
        servers_add_domain   ($server['servers_id'], $server['domain']);

        return $server;

    }catch(Exception $e) {
        throw new CoreException('email_servers_update(): Failed', $e);
    }
}



/*
 * Return data for the specified email_server
 *
 * This function returns information for the specified email_server. The email_server can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email_servers
 *
 * @param mixed $email_server The requested email_server. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The email_server data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified email_server does not exist, NULL will be returned.
 */
function email_servers_get($params) {
    try {
        array_params($params, 'seodomain', 'id');

        $params['table']     = 'email_servers';
        $params['connector'] = 'core';

        array_default($params, 'filters', array('id'        => $params['id'],
                                                'seodomain' => $params['seodomain']));

        array_default($params, 'joins'  , array('LEFT JOIN `servers`
                                                 ON        `servers`.`id` = `email_servers`.`servers_id`'));

        array_default($params, 'columns', 'email_servers.id,
                                           email_servers.createdon,
                                           email_servers.createdby,
                                           email_servers.meta_id,
                                           email_servers.status,
                                           email_servers.servers_id,
                                           email_servers.domains_id,
                                           email_servers.domain,
                                           email_servers.seodomain,
                                           email_servers.smtp_port,
                                           email_servers.imap,
                                           email_servers.poll_interval,
                                           email_servers.header,
                                           email_servers.footer,
                                           email_servers.description,

                                           servers.ssh_accounts_id,
                                           servers.database_accounts_id,

                                           servers.domain    AS server_domain,
                                           servers.seodomain AS server_seodomain');

        return sql_simple_get($params);

    }catch(Exception $e) {
        throw new CoreException('email_servers_get(): Failed', $e);
    }
}



/*
 * Return a list of all available email_servers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @see sql_simple_list()
 * @package email_servers
 * @version 2.5.38: Added function and documentation
 *
 * @param params $params The list parameters
 * @return mixed The list of available email_servers
 */
function email_servers_list($params = null) {
    try {
        array_params($params, 'status');

        $params['table']     = 'email_servers';
        $params['connector'] = 'core';

        array_default($params, 'columns', 'seodomain,domain');
        array_default($params, 'orderby', array('domain' => 'asc'));

        return sql_simple_list($params);

    }catch(Exception $e) {
        throw new CoreException(tr('email_servers_list(): Failed'), $e);
    }
}



/*
 * Return HTML for a email server select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available email servers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function email_servers_select($params = null) {
    try {
        Arrays::ensure($params);
        array_default($params, 'name'    , 'seodomain');
        array_default($params, 'class'   , 'form-control');
        array_default($params, 'selected', null);
        array_default($params, 'status'  , null);
        array_default($params, 'empty'   , tr('No email servers available'));
        array_default($params, 'none'    , tr('Select an email server'));
        array_default($params, 'tabindex', 0);
        array_default($params, 'extra'   , 'tabindex="'.$params['tabindex'].'"');
        array_default($params, 'orderby' , '`domain`');

        if ($params['status'] !== false) {
            $where[] = ' `status` '.sql_is($params['status'], ':status');
            $execute[':status'] = $params['status'];
        }

        if (empty($where)) {
            $where = '';

        } else {
            $where = ' WHERE '.implode(' AND ', $where).' ';
        }

        $query              = 'SELECT `seodomain`, `domain` FROM `email_servers` '.$where.' ORDER BY '.$params['orderby'];
        $params['resource'] = sql_query($query, $execute, 'core');
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('email_servers_select(): Failed', $e);
    }
}



/*
 * Validate the specified email domain
 *
 * This function will validate all relevant fields in the specified $domain array
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package categories
 *
 * @param params $domain The email domain to be validated
 * @return params The specified domain validated and sanitized
 */
function email_servers_validate_domain($domain) {
    try {
        load_libs('validate,seo,customers');

        $v = new ValidateForm($domain, 'id,name,seocustomer,description');
        $v->isNotEmpty($domain['name'], tr('Please specify a domain name'));
        $v->hasMaxChars($domain['name'], 64, tr('Please specify a domain of less than 64 characters'));
        $v->isFilter($domain['name'], FILTER_VALIDATE_DOMAIN, tr('Please specify a valid domain'));

        if ($domain['seocustomer']) {
            $domain['customer'] = customers_get(array('columns' => 'seoname',
                                                      'filters' => array('seoname' => $domain['seocustomer'])));

        } else {
            $domain['customer'] = null;
        }

        $v->isValid();

        $exists = sql_get('SELECT `id` FROM `domains` WHERE `name` = :name and `id` != :id LIMIT 1', true, array(':name' => $domain['name'], ':id' => isset_get($domain['id'], 0)));

        if ($exists) {
            $v->setError(tr('The domain ":name" is already registered on this email server', array(':name' => $domain['name'])));
        }

        if ($domain['description']) {
            $v->hasMinChars($domain['description'], 16, tr('Please specify at least 16 characters for a description'));
            $v->hasMaxChars($domain['description'], 2048, tr('Please specify no more than 2047 characters for a description'));

        } else {
            $domain['description'] = null;
        }

        $v->isValid();

        $domain['seoname'] = seo_unique($domain['name'], 'domains', $domain['id']);

        return $domain;

    }catch(Exception $e) {
        if ($e->getCode() == '1049') {
            load_libs('servers');

            $servers  = servers_list_domains($domain['server']);
            $server   = servers_get($domain['server']);
            $domain = not_empty($servers[$domain['server']], $domain['server']);

            throw new CoreException(tr('email_servers_validate_domain(): Specified email server ":server" (server domain ":domain") does not have a "mail" database', array(':server' => $domain, ':domain' => $server['domain'])), 'not-exists');
        }

        throw new CoreException(tr('email_servers_validate_domain(): Failed'), $e);
    }
}



/*
 * Insert the specified email domain on the mail server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 * @version
 *
 * @param params $server
 * @return params The specified email domain, validated and sanitized
 */
function email_servers_insert_domain($domain) {
    try {
        $domain = email_servers_validate_domain($domain);

        sql_query('INSERT INTO `domains` (`name`, `seoname`, `customer`, `description`)
                   VALUES                (:name , :seoname , :customer , :description )',

                   array(':name'        => $domain['name'],
                         ':seoname'     => $domain['seoname'],
                         ':customer'    => $domain['customer'],
                         ':description' => $domain['description']));

        $domain['id'] = sql_insert_id();
        return $domain;

    }catch(Exception $e) {
        throw new CoreException('email_servers_insert_domain(): Failed', $e);
    }
}



/*
 * Update the email account on the mail server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 * @version
 *
 * @param params $domain
 * @return params The specified email account, validated and sanitized
 */
function email_servers_update_domain($domain) {
    try {
        $domain   = email_servers_validate_domain($domain);
        $update   = sql_query('UPDATE `domains`

                               SET    `name`        = :name,
                                      `seoname`     = :seoname,
                                      `customer`    = :customer,
                                      `description` = :description

                               WHERE  `id`          = :id',

                               array(':id'          => $domain['id'],
                                     ':name'        => $domain['name'],
                                     ':seoname'     => $domain['seoname'],
                                     ':customer'    => $domain['customer'],
                                     ':description' => $domain['description']));

        $domain['_updated'] = (boolean) $update->rowCount();
        return $domain;

    }catch(Exception $e) {
        throw new CoreException('email_servers_update_domain(): Failed', $e);
    }
}



/*
 * Return data for the specified email_server
 *
 * This function returns information for the specified email_server. The email_server can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email_servers
 *
 * @param mixed $email_server The requested email_server. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The email_server data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified email_server does not exist, NULL will be returned.
 */
function email_servers_get_domain($params) {
    try {
        array_params($params, 'seoname', 'id');

        $params['table'] = 'domains';

        array_default($params, 'filters', array('id'      => $params['id'],
                                                'seoname' => $params['seoname']));

        array_default($params, 'columns', 'id,
                                           createdon,
                                           status,
                                           name,
                                           seoname,
                                           customer,
                                           description');

        return sql_simple_get($params);

    }catch(Exception $e) {
        throw new CoreException('email_servers_get_domain(): Failed', $e);
    }
}



/*
 * Return a list of all available email_servers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @see sql_simple_list()
 * @package email_servers
 * @version 2.5.38: Added function and documentation
 *
 * @param params $params The list parameters
 * @return mixed The list of available email_servers
 */
function email_servers_list_domains($params) {
    try {
        array_params($params, 'status');

        $params['table'] = 'domains';

        array_default($params, 'columns', 'seoname,name');

        return sql_simple_list($params);

    }catch(Exception $e) {
        throw new CoreException(tr('email_servers_list_domains(): Failed'), $e);
    }
}



/*
 * Return a list of all available email servers and domains for this customer
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @see sql_simple_list()
 * @package email_servers
 * @version 2.5.38: Added function and documentation
 *
 * @param params $params The scan parameters
 * @return mixed The list of available email servers and domains
 */
function email_servers_scan_domains($params = null) {
    try {
        if (isset($_SESSION['cache']['mail_servers'])) {
            return $_SESSION['cache']['mail_servers'];
        }

        if (empty($_SESSION['user']['customer'])) {
            if (!has_rights('god')) {
                throw new CoreException(tr('email_servers_scan_domains(): No customer specified for this user'), 'not-specified');
            }

            return array();
        }

        load_libs('databases');

        Arrays::ensure($params);
        array_default($params, 'seocustomer', $_SESSION['user']['customer']['seoname']);

        $retval      = array();
        $mailservers = email_servers_list(array('columns' => 'seodomain,domain,servers_id'));

        while ($mailserver = sql_fetch($mailservers)) {
            /*
             * Setup the database connector for this email server
             */
            $server    = servers_get($mailserver['servers_id'], true);
            $database  = databases_get_account(array('filter'  => array('id' => $server['database_accounts_id']),
                                                     'columns' => 'username,password'));

            $connector = sql_make_connector($mailserver['seodomain'], array('overwrite'  => true,
                                                                            'db'         => 'mail',
                                                                            'user'       => $database['username'],
                                                                            'pass'       => $database['password'],
                                                                            'ssh_tunnel' => array('domain' => $server['domain'])));

            $domains   = email_servers_list_domains(array('connector' => $mailserver['seodomain'],
                                                          'filters'   => array('customer' => $params['seocustomer'])));

            /*
             * If this server has domains where the customer has access to, add
             * it to the list
             */
            if ($domains->rowCount()) {
                $retval[$mailserver['seodomain']] = array();
            }

            /*
             * Add the domains where this customer has access to
             */
            while ($domain = sql_fetch($domains)) {
                $retval[$mailserver['seodomain']][$domain['seoname']] = $domain['name'];
            }
        }

        $_SESSION['cache']['mail_servers'] = $retval;

        return $retval;

    }catch(Exception $e) {
        throw new CoreException(tr('email_servers_scan_domains(): Failed'), $e);
    }
}



/*
 * Return HTML for a email server domain select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available email server domains
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function email_servers_select_domain($params = null) {
    try {
        Arrays::ensure($params);
        array_default($params, 'name'         , 'seodomain');
        array_default($params, 'class'        , 'form-control');
        array_default($params, 'selected'     , null);
        array_default($params, 'status'       , null);
        array_default($params, 'select_server', tr('Please select mail server first'));
        array_default($params, 'none'         , tr('Select a mail domain'));
        array_default($params, 'orderby'      , '`name`');

        if ($params['status'] !== false) {
            $where[] = ' `status` '.sql_is($params['status'], ':status');
            $execute[':status'] = $params['status'];
        }

        if (empty($where)) {
            $where = '';

        } else {
            $where = ' WHERE '.implode(' AND ', $where).' ';
        }

        if (empty($params['server'])) {
            /*
             * No mail server specified, we can't check for domains
             */
            $params['resource'] = null;
            array_default($params, 'empty', $params['select_server']);

        } else {
            $query              = 'SELECT `seoname`, `name` FROM `domains` '.$where.' ORDER BY '.$params['orderby'];
            $params['resource'] = sql_query($query, $execute);
        }

        array_default($params, 'empty', tr('No mail domains available'));

        $retval = html_select($params);

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('email_servers_select_domain(): Failed', $e);
    }
}



/*
 * Validate an email server
 *
 * This function will validate all relevant fields in the specified $email_server array
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available categories
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package categories
 *
 * @param params $account The email account to be validated
 * @params natural $account[id] The database table id for the specified email account, if not new
 * @params string $account[domains_id]
 * @params string $account[servers_id]
 * @params string $account[email]
 * @params string $account[description]
 * @return The specified email account, validated
 */
function email_servers_validate_account($account) {
    try {
        load_libs('email,seo,servers,domains');

        $v = new ValidateForm($account, 'id,email,server,domain,password,max_size,description');
        $v->isNotEmpty($account['email'], tr('Please specify an email address'));
        $v->isNotEmpty($account['domain'], tr('Please specify a mail domain'));
        $v->isNotEmpty($account['server'], tr('Please specify a mail server'));

        /*
         * Validate the server
         */
        try {
            $account['servers_id'] = servers_like($account['server']);
            $account['servers_id'] = servers_get ($account['servers_id'], false, true, true);

            if (!$account['servers_id']) {
                $v->setError(tr('The specified mail server ":server" does not exist', array(':server' => $account['server'])));
            }

        }catch(Exception $e) {
            /*
             * For now, servers_get also gives exception if server does not exist!
             */
            switch ($e->getCode()) {
                case 'not-exists';
                    $v->setError(tr('The specified mail server ":server" does not exist', array(':server' => $account['server'])));
                    break;

                default:
                    throw $e;
            }
        }

        /*
         * Validate the domain
         *
         * NOTE: This domain has to be available on the mail server!!
         */
        $account['domains_id'] = sql_get('SELECT `id` FROM `domains` WHERE `seoname` = :seoname', true, array(':seoname' => $account['domain']));

        if (!$account['domains_id']) {
            $v->setError(tr('The specified mail domain ":domain" does not exist', array(':domain' => $account['domain'])));
        }

        /*
         * Validate description
         */
        if ($account['description']) {
            $v->hasMinChars($account['description'], 16, tr('Please specify at least 16 characters for a description'));
            $v->hasMaxChars($account['description'], 2048, tr('Please specify no more than 2047 characters for a description'));

        } else {
            $account['description'] = null;
        }

        /*
         * Validate password
         */
        if ($account['password']) {
            $v->hasMinChars($account['password'], 8, tr('Please specify at least 8 characters for a password'));
            $v->hasMaxChars($account['password'], 64, tr('Please specify no more than 64 characters for a password'));

        } else {
            $account['password'] = '';
        }

        /*
         * Validate status
         */
        if ($account['status']) {
            $v->hasMinChars($account['status'], 2, tr('Please specify at least 2 characters for a password'));
            $v->hasMaxChars($account['status'], 16, tr('Please specify no more than 16 characters for a password'));

        } else {
            $account['status'] = null;
        }

        /*
         * Validate max_size
         */
        if ($account['max_size']) {
            $v->is_natural($account['max_size'], 8, tr('Please specify a valid natural number for max_size'));

        } else {
// :TODO: Make this configurable
            $account['max_size'] = 30000000000;
        }

        $v->isValid();

        $exists = sql_get('SELECT `id` FROM `accounts` WHERE `email` = :email AND `id` != :id LIMIT 1', true, array(':email' => $account['email'], ':id' => isset_get($account['id'], 0)));

        if ($exists) {
            $v->setError(tr('The account ":account" already exists', array(':domain' => $account['domain'])));
        }

        $exists = sql_get('SELECT `id` FROM `aliases` WHERE `source` = :source AND `id` != :id LIMIT 1', true, array(':source' => $account['email'], ':id' => isset_get($account['id'], 0)));

        if ($exists) {
            $v->setError(tr('The account ":account" already exists as an alias', array(':domain' => $account['domain'])));
        }

        $account['seoemail'] = seo_unique($account['email'], 'accounts', $account['id'], 'seoemail');

        $v->isValid();

        return $account;

    }catch(Exception $e) {
        if ($e->getCode() == '1049') {
            $servers = servers_list_domains($domain['server']);
            $server  = servers_get($domain['server']);
            $domain  = not_empty($servers[$domain['server']], $domain['server']);

            throw new CoreException(tr('email_servers_validate_account(): Specified email server ":server" (server domain ":domain") does not appear to have a "mail" database', array(':server' => $domain, ':domain' => $server['domain'])), 'not-exists');
        }

        throw new CoreException(tr('email_servers_validate_account(): Failed'), $e);
    }
}



/*
 * Insert the specified email account on the mail server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 * @version
 *
 * @param params $account
 * @param string $account[email]
 * @param string $account[password]
 * @param null numeric $account[max_size]
 * @param string $account[description]
 * @return params The specified email account, validated and sanitized
 */
function email_servers_insert_account($account) {
    try {
        $account  = email_servers_validate_account($account);
        $password = 'ENCRYPT(:password, CONCAT("$6$", SUBSTRING(SHA(RAND()), -16)))';

        if (!$account['password']) {
            /*
             * Have password be an empty entry
             */
            $password = ':password';
        }

        sql_query('INSERT INTO `accounts` (`domains_id`, `status`,   `password` , `email`, `seoemail`, `max_size`, `current_size`, `description`)
                   VALUES                 (:domains_id , :status , '.$password.', :email , :seoemail , :max_size , 0             , :description )',

                   array(':domains_id'    => $account['domains_id'],
                         ':status'        => $account['status'],
                         ':password'      => $account['password'],
                         ':email'         => $account['email'],
                         ':seoemail'      => $account['seoemail'],
                         ':max_size'      => $account['max_size'],
                         ':description'   => $account['description']));

        $account['id'] = sql_insert_id();
        return $account;

    }catch(Exception $e) {
        throw new CoreException('email_servers_insert_account(): Failed', $e);
    }
}



/*
 * Update the email account on the mail server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 * @version
 *
 * @param params $account
 * @return params The specified email account, validated and sanitized
 */
function email_servers_update_account($account) {
    try {
        $account = email_servers_validate_account($account);
        $update  = sql_query('UPDATE `accounts`

                              SET    `description` = :description

                              WHERE `id`           = :id',

                              array(':id'          => $account['id'],
                                    ':description' => $account['description']));

        $account['_updated'] = (boolean) $update->rowCount();

        if (!empty($account['password'])) {
            email_servers_update_password($account['email'], $account['password']);
            $account['_updated'] = true;
        }

        return $account;

    }catch(Exception $e) {
        throw new CoreException('email_servers_update_account(): Failed', $e);
    }
}



/*
 * Update the password for the specified email account
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @param string $account
 * @param string $password
 * @return void
 */
function email_servers_update_password($account, $password) {
    try {
        $update = sql_query('UPDATE `accounts`

                             SET    `password` = ENCRYPT(:password, CONCAT("$6$", SUBSTRING(SHA(RAND()), -16)))

                             WHERE  `email`    = :email',

                             array(':email'    => $account,
                                   ':password' => $password));

    }catch(Exception $e) {
        throw new CoreException('email_servers_update_password(): Failed', $e);
    }
}



/*
 * Return an array with the sizes for all mail boxes for the specified domain on the specified mail server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @param mixed $servers
 * @param mixed $servers
 * @return array An array with mailbox => bytes format
 */
function email_servers_list_mailbox_sizes($server, $domain) {
    try {
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
            throw new CoreException(tr('email_servers_list_mailbox_sizes(): Specified domain ":domain" is not a valid domain', array(':domain' => $domain)), 'invalid');
        }

        $total   = 0;
        $retval  = array();
        $results = linux_find($server, array('path'     => '/var/mail/vhosts/'.$domain,
                                             'sudo'     => true,
                                             'maxdepth' => 1,
                                             'type'     => 'd',
                                             'exec'     => array('du', array('-s', '{}'))));

        foreach ($results as $result) {
            $result = trim($result);
            $size   = (Strings::until($result, "\t") * 1024);
            $total += $size;

            $retval[Strings::fromReverse($result, '/')] = $size;
        }

        ksort($retval);
        $retval['--total--'] = $total;

        return $retval;

    }catch(Exception $e) {
        if (!linux_file_exists($server, '/var/mail/vhosts/'.$domain, true)) {
            $e->setCode('not-exist');
            throw new CoreException(tr('email_servers_list_mailbox_sizes(): Specified domain ":domain" does not exists as a mail domain', array(':domain' => $domain)), $e);
        }

        throw new CoreException('email_servers_list_mailbox_sizes(): Failed', $e);
    }
}



/*
 * Check for all known issues on the mail server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @param mixed $server
 * @param boolean [true] $fix If set to true, will auto fix all found issues
 * @return array An array with all accounts that had issues in the format $key => $value email-address (or domain) => issue
 */
function email_servers_check($server, $fix = true) {
    try {
        $failed['seodomains'] = email_servers_check_seo(array('fix'    => $fix,
                                                              'server' => $server,
                                                              'table'  => 'domains'));

        $failed['seosources'] = email_servers_check_seo(array('fix'    => $fix,
                                                              'server' => $server,
                                                              'table'  => 'aliases',
                                                              'column' => 'source'));

        $failed['seotargets'] = email_servers_check_seo(array('fix'    => $fix,
                                                              'server' => $server,
                                                              'table'  => 'aliases',
                                                              'column' => 'target'));

        $failed['seoaccounts'] = email_servers_check_seo(array('fix'    => $fix,
                                                               'server' => $server,
                                                               'table'  => 'accounts',
                                                               'column' => 'email'));

        $failed['orphans'] = email_servers_check_orphans($server, $fix);

        $failed['count'] = count($failed['orphans']) + count($failed['seotargets']) + count($failed['seosources']) + count($failed['seoaccounts']) + count($failed['seodomains']);

        return $failed;

    }catch(Exception $e) {
        throw new CoreException('email_servers_check(): Failed', $e);
    }
}



/*
 * Check the specified mail server for invalid seonames in the domains table
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @param mixed $server
 * @param boolean [true] $add If set to true, will add the orphaned account in the database again with status "orphaned"
 * @return array An array with all orphaned directories
 */
function email_servers_check_seo($params) {
    try {
        load_libs('seo');

        Arrays::ensure($params, 'server,domain,rerun');
        array_default($params, 'column', 'name');
        array_default($params, 'rerun' , true);

        if ($params['rerun']) {
            log_console(tr('Checking seo data for table ":table" on mail server ":server"', array(':server' => $params['server'], ':table' => $params['table'])), 'cyan');

        } else {
            log_console(tr('Re-checking seo data for table ":table" on mail server ":server"', array(':server' => $params['server'], ':table' => $params['table'])), 'cyan');
        }

        $failed  = array();
        $domains = sql_query('SELECT `id`, `'.$params['column'].'`, `seo'.$params['column'].'` FROM `'.$params['table'].'`');

        while ($domain = sql_fetch($domains)) {
            if ($domain['seo'.$params['column']] === seo_string($domain[$params['column']])) {
                /*
                 * Seo name maches, we're done!
                 */
                continue;
            }

            /*
             * Seo name does not match. This MIGHT be because it is seo_string()
             * + a number because of double issues
             */
            $extra = Strings::from($domain['seo'.$params['column']], seo_string($domain[$params['column']]));

            if ($extra) {
                if (is_natural($extra)) {
                    /*
                     * We have extra data that IS a natural number, so at least
                     * it is valid,
                     */
                    continue;
                }
            }

            /*
             * The seo name is NOT valid, update it.
             */
            $failed[] = $domain[$params['column']];

            if ($params['rerun']) {
                /*
                 * Update the SEO value to something temmporal because what if
                 * some other value is occupying the real SEO value (whcih then
                 * also would be detected as incorrect) ?
                 */
                log_console(tr('Updating seo:column for table ":table" with value ":value" to random value', array(':table' => $params['table'], ':column' => $params['column'], ':value' => $domain[$params['column']])), 'VERBOSE/cyan');
                sql_query('UPDATE `'.$params['table'].'` SET `seo'.$params['column'].'` = :seo'.$params['column'].' WHERE `id` = :id', array(':id' => $domain['id'], ':seo'.$params['column'] => str_random(8)));

            } else {
                /*
                 * Update the SEO value to the real and correct seoname
                 */
                $seoname = seo_unique($domain[$params['column']], $params['table'], $domain['id'], 'seo'.$params['column'], '-', null);

                log_console(tr('Updating seo:column for table ":table" with value ":value" to ":seovalue"', array(':table' => $params['table'], ':column' => $params['column'], ':value' => $domain[$params['column']], ':seovalue' => $seoname)), 'VERBOSE/cyan');
                sql_query('UPDATE `'.$params['table'].'` SET `seo'.$params['column'].'` = :seo'.$params['column'].' WHERE `id` = :id', array(':id' => $domain['id'], ':seo'.$params['column'] => $seoname));
            }
        }

        if ($failed and $params['rerun']) {
            /*
             * We've set all invalid SEO values to something temporary. Run this
             * function again to update it to the permanent, correct value
             */
            $params['rerun'] = false;

            log_console(tr('Found failed entries in table ":table", rerunning', array(':table' => $params['table'])), 'VERBOSE/warning');
            return email_servers_check_seo($params);
        }

        return $failed;

    }catch(Exception $e) {
        throw new CoreException('email_servers_check_seo(): Failed', $e);
    }
}



/*
 * Check the specified mail server for email account directories that are not registered in the database, effectively being orphaned
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @param mixed $server
 * @param boolean [true] $add If set to true, will add the orphaned account in the database again with status "orphaned"
 * @return array An array with all orphaned directories
 */
function email_servers_check_orphans($server, $add = true) {
    try {
        log_console(tr('Checking for orphaned email account data'), 'cyan');

        $retval  = array();
        $domains = linux_ls($server, '/var/mail/vhosts', true);

        foreach ($domains as $domain) {
            log_console(tr('Searching for orphaned accounts in domain ":domain"', array(':domain' => $domain)), 'VERBOSE/cyan');
            $accounts = linux_ls($server, '/var/mail/vhosts/'.$domain, true);

            foreach ($accounts as $account) {
                try {
                    $exists = sql_get('SELECT `id`, `status` FROM `accounts` WHERE `email` = :email', array(':email' => $account.'@'.$domain));

                    if (!$exists) {
                        /*
                         * Found an orphan!
                         */
                        log_console(tr('Found orphaned email data path ":path"', array(':path' => '/var/mail/vhosts/'.$domain.'/'.$account)), 'warning');

                        if ($add) {
                            log_console(tr('Adding email account ":account" for orphaned data path ":path"', array(':account' => $account.'@'.$domain, ':path' => '/var/mail/vhosts/'.$domain.'/'.$account)), 'warning');
                            $retval[] = $account.'@'.$domain;

                            email_servers_insert_account(array('server'      => $server,
                                                               'domain'      => $domain,
                                                               'email'       => $account.'@'.$domain,
                                                               'status'      => 'orphaned',
                                                               'description' => tr('This email address was found as file ":path" by the email_servers_find_orphans() function while it did not exist in the database. This database entry was created to ensure it will be accessible from the user interface', array(':path' => '/var/mail/vhosts/'.$domain.'/'.$account))));
                        }
                    }

                }catch(Exception $e) {
                    log_console(tr('Failed to add orphaned email address ":email"', array(':email' => $account.'@'.$domain)));
                    log_console($e);
                }
            }
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('email_servers_check_orphans(): Failed', $e);
    }
}
?>
