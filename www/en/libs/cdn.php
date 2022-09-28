<?php
/*
 * CDN library
 *
 * This library contains functions to manage the CDN servers
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package cdn
 */



/*
 * Send the specified file to the specified CDN server
 */
function cdn_send_files($files, $server, $section, $group = null){
    global $_CONFIG;

    try{
        load_libs('api');

        $api_account = cdn_get_api_account($server);
        $result      = api_call_base($api_account, '/cdn/add-files', array('project' => PROJECT, 'section' => $section, 'group' => $group), $files);

        log_file(tr('cdn_send_files(): Successfully sent files ":files" to server ":server" using api account ":account"', array(':files' => $files, ':server' => $server, ':account' => $api_account)), 'DEBUG/cdn');

        return $result;

    }catch(Exception $e){
        throw new CoreException('cdn_send_files(): Failed', $e);
    }
}



/*
 * Removes the specified files from all CDN servers
 */
function cdn_delete_files($list, $column = 'file'){
    global $_CONFIG;

    try{
        load_libs('api');

        if(!$list){
            throw new CoreException(tr('cdn_delete_files(): No files specified'), 'not-specified');
        }

        log_console(tr('Deleting files ":files" from CDN system', array(':files' => $list)), 'cyan');

        /*
         * Get list of servers / files
         */
        $count              = 0;
        $servers            = array();
        $files              = array();
        $in                 = sql_in($list);
        $in[':environment'] = ENVIRONMENT;

        $r = sql_query('SELECT   `cdn_servers`.`seoname` AS `server`,
                                 `cdn_files`.`file`

                        FROM     `cdn_files`

                        JOIN     `cdn_servers`
                        ON       `cdn_servers`.`id`           = `cdn_files`.`servers_id`

                        JOIN     `api_accounts`
                        ON       `api_accounts`.`id`          = `cdn_servers`.`api_accounts_id`
                        AND      `api_accounts`.`status`      IS NULL
                        AND      `api_accounts`.`environment` = :environment

                        WHERE    `cdn_files`.`'.$column.'` IN ('.sql_in_columns($in).')

                        ORDER BY `cdn_servers`.`seoname`',

                        $in);

        while($row = sql_fetch($r)){
            if(empty($servers[$row['server']])){
                $servers[$row['server']] = array();
            }

            $files[] = $row['file'];
            $servers[$row['server']]['files['.$count++.']'] = $row['file'];
        }

        /*
         * Delete files from each CDN server
         */
        foreach($servers as $server => $files){
            $files['project'] = PROJECT;
            $api_account      = cdn_get_api_account($server);

            api_call_base($api_account, '/cdn/delete-files', $files);
        }

        /*
         * Delete the files one by one from DB
         */
        $delete = sql_prepare('DELETE FROM `cdn_files` WHERE `file` = :file');

        foreach($files as $file){
            /*
             * Delete this file from the cdn_files table
             */
            $delete->execute(array(':file' => $file));
        }

    }catch(Exception $e){
        throw new CoreException('cdn_delete_files(): Failed', $e);
    }
}



/*
 * Removes the specified groups within the specified groups from all CDN servers
 */
function cdn_delete_groups($groups){
    try{
        return cdn_delete_files($groups, 'group');

    }catch(Exception $e){
        throw new CoreException('cdn_delete_groups(): Failed', $e);
    }
}



/*
 * Removes the specified sections within the specified sections from all CDN
 * servers
 */
function cdn_delete_sections($sections){
    try{
        return cdn_delete_files($groups, 'section');

    }catch(Exception $e){
        throw new CoreException('cdn_delete_sections(): Failed', $e);
    }
}



/*
 * Assigns random CDN servers for the file to be stored in the CDN
 */
function cdn_assign_servers(){
    global $_CONFIG;

    try{
        $servers = sql_list('SELECT `cdn_servers`.`id`,
                                    `cdn_servers`.`seoname`

                             FROM   `cdn_servers`

                             JOIN   `api_accounts`
                             ON     `api_accounts`.`id`          = `cdn_servers`.`api_accounts_id`
                             AND    `api_accounts`.`status`      IS NULL
                             AND    `api_accounts`.`environment` = :environment

                             WHERE  `cdn_servers`.`status` IS NULL

                             ORDER BY RAND() LIMIT '.$_CONFIG['cdn']['copies'],

                             array(':environment' => ENVIRONMENT));
        return $servers;

    }catch(Exception $e){
        throw new CoreException('cdn_assign_servers(): Failed', $e);
    }
}



