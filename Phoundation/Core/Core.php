<?php

namespace Phoundation\Core;

/**
 * Class Core
 *
 * This is the
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2021 <copyright@capmega.com>
 * @package Phoundation\Core
 */
class Core {
    /**
     * Singleton variable
     *
     * @var ?Core $instance
     */
    protected static ?Core $instance = null;

    /**
     * The generic system register to store data
     *
     * @var bool $debug
     */
    protected static bool $debug = false;

    private $callType = null;

    public $sql = array();
    public $mc = array();
    public $register = array('tabindex' => 0,
        'ready' => false,
        'js_header' => array(),
        'js_footer' => array(),
        'css' => array(),
        'quiet' => true,
        'footer' => '',
        'debug_queries' => array());



    /**
     *
     */
    public function __construct()
    {
        /*
         * Framework version
         */
        define('FRAMEWORKCODEVERSION', '4.0.0');
        define('PHP_MINIMUM_VERSION', '8.1.0');


        /*
         * This constant can be used to measure time used to render page or process
         * script
         */
        define('STARTTIME', microtime(true));
        define('REQUEST', substr(uniqid(), 7));


        /*
         * Define project paths.
         *
         * ROOT   is the root directory of this project and should be used as the root for all other paths
         * TMP    is a private temporary directory
         * PUBTMP is a public (accessible by web server) temporary directory
         */
        define('ROOT', realpath(__DIR__ . '/../../..') . '/');
        define('TMP', ROOT . 'data/tmp/');
        define('PUBTMP', ROOT . 'data/content/tmp/');
        define('CRLF', "\r\n");


        /*
         * Include project setup file. This file contains the very bare bones basic
         * information about this project
         *
         * Load system library and initialize core
         */
        include_once(ROOT . 'config/project.php');


        /*
         * Setup error handling, report ALL errors
         */
        error_reporting(E_ALL);
        set_error_handler(['Core', 'phpErrorHandler']);
        set_exception_handler(['Core', 'uncaughtException']);


        try {
            /*
             * Check what platform we're in
             */
            switch (php_sapi_name()) {
                case 'cli':
                    define('PLATFORM', 'cli');
                    define('PLATFORM_HTTP', false);
                    define('PLATFORM_CLI', true);

                    $file = realpath(ROOT . 'scripts/' . str_from($argv[0], 'scripts/'));
                    $file = str_from($file, ROOT . 'scripts/');

                    $core->register['real_script'] = $file;
                    $core->register['script'] = str_rfrom($file, '/');

                    unset($file);

                    /*
                     * Load basic libraries for command line interface
                     * All scripts will execute cli_done() automatically once done
                     */
                    load_libs('cli,http,strings,array,sql,mb,meta,file,json');
                    register_shutdown_function('cli_done');
                    break;

                default:
                    define('PLATFORM', 'http');
                    define('PLATFORM_HTTP', true);
                    define('PLATFORM_CLI', false);
                    define('NOCOLOR', (getenv('NOCOLOR') ? 'NOCOLOR' : null));

                    /*
                     * Define what the current script
                     * Detect requested language
                     */
                    $core->register['http_code'] = 200;
                    $core->register['script'] = str_runtil(str_rfrom($_SERVER['PHP_SELF'], '/'), '.php');
                    $core->register['real_script'] = $core->register['script'];
                    $core->register['accepts'] = accepts();
                    $core->register['accepts_languages'] = accepts_languages();

                    /*
                     * Load basic libraries
                     * All scripts will execute http_done() automatically once done
                     */
                    register_shutdown_function('http_done');

                    /*
                     * Check what environment we're in
                     */
                    $env = getenv(PROJECT . '_ENVIRONMENT');

                    if (empty($env)) {
                        /*
                         * No environment set in ENV, maybe given by parameter?
                         */
                        die('startup: Required environment not specified for project "' . PROJECT . '"');
                    }

                    if (strstr($env, '_')) {
                        die('startup: Specified environment "' . $env . '" is invalid, environment names cannot contain the underscore character');
                    }

                    define('ENVIRONMENT', $env);

                    /*
                     * Load basic configuration for the current environment
                     * Load cache libraries (done until here since these need configuration @ load time)
                     */
                    load_config(' ');
                    $core->register['ready'] = true;

                    /*
                     * Define VERBOSE / VERYVERBOSE here because we need debug() data
                     */
                    define('VERYVERBOSE', (debug() and ((getenv('VERYVERBOSE') or !empty($GLOBALS['veryverbose']))) ? 'VERYVERBOSE' : null));
                    define('VERBOSE', (debug() and (VERYVERBOSE or getenv('VERBOSE') or !empty($GLOBALS['verbose'])) ? 'VERBOSE' : null));

                    /*
                     * Set protocol
                     */
                    global $_CONFIG;
                    define('PROTOCOL', 'http' . ($_CONFIG['sessions']['secure'] ? 's' : '') . '://');

                    if ($_CONFIG['security']['url_cloaking']['enabled']) {
                        /*
                         * URL cloaking enabled. Load the URL library so that the URL cloaking
                         * functions are available
                         */
                        load_libs('url');
                    }

                    break;
            }

        } catch (\Throwable $e) {
            /*
             * Startup failed miserably, we will NOT have log_file() or exception
             * handler available!
             *
             * Unregister shutdown handler by kicking the entire array to avoid issues
             * with those shutdown handlers!
             */
            if (isset($core)) {
                $core->register = array();
            }

            if (defined('PLATFORM_HTTP')) {
                if (PLATFORM_HTTP) {
                    /*
                     * Died in browser
                     */
                    error_log('startup: Failed with "' . $e->getMessage() . '"');
                    die('startup: Failed, see web server error log');
                }

                /*
                 * Died in CLI
                 */
                die('startup: Failed with "' . $e->getMessage() . '"');
            }

            /*
             * We died even before PLATFORM_HTTP was defined? How?
             */
            error_log('startup: Failed with "' . $e->getMessage() . '"');
            die('startup: Failed, see error log');
        }
    }



