<?php
/*
 * Init library
 *
 * This library file contains the init function
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 */



/*
 * Execute database initialization
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package init
 *
 * @param string $projectfrom
 * @param string $frameworkfrom
 * @return void
 */
function init($projectfrom = null, $frameworkfrom = null){
    global $_CONFIG, $core;

    try{
        /*
         * Are we allowed to init?
         */
        if(!$_CONFIG['init'][PLATFORM]){
            throw new BException('init(): This platform is not authorized to do init()', 'denied');
        }

        if(version_compare(PHP_VERSION, PHP_MINIMUM_VERSION) < 1){
            throw new BException(tr('init(): This version of base requires at minimum PHP version ":required", anything below is not supported. The current version of PHP installed is ":installed" If you wish to force to run this version of base on this version of PHP, lower the required version defined with the constant PHP_MINIMUM_VERSION in the top of ROOT/libs/startup.php', array(':required' => PHP_MINIMUM_VERSION, ':installed' => PHP_VERSION)), 'denied');
        }

        load_libs('sql-exists');

        /*
         * Check tmp dir configuration
         */
        file_ensure_path(TMP.'www');
        touch(TMP.'www/.donotdelete');

        /*
         * To do the init, we need the database version data. The database version check is ONLY executed on sql_connect(),
         * so connect to DB to force this check and get the DB version constants
         */
        sql_init();

        if(empty($_CONFIG['db']['core']['init'])){
            throw new BException(tr('init(): Core database init system has been disabled in $_CONFIG[db][core][init]'), 'no-init');
        }

        if(!empty($core->register['time_zone_fail'])){
            /*
             * MySQL has no time_zone data, first initialize that, then reconnect
             */
            sql_init();
        }

        /*
         * Determine framework DB version (either from DB, or from command line)
         */
        $codeversions = array('PROJECT'   => PROJECTDBVERSION,
                              'FRAMEWORK' => FRAMEWORKDBVERSION);

        if($frameworkfrom){
            /*
             * We're (probably) redoing earlier versions, so remove registrations from earlier versions
             */
            sql_query('DELETE FROM `versions` WHERE (SUBSTRING(`framework`, 1, 1) != "-") AND (INET_ATON(CONCAT(`framework`, REPEAT(".0", 3 - CHAR_LENGTH(`framework`) + CHAR_LENGTH(REPLACE(`framework`, ".", ""))))) >= INET_ATON(CONCAT("'.$frameworkfrom.'", REPEAT(".0", 3 - CHAR_LENGTH("'.$frameworkfrom.'") + CHAR_LENGTH(REPLACE("'.$frameworkfrom.'", ".", ""))))))');
            $codeversions['FRAMEWORK'] = sql_get('SELECT `framework` FROM `versions` ORDER BY `id` DESC LIMIT 1;', 'framework');
        }

        /*
         * Determine project DB version (either from DB, or from command line)
         */
        if($projectfrom){
            /*
             * We're (probably) doing earlier versions, so remove registrations from earlier versions
             */
            sql_query('DELETE FROM `versions` WHERE (SUBSTRING(`project`, 1, 1) != "-") AND (INET_ATON(CONCAT(`project`, REPEAT(".0", 3 - CHAR_LENGTH(`project`) + CHAR_LENGTH(REPLACE(`project`, ".", ""))))) >= INET_ATON(CONCAT("'.$projectfrom.'", REPEAT(".0", 3 - CHAR_LENGTH("'.$projectfrom.'") + CHAR_LENGTH(REPLACE("'.$projectfrom.'", ".", ""))))))');
            $codeversions['PROJECT'] = sql_get('SELECT `project` FROM `versions` ORDER BY `id` DESC LIMIT 1;', 'project');
        }

        if(!$codeversions['FRAMEWORK'] or FORCE){
            /*
             * We're at 0, we must init everything!
             *
             * This point is just to detect that we need to init below. Dont init anything yet here
             *
             * Create a fake user session in case some init parts require some username
             */
            if(empty($_SESSION['user'])){
                $_SESSION['user'] = array('id'       => null,
                                          'name'     => 'System Init',
                                          'username' => 'init',
                                          'email'    => 'init@'.$_CONFIG['domain'],
                                          'rights'   => array('admin', 'users', 'rights'));
            }

        }elseif(!FORCE and (FRAMEWORKCODEVERSION == $codeversions['FRAMEWORK']) and (PROJECTCODEVERSION == $codeversions['PROJECT'])){
            /*
             * Fetch me a pizza, all is just fine!
             */
            log_console('The framework code and project code versions matches the database versions, so all is fine!', 'white');
            $noinit = true;
        }

        if(version_compare(FRAMEWORKCODEVERSION, $codeversions['FRAMEWORK']) < 0){
            if(!str_is_version(FRAMEWORKCODEVERSION)){
                throw new BException('init(): Cannot continue, the FRAMEWORK code version "'.str_log(FRAMEWORKCODEVERSION).'" (Defined at the top of '.ROOT.'/libs/system.php) is invalid', 'invalid-framework-code');
            }

            throw new BException(tr('init(): Cannot continue, the FRAMEWORK code version ":code" is OLDER (LOWER) than the database version ":db", the project is running with either old code or a too new database!', array(':code' => FRAMEWORKCODEVERSION, ':db' => FRAMEWORKDBVERSION)), 'old-framework-code');
        }

        if(version_compare(PROJECTCODEVERSION, $codeversions['PROJECT']) < 0){
            if(!str_is_version(PROJECTCODEVERSION)){
                throw new BException(tr('init(): Cannot continue, the PROJECT code version ":version" (Defined in ":file") is invalid', array(':version' => PROJECTCODEVERSION, ':file' => ROOT.'/config/project.php')), 'invalid-project-code');
            }

            throw new BException(tr('init(): Cannot continue, the PROJECT code version ":code" is OLDER (LOWER) than the database version ":db", the project is running with either old code or a too new database!', array(':code' => PROJECTCODEVERSION, ':db' => PROJECTDBVERSION)), 'old-project-code');
        }

        /*
         * Clear all cache
         */
        load_libs('cache');
        cache_clear();
        log_console(tr('Cleared cache'), 'green');

        /*
         * From this point on, we are doing an init
         */
        if(FORCE){
            if(!is_bool(FORCE) and !str_is_version(FORCE)){
                throw new BException('init(): Invalid "force" sub parameter "'.str_log(FORCE).'" specified. "force" can only be followed by a valid init version number', 'invalidforce');
            }

            $init = 'forced init';

        }else{
            $init = 'init';
        }

        if(empty($noinit)){
            if(FORCE){
                log_console('Starting '.$init.' FORCED from version "'.FORCE.'" for "'.$_CONFIG['name'].'" using PHP "'.phpversion().'"', 'white');

            }else{
                log_console('Starting '.$init.' for "'.$_CONFIG['name'].'" using PHP "'.phpversion().'"', 'white');
            }

            /*
             * Check MySQL timezone availability
             */
            if(!sql_get('SELECT CONVERT_TZ("2012-06-07 12:00:00", "GMT", "America/New_York") AS `time`', 'time')){
                log_console('No timezone data found in MySQL, importing timezone data files now', 'yellow');
                log_console('Please fill in MySQL root password in the following "Enter password:" request', 'white');
                log_console('You may ignore any "Warning: Unable to load \'/usr/share/zoneinfo/........\' as time zone. Skipping it." messages', 'yellow');

                safe_exec(array('commands' => array('mysql_tzinfo_to_sql', array('/usr/share/zoneinfo', 'connector' => '|'),
                                                    'mysql'              , array('-p', '-u', 'root', 'mysql'))));
            }
            define('INITPATH', slash(realpath(ROOT.'init')));

            $versions = array('framework' => $codeversions['FRAMEWORK'],
                              'project'   => $codeversions['PROJECT']);

            /*
             * ALWAYS First init framework, then project
             */
            foreach(array('framework', 'project') as $type){
                log_console(tr('Starting ":type" init', array(':type' => $type)));

                /*
                 * Get path for the init type (either init/framework or init/project)
                 * and then get a list of all init files for the init type, and walk
                 * over each init file, and see if it needs execution or not
                 */
                $initpath  = INITPATH.slash($type);
                $files     = scandir($initpath);
                $utype     = strtoupper($type);
                $dbversion = ((FORCE and str_is_version(FORCE)) ? FORCE : $codeversions[$utype]);

                /*
                 * Cleanup and order list
                 */
                foreach($files as $key => $file){
                    /*
                     * Skip garbage
                     */
                    if(($file == '.') or ($file == '..')){
                        unset($files[$key]);
                        continue;
                    }

                    if((file_extension($file) != 'php') or !str_is_version(str_until($file, '.php'))) {
                        log_console(tr('Skipping unknown file ":file"', array(':file' => $file)), 'yellow');
                        unset($files[$key]);
                        continue;
                    }

                    $files[$key] = substr($file, 0, -4);
                }

                usort($files, 'version_compare');

                /*
                 * Go over each init file, see if it needs execution or not
                 */
                foreach($files as $file){
                    $version = $file;
                    $file    = $file.'.php';

                    if(version_compare($version, constant($utype.'CODEVERSION')) >= 1){
                        /*
                         * This init file has a higher version number than the current code, so it should not yet be executed (until a later time that is)
                         */
                        log_console(tr('Skipped future init file ":version"', array(':version' => $version)), 'VERBOSE/warning');

                    }else{
                        if(($dbversion === 0) or (version_compare($version, $dbversion) >= 1)){
                            /*
                             * This init file is higher than the DB version, but lower than the code version, so it must be executed
                             */
                            try{
                                if(file_exists($hook = $initpath.'hooks/pre_'.$file)){
                                    log_console(tr('Executing newer init "pre" hook file with version ":version"', array(':version' => $version)), 'cyan');
                                    include_once($hook);
                                }

                            }catch(Exception $e){
                                /*
                                 * INIT FILE FAILED!
                                 */
                                throw new BException(tr('init(:type): Init "pre" hook file ":file" failed', array(':type' => $type, ':file' => $file)), $e);
                            }

                            try{
                                log_console(tr('Executing newer init file with version ":version"', array(':version' => $version)), 'VERBOSE/cyan');
                                init_include($initpath.$file);

                            }catch(Exception $e){
                                /*
                                 * INIT FILE FAILED!
                                 */
                                throw new BException('init('.$type.'): Init file "'.$file.'" failed', $e);
                            }

                            try{
                                if(file_exists($hook = $initpath.'hooks/post_'.$file)){
                                    log_console(tr('Executing newer init "post" hook file with version ":version"', array(':version' => $version)), 'VERBOSE/cyan');
                                    include_once($hook);
                                }

                            }catch(Exception $e){
                                /*
                                 * INIT FILE FAILED!
                                 */
                                throw new BException('init('.$type.'): Init "post" hook file "'.$file.'" failed', $e);
                            }

                            $versions[$type] = $version;

                            $core->sql['core']->query('INSERT INTO `versions` (`framework`, `project`) VALUES ("'.cfm($versions['framework']).'", "'.cfm($versions['project']).'")');

                            log_console('Finished init version "'.$version.'"', 'green');

                        }else{
                            /*
                             * This init file has already been executed so we can skip it.
                             */
                            log_console('Skipped older init file "'.$version.'"', 'VERBOSE/yellow');
                        }
                    }
                }

                /*
                 * There are no more init files. If the last executed init file has a lower
                 * version than the code version still, then update the DB version to the
                 * code version now.
                 *
                 * This way, the code version can be upped without having to add empty init files.
                 */
                if(version_compare(constant($utype.'CODEVERSION'), $versions[$type]) > 0){
                    log_console('Last init file was "'.$versions[$type].'" while code version is still higher at "'.constant($utype.'CODEVERSION').'"', 'yellow');
                    log_console('Updating database version to code version manually'                                                                  , 'yellow');

                    $versions[$type] = constant($utype.'CODEVERSION');

                    $core->sql['core']->query('INSERT INTO `versions` (`framework`, `project`) VALUES ("'.cfm((string) $versions['framework']).'", "'.cfm((string) $versions['project']).'")');
                }

                /*
                 * Finished one init part (either type framework or type project)
                 */
                log_console('Finished init', 'green');
            }
        }

        if($_CONFIG['production']){
            log_console('Removing data symlink or files in all languages', 'cyan');

            if($_CONFIG['language']['supported']){
                foreach($_CONFIG['language']['supported'] as $language => $name){
                    file_delete(ROOT.'www/'.substr($language, 0, 2).'/data', ROOT.'www/'.substr($language, 0, 2));
                }
            }

            log_console('Finished data symlink cleanup', 'green');
        }

        log_console('Finished all', 'green');

    }catch(Exception $e){
        switch($e->getRealCode()){
            case 'invalidforce':
                foreach($e->getMessages() as $message){
                    log_console($message);
                }

                die(1);

            case 'validation':
                /*
                 * In init mode, all validation warnings are fatal!
                 */
                $e->makeWarning(false);
        }

        throw new BException('init(): Failed', $e);
    }
}