/*
 * Returns a CDN server id from $_CONFIG[‘cdn’][servers’] or the specified cdns list
 */
function cdn_pick_server($cdns){
    global $_CONFIG;
    static $key = null;

    try{
        if(!$cdns){
            throw new CoreException(tr('cdn_pick_server(): No CDNs specified'), 'not-specified');
        }

        if(!is_array($cdns)){
            throw new CoreException(tr('cdn_pick_server(): Invalid CDN ":cdns" specified, must be array', array(':cdns' => $cdns)), 'invalid');
        }

        if(!array_diff($_CONFIG['cdn']['servers'], $cdns)){
            throw new CoreException(tr('cdn_pick_server(): Specified CDN ":cdns" does not exist, check "$_CONFIG[cdn][servers]" configuration', array(':cdns' => $cdns)), 'invalid');
        }

        if($key === null){
            if(empty($_SESSION['cdn']['first_id'])){
                /*
                 * Get $_SESSION['cdn'] data first!
                 */
                //cdn_get_session_data();
            }

            $key = $_SESSION['cdn']['first_id'];
        }

        if(++$key > count($cdns) - 1){
            $key = 0;
        }

        return $cdns[$key];

    }catch(Exception $e){
        throw new CoreException('cdn_pick_server(): Failed', $e);
    }
}



/*
 * Will balance all files over the available CDN servers using the configured amount of required copies
 */
function cdn_balance($params){
    global $_CONFIG;

    try{
        //
    }catch(Exception $e){
        throw new CoreException('cdn_balance(): Failed', $e);
    }
}



/*
 * Update $_SESSION[‘cdn’] from the CDN filesystem structure
 */
function cdn_update_session(){
    global $_CONFIG;

    try{
        //
    }catch(Exception $e){
        throw new CoreException('cdn_update_session(): Failed', $e);
    }
}



/*
 *
 */
function cdn_get_url($table, $filename){
    global $_CONFIG;

    try{

//        return /'.$_CONFIG['domain'].'/'.$table.'/'.$filename.'/';

    }catch(Exception $e){
        throw new CoreException('cdn_get_url(): Failed', $e);
    }
}



/*
 *
 */
function cdn_get_domain($cdn_id){
    global $_CONFIG;

    try{
        return str_replace(':id', $cdn_id, $_CONFIG['cdn']['domain']);

    }catch(Exception $e){
        throw new CoreException('cdn_get_url(): Failed', $e);
    }
}



/*
 * Validate CDN server
 */
function cdn_validate_server($server){

    try{
        load_libs('validate,seo');

        $v = new ValidateForm($server, 'name,baseurl,api_account,description');

        $v->isNotEmpty ($server['name']        , tr('Please specify a CDN server name'));
        $v->hasMaxChars($server['name']   ,  32, tr('Please make sure the specified CDN server name is less than 32 characters long'));

        $v->isNotEmpty ($server['baseurl']     , tr('Please specify a base URL'));
        $v->hasMaxChars($server['baseurl'], 127, tr('Please make sure the specified base URL is less than 127 characters long'));

        $v->isNotEmpty ($server['api_account'] , tr('Please specify an API account'));

        $server['api_accounts_id'] = sql_get('SELECT `id` FROM `api_accounts` WHERE `seoname` = :seoname AND `status` IS NULL', true, array(':seoname' => $server['api_account']));

        if(!$server['api_accounts_id']){
            $v->setError(tr('Specified API account ":account" does not exist', array(':account' => $server['api_account'])));
        }

        $exists = sql_exists('cdn_servers', 'name', $server['name'], $server['id']);

        if($exists){
            $v->setError(tr('The domain ":name" already exists', array(':name' => $server['name'])));
        }

        $server['seoname'] = seo_unique($server['name'], 'cdn_servers', $server['id'], 'seoname');

        $v->isValid();

        return $server;

    }catch(Exception $e){
        throw new CoreException(tr('cdn_validate_server(): Failed'), $e);
    }
}



/*
 * Validate CDN project
 */