    /**
     * Singleton
     *
     * @param string|null $target
     * @return Log
     */
    public static function getInstance(string $target = null): Log
    {
        try{
            if (!isset(self::$instance)) {
                self::$instance = new Core($target);
            }
        } catch (Throwable $e) {
            // Crap, we could not get a Log instance
            self::$fail = true;

            error_log('Log constructor failed with the following message. Until the following issue has been resolved, all log entries will be written to the PHP system log only');
            error_log($e->getMessage());
        }

        return self::$instance;
    }



    /**
     * Returns if the system is running in debug mode or not
     *
     * @return bool
     */
    public static function debug(): bool
    {
        return self::$debug;
    }



}





    /*
     * The core::startup() method starts the correct call type handler
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @return void
     */
    public function startup()
    {
        global $_CONFIG, $core;

        try {
            if (isset($this->register['startup'])) {
                /*
                 * Core already started up
                 */
                log_file(tr('Core already started @ ":time", not starting again', array(':time' => $this->register['startup'])), 'core::startup', 'error');
                return false;
            }

            /*
             * Detect platform and execute specific platform startup sequence
             */
            switch (PLATFORM) {
                case 'http':
                    /*
                     * Determine what our target file is. With direct execution,
                     * $_SERVER[PHP_SELF] would contain this, with route
                     * execution, $_SERVER[PHP_SELF] would be route, so we
                     * cannot use that. Route will store the file being executed
                     * in $this->register['script_path'] instead
                     */
                    if (isset($this->register['script_path'])) {
                        $file = '/' . $this->register['script_path'];

                    } else {
                        $file = '/' . $_SERVER['PHP_SELF'];
                    }

                    /*
                     * Auto detect what http call type we're on from the script
                     * being executed
                     */
                    if (str_exists($file, '/admin/')) {
                        $this->callType = 'admin';

                    } elseif (str_exists($file, '/ajax/')) {
                        $this->callType = 'ajax';

                    } elseif (str_exists($file, '/api/')) {
                        $this->callType = 'api';

                    } elseif ((substr($_SERVER['SERVER_NAME'], 0, 3) === 'api') and preg_match('/^api(?:-[0-9]+)?\./', $_SERVER['SERVER_NAME'])) {
                        $this->callType = 'api';

                    } elseif ((substr($_SERVER['SERVER_NAME'], 0, 3) === 'cdn') and preg_match('/^cdn(?:-[0-9]+)?\./', $_SERVER['SERVER_NAME'])) {
                        $this->callType = 'api';

                    } elseif ($_CONFIG['amp']['enabled'] and !empty($_GET['amp'])) {
                        $this->callType = 'amp';

                    } elseif (is_numeric(substr($file, -3, 3))) {
                        $this->register['http_code'] = substr($file, -3, 3);
                        $this->callType = 'system';

                    } else {
                        $this->callType = 'http';
                    }

                    break;

                case 'cli':
                    $this->callType = 'cli';
                    break;
            }

            $this->register['startup'] = microtime(true);

            require('handlers/system-' . $this->callType . '.php');

            /*
             * Set timeout for this request
             */
            set_timeout();

            /*
             * Verify project data integrity
             */
            if (!defined('SEED') or !SEED or (PROJECTCODEVERSION == '0.0.0')) {
                if ($core->register['script'] !== 'setup') {
                    if (!FORCE) {
                        throw new OutOfBoundsException(tr('startup: Project data in "ROOT/config/project.php" has not been fully configured. Please ensure that PROJECT is not empty, SEED is not empty, and PROJECTCODEVERSION is valid and not "0.0.0"'), 'project-not-setup');
                    }
                }
            }

        } catch (Error $e) {
            throw new OutOfBoundsException(tr('core::startup(): Failed calltype ":calltype" with PHP error', array(':calltype' => $this->callType)), $e);

        } catch (Exception $e) {
            if (PLATFORM_HTTP and headers_sent($file, $line)) {
                if (preg_match('/debug-.+\.php$/', $file)) {
                    throw new OutOfBoundsException(tr('core::startup(): Failed because headers were already sent on ":location", so probably some added debug code caused this issue', array(':location' => $file . '@' . $line)), $e);
                }

                throw new OutOfBoundsException(tr('core::startup(): Failed because headers were already sent on ":location"', array(':location' => $file . '@' . $line)), $e);
            }

            throw new OutOfBoundsException(tr('core::startup(): Failed calltype ":calltype"', array(':calltype' => $this->callType)), $e);
        }
    }



    /**
     *
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @return void
     */
    public function executedQuery($query_data)
    {
        $this->register['debug_queries'][] = $query_data;
        return count($this->register['debug_queries']);
    }



    /**
     * The register allows to store global variables without using the $GLOBALS scope
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @param string $key The key for the value that needs to be stored
     * @param mixed $value The data that has to be stored. If no value is specified, the function will return the value for the specified key.
     * @return mixed If a value is specified, this function will return the specified value. If no value is specified, it will return the value for the specified key.
     */
    public function register($key, $value = null)
    {
        if ($value === null) {
            return isset_get($this->register[$key]);
        }

        if (is_array($value)) {
            /*
             * If value is an array, then build up a list
             */
            if (!isset($this->register[$key])) {
                $this->register[$key] = array();
            }

            $this->register[$key] = array_merge($this->register[$key], $value);
            return $this->register[$key];
        }

        return $this->register[$key] = $value;
    }


    /**
     * This method will return the calltype for this call, as is stored in the private variable core::callType or if $type is specified, will return true if $calltype is equal to core::callType, false if not.
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @param (optional) string $type The call type you wish to compare to, or nothing if you wish to receive the current core::callType
     * @return mixed If $type is specified, this function will return true if $type matches core::callType, or false if it does not. If $type is not specified, it will return core::callType
     */
    public function callType($type = null)
    {
        if ($type) {
            switch ($type) {
                case 'http':
                    // FALLTHROUGH
                case 'admin':
                    // FALLTHROUGH
                case 'cli':
                    // FALLTHROUGH
                case 'mobile':
                    // FALLTHROUGH
                case 'ajax':
                    // FALLTHROUGH
                case 'api':
                    // FALLTHROUGH
                case 'amp':
                    // FALLTHROUGH
                case 'system':
                    break;

                default:
                    throw new OutOfBoundsException(tr('core::callType(): Unknown call type ":type" specified', array(':type' => $type)), 'unknown');
            }

            return ($this->callType === $type);
        }

        return $this->callType;
    }




    /*
     * Set or get debug mode.
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @param boolean $enable If set to true, will enable debug mode. If set to false, will disable debug mode. If not set at all, will only return the current debug mode setting.
     * @return boolean the current debug mode setting
     */
    function debug($enabled = null)
    {
        global $_CONFIG, $core;

        try {
            if (!$core->register['ready']) {
                throw new OutOfBoundsException(tr('debug(): Startup has not yet finished and base is not ready to start working properly. debug() may not be called until configuration is fully loaded and available'), 'invalid');
            }

            if (!is_array($_CONFIG['debug'])) {
                throw new OutOfBoundsException(tr('debug(): Invalid configuration, $_CONFIG[debug] is boolean, and it should be an array. Please check your config/ directory for "$_CONFIG[\'debug\']"'), 'invalid');
            }

            if ($enabled !== null) {
                $_CONFIG['debug']['enabled'] = (boolean)$enabled;
            }

            return $_CONFIG['debug']['enabled'];

        } catch (Exception $e) {
            throw new OutOfBoundsException(tr('debug(): Failed'), $e);
        }
    }

    /*
     * Get a valid language from the specified language
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @version 2.0.7: Added function and documentation
     *
     * @param string $language a language code
     * @return null string a valid language that is supported by the systems configuration
     */
    function get_language($language)
    {
        global $_CONFIG;

        try {
            if (empty($_CONFIG['language']['supported'])) {
                return '';
            }

            /*
             * Multilingual site
             */
            if ($language === null) {
                $language = LANGUAGE;
            }

            if ($language) {
                /*
                 * This is a multilingual website. Ensure language is supported and
                 * add language selection to the URL.
                 */
                if (empty($_CONFIG['language']['supported'][$language])) {
                    $language = $_CONFIG['language']['default'];

                    notify(array('code' => 'unknown',
                        'groups' => 'developers',
                        'title' => tr('Unknown language specified'),
                        'message' => tr('get_language(): The specified language ":language" is not known', array(':language' => $language))));
                }
            }

            return $language;

        } catch (Exception $e) {
            throw new OutOfBoundsException('get_language(): Failed', $e);
        }
    }


    /*
     * Return the correct current domain
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @version 2.0.7: Added function and documentation
     *
     * @return void
     */
    function get_domain()
    {
        global $_CONFIG;

        try {
            if (PLATFORM_HTTP) {
                return $_SERVER['HTTP_HOST'];
            }

            return $_CONFIG['domain'];

        } catch (Exception $e) {
            throw new OutOfBoundsException('get_domain(): Failed', $e);
        }
    }


