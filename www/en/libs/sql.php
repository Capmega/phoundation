<?php
/*
 * SQL library
 *
 * This file contains various functions to access databases over PDO
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
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
 * @package
 *
 * @return void
 */
function sql_library_init(){
    try{
        if(!class_exists('PDO')){
            /*
             * Wulp, PDO class not available, PDO driver is not loaded somehow
             */
            throw new BException('sql_library_init(): Could not find the "PDO" class, does this PHP have PDO available?', 'not-available');
        }

        if(!defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')){
            /*
             * Wulp, MySQL library is not available
             */
            throw new BException('sql_library_init(): Could not find the "MySQL" library. To install this on Ubuntu derrivates, please type "sudo apt install php-mysql', 'not-available');
        }

    }catch(Exception $e){
        throw new BException('sql_library_init(): Failed', $e);
    }
}



/*
 * Execute specified query
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_query($query, $execute = false, $connector_name = null){
    global $core;

    try{
        log_console(tr('Executing query ":query"', array(':query' => $query)), 'VERYVERBOSE/cyan');

        $connector_name = sql_connector_name($connector_name);
        $connector_name = sql_init($connector_name);
        $query_start    = microtime(true);

        if(!is_string($query)){
            if(is_object($query)){
                if(!($query instanceof PDOStatement)){
                    throw new BException(tr('sql_query(): Object of unknown class ":class" specified where either a string or a PDOStatement was expected', array(':class' => get_class($query))), 'invalid');
                }

                /*
                 * PDO statement was specified instead of a query
                 */
                if($query->queryString[0] == ' '){
                    debug_sql($query, $execute);
                }

                $query->execute($execute);
                return $query;
            }

            throw new BException(tr('sql_query(): Specified query ":query" is not a string', array(':query' => $query)), 'invalid');
        }

        if(!empty($core->register['sql_debug_queries'])){
            $core->register['sql_debug_queries']--;
            $query = ' '.$query;
        }

        if(substr($query, 0, 1) == ' '){
            debug_sql($query, $execute);
        }

        if(!$execute){
            /*
             * Just execute plain SQL query string.
             */
            $pdo_statement = $core->sql[$connector_name]->query($query);

        }else{
            /*
             * Execute the query with the specified $execute variables
             */
            $pdo_statement = $core->sql[$connector_name]->prepare($query);

            try{
                $pdo_statement->execute($execute);

            }catch(Exception $e){
                /*
                 * Failure is probably that one of the the $execute array values is not scalar
                 */
// :TODO: Move all of this to sql_error()
                if(!is_array($execute)){
                    throw new BException('sql_query(): Specified $execute is not an array!', 'invalid');
                }

                /*
                 * Check execute array for possible problems
                 */
                foreach($execute as $key => &$value){
                    if(!is_scalar($value) and !is_null($value)){
                        throw new BException(tr('sql_query(): Specified key ":value" in the execute array for query ":query" is NOT scalar! Value is ":value"', array(':key' => str_replace(':', '.', $key), ':query' => str_replace(':', '.', $query), ':value' => str_replace(':', '.', $value))), 'invalid');
                    }
                }

                throw $e;
            }
        }

        if(debug()){
            $current = 1;

            if(substr(current_function($current), 0, 4) == 'sql_'){
                $current = 2;
            }

            $function = current_function($current);
            $file     = current_function($current);
            $line     = current_function($current);

            $core->executedQuery(array('time'     => microtime(true) - $query_start,
                                       'query'    => debug_sql($query, $execute, true),
                                       'function' => current_function($current),
                                       'file'     => current_file($current),
                                       'line'     => current_line($current)));
        }

        return $pdo_statement;

    }catch(Exception $e){
        try{
            /*
             * Let sql_error() try and generate more understandable errors
             */
            sql_error($e, $query, $execute, isset_get($core->sql[$connector_name]));

        }catch(Exception $e){
            throw new BException(tr('sql_query(:connector): Query ":query" failed', array(':connector' => $connector_name, ':query' => $query)), $e);
        }
    }
}



/*
 * Prepare specified query
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_prepare($query, $connector_name = null){
    global $core;

    try{
        $connector_name = sql_connector_name($connector_name);
        $connector_name = sql_init($connector_name);

        return $core->sql[$connector_name]->prepare($query);

    }catch(Exception $e){
        throw new BException('sql_prepare(): Failed', $e);
    }
}



/*
 * Fetch and return data from specified resource
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_fetch($r, $single_column = false, $fetch_style = PDO::FETCH_ASSOC){
    try{
        if(!is_object($r)){
            throw new BException('sql_fetch(): Specified resource is not a PDO object', 'invalid');
        }

        $result = $r->fetch($fetch_style);

        if($result === false){
            /*
             * There are no entries
             */
            return null;
        }

        if($single_column === true){
            /*
             * Return only the first column
             */
            if(count($result) !== 1){
                throw new BException(tr('sql_fetch(): Failed for query ":query" to fetch single column, specified query result contains not 1 but ":count" columns', array(':count' => count($result), ':query' => $r->queryString)), 'multiple');
            }

            return array_shift($result);
        }

        if($single_column){
            if(!array_key_exists($single_column, $result)){
                throw new BException(tr('sql_fetch(): Failed for query ":query" to fetch single column ":column", specified query result does not contain the requested column', array(':column' => $single_column, ':query' => $r->queryString)), 'multiple');
            }

            return $result[$single_column];
        }

        /*
         * Return everything
         */
        return $result;

    }catch(Exception $e){
        throw new BException('sql_fetch(): Failed', $e);
    }
}



/*
 * Execute query and return only the first row
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_get($query, $single_column = null, $execute = null, $connector_name = null){
    try{
        $connector_name = sql_connector_name($connector_name);

        if(is_array($single_column)){
            /*
             * Argument shift, no columns were specified.
             */
            $tmp            = $execute;
            $execute        = $single_column;
            $single_column  = $tmp;
            unset($tmp);
        }

        $result = sql_query($query, $execute, $connector_name);

        if($result->rowCount() > 1){
            throw new BException(tr('sql_get(): Failed for query ":query" to fetch single row, specified query result contains not 1 but ":count" results', array(':count' => $result->rowCount(), ':query' => debug_sql($result->queryString, $execute, true))), 'multiple');
        }

        return sql_fetch($result, $single_column);

    }catch(Exception $e){
        if(is_object($query)){
            $query = $query->queryString;
        }

        if((strtolower(substr(trim($query), 0, 6)) !== 'select') and (strtolower(substr(trim($query), 0, 4)) !== 'show')){
            throw new BException('sql_get(): Query "'.str_log(debug_sql($query, $execute, true), 4096).'" is not a select or show query and as such cannot return results', $e);
        }

        throw new BException('sql_get(): Failed', $e);
    }
}



/*
 * Execute query and return only the first row
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_list($query, $execute = null, $numerical_array = false, $connector_name = null){
    try{
        $connector_name = sql_connector_name($connector_name);

        if(is_object($query)){
            $r     = $query;
            $query = $r->queryString;

        }else{
            $r = sql_query($query, $execute, $connector_name);
        }

        $retval = array();

        while($row = sql_fetch($r)){
            if(is_scalar($row)){
                $retval[] = $row;

            }else{
                switch($numerical_array ? 0 : count($row)){
                    case 0:
                        /*
                         * Force numerical array
                         */
                        $retval[] = $row;
                        break;

                    case 1:
                        $retval[] = array_shift($row);
                        break;

                    case 2:
                        $retval[array_shift($row)] = array_shift($row);
                        break;

                    default:
                        $retval[array_shift($row)] = $row;
                }
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new BException('sql_list(): Failed', $e);
    }
}