function cdn_validate_project($project, $insert = true){

    try{
        load_libs('validate,seo');

        $v = new ValidateForm($project, 'name,description');

        $v->isNotEmpty ($project['name']    , tr('No project specified'));
        $v->hasMinChars($project['name'],  2, tr('Please ensure the path has at least 2 characters'));
        $v->hasMaxChars($project['name'], 32, tr('Please ensure the path has less than 32 characters'));

        if(empty($project['desdription'])){
            $project['desdription'] = '';

        }else{
            $v->hasMinChars($project['desdription'],   16, tr('Please ensure the description has at least 16 characters, or empty'));
            $v->hasMaxChars($project['desdription'], 2047, tr('Please ensure the description has less than 2047 characters'));
        }

        $project['seoname'] = seo_unique($project['name'], 'cdn_projects', $project['id']);

        $v->isValid();

        return $project;

    }catch(Exception $e){
        throw new CoreException(tr('cdn_validate_project(): Failed'), $e);
    }
}



/*
 *
 */
function cdn_get_api_account($server){
    try{
        load_libs('api');

        $api_account = sql_get('SELECT `api_accounts`.`seoname`

                                FROM   `cdn_servers`

                                JOIN   `api_accounts`
                                ON     `api_accounts`.`id`          = `cdn_servers`.`api_accounts_id`
                                AND    `api_accounts`.`status`      IS NULL
                                AND    `api_accounts`.`environment` = :environment

                                WHERE  `cdn_servers`.`seoname`      = :seoname
                                AND   (`cdn_servers`.`status` IS NULL OR `cdn_servers`.`status` = "testing")',

                                true, array(':seoname'     => $server,
                                            ':environment' => ENVIRONMENT));

        if(!$api_account){
            throw new CoreException(tr('cdn_validate_project(): Specified server ":server" does not exist or is not available', array(':server' => $server)), 'warning/not-exists');
        }

        return $api_account;

    }catch(Exception $e){
        throw new CoreException('cdn_get_api_account(): Failed', $e);
    }
}



/*
 * Get information from specified CDN server
 */
function cdn_get_server_info($server){
    try{
        load_libs('api');

        $api_account = cdn_get_api_account($server);
        $result      = api_call_base($api_account, '/cdn/info');

        return $result;

    }catch(Exception $e){
        throw new CoreException('cdn_get_server_info(): Failed', $e);
    }
}



/*
 * Test specified CDN server
 */
function cdn_test_server($server){
    try{
        load_libs('api');
        $api_account = cdn_get_api_account($server);

        sql_query('UPDATE `cdn_servers` SET `status` = "testing" WHERE `seoname` = :seoname', array(':seoname' => $server));
        $result = api_test_account($api_account);

        sql_query('UPDATE `cdn_servers` SET `status` = NULL WHERE `seoname` = :seoname', array(':seoname' => $server));
        return $result;

    }catch(Exception $e){
        throw new CoreException('cdn_test_server(): Failed', $e);
    }
}



/*
 * Register this project at the specified CDN server
 */
function cdn_register_project($server){
    global $_CONFIG;

    try{
        load_libs('api');

        $api_account = cdn_get_api_account($server);
        $result      = api_call_base($api_account, '/cdn/project-exists', array('project' => PROJECT));

        if(empty($result['exists'])){
            sql_query('UPDATE `cdn_servers` SET `status` = "registering" WHERE `seoname` = :seoname', array(':seoname' => $server));
            $result = api_call_base($api_account, '/cdn/create-project', array('name' => PROJECT));

            sql_query('UPDATE `cdn_servers` SET `status` = NULL WHERE `seoname` = :seoname', array(':seoname' => $server));
            return $result;
        }

        /*
         * Project already exists
         */
        return false;

    }catch(Exception $e){
        throw new CoreException('cdn_register_project(): Failed', $e);
    }
}



/*
 * Unregister this project from the specified CDN server
 */
function cdn_unregister_project($server){
    try{
        load_libs('api');

        $api_account = cdn_get_api_account($server);
        $result      = api_call_base($api_account, '/cdn/project-exists', array('project' => PROJECT));

        if(!empty($result['exists'])){
            /*
             * Project does not exist on specified serv
             */
            return false;
        }

        sql_query('UPDATE `cdn_servers` SET `status` = "unregistering" WHERE `seoname` = :seoname', array(':seoname' => $server));
        $result = api_call_base($api_account, '/cdn/delete-project', array('name' => PROJECT));
        return $result;

    }catch(Exception $e){
        throw new CoreException('cdn_unregister_project(): Failed', $e);
    }
}



/*
 * Update all
 */
function cdn_update_pub(){
    try{

    }catch(Exception $e){
        throw new CoreException('cdn_update_pub(): Failed', $e);
    }
}