/*
 * Show the specified page
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 */
function page_show($pagename, $params = null, $get = null)
{
    global $_CONFIG, $core;

    try {
        array_ensure($params, 'message');

        if ($get) {
            if (!is_array($get)) {
                throw new OutOfBoundsException(tr('page_show(): Specified $get MUST be an array, but is an ":type"', array(':type' => gettype($get))), 'invalid');
            }

            $_GET = $get;
        }

        if (defined('LANGUAGE')) {
            $language = LANGUAGE;

        } else {
            $language = 'en';
        }

        $params['page'] = $pagename;

        if (is_numeric($pagename)) {
            /*
             * This is a system page, HTTP code. Use the page code as http code as well
             */
            $core->register['http_code'] = $pagename;
        }

        $core->register['real_script'] = $pagename;

        switch ($core->callType()) {
            case 'ajax':
                $include = ROOT . 'www/' . $language . '/ajax/' . $pagename . '.php';

                if (isset_get($params['exists'])) {
                    return file_exists($include);
                }

                /*
                 * Execute ajax page
                 */
                log_file(tr('Showing ":language" language ajax page ":page"', array(':page' => $pagename, ':language' => $language)), 'page-show', 'VERBOSE/cyan');
                return include($include);

            case 'api':
                $include = ROOT . 'www/api/' . (is_numeric($pagename) ? 'system/' : '') . $pagename . '.php';

                if (isset_get($params['exists'])) {
                    return file_exists($include);
                }

                /*
                 * Execute ajax page
                 */
                log_file(tr('Showing ":language" language api page ":page"', array(':page' => $pagename, ':language' => $language)), 'page-show', 'VERBOSE/cyan');
                return include($include);

            case 'admin':
                $admin = '/admin';
            // FALLTHROUGH

            default:
                if (is_numeric($pagename)) {
                    $include = ROOT . 'www/' . $language . isset_get($admin) . '/system/' . $pagename . '.php';

                    if (isset_get($params['exists'])) {
                        return file_exists($include);
                    }

                    log_file(tr('Showing ":language" language system page ":page"', array(':page' => $pagename, ':language' => $language)), 'page-show', 'warning');

                    /*
                     * Wait a small random time to avoid timing attacks on
                     * system pages
                     */
                    usleep(mt_rand(1, 250));

                } else {
                    $include = ROOT . 'www/' . $language . isset_get($admin) . '/' . $pagename . '.php';

                    if (isset_get($params['exists'])) {
                        return file_exists($include);
                    }

                    log_file(tr('Showing ":language" language http page ":page"', array(':page' => $pagename, ':language' => $language)), 'page-show', 'VERBOSE/cyan');
                }

                $result = include($include);

                if (isset_get($params['return'])) {
                    return $result;
                }
        }

        die();

    } catch (Exception $e) {
        if (isset($include) and !file_exists($include)) {
            throw new OutOfBoundsException(tr('page_show(): The requested page ":page" does not exist', array(':page' => $pagename)), 'not-exists');
        }

        throw new OutOfBoundsException(tr('page_show(): Failed to show page ":page"', array(':page' => $pagename)), $e);
    }




    /*
     * Execute the specified callback function with the specified $params only if the callback has been set with an executable function
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @version 2.0.6: Added documentation
     *
     * @param $callback
     * @param null params $params
      * @return string The results from the callback function, or null if no callback function was specified
     */
    function execute_callback($callback, $params = null)
    {
        try {
            if (is_callable($callback)) {
                return $callback($params);
            }

            return null;

        } catch (Exception $e) {
            throw new OutOfBoundsException(tr('execute_callback(): Failed'), $e);
        }
    }


    /*
     * Convert all PHP errors in exceptions. With this function the entirety of base works only with exceptions, and function output normally does not need to be checked for errors.
     *
     * NOTE: This function should never be called directly
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @param boolean $value Specify true if this exception should be a warning, false if not
     * @return object $this, so that you can string multiple calls together
     */
    function php_error_handler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        return include(__DIR__ . '/handlers/system-php-error-handler.php');
    }


    /*
     * This function is called automaticaly
     *
     * NOTE: This function should never be called directly
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @param boolean $value Specify true if this exception should be a warning, false if not
     * @return object $this, so that you can string multiple calls together
     */
    function uncaught_exception($e, $die = 1)
    {
        return include(__DIR__ . '/handlers/system-uncaught-exception.php');
    }



    /*
     * Set the timeout value for this script
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @see set_time_limit()
     * @version 2.7.5: Added function and documentation
     *
     * @param null natural $timeout The amount of seconds this script can run until it is aborted automatically
     * @return void
     */

    function set_timeout($timeout = null)
    {
        global $core, $_CONFIG;

        try {
            if ($timeout === null) {
                $timeout = getenv('TIMEOUT') ? getenv('TIMEOUT') : $_CONFIG['exec']['timeout'];
            }

            $core->register['timeout'] = $timeout;
            set_time_limit($timeout);

        } catch (Exception $e) {
            throw new OutOfBoundsException(tr('set_timeout(): Failed'), $e);
        }
    }


    /*
     *
     */
    function get_global_data_path($section = '', $writable = true)
    {
        return include(__DIR__ . '/handlers/system-get-global-data-path.php');
    }


    /*
     * THIS FUNCTION SHOULD NOT BE RUN BY ANYBODY! IT IS EXECUTED AUTOMATICALLY ON
     * SHUTDOWN
     *
     * This function facilitates execution of multiple registered shutdown functions
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @param boolean $value The true or false value to be asserted
     * @return void
     */
    function shutdown()
    {
        global $core, $_CONFIG;

        try {
            /*
             * Do we need to run other shutdown functions?
             */
            if (empty($core->register['script'])) {
                error_log(tr('Shutdown procedure started before $core->register[script] was ready, possibly on script ":script"', array(':script' => $_SERVER['PHP_SELF'])));
                return;
            }

            log_console(tr('Starting shutdown procedure for script ":script"', array(':script' => $core->register['script'])), 'VERYVERBOSE/cyan');

            foreach ($core->register as $key => $value) {
                try {
                    if (substr($key, 0, 9) !== 'shutdown_') {
                        continue;
                    }

                    $key = substr($key, 9);

                    /*
                     * Execute this shutdown function with the specified value
                     */
                    if (is_array($value)) {
                        /*
                         * Shutdown function value is an array. Execute it for each entry
                         */
                        foreach ($value as $entry) {
                            log_console(tr('shutdown(): Executing shutdown function ":function" with value ":value"', array(':function' => $key . '()', ':value' => $entry)), 'VERBOSE/cyan');
                            $key($entry);
                        }

                    } else {
                        log_console(tr('shutdown(): Executing shutdown function ":function" with value ":value"', array(':function' => $key . '()', ':value' => $value)), 'VERBOSE/cyan');
                        $key($value);
                    }

                } catch (Exception $e) {
                    notify($e);
                }
            }

            /*
             * Periodically execute the following functions
             */
            $level = mt_rand(0, 100);

            if (!empty($_CONFIG['shutdown'])) {
                if (!is_array($_CONFIG['shutdown'])) {
                    throw new OutOfBoundsException(tr('shutdown(): Invalid $_CONFIG[shutdown], it should be an array'), 'invalid');
                }

                foreach ($_CONFIG['shutdown'] as $name => $parameters) {
                    if ($parameters['interval'] and ($level < $parameters['interval'])) {
                        log_file(tr('Executing periodical shutdown function ":function()"', array(':function' => $name)), 'shutdown', 'cyan');
                        load_libs($parameters['library']);
                        $parameters['function']();
                    }
                }
            }

        } catch (Exception $e) {
            throw new OutOfBoundsException(tr('shutdown(): Failed'), $e);
        }
    }


    /*
     * Register the specified shutdown function
     *
     * This function will ensure that the specified function will be executed on shutdown with the specified value.
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @see shutdown()
     * @see unregister_shutdown()
     * @version 1.27.0: Added function and documentation
     * @version 2.8.18: $value is now optional, defaults to null
     *
     * @param string $name The function name to be executed
     * @param null mixed $value The value to be sent to the shutdown function. If $value is an array, and the function was already regsitered, the previous and current array will be mixed. See shutdown() for more on this subject
     * @return mixed The value as it is registered with the specified shutdown function. If the function name was already registered before, and the specified value was an array, then the return value will now contain the specified array merged with the already existing array
     */
    function register_shutdown($name, $value = null)
    {
        global $core;

        try {
            return $core->register('shutdown_' . $name, $value);

        } catch (Exception $e) {
            throw new OutOfBoundsException('register_shutdown(): Failed', $e);
        }
    }


    /*
     * Unregister the specified shutdown function
     *
     * This function will ensure that the specified function will not be executed on shutdown
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @see shutdown()
     * @see register_shutdown()
     * @version 1.27.0: Added function and documentation
     *
     * @param string $name The function name to be executed
     * @return mixed The value of the shutdown function in case it existed
     */
    function unregister_shutdown($name)
    {
        global $core;

        try {
            $value = $core->register('shutdown_' . $name);
            unset($core->register['shutdown_' . $name]);
            return $value;

        } catch (Exception $e) {
            throw new OutOfBoundsException(tr('unregister_shutdown(): Failed'), $e);
        }
    }


}