/*
 * Connect with the main database
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_init($connector_name = null){
    global $_CONFIG, $core;

    try{
        $connector_name = sql_connector_name($connector_name);

        if(!empty($core->sql[$connector_name])){
            /*
             * Already connected to requested DB
             */
            return $connector_name;
        }

        /*
         * Get a database configuration connector and ensure its valid
         */
        $connector = sql_ensure_connector($_CONFIG['db'][$connector_name]);

        /*
         * Set the MySQL rand() seed for this session
         */
// :TODO: On PHP7, update to random_int() for better cryptographic numbers
        $_SESSION['sql_random_seed'] = mt_rand();

        /*
         * Connect to database
         */
        log_console(tr('Connecting with SQL connector ":name"', array(':name' => $connector_name)), 'VERYVERBOSE/cyan');
        $core->sql[$connector_name] = sql_connect($connector);

        /*
         * This is only required for the system connection
         */
        if((PLATFORM_CLI) and ($core->register['script'] == 'init') and FORCE and !empty($connector['init'])){
            include(__DIR__.'/handlers/sql-init-force.php');
        }

        /*
         * Check current init data?
         */
        if(empty($core->register['skip_init_check'])){
            if(!defined('FRAMEWORKDBVERSION')){
                /*
                 * Get database version
                 *
                 * This can be disabled by setting $_CONFIG[db][CONNECTORNAME][init] to false
                 */
                if(!empty($_CONFIG['db'][$connector_name]['init'])){
                    try{
                        $r = $core->sql[$connector_name]->query('SELECT `project`, `framework`, `offline_until` FROM `versions` ORDER BY `id` DESC LIMIT 1;');

                    }catch(Exception $e){
                        if($e->getCode() !== '42S02'){
                            if($e->getMessage() === 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'offline_until\' in \'field list\''){
                                $r = $core->sql[$connector_name]->query('SELECT `project`, `framework` FROM `versions` ORDER BY `id` DESC LIMIT 1;');

                            }else{
                                /*
                                 * Compatibility issue, this happens when older DB is running init.
                                 * Just ignore it, since in these older DB's the functionality
                                 * wasn't even there
                                 */
                                throw $e;
                            }
                        }
                    }

                    try{
                        if(empty($r) or !$r->rowCount()){
                            log_console(tr('sql_init(): No versions table found or no versions in versions table found, assumed empty database ":db"', array(':db' => $_CONFIG['db'][$connector_name]['db'])), 'yellow');

                            define('FRAMEWORKDBVERSION', 0);
                            define('PROJECTDBVERSION'  , 0);

                            $core->register['no-db'] = true;

                        }else{
                            $versions = $r->fetch(PDO::FETCH_ASSOC);

                            if(!empty($versions['offline_until'])){
                                if(PLATFORM_HTTP){
                                    page_show(503, array('offline_until' => $versions['offline_until']));
                                }
                            }

                            define('FRAMEWORKDBVERSION', $versions['framework']);
                            define('PROJECTDBVERSION'  , $versions['project']);

                            if(version_compare(FRAMEWORKDBVERSION, '0.1.0') === -1){
                                $core->register['no-db'] = true;
                            }
                        }

                    }catch(Exception $e){
                        /*
                         * Database version lookup failed. Usually, this would be due to the database being empty,
                         * and versions table does not exist (yes, that makes a query fail). Just to be sure that
                         * it did not fail due to other reasons, check why the lookup failed.
                         */
                        load_libs('init');
                        init_process_version_fail($e);
                    }

                    /*
                     * On console, show current versions
                     */
                    if((PLATFORM_CLI) and VERBOSE){
                        log_console(tr('sql_init(): Found framework code version ":frameworkcodeversion" and framework database version ":frameworkdbversion"', array(':frameworkcodeversion' => FRAMEWORKCODEVERSION, ':frameworkdbversion' => FRAMEWORKDBVERSION)));
                        log_console(tr('sql_init(): Found project code version ":projectcodeversion" and project database version ":projectdbversion"'        , array(':projectcodeversion'   => PROJECTCODEVERSION  , ':projectdbversion'   => PROJECTDBVERSION)));
                    }


                    /*
                     * Validate code and database version. If both FRAMEWORK and PROJECT versions of the CODE and DATABASE do not match,
                     * then check exactly what is the version difference
                     */
                    if((FRAMEWORKCODEVERSION != FRAMEWORKDBVERSION) or (PROJECTCODEVERSION != PROJECTDBVERSION)){
                        load_libs('init');
                        init_process_version_diff();
                    }
                }
            }

        }else{
            /*
             * We were told NOT to do an init check. Assume database framework
             * and project versions are the same as their code variants
             */
            define('FRAMEWORKDBVERSION', FRAMEWORKCODEVERSION);
            define('PROJECTDBVERSION'  , PROJECTCODEVERSION);
        }

        return $connector_name;

    }catch(Exception $e){
        include(__DIR__.'/handlers/sql-init-fail.php');
    }
}



/*
 * Close the connection for the specified connector
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_close($connector = null){
    global $_CONFIG, $core;

    try{
        $connector = sql_connector_name($connector);
        unset($core->sql[$connector]);

    }catch(Exception $e){
        throw new BException(tr('sql_close(): Failed for connector ":connector"', array(':connector' => $connector)), $e);
    }
}



/*
 * Connect to database and do a DB version check.
 * If the database was already connected, then just ignore and continue.
 * If the database version check fails, then exception
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_connect(&$connector, $use_database = true){
    global $_CONFIG;

    try{
        array_params($connector);
        array_default($connector, 'driver' , null);
        array_default($connector, 'host'   , null);
        array_default($connector, 'user'   , null);
        array_default($connector, 'pass'   , null);
        array_default($connector, 'charset', null);

        /*
         * Does this connector require an SSH tunnel?
         */
        if(isset_get($connector['ssh_tunnel']['required'])){
            include(__DIR__.'/handlers/sql-ssh-tunnel.php');
        }

        /*
         * Connect!
         */
        $connector['pdo_attributes'][PDO::ATTR_ERRMODE]                  = PDO::ERRMODE_EXCEPTION;
        $connector['pdo_attributes'][PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = !(boolean) $connector['buffered'];
        $connector['pdo_attributes'][PDO::MYSQL_ATTR_INIT_COMMAND]       = 'SET NAMES '.strtoupper($connector['charset']);
        $retries = 7;

        while(--$retries >= 0){
            try{
                $connect_string = $connector['driver'].':host='.$connector['host'].(empty($connector['port']) ? '' : ';port='.$connector['port']).((empty($connector['db']) or !$use_database) ? '' : ';dbname='.$connector['db']);
                $pdo            = new PDO($connect_string, $connector['user'], $connector['pass'], $connector['pdo_attributes']);

                log_console(tr('Connected with PDO connect string ":string"', array(':string' => $connect_string)), 'VERYVERBOSE/green');
                break;

            }catch(Exception $e){
                /*
                 * This is a work around for the weird PHP MySQL error
                 * "PDO::__construct(): send of 5 bytes failed with errno=32
                 * Broken pipe". So far we have not been able to find a fix
                 * for this but we have noted that you always have to
                 * connect 3 times, and the 3rd time the bug magically
                 * disappears. The work around will detect the error and
                 * retry up to 3 times to work around this issue for now.
                 *
                 * Over time, it has appeared that the cause of this issue
                 * may be that MySQL is chewing on a huge and slow query
                 * which prevents it from accepting new connections. This is
                 * not confirmed yet, but very likely. Either way, this
                 * "fix" still fixes the issue..
                 */
                log_console(tr('Failed to connect with PDO connect string ":string"', array(':string' => $connect_string)), 'exception');
                log_console($e->getMessage(), 'exception');

                $message = $e->getMessage();

                if(!strstr($message, 'errno=32')){
                    if($e->getMessage() == 'ERROR 2013 (HY000): Lost connection to MySQL server at \'reading initial communication packet\', system error: 0'){
                        if(isset_get($connector['ssh_tunnel']['required'])){
                            /*
                             * The tunneling server has "AllowTcpForwarding"
                             * set to "no" in the sshd_config, attempt auto
                             * fix
                             */
                            os_enable_ssh_tcp_forwarding($connector['ssh_tunnel']['server']);
                            continue;
                        }
                    }

                    /*
                     * This is a different error. Continue throwing the
                     * exception as normal
                     */
                    throw $e;
                }

                /*
                 * This error seems to happen when MySQL is VERY busy
                 * processing queries. Wait a little before trying again
                 */
                usleep(100000);
            }
        }

        try{
            $pdo->query('SET time_zone = "'.$connector['timezone'].'";');

        }catch(Exception $e){
            include(__DIR__.'/handlers/sql-error-timezone.php');
        }

        if(!empty($connector['mode'])){
            $pdo->query('SET sql_mode="'.$connector['mode'].'";');
        }

        return $pdo;

    }catch(Exception $e){
        return include(__DIR__.'/handlers/sql-error-connect.php');
    }
}