/*
 * There is a version difference between either the framework code and database versions,
 * or the projects code and database versions. Determine which one differs, and how, so
 * we can diplay the correct error
 *
 * Differences may be:
 *
 * Project or framework database may be older than the code
 * Project or framework database may be newer than the code
 *
 * This function is only meant to display the correct error
 *
 */
function init_process_version_diff(){
    global $_CONFIG, $core;

    try{
        switch($core->register['script']){
            case 'info':
                // FALLTHROUGH
            case 'init':
                // FALLTHROUGH
            case 'sync':
                // FALLTHROUGH
            case 'update':
                // FALLTHROUGH
            case 'version':
                return false;
        }

        $compare_project   = version_compare(PROJECTCODEVERSION  , PROJECTDBVERSION);
        $compare_framework = version_compare(FRAMEWORKCODEVERSION, FRAMEWORKDBVERSION);

        if(PROJECTDBVERSION === 0){
            $versionerror     = 'Database is empty';
            $core->register['no-db'] = true;

        }else{
            if($compare_framework > 0){
                $versionerror = (empty($versionerror) ? "" : "\n").tr('Framework core database ":db" version ":dbversion" is older than code version ":codeversion"', array(':db' => str_log($_CONFIG['db']['core']['db']), ':dbversion' => FRAMEWORKDBVERSION, ':codeversion' => FRAMEWORKCODEVERSION));

            }elseif($compare_framework < 0){
                $versionerror = (empty($versionerror) ? "" : "\n").tr('Framework core database ":db" version ":dbversion" is older than code version ":codeversion"', array(':db' => str_log($_CONFIG['db']['core']['db']), ':dbversion' => FRAMEWORKDBVERSION, ':codeversion' => FRAMEWORKCODEVERSION));
            }

            if($compare_project > 0){
                $versionerror = (empty($versionerror) ? "" : "\n").tr('Project core database ":db" version ":dbversion" is older than code version ":codeversion"', array(':db' => str_log($_CONFIG['db']['core']['db']), ':dbversion' => PROJECTDBVERSION, ':codeversion' => PROJECTCODEVERSION));

            }elseif($compare_project < 0){
                $versionerror = (empty($versionerror) ? "" : "\n").tr('Project core database ":db" version ":dbversion" is newer than code version ":codeversion"!', array(':db' => str_log($_CONFIG['db']['core']['db']), ':dbversion' => PROJECTDBVERSION, ':codeversion' => PROJECTCODEVERSION));
            }
        }

        if(PLATFORM_HTTP or !cli_argument('--no-version-check')){
            throw new BException(tr('init_process_version_diff(): Please run script ROOT/scripts/base/init because ":error"', array(':error' => $versionerror)), 'warning/do-init');
        }

    }catch(Exception $e){
        throw new BException('init_process_version_diff(): Failed', $e);
    }
}



