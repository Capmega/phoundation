<?php

namespace Phoundation\Core;

use Phoundation\Core\Exception\CoreException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Http\Http;
use Throwable;



/**
 * Class Core
 *
 * This is the core class for the entire system.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Core {
    /**
     * Framework version and minimum required PHP version
     */
    public const FRAMEWORKCODEVERSION = '4.0.0';
    public const PHP_MINIMUM_VERSION = '8.1.0';

    /**
     * Singleton variable
     *
     * @var Core|null $instance
     */
    protected static ?Core $instance = null;

    /**
     * The generic system register to store data
     *
     * @var bool $debug
     */
    protected static bool $debug = false;

    /**
     * The type of call for this process. One of http, admin, cli, mobile, ajax, api, amp (deprecated), system
     *
     * @var string|null
     */
    protected static ?string $call_type = null;

    /**
     *
     *
     * @var string|null $processType
     */
    protected static ?string $processType = null;

    /**
     * @var array $db
     *
     * All database connections for this process
     */
    protected static array $db = [];

    /**
     * @var array $register
     *
     * General purpose data register
     */
    protected static array $register = [
        'tabindex' => 0,
        'ready' => false,
        'js_header' => [],
        'js_footer' => [],
        'css' => [],
        'quiet' => true,
        'footer' => '',
        'debug_queries' => []
    ];



    /**
     * Initialize the class object through the constructor.
     *
     * Core constructor.
     */
    protected function __construct()
    {
        // Register the process start
        Stopwatch::start('process');

        /*
         * Define a unique process request ID
         * Define project paths.
         *
         * ROOT   is the root directory of this project and should be used as the root for all other paths
         * TMP    is a private temporary directory
         * PUBTMP is a public (accessible by web server) temporary directory
         */
        define('REQUEST', substr(uniqid(), 7));
        define('ROOT', realpath(__DIR__ . '/../../..') . '/');
        define('TMP', ROOT . 'data/tmp/');
        define('PUBTMP', ROOT . 'data/content/tmp/');
        define('CRLF', "\r\n");


        /*
         * Setup error handling, report ALL errors
         */
        error_reporting(E_ALL);
        set_error_handler(['Core', 'phpErrorHandler']);
        set_exception_handler(['Core', 'uncaughtException']);


        try {
            // Check what platform we're in
            switch (php_sapi_name()) {
                case 'cli':
                    define('PLATFORM'     , 'cli');
                    define('PLATFORM_HTTP', false);
                    define('PLATFORM_CLI' , true);
                    break;

                default:
                    define('PLATFORM', 'http');
                    define('PLATFORM_HTTP', true);
                    define('PLATFORM_CLI', false);
                    define('NOCOLOR', (getenv('NOCOLOR') ? 'NOCOLOR' : null));

                    // Register basic HTTP information
                    // TODO MOVE TO HTTP CLASS
                    self::$register['http']['code'] = 200;
                    self::$register['http']['accepts'] = Http::accepts();
                    self::$register['http']['accepts_languages'] = Http::acceptsLanguages();

                    // Check what environment we're in
                    $env = getenv(PROJECT . '_ENVIRONMENT');

                    if (empty($env)) {
                        // No environment set in ENV, maybe given by parameter?
                        die('startup: Required environment not specified for project "' . PROJECT . '"');
                    }

                    if (str_contains($env, '_')) {
                        die('startup: Specified environment "' . $env . '" is invalid, environment names cannot contain the underscore character');
                    }

                    define('ENVIRONMENT', $env);

                    /*
                     * Load basic configuration for the current environment
                     * Load cache libraries (done until here since these need configuration @ load time)
                     */
                    self::$register['ready'] = true;

                    // Set protocol
                    define('PROTOCOL', Config::get('web.protocol', 'http'));
                    break;
            }

        } catch (Throwable $e) {
            // Startup failed miserably. Don't use anything fancy here, we're dying!
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
     * @return Core
     */
    public static function getInstance(): Core
    {
        if (!isset(self::$instance)) {
            self::$instance = new Core();
        }

        return self::$instance;
    }



    /**
     * The core::startup() method starts the correct call type handler
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @return void
     */
    public function startup(): void
    {
        global $_CONFIG, $core;

        try {
            // Detect platform and execute specific platform startup sequence
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
                    if (str_contains($file, '/admin/')) {
                        $this->callType = 'admin';

                    } elseif (str_contains($file, '/ajax/')) {
                        $this->callType = 'ajax';

                    } elseif (str_contains($file, '/api/')) {
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
                if (self::$register['script'] !== 'setup') {
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
     * Read and return the specified key / sub key from the core register.
     *
     * @note Will return NULL if the specified key does not exist
     * @param string $key
     * @param string $subkey
     * @return mixed
     */
    public static function readRegister(string$key, string $subkey): mixed
    {
        return isset_get(self::$register[$key][$subkey]);
    }


    /**
     * write the specified variable to the specified key / sub key in the core register
     *
     * @param mixed $value
     * @param string $key
     * @param string|null $subkey
     * @return void
     */
    public static function writeRegister(mixed $value, string $key, ?string $subkey = null): void
    {
        if ($subkey) {
            // We want to write to a sub key. Ensure that the key exists and is an array
            if (array_key_exists($key, self::$register)) {
                if (!is_array(self::$register[$key])) {
                    // Key exists but is not an array so cannot handle sub keys
                    throw new CoreException('Cannot write to register key "" subkey "" as register key "" already exist as a value instead of an array', [':key' => $key, 'subkey' => $subkey]);
                }
            } else {
                // Initialize the register sub array
                self::$register[$key] = [];
            }

            // Write the key / subkey
            self::$register[$key][$subkey] = $value;
        } else {
            // Write the key
            self::$register[$key] = $value;
        }
    }



    /**
     *
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
     * This method will return the calltype for this call, as is stored in the private variable core::callType
     *
     * @return string Returns core::callType
     */
    public function getCallType(): string
    {
        return self::$call_type;
    }



    /**
     * Will return true if $call_type is equal to core::callType, false if not.
     *
     * @param string $type The call type you wish to compare to
     * @return bool This function will return true if $type matches core::callType, or false if it does not.
     */
    public function isCallType(string $type): bool
    {
        return (self::$call_type === $type);
    }



    /**
     * Get a valid language from the specified language
     *
     * @version 2.0.7: Added function and documentation
     * @param string $language a language code
     * @return null string a valid language that is supported by the systems configuration
     */
    function getLanguage($language)
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
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 */
function page_show($pagename, $params = null, $get = null)
{
    global $_CONFIG, $core;

    try {
        Arrays::ensure($params, 'message');

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
            self::$register['http_code'] = $pagename;
        }

        self::$register['real_script'] = $pagename;

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
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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

            self::$register['timeout'] = $timeout;
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



    /**
     * Reguster a shutdown function
     *
     * @param string $function_name
     * @return void
     */
    public static function registerShutdown(string $function_name): void
    {
        self::register['shutdown'][] = $function_name;
    }



    /**
     * THIS METHOD SHOULD NOT BE RUN BY ANYBODY! IT IS EXECUTED AUTOMATICALLY ON SHUTDOWN
     *
     * This function facilitates execution of multiple registered shutdown functions
     *
     * @return void
     */
    public static function shutdown(): void
    {
        /*
         * Do we need to run other shutdown functions?
         */
        if (empty(self::$register['script'])) {
            error_log(tr('Shutdown procedure started before self::$register[script] was ready, possibly on script ":script"', array(':script' => $_SERVER['PHP_SELF'])));
            return;
        }

        Log::notice(tr('Starting shutdown procedure for script ":script"', [':script' => self::$register['script']]));

        foreach (self::$register as $key => $value) {
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
    }


    /*
     * Register the specified shutdown function
     *
     * This function will ensure that the specified function will be executed on shutdown with the specified value.
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
            return self::$register('shutdown_' . $name, $value);

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
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
            $value = self::$register('shutdown_' . $name);
            unset(self::$register['shutdown_' . $name]);
            return $value;

        } catch (Exception $e) {
            throw new OutOfBoundsException(tr('unregister_shutdown(): Failed'), $e);
        }
    }


}



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
//            if (!in_array($_SESSION[$key], Arrays::force($valid))) {
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
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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