///*
// * Extend basic PHP exception to automatically add exception trace information inside the exception objects
// *
// * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
// * @copyright Copyright (c) 2018 Capmega
// * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
// * @category Function reference
// * @package system
// */
//class BException extends Exception{
//    private $messages = array();
//    private $data     = null;
//    public  $code     = null;
//
//    /*
//     *
//     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2018 Capmega
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @package system
//     *
//     * @param mixed $messages
//     * @param string $code
//     * @param mixed $data
//     */
//    function __construct($messages, $code, $data = null){
//        return include(__DIR__.'/handlers/system-bexception-construct.php');
//    }
//
//
//
//    /*
//     * Add specified $message to the exception messages list
//     *
//     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2018 Capmega
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @package system
//     *
//     * @param string $message The message you wish to add to the exceptions messages list
//     * @return object $this, so that you can string multiple calls together
//     */
//    public function addMessages($messages){
//        if(is_object($messages)){
//            if(!($messages instanceof BException)){
//                throw new OutOfBoundsException(tr('BException::addMessages(): Only supported object class to add to messages is BException'), 'invalid');
//            }
//
//            $messages = $messages->getMessages();
//        }
//
//        foreach(array_force($messages) as $message){
//            $this->messages[] = $message;
//        }
//
//        return $this;
//    }
//
//
//
//    /*
//     * Set the exception objects code to the specified $code
//     *
//     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2018 Capmega
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @package system
//     *
//     * @param string $code The new exception code you wish to set BException::code to
//     * @return object $this, so that you can string multiple calls together
//     */
//    public function setCode($code){
//        $this->code = $code;
//        return $this;
//    }
//
//
//
//    /*
//     * Returns the current exception code but without any warning prefix. If the exception code has a prefix, it will be separated from the actual code by a forward slash /. For example, "warning/invalid" would return "invalid"
//     *
//     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2018 Capmega
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @package system
//     *
//     * @return string The current BException::code value from the first /
//     */
//    public function getRealCode(){
//        return str_from($this->code, '/');
//    }
//
//
//
//    /*
//     * Returns all messages from this exception object
//     *
//     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2018 Capmega
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @package system
//     *
//     * @param string $separator If specified, all messages will be returned as a string, each message separated by the specified $separator. If not specified, the messages will be returned as an array
//     * @return mixed An array with the messages list for this exception. If $separator has been specified, this method will return all messages in one string, each message separated by $separator
//     */
//    public function getMessages($separator = null){
//        if($separator === null){
//            return $this->messages;
//        }
//
//        return implode($separator, $this->messages);
//    }
//
//
//
//    /*
//     * Returns the data associated with the exception
//     *
//     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2018 Capmega
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @package system
//     *
//     * @return mixed Returns the content for BException::data
//     */
//    public function getData(){
//        return $this->data;
//    }
//
//
//
//    /*
//     * Set the data associated with the exception. This content could be a data structure received by the function or method that caused the exception, which could help with handling the exception, logging information, or debugging the issue
//     *
//     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2018 Capmega
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @package system
//     *
//     * @param mixed $data The content for this exception
//     */
//    public function setData($data){
//        $this->data = array_force($data);
//    }
//
//
//
//    /*
//     * Make this exception a warning or not.
//     *
//     * Returns all messages from this exception object
//     *
//     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2018 Capmega
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @package system
//     *
//     * @param boolean $value Specify true if this exception should be a warning, false if not
//     * @return object $this, so that you can string multiple calls together
//     */
//    public function makeWarning($value){
//        if($value){
//            $this->code = str_starts($this->code, 'warning/');
//
//        }else{
//            $this->code = str_starts_not($this->code, 'warning/');
//        }
//
//        return $this;
//    }
//
//
//
//    /*
//     * Returns if this exception is a warning exception or not
//     *
//     * Returns all messages from this exception object
//     *
//     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2018 Capmega
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @package system
//     *
//     * @return boolean True if thie exception is a warning, false if it is a real exception
//     */
//    public function isWarning(){
//        return (substr($this->code, 0, 7) === 'warning');
//    }
//}