/*
 * Import data from specified file
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_import($file, $connector = null){
    global $core;

    try {
        $connector = sql_connector_name($connector);

        if(!file_exists($file)){
            throw new BException(tr('sql_import(): Specified file ":file" does not exist', array(':file' =>$file)), 'not-exists');
        }

        $tel    = 0;
        $handle = @fopen($file, 'r');

        if(!$handle){
            throw new isException('sql_import(): Could not open file', 'notopen');
        }

        while (($buffer = fgets($handle)) !== false){
            $buffer = trim($buffer);

            if(!empty($buffer)){
                $core->sql[$connector]->query(trim($buffer));

                $tel++;
// :TODO:SVEN:20130717: Right now it updates the display for each record. This may actually slow down import. Make display update only every 10 records or so
                echo 'Importing SQL data ('.$file.') : '.number_format($tel)."\n";
                //one line up!
                echo "\033[1A";
            }
        }

        echo "\nDone\n";

        if(!feof($handle)){
            throw new isException(tr('sql_import(): Unexpected EOF'), 'invalid');
        }

        fclose($handle);

    }catch(Exception $e){
        throw new BException(tr('sql_import(): Failed to import file ":file"', array(':file' => $file)), $e);
    }
}



/*
 *
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_columns($source, $columns){
    try{
        if(!is_array($source)){
            throw new BException('sql_columns(): Specified source is not an array');
        }

        $columns = array_force($columns);
        $retval  = array();

        foreach($source as $key => $value){
            if(in_array($key, $columns)){
                $retval[] = '`'.$key.'`';
            }
        }

        if(!count($retval)){
            throw new BException('sql_columns(): Specified source contains non of the specified columns "'.str_log(implode(',', $columns)).'"');
        }

        return implode(', ', $retval);

    }catch(Exception $e){
        throw new BException('sql_columns(): Failed', $e);
    }
}



// :OBSOLETE: Remove this function soon
///*
// *
// */
//function sql_set($source, $columns, $filter = 'id'){
//    try{
//        if(!is_array($source)){
//            throw new BException('sql_set(): Specified source is not an array', 'invalid');
//        }
//
//        $columns = array_force($columns);
//        $filter  = array_force($filter);
//        $retval  = array();
//
//        foreach($source as $key => $value){
//            /*
//             * Add all in columns, but not in filter (usually to skip the id column)
//             */
//            if(in_array($key, $columns) and !in_array($key, $filter)){
//                $retval[] = '`'.$key.'` = :'.$key;
//            }
//        }
//
//        foreach($filter as $item){
//            if(!isset($source[$item])){
//                throw new BException('sql_set(): Specified filter item "'.str_log($item).'" was not found in source', 'not-exists');
//            }
//        }
//
//        if(!count($retval)){
//            throw new BException('sql_set(): Specified source contains non of the specified columns "'.str_log(implode(',', $columns)).'"', 'empty');
//        }
//
//        return implode(', ', $retval);
//
//    }catch(Exception $e){
//        throw new BException('sql_set(): Failed', $e);
//    }
//}



/*
 *
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_values($source, $columns, $prefix = ':'){
    try{
        if(!is_array($source)){
            throw new BException('sql_values(): Specified source is not an array');
        }

        $columns = array_force($columns);
        $retval  = array();

        foreach($source as $key => $value){
            if(in_array($key, $columns) or ($key == 'id')){
                $retval[$prefix.$key] = $value;
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new BException('sql_values(): Failed', $e);
    }
}



/*
 *
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_insert_id($connector = null){
    global $core;

    try{
        $connector = sql_connector_name($connector);
        return $core->sql[sql_connector_name($connector)]->lastInsertId();

    }catch(Exception $e){
        throw new BException(tr('sql_insert_id(): Failed for connector ":connector"', array(':connector' => $connector)), $e);
    }
}



/*
 *
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_get_id_or_name($entry, $seo = true, $code = false){
    try{
        if(is_array($entry)){
            if(!empty($entry['id'])){
                $entry = $entry['id'];

            }elseif(!empty($entry['name'])){
                $entry = $entry['name'];

            }elseif(!empty($entry['seoname'])){
                $entry = $entry['seoname'];

            }elseif(!empty($entry['code'])){
                $entry = $entry['code'];

            }else{
                throw new BException('sql_get_id_or_name(): Invalid entry array specified', 'invalid');
            }
        }

        if(is_numeric($entry)){
            $retval['where']   = '`id` = :id';
            $retval['execute'] = array(':id'   => $entry);

        }elseif(is_string($entry)){
            if($seo){
                if($code){
                    $retval['where']   = '`name` = :name OR `seoname` = :seoname OR `code` = :code';
                    $retval['execute'] = array(':code'    => $entry,
                                               ':name'    => $entry,
                                               ':seoname' => $entry);

                }else{
                    $retval['where']   = '`name` = :name OR `seoname` = :seoname';
                    $retval['execute'] = array(':name'    => $entry,
                                               ':seoname' => $entry);
                }

            }else{
                if($code){
                    $retval['where']   = '`name` = :name OR `code` = :code';
                    $retval['execute'] = array(':code' => $entry,
                                               ':name' => $entry);

                }else{
                    $retval['where']   = '`name` = :name';
                    $retval['execute'] = array(':name' => $entry);
                }
            }

        }else{
            throw new BException('sql_get_id_or_name(): Invalid entry with type "'.gettype($entry).'" specified', 'invalid');
        }

        return $retval;

    }catch(BException $e){
        throw new BException('sql_get_id_or_name(): Failed (use either numeric id, name sting, or entry array with id or name)', $e);
    }
}



/*
 * Return a unique, non existing ID for the specified table.column
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_unique_id($table, $column = 'id', $max = 10000000, $connector = null){
    try{
        $connector = sql_connector_name($connector);

        $retries    =  0;
        $maxretries = 50;

        while(++$retries < $maxretries){
            $id = mt_rand(1, $max);

            if(!sql_get('SELECT `'.$column.'` FROM `'.$table.'` WHERE `'.$column.'` = :id', array(':id' => $id), null, $connector)){
                return $id;
            }
        }

        throw new BException('sql_unique_id(): Could not find a unique id in "'.$maxretries.'" retries', 'not-exists');

    }catch(BException $e){
        throw new BException('sql_unique_id(): Failed', $e);
    }
}



/*
 *
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_filters($params, $columns, $table = ''){
    try{
        $retval  = array('filters' => array(),
                         'execute' => array());

        $filters = array_keep($params, $columns);

        foreach($filters as $key => $value){
            $safe_key = str_replace('`.`', '_', $key);

            if($value === null){
                $retval['filters'][] = ($table ? '`'.$table.'`.' : '').'`'.$key.'` IS NULL';

            }else{
                $retval['filters'][]              = ($table ? '`'.$table.'`.' : '').'`'.$key.'` = :'.$safe_key;
                $retval['execute'][':'.$safe_key] = $value;
            }
        }

        return $retval;

    }catch(BException $e){
        throw new BException('sql_filters(): Failed', $e);
    }
}



/*
 * Return a sequential array that can be used in sql_in
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_in($source, $column = ':value', $filter_null = false, $null_string = false){
    try{
        if(empty($source)){
            throw new BException(tr('sql_in(): Specified source is empty'), 'not-specified');
        }

        $column = str_starts($column, ':');
        $source = array_force($source);

        return array_sequential_keys($source, $column, $filter_null, $null_string);

    }catch(BException $e){
        throw new BException('sql_in(): Failed', $e);
    }
}



/*
 * Helper for building sql_in key value pairs
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @param
 * @return string a comma delimeted string of columns
 */