/*
 * Version check failed. Check why
 *
 * Basically, this function is ONLY executed if we are executing the init script. The version check failed,
 * which PROBABLY was because the database is empty at this point, but we cannot be 100% sure of that. This
 * function will just make sure that the version check did not fail because of other reason, so that we can
 * safely continue with system init
 */
function init_process_version_fail($e){
    global $_CONFIG, $core;

    try{
        $r = $core->sql['core']->query('SHOW TABLES WHERE `Tables_in_'.$_CONFIG['db']['core']['db'].'` = "versions";');

        if($r->rowCount($r)){
            throw new BException('init_process_version_fail(): Failed version detection', $e);
        }

        define('FRAMEWORKDBVERSION', 0);
        define('PROJECTDBVERSION'  , 0);

        $core->register['no-db'] = true;

        if(PLATFORM_CLI){
            log_console('init_process_version_fail(): No versions table found, assumed empty database', 'yellow');
        }

    }catch(Exception $e){
        throw new BException('init_process_version_fail(): Failed', $e);
    }
}



/*
 * Execute specified hook file
 */
function init_hook($hook, $disabled = false, $params = null){
    try{
        /*
         * Reshuffle arguments, if needed
         */
        if(is_bool($params)){
            $disabled = $params;
            $params   = null;
        }

        if(is_array($disabled)){
            $params   = $disabled;
            $disabled = $params;
        }

        if(!$disabled and file_exists(ROOT.'scripts/hooks/'.$hook)){
            return script_exec(array('commands' => array('hooks/'.$hook, $params)));
        }

    }catch(Exception $e){
        throw new BException(tr('init_hook(): Hook ":hook" failed', array(':hook' => $hook)), $e);
    }
}