///*
// * Return display status for specified status
// */
//function status($status, $list = null)
//{
//    try {
//        if (is_array($list)) {
//            /*
//             * $list contains list of possible statusses
//             */
//            if (isset($list[$status])) {
//                return $list[$status];
//            }
//
//
//            return 'Unknown';
//        }
//
//        if ($status === null) {
//            if ($list) {
//                /*
//                 * Alternative name specified
//                 */
//                return $list;
//            }
//
//            return 'Ok';
//        }
//
//        return str_capitalize(str_replace('-', ' ', $status));
//
//    } catch (Exception $e) {
//        throw new OutOfBoundsException(tr('status(): Failed'), $e);
//    }
//}
//
//
///*
// * Update the session with values directly from $_REQUEST
// */
//function session_request_register($key, $valid = null)
//{
//    try {
//        $_SESSION[$key] = isset_get($_REQUEST[$key], isset_get($_SESSION[$key]));
//
//        if ($valid) {
//            /*
//             * Only accept values in this valid list (AND empty!)
//             * Invalid values will be set to null
//             */
//            if (!in_array($_SESSION[$key], array_force($valid))) {
//                $_SESSION[$key] = null;
//            }
//        }
//
//        if (empty($_SESSION[$key])) {
//            unset($_SESSION[$key]);
//            return null;
//        }
//
//        return $_SESSION[$key];
//
//    } catch (Exception $e) {
//        throw new OutOfBoundsException('session_request_register(): Failed', $e);
//    }
//}


















