function sql_in_columns($in, $column_starts_with = null){
    try{
        if($column_starts_with){
            /*
             * Only return those columns that start with this string
             */
            foreach($in as $key => $column){
                if(substr($key, 0, strlen($column_starts_with)) !== $column_starts_with){
                    unset($in[$key]);
                }
            }
        }

        return implode(', ', array_keys($in));

    }catch(Exception $e){
        throw new BException('sql_in_columns(): Failed', $e);
    }
}


/*
 * Try to get single data entry from memcached. If not available, get it from
 * MySQL and store results in memcached for future use
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_get_cached($key, $query, $column = false, $execute = false, $expiration_time = 86400, $connector = null){
    try{
        $connector = sql_connector_name($connector);

        if(($value = memcached_get($key, 'sql_')) === false){
            /*
             * Keyword data not found in cache, get it from MySQL with
             * specified query and store it in cache for next read
             */
            if(is_array($column)){
                /*
                 * Argument shift, no columns were specified.
                 */
                $tmp     = $execute;
                $execute = $column;
                $column  = $tmp;
                unset($tmp);
            }

            if(is_numeric($column)){
                /*
                 * Argument shift, no columns were specified.
                 */
                $tmp             = $expiration_time;
                $expiration_time = $execute;
                $execute         = $tmp;
                unset($tmp);
            }

            $value = sql_get($query, $column, $execute, $connector);

            memcached_put($value, $key, 'sql_', $expiration_time);
        }

        return $value;

    }catch(BException $e){
        throw new BException('sql_get_cached(): Failed', $e);
    }
}



/*
 * Try to get data list from memcached. If not available, get it from
 * MySQL and store results in memcached for future use
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_list_cached($key, $query, $execute = false, $numerical_array = false, $connector = null, $expiration_time = 86400){
    try{
        $connector = sql_connector_name($connector);

        if(($list = memcached_get($key, 'sql_')) === false){
            /*
             * Keyword data not found in cache, get it from MySQL with
             * specified query and store it in cache for next read
             */
            $list = sql_list($query, $execute, $numerical_array, $connector);

            memcached_put($list, $key, 'sql_', $expiration_time);
        }

        return $list;

    }catch(BException $e){
        throw new BException('sql_list_cached(): Failed', $e);
    }
}



/*
 * Fetch and return data from specified resource
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_fetch_column($r, $column){
    try{
        $row = sql_fetch($r);

        if(!isset($row[$column])){
            throw new BException('sql_fetch_column(): Specified column "'.str_log($column).'" does not exist', $e);
        }

        return $row[$column];

    }catch(Exception $e){
        throw new BException('sql_fetch_column(): Failed', $e);
    }
}



/*
 * Merge database entry with new posted entry, overwriting the old DB values,
 * while skipping the values specified in $skip
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param array $database_entry
 * @param array $post
 * @param mixed $skip
 * @return array The specified datab ase entry, updated with all the data from the specified $_POST entry
 */
function sql_merge($database_entry, $post, $skip = null){
    try{
        if(!$post){
            /*
             * No post was done, there is nothing to merge
             */
            return $database_entry;
        }

        if($skip === null){
            $skip = 'id,status';
        }

        if(!is_array($database_entry)){
            if($database_entry !== null){
                throw new BException(tr('sql_merge(): Specified database source data type should be an array but is a ":type"', array(':type' => gettype($database_entry))), 'invalid');
            }

            /*
             * Nothing to merge
             */
            $database_entry = array();
        }

        if(!is_array($post)){
            if($post !== null){
                throw new BException(tr('sql_merge(): Specified post source data type should be an array but is a ":type"', array(':type' => gettype($post))), 'invalid');
            }

            /*
             * Nothing to merge
             */
            $post = array();
        }

        $skip = array_force($skip);

        /*
         * Copy all POST variables over DB
         * Skip POST variables that have NULL value
         */
        foreach($post as $key => $value){
            if(in_array($key, $skip)){
                continue;
            }

            $database_entry[$key] = $post[$key];
        }

        return $database_entry;

    }catch(Exception $e){
        throw new BException('sql_merge(): Failed', $e);
    }
}



/*
 * Ensure that $connector_name is default in case its not specified
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param string $connector_name
 * @return string The connector that should be used
 */
function sql_connector_name($connector_name){
    global $_CONFIG, $core;

    try{
        if(!$connector_name){
            $connector_name = $core->register('sql_connector');

            if($connector_name){
                return $connector_name;
            }

            return $_CONFIG['db']['default'];
        }

        if(!is_scalar($connector_name)){
            throw new BException(tr('sql_connector_name(): Invalid connector ":connector" specified, it must be scalar', array(':connector' => $connector_name)), 'invalid');
        }

        if(empty($_CONFIG['db'][$connector_name])){
            throw new BException(tr('sql_connector_name(): Specified database connector ":connector" does not exist', array(':connector' => $connector_name)), 'not-exists');
        }

        return $connector_name;

    }catch(Exception $e){
        throw new BException('sql_connector_name(): Failed', $e);
    }
}