/*
 * Upgrade the specified part of the specified version
 */
function init_version_upgrade($version, $part){
    try{
        if(!str_is_version($version)){
            throw new BException('init_version_upgrade(): Specified version is not a valid n.n.n version format');
        }

        $version = explode('.', $version);

        switch($part){
            case 'major':
                $version[0]++;
                break;

            case 'minor':
                $version[1]++;
                break;

            case 'revision':
                $version[2]++;
                break;

            default:
                throw new BException(tr('init_version_upgrade(): Unknown version part type ":part" specified. Please specify one of "major", "minor", or "revision"', array(':part' => $part)));
        }

        return implode('.', $version);

    }catch(Exception $e){
        throw new BException('init_version_upgrade(): Failed', $e);
    }
}



/*
 * Have a function that executes the include to separate the variable scope and
 * avoid init files interfering with variables in this library
 */
function init_include($file, $section = null){
    global $_CONFIG;

    try{
        include_once($file);

    }catch(Exception $e){
        throw new BException('init_include(): Failed', $e);
    }
}



/*
 * Initialize the specified section.
 *
 * The section must be available as a directory with the name of the section in the ROOT/init path. If (for example) the section is called "mail", the init section in ROOT/init/mail will be executed. The section name will FORCE all sql_query() calls to use the connector with the $section name.
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package init
 *
 * @param string $section
 * @param string $version
 * @return void
 */