/*
 * Switch to specified site type, and redirect back
 */
function switch_type($type, $redirect = '')
{
    return include(__DIR__ . '/handlers/system-switch-type.php');
}





/*
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param mixed $variable
 * @return
 */
function variable_zts_safe($variable, $level = 0)
{
    return include(__DIR__ . '/handlers/variable-zts-safe.php');
}


/*
 * Force the specified $source variable to be a clean string
 *
 * A clean string, in this case, means a string data type which contains no HTML code
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see str_clean()
 * @deprecated This function is now replaced by str_clean()
 *
 * @param mixed $source The variable that should be forced to be a string data type
 * @return float The specified $source variable being a string datatype
 */
function cfm($source, $utf8 = true)
{
    try {
        return str_clean($source, $utf8);

    } catch (Exception $e) {
        throw new OutOfBoundsException(tr('cfm(): Failed'), $e);
    }
}


/*
 * Force the specified $source variable to be an integer
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param mixed $source The variable that should be forced to be a integer data type
 * @return float The specified $source variable being a integer datatype
 */
function cfi($source, $allow_null = true)
{
    try {
        if (!$source and $allow_null) {
            return null;
        }

        return (integer)$source;

    } catch (Exception $e) {
        throw new OutOfBoundsException(tr('cfi(): Failed'), $e);
    }
}


/*
 * Force the specified $source variable to be a float
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param mixed $source The variable that should be forced to be a float data type
 * @return float The specified $source variable being a float datatype
 */
function cf($source, $allow_null = true)
{
    try {
        if (!$source and $allow_null) {
            return null;
        }

        return (float)$source;

    } catch (Exception $e) {
        throw new OutOfBoundsException(tr('cf(): Failed'), $e);
    }
}