/*
 * Use correct SQL in case NULL is used in queries
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_is($value, $label, $not = false){
    try{
        if($not){
            if($value === null){
                return ' IS NOT '.$label.' ';
            }

            return ' != '.$label.' ';
        }

        if($value === null){
            return ' IS '.$label.' ';
        }

        return ' = '.$label.' ';

    }catch(Exception $e){
        throw new BException('sql_is(): Failed', $e);
    }
}



/*
 * Enable / Disable all query logging on mysql server
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_log($enable){
    try{
        if($enable){
            sql_query('SET global log_output = "FILE";');
            sql_query('SET global general_log_file="/var/log/mysql/queries.log";');
            sql_query('SET global general_log = 1;');

        }else{
            sql_query('SET global log_output = "OFF";');
        }

    }catch(Exception $e){
        throw new BException('sql_log(): Failed', $e);
    }
}



/*
 *
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_exists($table, $column, $value, $id = null){
    try{
        if($id){
            return sql_get('SELECT `id` FROM `'.$table.'` WHERE `'.$column.'` = :'.$column.' AND `id` != :id', true, array($column => $value, ':id' => $id));
        }

        return sql_get('SELECT `id` FROM `'.$table.'` WHERE `'.$column.'` = :'.$column.'', true, array($column => $value));

    }catch(Exception $e){
        throw new BException(tr('sql_exists(): Failed'), $e);
    }
}



/*
 * NOTE: Use only on huge tables (> 1M rows)
 *
 * Return table row count by returning results count for SELECT `id`
 * Results will be cached in a counts table
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_count($table, $where = '', $execute = null, $column = '`id`'){
    global $_CONFIG;

    try{
        load_config('sql_large');

        $expires = $_CONFIG['sql_large']['cache']['expires'];
        $hash    = hash('sha1', $table.$where.$column.json_encode($execute));
        $count   = sql_get('SELECT `count` FROM `counts` WHERE `hash` = :hash AND `until` > NOW()', 'count', array(':hash' => $hash));

        if($count){
            return $count;
        }

        /*
         * Count value was not found cached, count it directly
         */
        $count = sql_get('SELECT COUNT('.$column.') AS `count` FROM `'.$table.'` '.$where, 'count', $execute);

        sql_query('INSERT INTO `counts` (`createdby`, `count`, `hash`, `until`)
                   VALUES               (:createdby , :count , :hash , NOW() + INTERVAL :expires SECOND)

                   ON DUPLICATE KEY UPDATE `count`      = :update_count,
                                           `modifiedon` = NOW(),
                                           `modifiedby` = :update_modifiedby,
                                           `until`      = NOW() + INTERVAL :update_expires SECOND',

                   array(':createdby'         => isset_get($_SESSION['user']['id']),
                         ':hash'              => $hash,
                         ':count'             => $count,
                         ':expires'           => $expires,
                         ':update_expires'    => $expires,
                         ':update_modifiedby' => isset_get($_SESSION['user']['id']),
                         ':update_count'      => $count));

        return $count;

    }catch(Exception $e){
        throw new BException('sql_count(): Failed', $e);
    }
}



/*
 * Returns what database currently is selected
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_current_database(){
    try{
        return sql_get('SELECT DATABASE() AS `database` FROM DUAL;');

    }catch(Exception $e){
        throw new BException('sql_current_database(): Failed', $e);
    }
}



/*
 *
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_random_id($table, $min = 1, $max = 2147483648, $connector_name = null){
    try{
        $connector_name = sql_connector_name($connector_name);
        $exists         = true;
        $timeout        = 50; // Don't do more than 50 tries on this!

        while($exists and --$timeout > 0){
            $id     = mt_rand($min, $max);
            $exists = sql_query('SELECT `id` FROM `'.$table.'` WHERE `id` = :id', array(':id' => $id), $connector_name);
        }

        return $id;

    }catch(Exception $e){
        throw new BException(tr('sql_random_id(): Failed for table ":table"', array(':table' => $table)), $e);
    }
}



/*
 * Execute a query on a remote SSH server.
 * NOTE: This does NOT support bound variables!
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_exec($server, $query, $root = false, $simple_quotes = false){
    try{
        load_libs('servers');

        $query = addslashes($query);

        if(!is_array($server)){
            $server = servers_get($server, true);
        }

        /*
         * Are we going to execute as root?
         */
        if($root){
            sql_create_password_file('root', $server['db_root_password'], $server);

        }else{
            sql_create_password_file($server['db_username'], $server['db_password'], $server);
        }

        if($simple_quotes){
            $results = servers_exec($server, 'mysql -e \''.str_ends($query, ';').'\'');

        }else{
            $results = servers_exec($server, 'mysql -e \"'.str_ends($query, ';').'\"');
        }

        sql_delete_password_file($server);

        return $results;

    }catch(Exception $e){
        /*
         * Make sure the password file gets removed!
         */
        try{
            sql_delete_password_file($server);

        }catch(Exception $e){

        }

        throw new BException(tr('sql_exec(): Failed'), $e);
    }
}



///*
// *
// *
// * @copyright Copyright (c) 2018 Capmega
// * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
// * @category Function reference
// * @package sql
// *
// * @return array
// */
//function sql_exec_get($server, $query, $root = false, $simple_quotes = false){
//    try{
//
//    }catch(Exception $e){
//        throw new BException(tr('sql_exec_get(): Failed'), $e);
//    }
//}



/*
 *
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param array $params
 * @return
 */
function sql_get_database($db_name){
    try{
        $database = sql_get('SELECT    `databases`.`id`,
                                       `databases`.`servers_id`,
                                       `databases`.`status`,
                                       `databases`.`replication_status`,
                                       `databases`.`name` AS `database`,
                                       `databases`.`error`,

                                       `servers`.`id` AS `servers_id`,
                                       `servers`.`hostname`,
                                       `servers`.`port`,
                                       `servers`.`replication_status` AS `servers_replication_status`,

                                       `database_accounts`.`username`      AS `replication_db_user`,
                                       `database_accounts`.`password`      AS `replication_db_password`,
                                       `database_accounts`.`root_password` AS `root_db_password`

                             FROM      `databases`

                             LEFT JOIN `servers`
                             ON        `servers`.`id`           = `databases`.`servers_id`

                             LEFT JOIN `database_accounts`
                             ON        `database_accounts`.`id` = `servers`.`database_accounts_id`

                             WHERE     `databases`.`id`         = :name
                             OR        `databases`.`name`       = :name',

                             array(':name' => $db_name));

        if(!$database){
            throw new BException(log_database(tr('Specified database ":database" does not exist', array(':database' => $_GET['database'])), 'not-exists'));
        }

        return $database;

    }catch(Exception $e){
        throw new BException(tr('sql_get_database(): Failed'), $e);
    }
}



/*
 * Return connector data for the specified connector.
 *
 * Connector data will first be searched for in $_CONFIG[db][CONNECTOR]. If the connector is not found there, the sql_connectors table will be searched. If the connector is not found there either, NULL will be returned
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param string $connector_name The requested connector name
 * @return array The requested connector data. NULL if the specified connector does not exist
 */
function sql_get_connector($connector_name){
    global $_CONFIG;

    try{
        if(!is_natural($connector_name)){
            /*
             * Connector was specified by name
             */
            if(isset($_CONFIG['db'][$connector_name])){
                return $_CONFIG['db'][$connector_name];
            }

            $where   = ' `name` = :name ';
            $execute = array(':name' => $connector_name);

        }else{
            /*
             * Connector was specified by id
             */
            $where   = ' `id` = :id ';
            $execute = array(':id' => $connector_name);
        }

        $connector = sql_get('SELECT `id`,
                                     `createdon`,
                                     `createdby`,
                                     `meta_id`,
                                     `status`,
                                     `name`,
                                     `seoname`,
                                     `servers_id`,
                                     `hostname`,
                                     `driver`,
                                     `database`,
                                     `user`,
                                     `password`,
                                     `autoincrement`,
                                     `buffered`,
                                     `charset`,
                                     `collate`,
                                     `limit_max`,
                                     `mode`,
                                     `ssh_tunnel_required`,
                                     `ssh_tunnel_source_port`,
                                     `ssh_tunnel_hostname`,
                                     `usleep`,
                                     `pdo_attributes`,
                                     `timezone`

                              FROM   `sql_connectors`

                              WHERE  '.$where,

                              $execute);

        if($connector){
            $connector['ssh_tunnel'] = array('required'    => $connector['ssh_tunnel_required'],
                                             'source_port' => $connector['ssh_tunnel_source_port'],
                                             'hostname'    => $connector['ssh_tunnel_hostname']);

            unset($connector['ssh_tunnel_required']);
            unset($connector['ssh_tunnel_source_port']);
            unset($connector['ssh_tunnel_hostname']);

            $_CONFIG['db'][$connector_name] = $connector;
        }

        return $connector;

    }catch(Exception $e){
        throw new BException(tr('sql_get_connector(): Failed'), $e);
    }
}