function init_section($section, $version){
    global $_CONFIG, $core;

    try{
        load_libs('sql_exists');

        $path = ROOT.'init/'.$section.'/';

        if(!file_exists($path)){
            throw new BException(tr('init_section(): Specified section ":section" path ":path" does not exist', array(':section' => $section, ':path' => $path)), 'not-exists');
        }

        $connector = sql_get_connector($section);

        if(!$connector){
            throw new BException(tr('init_section(): Specified section ":section" does not have a connector configuration. Please check $_CONFIG[db] or the `sql_connectors` table', array(':section' => $section)), 'not-exists');
        }

        /*
         * Set the default connector to the connector for the specified section
         */
        $core->register('sql_connector', $section);
        $exists = sql_get('SHOW TABLES LIKE "versions"', true);

        if($exists){
            if($version){
                /*
                 * Reset the versions table to the specified version
                 */
                sql_query('DELETE FROM `versions` WHERE (SUBSTRING(`version`, 1, 1) != "-") AND (INET_ATON(CONCAT(`version`, REPEAT(".0", 3 - CHAR_LENGTH(`version`) + CHAR_LENGTH(REPLACE(`version`, ".", ""))))) >= INET_ATON(CONCAT("'.$version.'", REPEAT(".0", 3 - CHAR_LENGTH("'.$version.'") + CHAR_LENGTH(REPLACE("'.$version.'", ".", ""))))))');
            }

            $dbversion = sql_get('SELECT `version` FROM `versions` ORDER BY `id` DESC LIMIT 1', true);

            if($dbversion === null){
                /*
                 * No version data found, we're at 0.0.0
                 */
                $dbversion = '0.0.0';
            }

        }else{
            /*
             * No version table found, we're at 0.0.0
             */
            $dbversion = '0.0.0';
        }

        log_console(tr('Starting ":section" init at version ":version"', array(':section' => $section, ':version' => $dbversion)), 'cyan');

        $files = scandir($path);

        /*
         * Cleanup and order file list
         */
        foreach($files as $key => $file){
            /*
             * Skip garbage
             */
            if(($file == '.') or ($file == '..')){
                unset($files[$key]);
                continue;
            }

            if((file_extension($file) != 'php') or !str_is_version(str_until($file, '.php'))) {
                log_console(tr('Skipping unknown file ":file"', array(':file' => $file)), 'yellow');
                unset($files[$key]);
                continue;
            }

            $files[$key] = substr($file, 0, -4);
        }

        usort($files, 'version_compare');

        /*
         * Go over each init file, see if it needs execution or not
         */
        foreach($files as $file){
            $version = $file;
            $file    = $file.'.php';

            if(version_compare($version, $connector['version']) >= 1){
                /*
                 * This init file has a higher version number than the current code, so it should not yet be executed (until a later time that is)
                 */
                log_console(tr('Skipped future section ":section" init file ":version"', array(':version' => $version, ':section' => $section)), 'VERBOSE');

            }else{
                if(($dbversion === 0) or (version_compare($version, $dbversion) >= 1)){
                    /*
                     * This init file is higher than the DB version, but lower than the code version, so it must be executed
                     */
                    try{
                        if(file_exists($hook = $path.'hooks/pre_'.$file)){
                            log_console('Executing newer init "pre" hook file with version "'.$version.'"', 'cyan');
                            include_once($hook);
                        }

                    }catch(Exception $e){
                        /*
                         * INIT FILE FAILED!
                         */
                        throw new BException('init('.$section.'): Init "pre" hook file "'.$file.'" failed', $e);
                    }

                    try{
                        log_console('Executing newer init file with version "'.$version.'"', 'VERBOSE/cyan');
                        init_include($path.$file, $section);

                    }catch(Exception $e){
                        /*
                         * INIT FILE FAILED!
                         */
                        throw new BException('init('.$section.'): Init file "'.$file.'" failed', $e);
                    }

                    try{
                        if(file_exists($hook = $path.'hooks/post_'.$file)){
                            log_console('Executing newer init "post" hook file with version "'.$version.'"', 'VERBOSE/cyan');
                            include_once($hook);
                        }

                    }catch(Exception $e){
                        /*
                         * INIT FILE FAILED!
                         */
                        throw new BException('init('.$section.'): Init "post" hook file "'.$file.'" failed', $e);
                    }

                    sql_query('INSERT INTO `versions` (`version`) VALUES (:version)', array(':version' => $version));
                    log_console('Finished init version "'.$version.'"', 'green');

                }else{
                    /*
                     * This init file has already been executed so we can skip it.
                     */
                    if(VERBOSE){
                        log_console('Skipped older init file "'.$version.'"', 'yellow');
                    }
                }
            }
        }

        /*
         * There are no more init files. If the last executed init file has a lower
         * version than the code version still, then update the DB version to the
         * code version now.
         *
         * This way, the code version can be upped without having to add empty init files.
         */
        if(version_compare($dbversion, $version) > 0){
            log_console('Last init file was "'.$version.'" while code version is still higher at "'.$connector['version'].'"', 'yellow');
            log_console('Updating database version to code version manually'                                                 , 'yellow');

            $version = $connector['version'];

            sql_query('INSERT INTO `versions` (`version`) VALUES (:version)', array(':version' => $version));
        }

        /*
         * Finished one init part (either type version or type project)
         */
        log_console(tr('Finished section ":section" init', array(':section' => $section)), 'green');

        /*
         * Reset the default connector
         */
        $core->register('sql_connector', null);

    }catch(Exception $e){
        throw new BException('init_section(): Failed', $e);
    }
}



