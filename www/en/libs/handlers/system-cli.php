<?php
/*
 * This is the startup sequence for CLI programs
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */



/*
 * Define basic platform constants
 */
try {
    /*
     * Make sure we have the original arguments available
     */
    $core->register['argv'] = $GLOBALS['argv'];
    putenv('TIMEOUT='.cli_argument('--timeout', true));



    /*
     * Define basic platform constants
     */
    define('ADMIN'      , '');
    define('PWD'        , Strings::slash(isset_get($_SERVER['PWD'])));
    define('VERYVERBOSE', (cli_argument('-VV,--very-verbose')                               ? 'VERYVERBOSE' : null));
    define('VERBOSE'    , ((VERYVERBOSE or cli_argument('-V,--verbose,-V2,--very-verbose')) ? 'VERBOSE'     : null));
    define('QUIET'      , cli_argument('-Q,--quiet'));
    define('FORCE'      , cli_argument('-F,--force'));
    define('NOCOLOR'    , cli_argument('-C,--no-color'));
    define('TEST'       , cli_argument('-T,--test'));
    define('DELETED'    , cli_argument('--deleted'));
    define('STATUS'     , cli_argument('-S,--status', true));
    define('STARTDIR'   , Strings::slash(getcwd()));



    /*
     * Check what environment we're in
     */
    $environment = cli_argument('-E,--env,--environment', true);

    if (empty($environment)) {
        $env = getenv(PROJECT.'_ENVIRONMENT');

        if (empty($env)) {
            echo "\033[0;31mstartup: No required environment specified for project \"".PROJECT."\"\033[0m\n";
            $core->register['exit_code'] = 2;
            die(2);
        }

    } else {
        $env = $environment;
    }

    if (strstr($env, '_')) {
        echo "\033[0;31mstartup: Specified environment \"$env\" is invalid, environment names cannot contain the underscore character\033[0m\n";
        $core->register['exit_code'] = 4;
        die(4);
    }

    define('ENVIRONMENT', $env);
    load_config(' ');

    if (!file_exists(ROOT.'config/'.$env.'.php')) {
        echo "\033[0;31mstartup: Configuration file \"ROOT/config/".$env.".php\" for specified environment\"".$env."\" not found\033[0m\n";
        $core->register['exit_code'] = 5;
        die(5);
    }

    /*
     * Set protocol
     */
    global $_CONFIG;
    define('PROTOCOL', 'http'.($_CONFIG['sessions']['secure'] ? 's' : '').'://');

    /*
     * Process basic shell arguments
     */
    if (empty($e)) {
        /*
         * Correct $_SERVER['PHP_SELF'], sometimes seems empty
         */
        if (empty($_SERVER['PHP_SELF'])) {
            if (!isset($_SERVER['_'])) {
                $e = new Exception('No $_SERVER[PHP_SELF] or $_SERVER[_] found', 'not-exists');
            }

             $_SERVER['PHP_SELF'] =  $_SERVER['_'];
        }

        foreach ($GLOBALS['argv'] as $argid => $arg) {
            /*
             * (Usually first) argument may contain the startup of this script, which we may ignore
             */
            if ($arg == $_SERVER['PHP_SELF']) {
                continue;
            }

            switch ($arg) {
                case '--version':
                    /*
                     * Show version information
                     */
                    log_console(tr('BASE framework code version ":fv", project code version ":pv"', array(':fv' => FRAMEWORKCODEVERSION, ':pv' => PROJECTCODEVERSION)));
                    $die = 0;
                    break;

                case '-U':
                    // no-break
                case '--usage':
                    // no-break
                case 'usage':
                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                    $die = 0;
                    break;

                case '-H':
                    // no-break
                case '--help':
                    // no-break
                case 'help':
                    if (isset_get($GLOBALS['argv'][$argid + 1]) == 'system') {
                        load_libs('help');
                        help('system');

                    } else {
                        if (empty($GLOBALS['help'])) {
                            $e = new CoreException(tr('core::startup(): Sorry, this script has no help text defined'), 'warning');
                        }

                        $GLOBALS['help'] = Arrays::force($GLOBALS['help'], "\n");

                        if (count($GLOBALS['help']) == 1) {
                            log_console(array_shift($GLOBALS['help']), 'white');

                        } else {
                            foreach (Arrays::force($GLOBALS['help'], "\n") as $line) {
                                log_console($line, 'white');
                            }

                            log_console();
                        }
                    }

                    $die = 0;
                    break;

                case '-L':
                    // no-break
                case '--language':
                    /*
                     * Set language to be used
                     */
                    if (isset($language)) {
                        $e = new CoreException(tr('core::startup(): Language has been specified twice'), 'exists');
                    }

                    if (!isset($GLOBALS['argv'][$argid + 1])) {
                        $e = new CoreException(tr('core::startup(): The "language" argument requires a two letter language core right after it'), 'invalid');
                    }

                    $language = $GLOBALS['argv'][$argid + 1];

                    unset($GLOBALS['argv'][$argid]);
                    unset($GLOBALS['argv'][$argid + 1]);
                    break;

                //case '-E':
                //    // no-break
                //case '--env':
                //    /*
                //     * Set environment and reset next
                //     */
                //    if (isset($environment)) {
                //        $e = new CoreException(tr('core::startup(): Environment has been specified twice'), 'exists');
                //    }
                //
                //    if (!isset($GLOBALS['argv'][$argid + 1])) {
                //        $e = new CoreException(tr('core::startup(): The "environment" argument requires an existing environment name right after it'), 'invalid');
                //    }
                //
                //    $environment = $GLOBALS['argv'][$argid + 1];
                //
                //    unset($GLOBALS['argv'][$argid]);
                //    unset($GLOBALS['argv'][$argid + 1]);
                //    break;

                case '-O':
                    // TALLTHROUGH
                case '--orderby':
                    define('ORDERBY', ' ORDER BY `'.Strings::until($GLOBALS['argv'][$argid + 1], ' ').'` '.Strings::from($GLOBALS['argv'][$argid + 1], ' ').' ');

                    $valid = preg_match('/^ ORDER BY `[a-z0-9_]+`(?:\s+(?:DESC|ASC))? $/', ORDERBY);

                    if (!$valid) {
                        /*
                         * The specified column ordering is NOT valid
                         */
                        $e = new CoreException(tr('core::startup(): The specified orderby argument ":argument" is invalid', array(':argument' => ORDERBY)), 'invalid');
                    }

                    unset($GLOBALS['argv'][$argid]);
                    unset($GLOBALS['argv'][$argid + 1]);
                    break;

                case '--timezone':
                    /*
                     * Set timezone
                     */
                    if (isset($timezone)) {
                        $e = new CoreException(tr('core::startup(): Timezone has been specified twice'), 'exists');
                    }

                    if (!isset($GLOBALS['argv'][$argid + 1])) {
                        $e = new CoreException(tr('core::startup(): The "timezone" argument requires a valid and existing timezone name right after it'), 'invalid');

                    }

                    $timezone = $GLOBALS['argv'][$argid + 1];

                    unset($GLOBALS['argv'][$argid]);
                    unset($GLOBALS['argv'][$argid + 1]);
                    break;

                case '-I':
                    // no-break
                case '--skip-init-check':
                    /*
                     * Skip init check for the core database
                     */
                    $core->register['skip_init_check'] = true;
                    break;

                default:
                    /*
                     * This is not a system parameter
                     */
                    break;
            }
        }

        unset($arg);
        unset($argid);

        if (!defined('ORDERBY')) {
            define('ORDERBY', '');
        }
    }



    /*
     * Remove the command itself from the argv array
     */
    array_shift($GLOBALS['argv']);



    /*
     * Load basic configuration for the current environment
     * Load cache libraries (done until here since these need configuration @ load time)
     * Set timeout
     */
    load_libs('cache'.(empty($_CONFIG['cdn']['enabled']) ? '' : ',cdn'));
    set_timeout();



    /*
     * Something failed?
     */
    if (isset($e)) {
        echo "startup-cli: Command line parser failed with \"".$e->getMessage()."\"\n";
        $core->register['exit_code'] = 1;
        die(1);
    }

    if (isset($die)) {
        $core->register['ready']     = true;
        $core->register['exit_code'] = $die;
        die($die);
    }



    /*
     * Get terminal data
     */
    $core->register['cli'] = array('term' => cli_get_term());

    if ($core->register['cli']['term']) {
        $core->register['cli']['columns'] = cli_get_columns();
        $core->register['cli']['lines']   = cli_get_lines();

        if (!$core->register['cli']['columns']) {
            $core->register['cli']['size'] = 'unknown';

        } elseif ($core->register['cli']['columns'] <= 80) {
            $core->register['cli']['size'] = 'small';

        } elseif ($core->register['cli']['columns'] <= 160) {
            $core->register['cli']['size'] = 'medium';

        } else {
            $core->register['cli']['size'] = 'large';
        }
    }



    /*
     * Set security umask
     */
    umask($_CONFIG['security']['umask']);



    /*
     * Ensure that the process UID matches the file UID
     */
    cli_process_uid_matches(true);
    log_file(tr('Running script ":script"', array(':script' => $_SERVER['PHP_SELF'])), 'startup', 'cyan');



    /*
     * Get required language.
     */
    try {
        $language = not_empty(cli_argument('--language'), cli_argument('L'), $_CONFIG['language']['default']);

        if ($_CONFIG['language']['supported'] and !isset($_CONFIG['language']['supported'][$language])) {
            throw new CoreException(tr('core::startup(): Unknown language ":language" specified', array(':language' => $language)), 'unknown');
        }

        define('LANGUAGE', $language);
        define('LOCALE'  , $language.(empty($_SESSION['location']['country']['code']) ? '' : '_'.$_SESSION['location']['country']['code']));

        $_SESSION['language'] = $language;

    }catch(Exception $e) {
        /*
         * Language selection failed
         */
        if (!defined('LANGUAGE')) {
            define('LANGUAGE', 'en');
        }

        $e = new CoreException('core::startup(): Language selection failed', $e);
    }

    define('LIBS', ROOT.'www/'.LANGUAGE.'/libs/');



    /*
     * Setup locale and character encoding
     */
    ini_set('default_charset', $_CONFIG['encoding']['charset']);
    $this->register('locale', set_locale());



    /*
     * Prepare for unicode usage
     */
    if ($_CONFIG['encoding']['charset'] == 'UTF-8') {
        mb_init(not_empty($_CONFIG['locale'][LC_CTYPE], $_CONFIG['locale'][LC_ALL]));

        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
        }
    }



    /*
     * Set timezone information
     * See http://www.php.net/manual/en/timezones.php for more info
     */
    try {
        date_default_timezone_set($_CONFIG['timezone']['system']);

    }catch(Exception $e) {
        /*
         * Users timezone failed, use the configured one
         */
        notify($e);
    }

    define('TIMEZONE', $_CONFIG['timezone']['display']);
    $_SESSION['user']['timezone'] = $_CONFIG['timezone']['display'];



    /*
     *
     */
    $core->register['ready'] = true;

    if (cli_argument('-D,--debug')) {
        Debug::enabled();
    }



    /*
     * Set more system parameters
     */
    $core->register['all']         = cli_argument('-A,--all');
    $core->register['page']        = not_empty(cli_argument('-P,--page', true), 1);
    $core->register['limit']       = not_empty(cli_argument('--limit'  , true), $_CONFIG['paging']['limit']);
    $core->register['clean_debug'] = cli_argument('--clean-debug');



    /*
     * Validate parameters
     * Give some startup messages, if needed
     */
    if (VERBOSE) {
        if (QUIET) {
            throw new CoreException(tr('core::startup(): Both QUIET and VERBOSE have been specified but these options are mutually exclusive. Please specify either one or the other'), 'warning/invalid');
        }

        if (VERYVERBOSE) {
            log_console(tr('Running in VERYVERBOSE mode, started @ ":datetime"', array(':datetime' => date_convert(STARTTIME, 'human_datetime'))), 'white');

        } else {
            log_console(tr('Running in VERBOSE mode, started @ ":datetime"', array(':datetime' => date_convert(STARTTIME, 'human_datetime'))), 'white');
        }

        log_console(tr('Detected ":size" terminal with ":columns" columns and ":lines" lines', array(':size' => $core->register['cli']['size'], ':columns' => $core->register['cli']['columns'], ':lines' => $core->register['cli']['lines'])));
    }

    if (FORCE) {
        if (TEST) {
            throw new CoreException(tr('core::startup(): Both FORCE and TEST modes where specified, these modes are mutually exclusive'), 'invalid');
        }

        log_console(tr('Running in FORCE mode'), 'yellow');

    } elseif (TEST) {
        log_console(tr('Running in TEST mode'), 'yellow');
    }

    if (Debug::enabled()) {
        log_console(tr('Running in DEBUG mode'), 'VERBOSE/yellow');
    }

    if (!is_natural($core->register['page'])) {
        throw new CoreException(tr('paging_library_init(): Specified -P or --page ":page" is not a natural number', array(':page' => $core->register['page'])), 'invalid');
    }

    if (!is_natural($core->register['limit'])) {
        throw new CoreException(tr('paging_library_init(): Specified --limit":limit" is not a natural number', array(':limit' => $core->register['limit'])), 'invalid');
    }

    if ($core->register['all']) {
        if ($core->register['page'] > 1) {
            throw new CoreException(tr('paging_library_init(): Both -A or --all and -P or --page have been specified, these options are mutually exclusive'), 'invalid');
        }

        if (DELETED) {
            throw new CoreException(tr('paging_library_init(): Both -A or --all and -D or --deleted have been specified, these options are mutually exclusive'), 'invalid');
        }

        if (STATUS) {
            throw new CoreException(tr('paging_library_init(): Both -A or --all and -S or --status have been specified, these options are mutually exclusive'), 'invalid');
        }

    }



    /*
     * Load custom library, if available
     */
    load_libs('custom');



    /*
     * Did the startup sequence encounter reasons for us to actually show another
     * page?
     */
    if (isset($core->register['page_show'])) {
        page_show($core->register['page_show']);
    }

    /*
     * Setup language map in case domain() calls are used
     */
    load_libs('route');
    route_map();

}catch(Exception $e) {
    throw new CoreException(tr('core::cli(): Failed'), $e);
}