/*
 * Create an SQL connector in $_CONFIG['db'][$connector_name] = $data
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param string $connector_name
 * @param array $connector
 * @return array The specified connector data, with all informatinon completed if missing
 */
function sql_make_connector($connector_name, $connector){
    global $_CONFIG;

    try{
        if(empty($connector['ssh_tunnel'])){
            $connector['ssh_tunnel'] = array();
        }

        if(sql_get_connector($connector_name)){
            throw new BException(tr('sql_make_connector(): The specified connector name ":name" already exists', array(':name' => $connector_name)), 'exists');
        }

        $connector = sql_ensure_connector($connector);

        if($connector['ssh_tunnel']){
            $connector['ssh_tunnel']['required'] = true;
        }

        $_CONFIG['db'][$connector_name] = $connector;
        return $connector;

    }catch(Exception $e){
        throw new BException(tr('sql_make_connector(): Failed'), $e);
    }
}



/*
 * Ensure all SQL connector fields are available
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param array $connector
 * @return array The specified connector data with all fields available
 */
function sql_ensure_connector($connector){
    try{
        $template = array('driver'           => 'mysql',
                          'host'             => '127.0.0.1',
                          'port'             => null,
                          'db'               => '',
                          'user'             => '',
                          'pass'             => '',
                          'autoincrement'    => 1,
                          'init'             => false,
                          'buffered'         => false,
                          'charset'          => 'utf8mb4',
                          'collate'          => 'utf8mb4_general_ci',
                          'limit_max'        => 10000,
                          'mode'             => 'PIPES_AS_CONCAT,IGNORE_SPACE,NO_KEY_OPTIONS,NO_TABLE_OPTIONS,NO_FIELD_OPTIONS',
                          'ssh_tunnel'       => array('required'    => false,
                                                      'source_port' => null,
                                                      'hostname'    => '',
                                                      'usleep'      => 1200000),
                          'pdo_attributes'   => array(),
                          'version'          => '0.0.0',
                          'timezone'         => 'UTC');

        $connector['ssh_tunnel'] = sql_merge($template['ssh_tunnel'], isset_get($connector['ssh_tunnel'], array()));
        $connector               = sql_merge($template, $connector);

        if(!is_array($connector['ssh_tunnel'])){
            throw new BException(tr('sql_ensure_connector(): Specified ssh_tunnel ":tunnel" should be an array but is a ":type"', array(':tunnel' => $connector['ssh_tunnel'], ':type' => gettype($connector['ssh_tunnel']))), 'invalid');
        }

        return $connector;

    }catch(Exception $e){
        throw new BException(tr('sql_ensure_connector(): Failed'), $e);
    }
}



/*
 * Test SQL functions over SSH tunnel for the specified server
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 * @exception BException when the test failse
 *
 * @param mixed $server The server that is to be tested
 * @return void
 */
function sql_test_tunnel($server){
    global $_CONFIG;

    try{
        load_libs('servers');

        $connector_name = 'test';
        $port           = 6000;
        $server         = servers_get($server, true);

        if(!$server['database_accounts_id']){
            throw new BException(tr('sql_test_tunnel(): Cannot test SQL over SSH tunnel, server ":server" has no database account linked', array(':server' => $server['domain'])), 'not-exists');
        }

        sql_make_connector($connector_name, array('port'       => $port,
                                                  'user'       => $server['db_username'],
                                                  'pass'       => $server['db_password'],
                                                  'ssh_tunnel' => array('source_port' => $port,
                                                                        'domain'      => $server['domain'])));

        sql_get('SELECT TRUE', true, null, $connector_name);

    }catch(Exception $e){
        throw new BException(tr('sql_test_tunnel(): Failed'), $e);
    }
}



/*
 * Process SQL query errors
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 * @exception BException when the test failse
 *
 * @param BException $e The query exception
 * @param string $query The executed query
 * @param array $execute The bound query variables
 * @param BException $sql The PDO SQL object
 * @return void
 */
function sql_error($e, $query, $execute, $sql){
    include(__DIR__.'/handlers/sql-error.php');
}



/*
 *
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_valid_limit($limit, $connector = null){
    global $_CONFIG;

    try{
        $connector = sql_connector_name($connector);
        $limit     = force_natural($limit);

        if($limit > $_CONFIG['db'][$connector]['limit_max']){
            return $_CONFIG['db'][$connector]['limit_max'];
        }

        return $limit;

    }catch(Exception $e){
        throw new BException('sql_valid_limit(): Failed', $e);
    }
}



/*
 * Return a valid " LIMIT X, Y " string built from the specified parameters
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 * @version 2.4.8: Added function and documentation
 *
 * @param natural $limit
 * @param natural $limit
 * @return string The SQL " LIMIT X, Y " string
 */
function sql_limit($limit = null, $page = null){
    try{
        load_libs('paging');

        if(!$limit){
            $limit = paging_limit();
        }

        if(!$limit){
            /*
             * No limits, so show all
             */
            return '';
        }

        return ' LIMIT '.((paging_page($page) - 1) * $limit).', '.$limit;

    }catch(Exception $e){
        throw new BException(tr('sql_limit(): Failed'), $e);
    }
}



/*
 *
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 *
 * @param
 * @return
 */
function sql_where_null($value, $not = false){
    try{
        if($value === null){
            if($not){
                return ' IS NOT NULL ';
            }

            return ' IS NULL ';
        }

        if($not){
            return ' != '.quote($value);
        }

        return ' = '.quote($value);

    }catch(BException $e){
        throw new BException('sql_where_null(): Failed', $e);
    }
}



/*
 * Return a valid " WHERE `column` = :value ", " WHERE `column` IS NULL ", or " WHERE `column` IN (:values) " string built from the specified parameters
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 * @version 2.4.8: Added function and documentation
 *
 * @param string $column
 * @param mixed $values
 * @param booealn $not
 * @return string The SQL " WHERE.... " string
 */