/*
 * Reset the database version back to the current code version in case it is ahead. Any extra entries in the versions table AFTER the current version will be wiped. This option does NOT allow to reset the database version in case the current code version is ahead. For that, a normal init must be executed
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package init
 * @version 2.5.2: Added function and documentation
 *
 * @return natural The amount of entries removed from the `versions` table
 */
function init_reset(){
    try{
        $versions = sql_query('SELECT `id`, `framework`, `project` FROM `versions`');
        $erase    = sql_prepare('DELETE FROM `versions` WHERE `id` = :id');
        $changed  = 0;

        while($version = sql_fetch($versions)){
            if(version_compare($version['framework'], FRAMEWORKCODEVERSION) > 0){
                $erase->execute(array(':id' => $version['id']));
                $changed++;
                continue;
            }

            if(version_compare($version['project'], PROJECTCODEVERSION) > 0){
                $erase->execute(array(':id' => $version['id']));
                $changed++;
                continue;
            }
        }

        return $changed;

    }catch(Exception $e){
        throw new BException('init_reset(): Failed', $e);
    }
}



/*
 * Get the verion of the init file with the highest version for the specified section
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package init
 * @version 2.5.2: Added function and documentation
 *
 * @param string $section The section (either "framework", "project", or a custom one) in which to find the highest init file version
 * @return string The highest init file version available for the specified section
 */
function init_get_highest_file_version($section){
    try{
        switch($section){
            case 'framework':
                // FALLTHROUGH
            case 'project':
                /*
                 * These are the default sections, these are okay
                 */
                break;

            default:
                /*
                 * Custom section, check if it exists
                 */
                if(!file_exists(ROOT.'init/'.$section)){
                    throw new BException(tr('init_get_highest_file_version(): The specified custom init section ":section" does not exist', array(':section' => $section)), 'not-exist');
                }
        }

        $version = '0.0.0';
        $files   = scandir(ROOT.'init/'.$section);

        foreach($files as $file){
            if(($file === '.') or ($file === '..')){
                continue;
            }

            $file = str_runtil($file, '.php');

            if(version_compare($file, $version) === 1){
                $version = $file;
            }
        }

        return $version;

    }catch(Exception $e){
        throw new BException('init_get_highest_file_version(): Failed', $e);
    }
}
?>