function sql_simple_where($column, $values, $not = false, $extra = null){
    try{
        $extra  = '';
        $table  = str_until($column, '.', 0, 0, true);
        $column = str_from ($column, '.');

        if(!$values){
            return $extra;
        }

        if(is_scalar($values)){
            if($not){
                return ' WHERE '.($table ? '`'.$table.'`.' : '').'`'.$column.'` != :'.$column.' '.$extra.' ';
            }

            return ' WHERE '.($table ? '`'.$table.'`.' : '').'`'.$column.'` = :'.$column.' '.$extra.' ';
        }

        $not = ($not ? 'NOT' : '');

        if(($values === null) or ($values === 'null') or ($values === 'NULL')){
            return ' WHERE '.($table ? '`'.$table.'`.' : '').'`'.$column.'` IS '.$not.' NULL '.$extra.' ';
        }

        if(is_array($values)){
            $values = sql_in($values);

            foreach($values as $key => $value){
                if(($value === null) or ($value === 'null') or ($value === 'NULL')){
                    unset($values[$key]);
                    $extra = ' OR '.($table ? '`'.$table.'`.' : '').'`'.$column.'` IS '.$not.' NULL ';
                    break;
                }
            }

            return ' WHERE ('.($table ? '`'.$table.'`.' : '').'`'.$column.'` '.$not.' IN ('.sql_in_columns($values).')'.$extra.') '.$extra.' ';
        }

        throw new BException(tr('sql_simple_where(): Specified values ":values" is neither NULL nor scalar nor an array', array(':values' => $values)), 'invalid');

    }catch(Exception $e){
        throw new BException(tr('sql_simple_where(): Failed'), $e);
    }
}



/*
 * Return a valid PDO execute array
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 * @version 2.4.8: Added function and documentation
 *
 * @param string $column
 * @param mixed $values
 * @return params The execute array corrected
 */
function sql_simple_execute($column, $values, $extra = null){
    try{
        if(!$values){
            return $extra;
        }

        if(is_scalar($values) or ($values === null)){
            $values = array(str_starts($column, ':') => $values);

        }elseif(is_array($values)){
            $values = sql_in($values, ':value', true, true);

        }else{
            throw new BException(tr('sql_simple_execute(): Specified values ":values" is neither NULL nor scalar nor an array', array(':values' => $values)), 'invalid');
        }

        if($extra){
            $values = array_merge($values, $extra);
        }

        return $values;

    }catch(Exception $e){
        throw new BException(tr('sql_simple_execute(): Failed'), $e);
    }
}



/*
 * OBSOLETE / COMPATIBILITY FUNCTIONS
 *
 * These functions below exist only for compatibility between pdo.php and mysqli.php
 *
 * Return affected rows
 */



/*
 * Build an SQL WHERE string out of the specified filters, typically used for basic foobar_list() like functions
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 * @note Any filter key that has the value "-" WILL BE IGNORED
 * @note Any keys prefixed with ! will perform a NOT operation
 * @note Any keys prefixed with ~ will perform a LIKE operation
 * @note Any keys prefixed with # will allow $value to be an arran and operate an IN operation
 * @note Key prefixes may be combined in any order
 * @version 2.5.38: Added function and documentation
 *
 * @param params A key => value array with required filters
 * @param byref array $execute The execute array that will be created by this function
 * @param string $table The table for which these colums will be setup
 * @return string The WHERE string
 */
function sql_get_where_string($filters, &$execute, $table){
    try{
        if(!is_array($filters)){
            throw new BException(tr('sql_get_where_string(): The specified filters are invalid, it should be a key => value array'), 'invalid');
        }

        /*
         * Build the where section from the specified filters
         */
        foreach($filters as $key => $value){
            /*
             * Any entry with value BOOLEAN FALSE will not be considered. this
             * way we have a simple way to skip keys if needed
             */
// :TODO: Look up why '-' also was considered "skip"
            if(($value === '-') or ($value === false)){
                /*
                 * Ignore this entry
                 */
                continue;
            }

            $like       = false;
            $array      = false;
            $not_string = '';
            $not        = '';

            /*
             * Check for modifiers in the keys
             * ! will make it a NOT filter
             * # will allow arrays
             */
            while(true){
                switch($key[0]){
                    case '~':
                        /*
                         * LIKE
                         */
                        $key   = substr($key, 1);
                        $like  = true;
                        $value = '%'.$value.'%';
                        break;

                    case '!':
                        /*
                         * NOT
                         */
                        $key        = substr($key, 1);
                        $not_string = ' NOT ';
                        $not        = '!';
                        break;

                    case '#':
                        /*
                         * IN
                         */
                        $key   = substr($key, 1);
                        $array = true;

                    default:
                        break 2;
                }
            }

            if(strpos($key, '.') === false){
                $key = $table.'.'.$key;
            }

            $column = '`'.str_replace('.', '`.`', trim($key)).'`';
            $key    = str_replace('.', '_', $key);

            if($like){
                if(is_string($value)){
                    $where[] = ' '.$column.' '.$not.'LIKE :'.$key.' ';
                    $execute[':'.$key] = $value;

                }else{
                    if(is_array($value)){
                        throw new BException(tr('sql_get_where_string(): The specified filter key ":key" is an array, which is not allowed with a LIKE comparisson.', array(':key' => $key)), 'invalid');
                    }

                    if(is_bool($value)){
                        throw new BException(tr('sql_get_where_string(): The specified filter key ":key" is a boolean, which is not allowed with a LIKE comparisson.', array(':key' => $key)), 'invalid');
                    }

                    if($value === null){
                        throw new BException(tr('sql_get_where_string(): The specified filter key ":key" is a null, which is not allowed with a LIKE comparisson.', array(':key' => $key)), 'invalid');
                    }

                    throw new BException(tr('sql_get_where_string(): Specified value ":value" is of invalid datatype ":datatype"', array(':value' => $value, ':datatype' => gettype($value))), 'invalid');
                }

            }else{
                if(is_array($value)){
                    if($array){
                        throw new BException(tr('sql_get_where_string(): The specified filter key ":key" contains an array, whcih is not allowed. Specify the key as "#:array" to allow arrays', array(':key' => $key, ':array' => $key)), 'invalid');
                    }

                    $value   = sql_in($value);
                    $where[] = ' '.$column.' '.$not_string.'IN ('.sql_in_columns($value).') ';
                    $execute = array_merge($execute, $value);

                }elseif(is_bool($value)){
                    $where[] = ' '.$column.' '.$not.'= :'.$key.' ';
                    $execute[':'.$key] = (integer) $value;

                }elseif(is_string($value)){
                    $where[] = ' '.$column.' '.$not.'= :'.$key.' ';
                    $execute[':'.$key] = $value;

                }elseif($value === null){
                    $where[] = ' '.$column.' IS'.$not_string.' :'.$key.' ';
                    $execute[':'.$key] = $value;

                }else{
                    throw new BException(tr('sql_get_where_string(): Specified value ":value" is of invalid datatype ":datatype"', array(':value' => $value, ':datatype' => gettype($value))), 'invalid');
                }
            }
        }

        if(isset($where)){
            $where = ' WHERE '.implode(' AND ', $where);
        }

        return $where;

    }catch(Exception $e){
        throw new BException('sql_get_where_string(): Failed', $e);
    }
}



/*
 * Build the SQL columns list for the specified columns list, escaping all columns with backticks
 *
 * If the specified column is of the "column" format, it will be returned as "`column`". If its of the "table.column" format, it will be returned as "`table`.`column`"
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 * @version 2.5.38: Added function and documentation
 *
 * @param csv array $columns The list of columns from which the query must be built
 * @param string $table The table for which these colums will be setup
 * @return string The columns with column quotes
 */
function sql_get_columns_string($columns, $table){
    try{
        /*
         * Validate the columns
         */
        if(!$columns){
            throw new BException(tr('sql_get_columns_string(): No columns specified'));
        }

        $columns = array_force($columns);

        foreach($columns as $id => &$column){
            if(!$column){
                unset($columns[$id]);
                continue;
            }

            $column = strtolower(trim($column));

            if(strpos($column, '.') === false){
                $column = $table.'.'.$column;
            }

            if(str_exists($column, ' as ')){
                $target  = trim(str_from($column, ' as '));
                $column  = trim(str_until($column, ' as '));
                $column  = '`'.str_replace('.', '`.`', trim($column)).'`';
                $column .= ' AS `'.trim($target).'`';

            }else{
                $column = '`'.str_replace('.', '`.`', trim($column)).'`';
            }
        }

        $columns = implode(', ', $columns);

        unset($column);
        return $columns;

    }catch(Exception $e){
        throw new BException('sql_get_columns_string(): Failed', $e);
    }
}



/*
 * Build the SQL columns list for the specified columns list
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 * @version 2.5.38: Added function and documentation
 *
 * @param array $orderby A key => value array containing the columns => direction definitions
 * @return string The columns with column quotes
 */
function sql_get_orderby_string($orderby){
    try{
        /*
         * Validate the columns
         */
        if(!$orderby){
            return '';
        }

        if(!is_array($orderby)){
            throw new BException(tr('sql_get_orderby_string(): Specified orderby ":orderby" should be an array but is a ":datatype"', array(':orderby' => $orderby, ':datatype' => gettype($orderby))), 'invalid');
        }

        foreach($orderby as $column => $direction){
            if(!is_string($direction)){
                throw new BException(tr('sql_get_orderby_string(): Specified orderby direction ":direction" for column ":column" is invalid, it should be a string', array(':direction' => $direction, ':column' => $column)), 'invalid');
            }

            $direction = strtoupper($direction);

            switch($direction){
                case 'ASC':
                    // FALLTHOGUH
                case 'DESC':
                    break;

                default:
                    throw new BException(tr('sql_get_orderby_string(): Specified orderby direction ":direction" for column ":column" is invalid, it should be either "ASC" or "DESC"', array(':direction' => $direction, ':column' => $column)), 'invalid');
            }

            $retval[] = '`'.$column.'` '.$direction;
        }

        $retval = implode(', ', $retval);

        return ' ORDER BY '.$retval.' ';

    }catch(Exception $e){
        throw new BException('sql_get_orderby_string(): Failed', $e);
    }
}



/*
 * Build and execut a SQL function that lists entries from the specified table using the specified parameters
 *
 * This function can build a SELECT query, specifying the required table columns, WHERE filtering, ORDER BY, and LIMIT
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 * @see sql_simple_get()
 * @note Any filter key that has the value "-" WILL BE IGNORED
 * @version 2.5.38: Added function and documentation
 *
 * @param params $params The parameters for the SELECT command
 * @param enum(resource, array) $params[method]
 * @param string $params[connector]
 * @param string $params[table]
 * @param list $params[columns]
 * @param null array $params[filters]
 * @param null array $params[orderby]
 * @param null list $params[joins]
 * @param false boolean $params[debug]
 * @param null boolean $params[auto_status]
 * @return mixed The entries from the requested table
 */
function sql_simple_list($params){
    try{
        array_ensure($params, 'joins,debug,limit,page');

        if(empty($params['table'])){
            throw new BException(tr('sql_simple_list(): No table specified'), 'not-specified');
        }

        if(empty($params['columns'])){
            throw new BException(tr('sql_simple_list(): No columns specified'), 'not-specified');
        }

        array_default($params, 'connector'  , 'core');
        array_default($params, 'method'     , 'resource');
        array_default($params, 'filters'    , array('status' => null));
        array_default($params, 'orderby'    , array('name'   => 'asc'));
        array_default($params, 'auto_status', null);

        /*
         * Apply automatic filter settings
         */
        if(($params['auto_status'] !== false) and !array_key_exists('status', $params['filters']) and !array_key_exists($params['table'].'.status', $params['filters'])){
            /*
             * Automatically ensure we only get entries with the auto status
             */
            $params['filters'][$params['table'].'.status'] = $params['auto_status'];
        }

        $columns  = sql_get_columns_string($params['columns'], $params['table']);
        $joins    = str_force($params['joins'], ' ');
        $where    = sql_get_where_string($params['filters'], $execute, $params['table']);
        $orderby  = sql_get_orderby_string($params['orderby']);
        $limit    = sql_limit($params['limit'], $params['page']);
        $resource = sql_query(($params['debug'] ? ' ' : '').'SELECT '.$columns.' FROM  `'.$params['table'].'` '.$joins.$where.$orderby.$limit, $execute, $params['connector']);

        /*
         * Execute query and return results
         */
        switch($params['method']){
            case 'resource':
                /*
                 * Return a query instead of a list array
                 */
                return $resource;

            case 'array':
                /*
                 * Return a list array instead of a query
                 */
                return sql_list($resource);

            default:
                throw new BException(tr('sql_simple_list(): Unknown method ":method" specified', array(':method' => $method)), 'unknown');
        }


    }catch(Exception $e){
        throw new BException(tr('sql_simple_list(): Failed'), $e);
    }
}



/*
 * Build and execut a SQL function that returns a single entry from the specified table using the specified parameters
 *
 * This function can build a SELECT query, specifying the required table columns, WHERE filtering
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sql
 * @see sql_simple_list()
 * @note Any filter key that has the value "-" WILL BE IGNORED
 * @version 2.5.38: Added function and documentation
 *
 * @param params $params A parameters array
 * @param enum(resource, array) $params[method]
 * @param string $params[connector]
 * @param string $params[table]
 * @param list $params[columns]
 * @param null array $params[filters]
 * @param null array $params[orderby]
 * @param null list $params[joins]
 * @param false boolean $params[debug]
 * @param null boolean $params[auto_status]
 * @return mixed The entries from the requested table
 */
function sql_simple_get($params){
    try{
        array_ensure($params, 'joins,debug');

        if(empty($params['table'])){
            throw new BException(tr('sql_simple_get(): No table specified'), 'not-specified');
        }

        if(empty($params['columns'])){
            throw new BException(tr('sql_simple_get(): No columns specified'), 'not-specified');
        }

        array_default($params, 'connector'  , 'core');
        array_default($params, 'single'     , null);
        array_default($params, 'filters'    , array('status' => null));
        array_default($params, 'auto_status', null);
        array_default($params, 'limit'      , null);
        array_default($params, 'page'       , null);

        $params['columns'] = array_force($params['columns']);

        /*
         * Apply automatic filter settings
         */
        if(($params['auto_status'] !== false) and !array_key_exists('status', $params['filters']) and !array_key_exists($params['table'].'.status', $params['filters'])){
            /*
             * Automatically ensure we only get entries with the auto status
             */
            $params['filters'][$params['table'].'.status'] = $params['auto_status'];
        }

        if((count($params['columns']) === 1) and ($params['single'] !== false)){
            /*
             * By default, when one column is selected, return the value
             * directly, instead of in an array
             */
            $params['single'] = true;
        }

        $columns = sql_get_columns_string($params['columns'], $params['table']);
        $joins   = str_force($params['joins'], ' ');
        $where   = sql_get_where_string($params['filters'], $execute, $params['table']);

        return sql_get(($params['debug'] ? ' ' : '').'SELECT '.$columns.' FROM  `'.$params['table'].'` '.$joins.$where, $execute, $params['single'], $params['connector']);

    }catch(Exception $e){
        throw new BException(tr('sql_simple_get(): Failed'), $e);
    }
}



/*
 * HERE BE OBSOLETE CRAP
 */
function sql_affected_rows($r){
    return $r->rowCount();
}

/*
 * Return number of rows in the specified resource
 */
function sql_num_rows(&$r){
    return $r->rowCount();
}

function sql_merge_entry($db, $post, $skip = null){
    return sql_merge($db, $post, $skip);
}
?>
