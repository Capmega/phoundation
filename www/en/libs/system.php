<?php
/*
 * System library
 *
 * This library contains all system functions for Phoundation
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 */



/*
 * Framework version
 */
define('FRAMEWORKCODEVERSION', '2.8.22');
define('PHP_MINIMUM_VERSION' , '7.2.19');



/*
 * This constant can be used to measure time used to render page or process
 * script
 */
define('STARTTIME', microtime(true));
define('REQUEST'  , substr(uniqid(), 7));



/*
 * Define project paths.
 *
 * ROOT   is the root directory of this project and should be used as the root for all other paths
 * TMP    is a private temporary directory
 * PUBTMP is a public (accessible by web server) temporary directory
 */
define('ROOT'  , realpath(__DIR__.'/../../..').'/');
define('TMP'   , ROOT.'data/tmp/');
define('PUBTMP', ROOT.'data/content/tmp/');
define('CRLF'  , "\r\n");



/*
 * Include project setup file. This file contains the very bare bones basic
 * information about this project
 *
 * Load system library and initialize core
 */
include_once(ROOT.'config/project.php');



/*
 * Setup error handling, report ALL errors
 */
error_reporting(E_ALL);
set_error_handler('php_error_handler');
set_exception_handler('uncaught_exception');



try{
    /*
     * Create the core object and load the basic libraries
     */
    $core = new Core();



    /*
     * Check what platform we're in
     */
    switch(php_sapi_name()){
        case 'cli':
            define('PLATFORM'     , 'cli');
            define('PLATFORM_HTTP', false);
            define('PLATFORM_CLI' , true);

            $file = realpath(ROOT.'scripts/'.str_from($argv[0], 'scripts/'));
            $file = str_from($file, ROOT.'scripts/');

            $core->register['real_script'] = $file;
            $core->register['script']      = str_rfrom($file, '/');

            unset($file);

            /*
             * Load basic libraries for command line interface
             * All scripts will execute cli_done() automatically once done
             */
            load_libs('cli,http,strings,array,sql,mb,meta,file,json');
            register_shutdown_function('cli_done');
            break;

        default:
            define('PLATFORM'     , 'http');
            define('PLATFORM_HTTP', true);
            define('PLATFORM_CLI' , false);
            define('NOCOLOR'      ,  (getenv('NOCOLOR') ? 'NOCOLOR' : null));

            /*
             * Define what the current script
             * Detect requested language
             */
            $core->register['http_code']         = 200;
            $core->register['script']            = str_runtil(str_rfrom($_SERVER['PHP_SELF'], '/'), '.php');
            $core->register['real_script']       = $core->register['script'];
            $core->register['accepts']           = accepts();
            $core->register['accepts_languages'] = accepts_languages();

            /*
             * Load basic libraries
             * All scripts will execute http_done() automatically once done
             */
            load_libs('http,strings,array,sql,mb,meta,file,json');
            register_shutdown_function('http_done');

            /*
             * Check what environment we're in
             */
            $env = getenv(PROJECT.'_ENVIRONMENT');

            if(empty($env)){
                /*
                 * No environment set in ENV, maybe given by parameter?
                 */
                die('startup: Required environment not specified for project "'.PROJECT.'"');
            }

            if(strstr($env, '_')){
                die('startup: Specified environment "'.$env.'" is invalid, environment names cannot contain the underscore character');
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
            define('VERYVERBOSE', (debug() and ((getenv('VERYVERBOSE') or !empty($GLOBALS['veryverbose'])))      ? 'VERYVERBOSE' : null));
            define('VERBOSE'    , (debug() and (VERYVERBOSE or getenv('VERBOSE') or !empty($GLOBALS['verbose'])) ? 'VERBOSE'     : null));
            break;
    }

}catch(Exception $e){
    /*
     * Startup failed miserably, we will NOT have log_file() or exception
     * handler available!
     *
     * Unregister shutdown handler by kicking the entire array to avoid issues
     * with those shutdown handlers!
     */
    if(isset($core)){
        $core->register = array();
    }

    if(defined('PLATFORM_HTTP')){
        if(PLATFORM_HTTP){
            /*
             * Died in browser
             */
            error_log('startup: Failed with "'.$e->getMessage().'"');
            die('startup: Failed, see web server error log');
        }

        /*
         * Died in CLI
         */
        die('startup: Failed with "'.$e->getMessage().'"');
    }

    /*
     * We died even before PLATFORM_HTTP was defined? How?
     */
    error_log('startup: Failed with "'.$e->getMessage().'"');
    die('startup: Failed, see error log');
}



/*
 * Set protocol
 */
define('PROTOCOL', 'http'.($_CONFIG['sessions']['secure'] ? 's' : '').'://');

if($_CONFIG['security']['url_cloaking']['enabled']){
    /*
     * URL cloaking enabled. Load the URL library so that the URL cloaking
     * functions are available
     */
    load_libs('url');
}



/*
 * BELOW FOLLOW TWO CLASSES AND AFTER THAT ONLY SYSTEM FUNCTIONS
 */



/*
 * $core is the main object for BASE. It starts automatically once the startup library is loaded, determines the platform (cli or http), and in case of http, what the call type is. The call type differentiates between http web pages, admin web pages (pages with /admin, showing the admin section), ajax requests (URL's starting with /ajax), api requests (URL's starting with /api), system pages (any 404, 403, 500, 503, etc. page), Google AMP pages (any URL starting with /amp), and explicit mobile pages (any URL starting with /mobile). $core will automatically run the correct handler for the specified request, which will automatically load the required libraries, setup timezones, configure language and locale, and load the custom library. After that, control is returned to the webpage that called the startup library
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 */
class Core{
    private $callType = null;

    public $sql       = array();
    public $mc        = array();
    public $register  = array('tabindex'      => 0,
                              'ready'         => false,
                              'js_header'     => array(),
                              'js_footer'     => array(),
                              'css'           => array(),
                              'quiet'         => true,
                              'footer'        => '',
                              'debug_queries' => array());



    /*
     * The core::startup() method starts the correct call type handler
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @return void
     */
    public function startup(){
        global $_CONFIG, $core;

        try{
            if(isset($this->register['startup'])){
                /*
                 * Core already started up
                 */
                log_file(tr('Core already started @ ":time", not starting again', array(':time' => $this->register['startup'])), 'core::startup', 'error');
                return false;
            }

            /*
             * Detect platform and execute specific platform startup sequence
             */
            switch(PLATFORM){
                case 'http':
                    /*
                     * Determine what our target file is. With direct execution,
                     * $_SERVER[PHP_SELF] would contain this, with route
                     * execution, $_SERVER[PHP_SELF] would be route, so we
                     * cannot use that. Route will store the file being executed
                     * in $this->register['script_path'] instead
                     */
                    if(isset($this->register['script_path'])){
                        $file = '/'.$this->register['script_path'];

                    }else{
                        $file = '/'.$_SERVER['PHP_SELF'];
                    }

                    /*
                     * Auto detect what http call type we're on from the script
                     * being executed
                     */
                    if(str_exists($file, '/admin/')){
                        $this->callType = 'admin';

                    }elseif(str_exists($file, '/ajax/')){
                        $this->callType = 'ajax';

                    }elseif(str_exists($file, '/api/')){
                        $this->callType = 'api';

                    }elseif((substr($_SERVER['SERVER_NAME'], 0, 3) === 'api') and preg_match('/^api(?:-[0-9]+)?\./', $_SERVER['SERVER_NAME'])){
                        $this->callType = 'api';

                    }elseif((substr($_SERVER['SERVER_NAME'], 0, 3) === 'cdn') and preg_match('/^cdn(?:-[0-9]+)?\./', $_SERVER['SERVER_NAME'])){
                        $this->callType = 'api';

                    }elseif($_CONFIG['amp']['enabled'] and !empty($_GET['amp'])){
                        $this->callType = 'amp';

                    }elseif(is_numeric(substr($file, -3, 3))){
                        $this->register['http_code'] = substr($file, -3, 3);
                        $this->callType = 'system';

                    }else{
                        $this->callType = 'http';
                    }

                    break;

                case 'cli':
                    $this->callType = 'cli';
                    break;
            }

            $this->register['startup'] = microtime(true);

            require('handlers/system-'.$this->callType.'.php');

            /*
             * Set timeout for this request
             */
            set_timeout();

            /*
             * Verify project data integrity
             */
            if(!defined('SEED') or !SEED or (PROJECTCODEVERSION == '0.0.0')){
                if($core->register['script'] !== 'setup'){
                    if(!FORCE){
                        throw new BException(tr('startup: Project data in "ROOT/config/project.php" has not been fully configured. Please ensure that PROJECT is not empty, SEED is not empty, and PROJECTCODEVERSION is valid and not "0.0.0"'), 'project-not-setup');
                    }
                }
            }

        }catch(Error $e){
            throw new BException(tr('core::startup(): Failed calltype ":calltype" with PHP error', array(':calltype' => $this->callType)), $e);

        }catch(Exception $e){
            if(PLATFORM_HTTP and headers_sent($file, $line)){
                if(preg_match('/debug-.+\.php$/', $file)){
                    throw new BException(tr('core::startup(): Failed because headers were already sent on ":location", so probably some added debug code caused this issue', array(':location' => $file.'@'.$line)), $e);
                }

                throw new BException(tr('core::startup(): Failed because headers were already sent on ":location"', array(':location' => $file.'@'.$line)), $e);
            }

            throw new BException(tr('core::startup(): Failed calltype ":calltype"', array(':calltype' => $this->callType)), $e);
        }
    }



    /*
     *
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @return void
     */
    public function executedQuery($query_data){
        $this->register['debug_queries'][] = $query_data;
        return count($this->register['debug_queries']);
    }



    /*
     * The register allows to store global variables without using the $GLOBALS scope
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @param string $key The key for the value that needs to be stored
     * @param mixed $value The data that has to be stored. If no value is specified, the function will return the value for the specified key.
     * @return mixed If a value is specified, this function will return the specified value. If no value is specified, it will return the value for the specified key.
     */
    public function register($key, $value = null){
        if($value === null){
            return isset_get($this->register[$key]);
        }

        if(is_array($value)){
            /*
             * If value is an array, then build up a list
             */
            if(!isset($this->register[$key])){
                $this->register[$key] = array();
            }

            $this->register[$key] = array_merge($this->register[$key], $value);
            return $this->register[$key];
        }

        return $this->register[$key] = $value;
    }



    /*
     * This method will return the calltype for this call, as is stored in the private variable core::callType or if $type is specified, will return true if $calltype is equal to core::callType, false if not.
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @param (optional)string $type The call type you wish to compare to, or nothing if you wish to receive the current core::callType
     * @return mixed If $type is specified, this function will return true if $type matches core::callType, or false if it does not. If $type is not specified, it will return core::callType
     */
    public function callType($type = null){
        if($type){
            switch($type){
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
                    throw new BException(tr('core::callType(): Unknown call type ":type" specified', array(':type' => $type)), 'unknown');
            }

            return ($this->callType === $type);
        }

        return $this->callType;
    }
}



/*
 * Extend basic PHP exception to automatically add exception trace information inside the exception objects
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 */
class BException extends Exception{
    private $messages = array();
    private $data     = null;
    public  $code     = null;

    /*
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @param mixed $messages
     * @param string $code
     * @param mixed $data
     */
    function __construct($messages, $code, $data = null){
        return include(__DIR__.'/handlers/system-bexception-construct.php');
    }



    /*
     * Add specified $message to the exception messages list
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @param string $message The message you wish to add to the exceptions messages list
     * @return object $this, so that you can string multiple calls together
     */
    public function addMessages($messages){
        if(is_object($messages)){
            if(!($messages instanceof BException)){
                throw new BException(tr('BException::addMessages(): Only supported object class to add to messages is BException'), 'invalid');
            }

            $messages = $messages->getMessages();
        }

        foreach(array_force($messages) as $message){
            $this->messages[] = $message;
        }

        return $this;
    }



    /*
     * Set the exception objects code to the specified $code
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @param string $code The new exception code you wish to set BException::code to
     * @return object $this, so that you can string multiple calls together
     */
    public function setCode($code){
        $this->code = $code;
        return $this;
    }



    /*
     * Returns the current exception code but without any warning prefix. If the exception code has a prefix, it will be separated from the actual code by a forward slash /. For example, "warning/invalid" would return "invalid"
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @return string The current BException::code value from the first /
     */
    public function getRealCode(){
        return str_from($this->code, '/');
    }



    /*
     * Returns all messages from this exception object
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @param string $separator If specified, all messages will be returned as a string, each message separated by the specified $separator. If not specified, the messages will be returned as an array
     * @return mixed An array with the messages list for this exception. If $separator has been specified, this method will return all messages in one string, each message separated by $separator
     */
    public function getMessages($separator = null){
        if($separator === null){
            return $this->messages;
        }

        return implode($separator, $this->messages);
    }



    /*
     * Returns the data associated with the exception
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @return mixed Returns the content for BException::data
     */
    public function getData(){
        return $this->data;
    }



    /*
     * Set the data associated with the exception. This content could be a data structure received by the function or method that caused the exception, which could help with handling the exception, logging information, or debugging the issue
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @param mixed $data The content for this exception
     */
    public function setData($data){
        $this->data = array_force($data);
    }



    /*
     * Make this exception a warning or not.
     *
     * Returns all messages from this exception object
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @param boolean $value Specify true if this exception should be a warning, false if not
     * @return object $this, so that you can string multiple calls together
     */
    public function makeWarning($value){
        if($value){
            $this->code = str_starts($this->code, 'warning/');

        }else{
            $this->code = str_starts_not($this->code, 'warning/');
        }

        return $this;
    }



    /*
     * Returns if this exception is a warning exception or not
     *
     * Returns all messages from this exception object
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @return boolean True if thie exception is a warning, false if it is a real exception
     */
    public function isWarning(){
        return (substr($this->code, 0, 7) === 'warning');
    }
}



/*
 * Convert all PHP errors in exceptions. With this function the entirety of base works only with exceptions, and function output normally does not need to be checked for errors.
 *
 * NOTE: This function should never be called directly
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param boolean $value Specify true if this exception should be a warning, false if not
 * @return object $this, so that you can string multiple calls together
 */
function php_error_handler($errno, $errstr, $errfile, $errline, $errcontext){
    return include(__DIR__.'/handlers/system-php-error-handler.php');
}



/*
 * This function is called automaticaly
 *
 * NOTE: This function should never be called directly
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param boolean $value Specify true if this exception should be a warning, false if not
 * @return object $this, so that you can string multiple calls together
 */
function uncaught_exception($e, $die = 1){
    return include(__DIR__.'/handlers/system-uncaught-exception.php');
}



/*
 * Display value if exists
 * IMPORTANT! After calling this function, $var will exist!
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param mixed $variable
 * @param mixed (optional) $return
 * @param mixed (optional) $alt_return
 * @return mixed
 */
function isset_get(&$variable, $return = null, $alt_return = null){
    if(isset($variable)){
        return $variable;
    }

    unset($variable);

    if($return === null){
        return $alt_return;
    }

    return $return;
}



/*
 * Ensures the specified variable exists. If the variable already exists with a non NULL value, it will not be touched. If the variable does not exist, or has a NULL value, it will be set to the $initialization variable
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param mixed $variable
 * @param mixed $initialize The value to initialize the variable with
 * @return mixed the value of the variable. Either the value of the existing variable, or the value of the $initialize variable, if the variable did not exist, or was NULL
 */
function ensure_variable(&$variable, $initialize){
    if(isset($variable)){
        $variable = $initialize;

    }elseif($variable === null){
        $variable = $initialize;
    }

    return $variable;
}



/*
 * tr() is a translator marker function. It basic function is to tell the
 * translation system that the text within should be translated.
 *
 * Since text may contain data from either variables or function output, and
 * translators should not be burdened with copying variables or function calls,
 * all variable data should be identified in the text by a :marker, and the
 * :marker should be a key (with its value) in the $replace array.
 *
 * $replace values are always processed first by str_log() to ensure they are
 * readable texts, so the texts sent to tr() do NOT require str_log().
 *
 * On non production systems, tr() will perform a check on both the $text and
 * $replace data to ensure that all markers have been replaced, and non were
 * forgotten. If results were found, an exception will be thrown. This
 * behaviour does NOT apply to production systems
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param string $text
 * @param array $replace
 * @param boolean $verify
 * @return string
 */
function tr($text, $replace = null, $verify = true){
    global $_CONFIG;

    try{
        if($replace){
            foreach($replace as &$value){
                $value = str_log($value);
            }

            unset($value);

            $text = str_replace(array_keys($replace), array_values($replace), $text, $count);

            /*
             * Only on non production machines, crash when not all entries were replaced as an extra check.
             */
            if(empty($_CONFIG['production']) and $verify){
                if($count != count($replace)){
                    foreach($replace as $value){
                        if(strstr($value, ':')){
                            /*
                             * The one of the $replace values contains :blah
                             * This will cause the detector to fire off
                             * incorrectly. Ignore this.
                             */
                            return $text;
                        }
                    }

                    throw new BException('tr(): Not all specified keywords were found in text', 'not-exists');
                }

                /*
                 * Do NOT check for :value here since the given text itself may contain :value (ie, in prepared statements!)
                 */
            }

            return $text;
        }

        return $text;

    }catch(Exception $e){
        /*
         * Do NOT use tr() here for obvious endless loop reasons!
         */
        throw new BException('tr(): Failed with text "'.str_log($text).'". Very likely issue with $replace not containing all keywords, or one of the $replace values is non-scalar', $e);
    }
}



/*
 * Set or get debug mode.
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param boolean $enable If set to true, will enable debug mode. If set to false, will disable debug mode. If not set at all, will only return the current debug mode setting.
 * @return boolean the current debug mode setting
 */
function debug($enabled = null){
    global $_CONFIG, $core;

    try{
        if(!$core->register['ready']){
            throw new BException(tr('debug(): Startup has not yet finished and base is not ready to start working properly. debug() may not be called until configuration is fully loaded and available'), 'invalid');
        }

        if(!is_array($_CONFIG['debug'])){
            throw new BException(tr('debug(): Invalid configuration, $_CONFIG[debug] is boolean, and it should be an array. Please check your config/ directory for "$_CONFIG[\'debug\']"'), 'invalid');
        }

        if($enabled !== null){
            $_CONFIG['debug']['enabled'] = (boolean) $enabled;
        }

        return $_CONFIG['debug']['enabled'];

    }catch(Exception $e){
        throw new BException(tr('debug(): Failed'), $e);
    }
}



/*
 * Send a notification to specified groups. This function can send one and the same message over multiple paths like email, sms, push notifications, log, etc.
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see notifications()
 * @version 2.5.0: Updated to now call notifications()
 * @version 2.5.14: Improved documentation
 * @note $notification[code] is obligatory, and either $notification[title] or $notification[message] must be set
 * @example
 * code
 * notify(array('code'    => 'test',
 *              'title'   => 'This is a test!',
 *              'message' => 'This is just a message to test the notification system'));
 *
 * notify(array('code'    => 'foobar',
 *              'groups'  => 'developers,moderators',
 *              'title'   => 'This is a test!',
 *              'message' => 'This is just a message to test the notification system'));
 * /code
 *
 * @param params Error Exception BException $params The notification parameters, or an Exception / Error / BException object
 * @param string $notification[code]
 * @param null natural $notification[priority]
 * @param null string $notification[title]
 * @param null string $notification[message]
 * @param null mixed $notification[data]
 * @param null mixed $notification[groups]
 * @param boolean $log If set to true, will log the notification
 * @param boolean $throw If set to true, if the notification is an exception and the system is non production, it will throw the exception instead of notifying
 * @return void
 */
function notify($notification, $log = true, $throw = true){
    try{
        load_libs('notifications');
        return notifications($notification, $log, $throw);

    }catch(Exception $e){
        if($e){
            /*
             * This is just the notification being thrown as an exception, keep
             * on throwing
             */
            throw $e;
        }

        /*
         * Notification failed!
         *
         * Do NOT cause exception, because it its not caught, it might cause another notification, that will fail, cause exception and an endless loop!
         */
        log_console(tr('Failed to notify event ":event"', array(':event' => $notification)), 'error');
        return false;
    }
}



/*
 * Load external library files. All external files must be loaded in ROOT/www/en/libs/external/ and must be specified without that path, with their extension
 *
 * Examples:
 * load_external('Twilio/autoload.php');
 * load_external(array('Twilio/autoload.php', 'Facebook/facebook.php'));
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see load_libs()
 * @see load_config()
 *
 * @param mixed $files Either array or CSV string with the files to be loaded
 * @return void
 */
function load_external($files){
    try{
        foreach(array_force($files) as $file){
            include_once(ROOT.'www/en/libs/external/'.$files);
        }

    }catch(Exception $e){
        throw new BException(tr('load_external(): Failed'), $e);
    }
}



/*
 * Load specified libraries. All libraries to be loaded must be specified by only their name, not extension
 *
 * Examples:
 * load_libs('atlant');
 * load_libs('buks,backup,ssl');
 * load_libs(array('buks', 'backup', 'ssl'));
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see load_config()
 * @see load_external()
 *
 * @param mixed $files Either array or CSV string with the libraries to be loaded
 * @return void
 */
function load_libs($libraries){
    global $_CONFIG, $core;
    static $loaded = array();

    try{
        if(defined('LIBS')){
            $libs = LIBS;

        }else{
            /*
             * LIBS is not defined yet. This may happen when load_libs() is
             * called during the startup sequence (for example, location
             * detection during startup sequence uses load_libs(). For now,
             * assume the same directory as this systems library file
             */
            $libs = slash(__DIR__);
        }

        foreach(array_force($libraries) as $library){
            if(!$library){
                notify(new BException('load_libs(): Empty library specified', 'warning/not-specified'));
                continue;
            }

            if(isset($loaded[$library])){
                /*
                 * This library has already been loaded, skip
                 */
                continue;
            }

            if($core->register['ready'] and str_exists('http,strings,array,sql,mb,meta,file,json', $library)){
                /*
                 * These are system libraries that are always loaded. Do not
                 * load them again
                 */
                notify(new BException(tr('load_libs(): Library ":library" was already loaded during system startup, not loading again', array(':library' => $library)), 'warning/already-done'));
                continue;
            }

            include_once($libs.$library.'.php');
            $function         = str_replace('-', '_', $library).'_library_init';
            $loaded[$library] = true;

            if(is_callable($function)){
                /*
                 * Auto initialize the library
                 */
                $function();
            }
        }

    }catch(Error $e){
        if(isset($library)){
            $e = new BException(tr('load_libs(): Failed to load library ":library"', array(':library' => $library)), $e);

        }else{
            $e = new BException(tr('load_libs(): Failed to load libraries ":libraries"', array(':libraries' => $libraries)), $e);
        }

        throw $e->setCode('load-libs-fail');

    }catch(Exception $e){
        if(empty($library)){
            throw new BException(tr('load_libs(): Failed to load one or more of libraries ":libraries"', array(':libraries' => $libraries)), $e);
        }

        if(!file_exists($libs.$library.'.php')){
            throw new BException(tr('load_libs(): Failed to load library ":library", the library does not exist', array(':library' => $library)), $e);
        }

        throw new BException(tr('load_libs(): Failed to load library ":library" from the requested libraries list ":libraries"', array(':libraries' => $libraries, ':library' => $library)), $e);
    }
}



/*
 * Load specified configuration files. All files must be specified by their section name only, no extension nor environment.
 * The function will then load the files ROOT/config/base/NAME.php, ROOT/config/base/production_NAME.php, and on non "production" environments, ROOT/config/base/ENVIRONMENT_NAME.php
 * For example, if you want to load the "fprint" configuration, use load_config('fprint'); The function will load ROOT/config/base/fprint.php, ROOT/config/base/production_fprint.php, and on (for example) the "local" environment, ROOT/config/base/local_fprint.php
 *
 * Examples:
 * load_config('fprint');
 * load_config('fprint,buks');
 * load_libs(array('fprint', 'buks'));
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param mixed $files Either array or CSV string with the libraries to be loaded
 * @return void
 */
function load_config($files = ''){
    global $_CONFIG, $core;
    static $paths;

    try{
        if(!$paths){
            $paths = array(ROOT.'config/base/',
                           ROOT.'config/production',
                           ROOT.'config/'.ENVIRONMENT);
        }

        $files = array_force($files);

        foreach($files as $file){
            $loaded = false;
            $file   = trim($file);

            /*
             * Include first the default configuration file, if available, then
             * production configuration file, if available, and then, if
             * available, the environment file
             */
            foreach($paths as $id => $path){
                if(!$file){
                    /*
                     * Trying to load default configuration files again
                     */
                    if(!$id){
                        $path .= 'default.php';

                    }else{
                        $path .= '.php';
                    }

                }else{
                    if($id){
                        $path .= '_'.$file.'.php';

                    }else{
                        $path .= $file.'.php';
                    }
                }

                if(file_exists($path)){
                    include($path);
                    $loaded = true;
                }
            }

            if(!$loaded){
                throw new BException(tr('load_config(): No configuration file was found for requested configuration ":file"', array(':file' => $file)), 'not-exists');
            }
        }

        /*
         * Configuration has been loaded succesfully, from here all debug
         * functions will work correctly. This is
         */
        $core->register['ready'] = true;

    }catch(Exception $e){
        throw new BException(tr('load_config(): Failed to load some or all of config file(s) ":file"', array(':file' => $files)), $e);
    }
}



/*
 * Returns the configuration array from the specified file and specified environment
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @version 2.0.7: Fixed loading bugs, improved error handling
 * @version 2.4.62: Fixed bug with "deploy" config
 *
 * @param string $file
 * @param string $environment
 * @return array The requested configuration array
 */
function read_config($file = null, $environment = null){
    try{
        if(!$environment){
            $environment = ENVIRONMENT;
        }

        if($file === 'deploy'){
            include(ROOT.'config/deploy.php');
            return $_CONFIG;
        }

        if($file){
            if(file_exists(ROOT.'config/base/'.$file.'.php')){
                $loaded = true;
                include(ROOT.'config/base/'.$file.'.php');
            }

            $file = '_'.$file;

        }else{
            $loaded = true;
            include(ROOT.'config/base/default.php');
        }

        if(file_exists(ROOT.'config/production'.$file.'.php')){
            $loaded = true;
            include(ROOT.'config/production'.$file.'.php');
        }

        if(file_exists(ROOT.'config/'.$environment.$file.'.php')){
            $loaded = true;
            include(ROOT.'config/'.$environment.$file.'.php');
        }

        if(empty($loaded)){
            throw new BException(tr('The specified configuration ":config" does not exist', array(':config' => $file)), 'not-exists');
        }

        return $_CONFIG;

    }catch(Exception $e){
        throw new BException('read_config(): Failed', $e);
    }
}



/*
 * Safely executes shell commands.
 *
 * NOTE: This function as currently is, is actually NOT YET safe, its still a partial work in progress!
 *
 * Examples:
 * safe_exec('rm '.ROOT.'data/log/test', '1,2')
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see ssh_exec()
 * @see servers_exec()
 * @see notifications_send()
 *
 * @param string $commands The commands to be executed
 * @param (optional, default null) mixed $ok_exitcodes  If specified, will not cause exception for the specified command exit codes.
 * @param (optional, default true) boolean $route_errors If set to true, error output from the commands will be mixed in with their regular output
 * @param (optional, default 'exec') string $function One of 'exec', 'passthru', or 'system'. The function to be used internally by safe_exec(). Each function will have different execution and different output. 'passthru' will send all command output directly to the client (browser or command line), 'exec' will return the complete output of the command, but cannot be used for background commands as it will check the process exit code, 'system' can run background processes.
 * @return mixed The output from the command. The exact format of this output depends on the exact function used within safe exec, specified with $function (See description of that parameter)
 */
function safe_exec($params){
    return include(__DIR__.'/handlers/system-safe-exec.php');
}



/*
 * Executes the specified base script directly inside the current process
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see notifications_send()
 * @see safe_exec()
 * @example
 * script_exec(array('commands' => array('base/users', array('list', '-C', '-Q');
 * script_exec(array('commands' => array('test')));
 *
 * @param string $script The commands to be executed
 * @param null string $arguments
 * @param null mixed $ok_exitcodes If specified, will not cause exception for the specified command exit codes.
 * @param "passthru" mixed $function One of "passthru", "exec", "shell_exec" or "system"
 * @return mixed The output from the command. The exact format of this output depends on the exact function used within safe exec, specified with $function (See description of that parameter)
 */
function script_exec($params){
    return include(__DIR__.'/handlers/system-script-exec.php');
}



/*
 * Load and return HTML contents from files (SOON DATABASE!)
 *
 * Examples:
 * load_content('index/top')
 * load_content('index/top', array(':foo' => 'bar'))
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see notifications_send()
 *
 * @param string $file
 * @param (optional, default false) $replace
 * @param (optional, default false) $language
 * @param (optional, default false) $autocreate
 * @param (optional, default false) $validate
 * @return string The content of the specified content file
 */
function load_content($file, $replace = false, $language = null, $autocreate = null, $validate = true){
    global $_CONFIG, $core;

    try{
        /*
         * Set default values
         */
        if($language === null){
            if($core->callType('cli')){
                $language = '';

            }else{
                $language = LANGUAGE.'/';
            }

        }else{
            $language = slash($language);
        }

        if(!isset($replace['###SITENAME###'])){
            $replace['###SITENAME###'] = str_capitalize(isset_get($_SESSION['domain']));
        }

        if(!isset($replace['###DOMAIN###'])){
            $replace['###DOMAIN###'] = isset_get($_SESSION['domain']);
        }

        /*
         * Check if content file exists
         */
        $realfile = realpath(ROOT.'data/content/'.$language.cfm($file).'.html');

        if($realfile){
            /*
             * File exists, we're okay, get and return contents.
             */
            $retval = file_get_contents($realfile);
            $retval = str_replace(array_keys($replace), array_values($replace), $retval);

            /*
             * Make sure no replace markers are left
             */
            if($validate and preg_match('/###[a-z]+?###/i', $retval, $matches)){
                /*
                 * Oops, specified $from array does not contain all replace markers
                 */
                if(!$_CONFIG['production']){
                    throw new BException(tr('load_content(): Missing markers ":markers" for content file ":file"', array(':markers' => $matches, ':file' => $realfile)), 'missing-markers');
                }
            }

            return $retval;
        }

        $realfile = ROOT.'data/content/'.cfm($language).'/'.cfm($file).'.html';

        /*
         * From here, the file does not exist.
         */
        if(!$_CONFIG['production']){
            notify(array('code'    => 'not-exist',
                         'groups'  => 'developers',
                         'title'   => tr('Content file missing'),
                         'message' => tr('load_content(): Content file ":file" is missing', array(':file' => $realfile))));

            return '';
        }

        if($autocreate === null){
            $autocreate = $_CONFIG['content']['autocreate'];
        }

        if(!$autocreate){
            throw new BException(tr('load_content(): Specified file ":file" does not exist for language ":language"', array(':file' => $file, ':language' => $language)), 'not-exists');
        }

        /*
         * Make content directory exists
         */
        file_ensure_path(dirname($realfile));

        $default  = 'File created '.$file.' by '.realpath(PWD.$_SERVER['PHP_SELF'])."\n";
        $default .= print_r($replace, true);

        file_put_contents($realfile, $default);

        if($replace){
            return str_replace(array_keys($replace), array_values($replace), $default);
        }

        return $default;

    }catch(Exception $e){
        notify($e);

        switch($e->getCode()){
            case 'not-exist':
                log_file(tr('load_content(): File ":language/:file" does not exist', array(':language' => $language, ':file' => $file)), 'warning');
                break;

            case 'missing-markers':
                log_file(tr('load_content(): File ":language/:file" still contains markers after replace', array(':language' => $language, ':file' => $file)), 'warning');
                break;

            case 'search-replace-counts':
                log_file(tr('load_content(): Search count does not match replace count'), 'warning');
                break;
        }

        throw new BException(tr('load_content(): Failed for file ":file"', array(':file' => $file)), $e);
    }
}



/*
 * Returns requested main mimetype, or if requested mimetype is accepted or not
 *
 * If $mimetype is specified, the function will return true if the specified mimetype is supported, or false, if not
 *
 * If $mimetype is not specified, the function will return the first mimetype that was specified in the HTTP ACCEPT header
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see accepts_languages()
 * @version 2.4.11: Added function and documentation
 * @version 2.5.170: Added documentation, added support for $mimetype
 * @example
 * code
 * // This will return true
 * $result = accepts('image/webp');
 *
 * // This will return false
 * $result = accepts('image/foobar');
 *
 * // On a browser, this typically would return text/html
 * $result = accepts();
 * /code
 *
 * This would return
 * code
 * Foo...bar
 * /code
 *
 * @param null string $mimetype If specified, the mimetype that must be tested if accepted by the client
 * @return mixed If $mimetype was specified, true if the client accepts it, false if not. If $mimetype was not specified, a string will be returned containing the first requested mimetype
 */
function accepts($mimetype = null){
    static $headers = null;

    try{
        if(!$headers){
            /*
             * Cleanup the HTTP accept headers (opera aparently puts spaces in
             * there, wtf?), then convert them to an array where the accepted
             * headers are the keys so that they are faster to access
             */
            $headers = isset_get($_SERVER['HTTP_ACCEPT']);
            $headers = str_replace(', ', '', $headers);
            $headers = array_force($headers);
            $headers = array_flip($headers);
        }

        if($mimetype){
            /*
             * Return if the browser supports the specified mimetype
             */
            return isset($headers[$mimetype]);
        }

        reset($headers);
        return key($headers);

    }catch(Exception $e){
        throw new BException(tr('accepts(): Failed'), $e);
    }
}



/*
 * Parse the HTTP_ACCEPT_LANGUAGES header and return requested / available languages by priority and return a list of languages / locales accepted by the HTTP client
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see accepts()
 * @note: This function is called by the startup system and its output stored in $core->register['accept_language']. There is typically no need to execute this function on any other places
 * @version 1.27.0: Added function and documentation
 *
 * @return array The list of accepted languages and locales as specified by the HTTP client
 */
function accepts_languages(){
    global $_CONFIG;

    try{
        if(empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
            /*
             * No accept language headers were specified
             */
            $retval  = array('1.0' => array('language' => isset_get($_CONFIG['language']['default'], 'en'),
                                            'locale'   => str_cut(isset_get($_CONFIG['locale'][LC_ALL], 'US'), '_', '.')));

        }else{
            $headers = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            $headers = array_force($headers, ',');
            $default = array_shift($headers);
            $retval  = array('1.0' => array('language' => str_until($default, '-'),
                                            'locale'   => (str_exists($default, '-') ? str_from($default, '-') : null)));

            if(empty($retval['1.0']['language'])){
                /*
                 * Specified accept language headers contain no language
                 */
                $retval['1.0']['language'] = isset_get($_CONFIG['language']['default'], 'en');
            }

            if(empty($retval['1.0']['locale'])){
                /*
                 * Specified accept language headers contain no locale
                 */
                $retval['1.0']['locale'] = str_cut(isset_get($_CONFIG['locale'][LC_ALL], 'US'), '_', '.');
            }

            foreach($headers as $header){
                $requested =  str_until($header, ';');
                $requested =  array('language' => str_until($requested, '-'),
                                    'locale'   => (str_exists($requested, '-') ? str_from($requested, '-') : null));

                if(empty($_CONFIG['language']['supported'][$requested['language']])){
                    continue;
                }

                $retval[str_from(str_from($header, ';'), 'q=')] = $requested;
            }
        }

        krsort($retval);
        return $retval;

    }catch(Exception $e){
        throw new BException(tr('accepts_languages(): Failed'), $e);
    }
}



/*
 * Parse flags from the specified log text color
 */
function log_flags($color){
    try{
        switch(str_until($color, '/')){
            case 'VERBOSE':
                if(!VERBOSE){
                    /*
                     * Only log this if we're in verbose mode
                     */
                    return false;
                }

                /*
                 * Remove the VERBOSE
                 */
                $color = str_from(str_from($color, 'VERBOSE', 0, true), '/');
                break;

            case 'VERBOSEDOT':
                if(!VERBOSE){
                    /*
                     * Only log this if we're in verbose mode
                     */
                    $color = str_from(str_from($color, 'VERBOSEDOT', 0, true), '/');
                    cli_dot(10, $color);
                    return false;
                }

                /*
                 * Remove the VERBOSE
                 */
                $color = str_from(str_from($color, 'VERBOSEDOT', 0, true), '/');
                break;

            case 'VERYVERBOSE':
                if(!VERYVERBOSE){
                    /*
                     * Only log this if we're in verbose mode
                     */
                    return false;
                }

                /*
                 * Remove the VERYVERBOSE
                 */
                $color = str_from(str_from($color, 'VERYVERBOSE', 0, true), '/');
                break;

            case 'VERYVERBOSEDOT':
                if(!VERYVERBOSE){
                    /*
                     * Only log this if we're in verbose mode
                     */
                    $color = str_from(str_from($color, 'VERYVERBOSEDOT', 0, true), '/');
                    cli_dot(10, $color);
                    return false;
                }

                /*
                 * Remove the VERYVERBOSE
                 */
                $color = str_from(str_from($color, 'VERYVERBOSEDOT', 0, true), '/');
                break;

            case 'QUIET':
                if(QUIET){
                    /*
                     * Only log this if we're in verbose mode
                     */
                    return false;
                }

                /*
                 * Remove the QUIET
                 */
                $color = str_from(str_from($color, 'QUIET', 0, true), '/');
                break;

            case 'DEBUG':
                if(!debug()){
                    /*
                     * Only log this if we're in debug mode
                     */
                    return false;
                }

                /*
                 * Remove the QUIET
                 */
                $color = str_from(str_from($color, 'DEBUG', 0, true), '/');
        }

        return $color;

    }catch(Exception $e){
        throw new BException(tr('log_flags(): Failed'), $e);
    }
}



/*
 * Sanitize the specified log message
 *
 * Also, if required, sets the log message color, filters double messages and can set the log_file() $class
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @note: This function basically only needs to be executed by log_file() and log_console()
 * @version 2.5.22: Added function and documentation
 *
 * @param mixed $messages
 * @param string $color
 * @param boolean $filter_double
 * @return null string $class
 */
function log_sanitize($messages, $color, $filter_double = true, &$class = null){
    static $last;

    try{
        if($filter_double and ($messages == $last)){
            /*
            * We already displayed this message, skip!
            */
            return array();
        }

        if(is_scalar($messages)){
            $messages = array($messages);

        }elseif(is_array($messages)){
            /*
             * Do nothing, we're good
             */

        }elseif(is_object($messages)){
            if($messages instanceof BException){
                $data = $messages->getData();

                if($messages->isWarning()){
                    $messages = array($messages->getMessage());
                    $color    = 'warning';

                }else{
                    $messages = $messages->getMessages();
                    $color    = 'error';
                }

                if($data){
                    /*
                     * Add data to messages
                     */
                    $messages[] = cli_color('Exception data:', 'error', null, true);

                    foreach(array_force($data) as $line){
                        if($line){
                            if(is_scalar($line)){
                                $messages[] = cli_color($line, 'error', null, true);

                            }elseif(is_array($line)){
                                /*
                                 * This is a multi dimensional array or object,
                                 * we cannot cli_color() these, so just JSON it.
                                 */
                                $messages[] = cli_color(json_encode_custom($line), 'error', null, true);
                            }
                        }
                    }
                }

                if(!$class){
                    $class = 'exception';
                }

            }elseif($messages instanceof Exception){
                $messages = array($messages->getMessage());

            }elseif($messages instanceof Error){
                $messages = array($messages->getMessage());

            }else{
                $messages = $messages->__toString();
            }
        }

        $last = $messages;

        return $messages;

    }catch(Exception $e){
        throw new BException('log_sanitize(): Failed', $e);
    }
}



/*
 * Log specified message to console, but only if we are in console mode!
 *
 * Messages can be specified as a string, array, or Error, Exception or BException objects
 *
 * The function will sanitize the log message using log_sanitize() before displaying it on the console, and by default also log to the system logs using log_file()
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @see log_sanitize()
 * @see log_file()
 * @package system
 * @version 2.5.22: Added documentation, upgraded to use log_sanitize()
 *
 * @param mixed $messages
 * @param string $color
 * @param boolean $newline
 * @param boolean $filter_double
 * @param boolean $log_file
 * @return array the sanitized log messages in array format
 */
function log_console($messages = '', $color = null, $newline = true, $filter_double = false, $log_file = true){
    global $core;
    static $c;

    try{
        if($color and !is_scalar($color)){
            log_console(tr('[ WARNING ] log_console(): Invalid color ":color" specified for the following message, color has been stripped', array(':color' => $color)), 'warning');
            $color = null;
        }

        /*
         * Process logging flags embedded in the log text color
         */
        $color = log_flags($color);

        if($color === false){
            /*
             * log_flags() returned false, do not log anything at all
             */
            return false;
        }

        /*
         * Always log to file log as well
         */
        if($log_file){
            log_file($messages, $core->register['real_script'], $color);
        }

        if(!PLATFORM_CLI){
            /*
             * Only log to console on CLI platform
             */
            return false;
        }

        $messages = log_sanitize($messages, $color, $filter_double);

        if($color){
            if(defined('NOCOLOR') and !NOCOLOR){
                if(empty($c)){
                    if(!class_exists('Colors')){
                        /*
                         * This log_console() was called before the "cli" library
                         * was loaded. Show the line without color
                         */
                        $color = '';

                    }else{
                        $c = new Colors();
                    }
                }
            }

            switch($color){
                case 'yellow':
                    // FALLTHROUGH
                case 'warning':
                    // FALLTHROUGH
                case 'red':
                    // FALLTHROUGH
                case 'error':
                    $error = true;
            }
        }

        foreach($messages as $message){
            if($color and defined('NOCOLOR') and !NOCOLOR){
                $message = $c->getColoredString($message, $color);
            }

            if(QUIET){
                $message = trim($message);
            }

            $message = stripslashes(br2nl($message)).($newline ? "\n" : '');

            if(empty($error)){
                echo $message;

            }else{
                /*
                 * Log to STDERR instead of STDOUT
                 */
                fwrite(STDERR, $message);
            }
        }

        return $messages;

    }catch(Exception $e){
        throw new BException('log_console(): Failed', $e, array('message' => $messages));
    }
}



/*
 * Log specified message(s) to file.
 *
 * Messages can be specified as a string, array, or Error, Exception or BException objects
 *
 * The function will sanitize the log message using log_sanitize() before displaying it on the console, and by default also log to the system logs using log_file()

 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @see log_sanitize()
 * @see log_console()
 * @package system
 * @version 2.5.22: Added documentation, upgraded to use log_sanitize()
 *
 * @param mixed $messages
 * @param string $class
 * @param string $color
 * @param string $color
 * @return array the sanitized log messages in array format
 */
function log_file($messages, $class = 'syslog', $color = null, $filter_double = true){
    global $_CONFIG, $core;
    static $h   = array(),
           $log = true;

    try{
        if(!$log){
            /*
             * Do not log!
             */
            return false;
        }

        /*
         * Process logging flags embedded in the log text color
         */
        $color = log_flags($color);

        if($color === false){
            /*
             * log_flags() returned false, do not log anything at all
             */
            return false;
        }

        $messages = log_sanitize($messages, $color, $filter_double, $class);

        if(!is_scalar($class)){
            if($class){
                throw new BException(tr('log_file(): Specified class ":class" is not scalar', array(':class' => str_truncate(json_encode_custom($class), 20))), 'invalid');
            }

            $class = $core->register['script'];
        }

        /*
         * Add session data
         */
        if(PLATFORM_HTTP){
            $session = '('.substr(session_id(), -8, 8).' / '.REQUEST.') ';

        }else{
            $session = '(CLI-'.getmypid().' / '.REQUEST.') ';
        }

        /*
         * Single log or multi log?
         */
        if(!$core or !$core->register('ready')){
            $file  = 'syslog';
            $class = $session.cli_color('[ '.$class.' ] ', 'white', null, true);

        }elseif($_CONFIG['log']['single']){
            $file  = 'syslog';
            $class = $session.cli_color('[ '.$class.' ] ', 'white', null, true);

        }else{
            $file  = $class;
            $class = $session;
        }

        /*
         * Write log entries
         */
        if(empty($h[$file])){
            file_ensure_path(ROOT.'data/log');

            try{
                $h[$file] = @fopen(ROOT.'data/log/'.$file, 'a+');

            }catch(Exception $e){
                throw new BException(tr('log_file(): Failed to open logfile ":file" to store messages ":messages"', array(':file' => $file, ':messages' => $messages)), $e);
            }

            if(!$h[$file]){
                throw new BException(tr('log_file(): Failed to open logfile ":file" to store messages ":messages"', array(':file' => $file, ':messages' => $messages)), 'failed');
            }
        }

        $date = new DateTime();
        $date = $date->format('Y/m/d H:i:s');

        foreach($messages as $key => $message){
            if(!is_scalar($message)){
                if(is_array($message) or is_object($message)){
                    $message = json_encode_custom($message);

                }else{
                    $message = '* '.gettype($message).' *';
                }
            }

            if(count($messages) > 1){
                /*
                 * There are multiple messages in this log_file() call. Display
                 * them all using their keys
                 */
                if(!is_scalar($message)){
                    $message = str_log($message);
                }

                if(!empty($color)){
                    $message = cli_color($message, $color, null, true);
                }

                fwrite($h[$file], cli_color($date, 'cyan', null, true).' '.$core->callType().'/'.$core->register['real_script'].' '.$class.$key.' => '.$message."\n");

            }else{
                /*
                 * There is only one message in this log_file() call, even when
                 * the log_file() was called with an array, it only contained
                 * one entry
                 */
                if(!empty($color)){
                    $message = cli_color($message, $color, null, true);
                }

                fwrite($h[$file], cli_color($date, 'cyan', null, true).' '.$core->callType().'/'.$core->register['real_script'].' '.$class.$message."\n");
            }
        }

        return $messages;

    }catch(Exception $e){
        /*
         * We encountered an exception trying to log, don't log ever again
         */
        $log = false;

        if(empty($file)){
            throw new BException('log_file(): Failed before $file was determined', $e, array('message' => $messages));
        }

        if(!is_writable(slash(ROOT.'data/log').$file)){
            if(PLATFORM_HTTP){
                error_log(tr('log_file() failed because log file ":file" is not writable', array(':file' => $file)));
            }

            throw new BException(tr('log_file(): Failed because log file ":file" is not writable', array(':file' => $file)), $e);
        }

        /*
         * If log_file() fails, assume we cannot log to data/log/, log to PHP error instead
         */
        error_log(tr('log_file() failed to log the following exception:'));

        foreach($e->getMessages() as $message){
            error_log($message);
        }

        $message = $e->getMessage();

        if(strstr($message, 'data/log') and strstr($message, 'failed to open stream: Permission denied')){
            /*
             * We cannot write to the log file
             */
            throw new BException(tr('log_file(): Failed to write to log, permission denied to write to log file ":file". Please ensure the correct write permissions for this file and the ROOT/data/log directory in general', array(':file' => str_cut($message, 'fopen(', ')'))), 'warning');
        }

        throw new BException('log_file(): Failed', $e, array('message' => $messages));
    }
}



/*
 * Calculate the hash value for the given password with the (possibly) given
 * algorithm
 */
function password($source, $algorithm, $add_meta = true){
    return get_hash($source, $algorithm, $add_meta );
}

function get_hash($source, $algorithm, $add_meta = true){
    global $_CONFIG;

    try{
        try{
            $source = hash($algorithm, SEED.$source);

        }catch(Exception $e){
            if(strstr($e->getMessage(), 'Unknown hashing algorithm')){
                throw new BException(tr('get_hash(): Unknown hash algorithm ":algorithm" specified', array(':algorithm' => $algorithm)), 'unknown-algorithm');
            }

            throw $e;
        }

        if($add_meta){
            return '*'.$algorithm.'*'.$source;
        }

        return $source;

    }catch(Exception $e){
        throw new BException('get_hash(): Failed', $e);
    }
}



/*
 * Get a valid language from the specified language
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @version 2.0.7: Added function and documentation
 *
 * @param string $language a language code
 * @return null string a valid language that is supported by the systems configuration
 */
function get_language($language){
    global $_CONFIG;

    try{
        if(empty($_CONFIG['language']['supported'])){
            return '';
        }

        /*
         * Multilingual site
         */
        if($language === null){
            $language = LANGUAGE;
        }

        if($language){
            /*
             * This is a multilingual website. Ensure language is supported and
             * add language selection to the URL.
             */
            if(empty($_CONFIG['language']['supported'][$language])){
                $language = $_CONFIG['language']['default'];

                notify(array('code'    => 'unknown',
                             'groups'  => 'developers',
                             'title'   => tr('Unknown language specified'),
                             'message' => tr('get_language(): The specified language ":language" is not known', array(':language' => $language))));
            }
        }

        return $language;

    }catch(Exception $e){
        throw new BException('get_language(): Failed', $e);
    }
}



/*
 * Return the correct current domain
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @version 2.0.7: Added function and documentation
 *
 * @return void
 */
function get_domain(){
    global $_CONFIG;

    try{
        if(PLATFORM_HTTP){
            return $_SERVER['HTTP_HOST'];
        }

        return $_CONFIG['domain'];

    }catch(Exception $e){
        throw new BException('get_domain(): Failed', $e);
    }
}



/*
 * Return complete domain with HTTP and all
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @see cdn_domain()
 * @see get_domain()
 * @see mapped_domain()
 * @package system
 *
 * @param null string $url
 * @param null string $query
 * @param null string $prefix
 * @param null string $domain
 * @param null string $language
 * @param null boolean $allow_cloak
 * @return string the URL
 */
function domain($url_params = null, $query = null, $prefix = null, $domain = null, $language = null, $allow_cloak = true){
    global $_CONFIG, $core;

    try{
        if(!is_array($url_params)){
            if(!is_string($url_params) and !is_bool($url_params) and ($url_params !== null)){
                throw new BException(tr('domain(): Specified $url_params should be either null, a string, or a parameters array but is an ":type"', array(':type' => gettype($url_params))), 'invalid');
            }

            $url_params = array('url'           => $url_params,
                                'query'         => $query,
                                'prefix'        => $prefix,
                                'domain'        => $domain,
                                'language'      => $language,
                                'allow_cloak'   => $allow_cloak);
        }

        array_default($url_params, 'from_language', LANGUAGE);

        if(preg_match('/^(?:(?:https?)|(?:ftp):)?\/\//i', $url_params['url'])){
            /*
             * Absolute URL specified, don't modify
             */
            return $url_params['url'];
        }

        if(!$url_params['domain']){
            /*
             * Use current domain.
             * Current domain MAY not be the same as the configured domain, so
             * always use $_SESSION[domain] unless we're at the point where
             * sessions are not available (yet) or are not available (cli, for
             * example). In that case, fall back on the configured domain
             * $_CONFIG[domain]
             */
            $url_params['domain'] = get_domain();

        }elseif($url_params['domain'] === true){
            /*
             * Use current domain name
             */
            $url_params['domain'] = $_SERVER['HTTP_HOST'];
        }

        /*
         * Use url_prefix, for URL's like domain.com/en/admin/page.html, where
         * "/admin/" is the prefix
         */
        if($url_params['prefix'] === null){
            $url_params['prefix'] = $_CONFIG['url_prefix'];
        }

        $url_params['prefix']   = str_starts_not(str_ends($url_params['prefix'], '/'), '/');
        $url_params['domain']   = slash($url_params['domain']);
        $url_params['language'] = get_language($url_params['language']);

        /*
         * Build up the URL part
         */
        if(!$url_params['url']){
            $retval = PROTOCOL.$url_params['domain'].($url_params['language'] ? $url_params['language'].'/' : '').$url_params['prefix'];

        }elseif($url_params['url'] === true){
            $retval = PROTOCOL.$url_params['domain'].str_starts_not($_SERVER['REQUEST_URI'], '/');

        }else{
            $retval = PROTOCOL.$url_params['domain'].($url_params['language'] ? $url_params['language'].'/' : '').$url_params['prefix'].str_starts_not($url_params['url'], '/');
        }

        /*
         * Do language mapping, but only if routemap has been set
         */
// :TODO: This will fail when using multiple CDN servers (WHY?)
        if(!empty($_CONFIG['language']['supported']) and ($url_params['domain'] !== $_CONFIG['cdn']['domain'].'/')){
            if($url_params['from_language'] !== 'en'){
                /*
                 * Translate the current non-English URL to English first
                 * because the specified could be in dutch whilst we want to end
                 * up with Spanish. So translate always
                 * FOREIGN1 > English > Foreign2.
                 *
                 * Also add a / in front of $retval before replacing to ensure
                 * we don't accidentally replace sections like "services/" with
                 * "servicen/" with Spanish URL's
                 */
                $retval = str_replace('/'.$url_params['from_language'].'/', '/en/', '/'.$retval);
                $retval = substr($retval, 1);

                if(!empty($core->register['route_map'])){
                    foreach($core->register['route_map'][$url_params['from_language']] as $foreign => $english){
                        $retval = str_replace($foreign, $english, $retval);
                    }
                }
            }

            /*
             * From here the URL *SHOULD* be in English. If the URL is not
             * English here, then conversion from local language to English
             * right above failed
             */
            if($url_params['language'] !== 'en'){
                /*
                 * Map the english URL to the requested non-english URL
                 * Only map if routemap has been set for the requested language
                 */
                if(empty($core->register['route_map'])){
                    /*
                     * No route_map was set, only translate language selector
                     */
                    $retval = str_replace('en/', $url_params['language'].'/', $retval);

                }else{
                    if(empty($core->register['route_map'][$url_params['language']])){
                        notify(new BException(tr('domain(): Failed to update language sections for url ":url", no language routemap specified for requested language ":language"', array(':url' => $retval, ':language' => $url_params['language'])), 'not-specified'));

                    }else{
                        $retval = str_replace('en/', $url_params['language'].'/', $retval);

                        foreach($core->register['route_map'][$url_params['language']] as $foreign => $english){
                            $retval = str_replace($english, $foreign, $retval);
                        }
                    }
                }
            }
        }

        if($url_params['query']){
            load_libs('inet');
            $retval = url_add_query($retval, $url_params['query']);

        }elseif($url_params['query'] === false){
            $retval = str_until($retval, '?');
        }

        if($url_params['allow_cloak'] and $_CONFIG['security']['url_cloaking']['enabled']){
            /*
             * Cloak the URL before returning it
             */
            $retval = url_cloak($retval);
        }

        return $retval;

    }catch(Exception $e){
        throw new BException('domain(): Failed', $e);
    }
}



/*
 * Return complete URL for the specified API URL section with HTTP and all
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see domain()
 * @see cdn_domain()
 * @version 2.7.102: Added function and documentation
 *
 * @param null string $url
 * @param null string $query
 * @param null string $prefix
 * @param null string $domain
 * @param null string $language
 * @param null boolean $allow_url_cloak
 * @return string the URL
 */
function api_domain($url = null, $query = null, $prefix = null, $domain = null, $language = null, $allow_url_cloak = true){
    try{
        load_config('api');
        return domain($url, $query, $prefix, $_CONFIG['api']['domain'], $language, $allow_url_cloak);

    }catch(Exception $e){
        throw new BException('api_domain(): Failed', $e);
    }
}



/*
 * Return complete URL for the specified AJAX URL section with HTTP and all
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see domain()
 * @see cdn_domain()
 * @version 2.7.102: Added function and documentation
 *
 * @param null string $url
 * @param null string $query
 * @param null string $prefix
 * @param null string $domain
 * @param null string $language
 * @param null boolean $allow_url_cloak
 * @return string the URL
 */
function ajax_domain($url = null, $query = null, $language = null, $allow_url_cloak = true){
    global $_CONFIG;

    try{
        if($_CONFIG['ajax']['prefix']){
            $prefix = $_CONFIG['ajax']['prefix'];

        }else{
            $prefix = null;
        }

        if($_CONFIG['ajax']['domain']){
            return domain($url, $query, $prefix, $_CONFIG['ajax']['domain'], $language, $allow_url_cloak);
        }

        return domain($url, $query, $prefix, null, $language, $allow_url_cloak);

    }catch(Exception $e){
        throw new BException('ajax_domain(): Failed', $e);
    }
}



/*
 * Download the specified single file to the specified path
 *
 * If the path is not specified then by default the function will download to the TMP directory; ROOT/data/tmp
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see file_get_local()
 * @version 2.0.3: Added function and documentation
 * @example This shows how to download a single file
 * code
 * $result = download('https://capmega.com', TMP);
 * showdie($result);
 * /code
 *
 * This would display
 * code
 * ROOT/data/tmp/capmega.com
 * /code
 *
 * @param string $url The URL of the file to be downloaded
 * @param mixed $section If set to false, will return the contents of the downloaded file instead of the target filename. As the caller function will not know the exact filename used, the target file will be deleted automatically! If set to a string
 * @param null function $callback If specified, download will execute this callback with either the filename or file contents (depending on $section)
 * @return string The downloaded file
 */
function download($url, $contents = false, $callback = null){
    try{
        load_libs('wget');
        $file = wget($url);

        if($contents){
            /*
             * Do not return the filename but the file contents instead
             * When doing this, automatically delete the temporary file in
             * question, since the caller will not know the exact file name used
             */
            $retval = file_get_contents($file);
            file_delete($file);

            if($callback){
                $callback($retval);
            }

            return $retval;
        }

        /*
         * No section was specified, return contents of file instead.
         */
        if($callback){
            /*
             * Execute the callbacks before returning the data, delete the
             * temporary file after
             */
            $callback($file);
            file_delete($file);
        }

        return $file;

    }catch(Exception $e){
        throw new BException('download(): Failed', $e);
    }
}



/*
 * Returns true if the current session user has the specified right
 * This function will automatically load the rights for this user if
 * they are not yet in the session variable
 */
function has_rights($rights, &$user = null){
    global $_CONFIG;

    try{
        if($user === null){
            if(empty($_SESSION['user'])){
                /*
                 * No user specified and there is no session user either,
                 * so there are absolutely no rights at all
                 */
                return false;
            }

            $user = &$_SESSION['user'];

        }elseif(!is_array($user)){
            throw new BException(tr('has_rights(): Specified user is not an array'), 'invalid');
        }

        /*
         * Dynamically load the user rights
         */
        if(empty($user['rights'])){
            if(empty($user)){
                /*
                 * There is no user, so there are no rights at all
                 */
                return false;
            }

            load_libs('user');
            $user['rights'] = user_load_rights($user);
        }

        if(empty($rights)){
            throw new BException('has_rights(): No rights specified');
        }

        if(!empty($user['rights']['god'])){
            return true;
        }

        foreach(array_force($rights) as $right){
            if(empty($user['rights'][$right]) or !empty($user['rights']['devil']) or !empty($fail)){
                if((PLATFORM_CLI) and VERBOSE){
                    load_libs('user');
                    log_console(tr('has_rights(): Access denied for user ":user" in page ":page" for missing right ":right"', array(':user' => name($_SESSION['user']), ':page' => $_SERVER['PHP_SELF'], ':right' => $right)), 'yellow');
                }

                return false;
            }
        }

        return true;

    }catch(Exception $e){
        throw new BException('has_rights(): Failed', $e);
    }
}






/*
 * Returns true if the current session user has the specified group
 * This function will automatically load the groups for this user if
 * they are not yet in the session variable
 */
function has_groups($groups, &$user = null){
    global $_CONFIG;

    try{
        if($user === null){
            if(empty($_SESSION['user'])){
                /*
                 * No user specified and there is no session user either,
                 * so there are absolutely no groups at all
                 */
                return false;
            }

            $user = &$_SESSION['user'];

        }elseif(!is_array($user)){
            throw new BException(tr('has_groups(): Specified user is not an array'), 'invalid');
        }

        /*
         * Dynamically load the user groups
         */
        if(empty($user['groups'])){
            if(empty($user)){
                /*
                 * There is no user, so there are no groups at all
                 */
                return false;
            }

            load_libs('user');
            $user['groups'] = user_load_groups($user);
        }

        if(empty($groups)){
            throw new BException('has_groups(): No groups specified');
        }

        if(!empty($user['rights']['god'])){
            return true;
        }

        foreach(array_force($groups) as $group){
            if(empty($user['groups'][$group]) or !empty($user['rights']['devil']) or !empty($fail)){
                if((PLATFORM_CLI) and VERBOSE){
                    load_libs('user');
                    log_console(tr('has_groups(): Access denied for user ":user" in page ":page" for missing group ":group"', array(':user' => name($_SESSION['user']), ':page' => $_SERVER['PHP_SELF'], ':group' => $group)), 'yellow');
                }

                return false;
            }
        }

        return true;

    }catch(Exception $e){
        throw new BException('has_groups(): Failed', $e);
    }
}



/*
 * Either a user is logged in or the person will be redirected to the specified URL
 */
function user_or_signin(){
    global $_CONFIG, $core;

    try{
        if(PLATFORM_CLI){
            return $_SESSION['user'];
        }

        if(empty($_SESSION['user']['id'])){
            /*
             * No session
             */
            if($core->callType('api') or $core->callType('ajax')){
                json_reply(tr('Specified token ":token" has no session', array(':token' => isset_get($_POST['PHPSESSID']))), 'signin');
            }

            $url = domain(isset_get($_CONFIG['redirects']['signin'], 'signin.php').'?redirect='.urlencode($_SERVER['REQUEST_URI']));

            html_flash_set('Unauthorized: Please sign in to continue');
            log_file(tr('No user, redirecting to sign in page ":url"', array(':url' => $url)), 'user-or-signin', 'VERBOSE/yellow');
            redirect($url, 302);
        }

        if(!empty($_SESSION['force_page'])){
            /*
             * Session is, but locked
             * Redirect all pages EXCEPT the lock page itself!
             */
            if(empty($_CONFIG['redirects'][$_SESSION['force_page']])){
                throw new BException(tr('user_or_signin(): Forced page ":page" does not exist in $_CONFIG[redirects]', array(':page' => $_SESSION['force_page'])), 'not-exist');
            }

            if($_CONFIG['redirects'][$_SESSION['force_page']] !== str_until(str_rfrom($_SERVER['REQUEST_URI'], '/'), '?')){
                log_file(tr('User ":user" has forced page ":page"', array(':user' => name($_SESSION['user']), ':page' => $_SESSION['force_page'])), 'user-or-signin', 'VERBOSE/yellow');
                redirect(domain($_CONFIG['redirects'][$_SESSION['force_page']].'?redirect='.urlencode($_SERVER['REQUEST_URI'])));
            }
        }

        /*
         * Is user restricted to a page? if so, keep him there
         */
        if(empty($_SESSION['lock']) and !empty($_SESSION['user']['redirect'])){
            if(str_from($_SESSION['user']['redirect'], '://') != $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']){
                log_file(tr('User ":user" has is restricted to page ":page"', array(':user' => name($_SESSION['user']), ':page' => $_SESSION['user']['redirect'])), 'user-or-signin', 'VERBOSE/yellow');
                redirect(domain($_SESSION['user']['redirect']));
            }
        }

        return $_SESSION['user'];

    }catch(Exception $e){
        throw new BException('user_or_signin(): Failed', $e);
    }
}



/*
 * The current user has the specified rights, or will be redirected or get shown "access denied"
 */
function rights_or_access_denied($rights, $url = null){
    global $_CONFIG;

    try{
        if(!$rights){
            return true;
        }

        user_or_signin();

        if(PLATFORM_CLI or has_rights($rights)){
            /*
             * We're on CLI or the user has the required rights
             */
            return $_SESSION['user'];
        }

        /*
         * If user has no admin permissions we're not even showing 403, we're
         * simply showing the signin page
         */
        if(in_array('admin', array_force($rights))){
            redirect(domain(isset_get($url, $_CONFIG['redirects']['signin'])));
        }

        log_file(tr('User ":user" is missing one or more of the rights ":rights"', array(':user' => name($_SESSION['user']), ':rights' => $rights)), 'rights-or-access-denied', 'yellow');
        page_show(403);

    }catch(Exception $e){
        throw new BException('rights_or_access_denied(): Failed', $e);
    }
}



/*
 * The current user has the specified groups, or will be redirected or get shown "access denied"
 */
function groups_or_access_denied($groups){
    global $_CONFIG;

    try{
        user_or_signin();

        if(PLATFORM_CLI or has_groups($groups)){
            return $_SESSION['user'];
        }

        if(in_array('admin', array_force($groups))){
            redirect(domain($_CONFIG['redirects']['signin']));
        }

        page_show($_CONFIG['redirects']['access-denied']);

    }catch(Exception $e){
        throw new BException('groups_or_access_denied(): Failed', $e);
    }
}



/*
 * Either a user is logged in or  the person will be shown specified page.
 */
function user_or_page($page){
    if(empty($_SESSION['user'])){
        page_show($page);
        return false;
    }

    return $_SESSION['user'];
}



/*
 * Return $with_rights if the current user has the specified rights
 * Return $without_rights if not
 */
function return_with_rights($rights, $with_rights, $without_rights = null){
    try{
        if(has_rights($rights)){
            return $with_rights;
        }

        return $without_rights;

    }catch(Exception $e){
        throw new BException('return_with_rights(): Failed', $e);
    }
}



/*
 * Return $with_groups if the current user is member of the specified groups
 * Return $without_groups if not
 */
function return_with_groups($groups, $with_groups, $without_groups = null){
    try{
        if(has_groups($groups)){
            return $with_groups;
        }

        return $without_groups;

    }catch(Exception $e){
        throw new BException('return_with_groups(): Failed', $e);
    }
}



/*
 * Read extended signin
 */
function check_extended_session(){
    global $_CONFIG;

    try{
        if(empty($_CONFIG['sessions']['extended']['enabled'])){
            return false;
        }

        if(isset($_COOKIE['extsession']) and !isset($_SESSION['user'])) {
            /*
             * Pull  extsession data
             */
            $ext = sql_get('SELECT `users_id` FROM `extended_sessions` WHERE `session_key` = ":session_key" AND DATE(`addedon`) < DATE(NOW());', array(':session_key' => cfm($_COOKIE['extsession'])));

            if($ext['users_id']) {
                $user = sql_get('SELECT * FROM `users` WHERE `users`.`id` = :id', array(':id' => cfi($ext['users_id'])));

                if($user['id']){
                    /*
                     * Auto sign in user
                     */
                    load_libs('user');
                    user_signin($user, true);

                }else{
                    /*
                     * Remove cookie
                     */
                    setcookie('extsession', 'stub', 1);
                }

            }else{
                /*
                 * Remove cookie
                 */
                setcookie('extsession', 'stub', 1);
            }
        }

    }catch(Exception $e){
        throw new BException('user_create_extended_session(): Failed', $e);
    }
}



/*
 * Return the first non empty argument
 */
function not_empty(){
    foreach(func_get_args() as $argument){
        if($argument){
            return $argument;
        }
    }

    return $argument;
}



/*
 * Return the first non null argument
 */
function not_null(){
    foreach(func_get_args() as $argument){
        if($argument === null) continue;
        return $argument;
    }
}



/*
 * Return the first non empty argument
 */
function pick_random($count){
    try{
        $args = func_get_args();

        /*
         * Remove the $count argument from the list
         */
        array_shift($args);

        if(!$count){
            /*
             * Get a random count
             */
            $count = mt_rand(1, count($args));
            $array = true;
        }

        if(($count < 1) or ($count > count($args))){
            throw new BException(tr('pick_random(): Invalid count ":count" specified for ":args" arguments', array(':count' => $count, ':args' => count($args))), 'invalid');

        }elseif($count == 1){
            if(empty($array)){
                return $args[array_rand($args, $count)];
            }

            return array($args[array_rand($args, $count)]);

        }else{
            $retval = array();

            for($i = 0; $i < $count; $i++){
                $retval[] = $args[$key = array_rand($args)];
                unset($args[$key]);
            }

            return $retval;
        }

    }catch(Exception $e){
        throw new BException(tr('pick_random(): Failed'), $e);
    }
}



/*
 * Return display status for specified status
 */
function status($status, $list = null){
    try{
        if(is_array($list)){
            /*
             * $list contains list of possible statusses
             */
            if(isset($list[$status])){
                return $list[$status];
            }


            return 'Unknown';
        }

        if($status === null){
            if($list){
                /*
                 * Alternative name specified
                 */
                return $list;
            }

            return 'Ok';
        }

        return str_capitalize(str_replace('-', ' ', $status));

    }catch(Exception $e){
        throw new BException(tr('status(): Failed'), $e);
    }
}



/*
 * Generate a CSRF code and set it in the $_SESSION[csrf] array
 */
function set_csrf($prefix = ''){
    global $_CONFIG, $core;

    try{
        if(empty($_CONFIG['security']['csrf']['enabled'])){
            /*
             * CSRF check system has been disabled
             */
            return false;
        }

        if($core->register('csrf')){
            return $core->register('csrf');
        }

        /*
         * Avoid people messing around
         */
        if(isset($_SESSION['csrf']) and (count($_SESSION['csrf']) >= $_CONFIG['security']['csrf']['buffer_size'])){
            /*
             * Too many csrf, so too many post requests open. Remove the oldest
             * CSRF code and add a new one
             */
            if(count($_SESSION['csrf']) >= ($_CONFIG['security']['csrf']['buffer_size'] + 5)){
                /*
                 * WTF? How did we get so many?? Throw it all away, start over
                 */
                unset($_SESSION['csrf']);

            }else{
                array_shift($_SESSION['csrf']);
            }
        }

        $csrf = $prefix.unique_code('sha256');

        if(empty($_SESSION['csrf'])){
            $_SESSION['csrf'] = array();
        }

        $_SESSION['csrf'][$csrf] = new DateTime();
        $_SESSION['csrf'][$csrf] = $_SESSION['csrf'][$csrf]->getTimestamp();

        $core->register('csrf', $csrf);
        return $csrf;

    }catch(Exception $e){
        throw new BException(tr('set_csrf(): Failed'), $e);
    }
}



/*
 *
 */
function check_csrf(){
    global $_CONFIG, $core;

    try{
        if(empty($_CONFIG['security']['csrf']['enabled'])){
            /*
             * CSRF check system has been disabled
             */
            return false;
        }

        if(!$core->callType('http') and !$core->callType('admin')){
            /*
             * CSRF only works for HTTP or ADMIN requests
             */
            return false;
        }

        if(!empty($core->register['csrf_ok'])){
            /*
             * CSRF check has already been executed for this post, all okay!
             */
            return true;
        }

        if(empty($_POST)){
            /*
             * There is no POST data
             */
            return false;
        }

        if(empty($_POST['csrf'])){
log_file($core->callType());
            throw new BException(tr('check_csrf(): No CSRF field specified'), 'warning/not-specified');
        }

        if($core->callType('ajax')){
            if(substr($_POST['csrf'], 0, 5) != 'ajax_'){
                /*
                 * Invalid CSRF code is sppokie, don't make this a warning
                 */
                throw new BException(tr('check_csrf(): Specified CSRF ":code" is invalid'), 'invalid');
            }
        }

        if(empty($_SESSION['csrf'][$_POST['csrf']])){
            throw new BException(tr('check_csrf(): Specified CSRF ":code" does not exist', array(':code' => $_POST['csrf'])), 'warning/not-exist');
        }

        /*
         * Get the code from $_SESSION and delete it so it won't be used twice
         */
        $timestamp = $_SESSION['csrf'][$_POST['csrf']];
        $now       = new DateTime();

        unset($_SESSION['csrf'][$_POST['csrf']]);

        /*
         * Code timed out?
         */
        if($_CONFIG['security']['csrf']['timeout']){
            if(($timestamp + $_CONFIG['security']['csrf']['timeout']) < $now->getTimestamp()){
                throw new BException(tr('check_csrf(): Specified CSRF ":code" timed out', array(':code' => $_POST['csrf'])), 'warning/timeout');
            }
        }

        $core->register['csrf_ok'] = true;

        if($core->callType('ajax')){
            /*
             * Send new CSRF code with the AJAX return payload
             */
            $core->register['ajax_csrf'] = set_csrf('ajax_');
        }

        return true;

    }catch(Exception $e){
        /*
         * CSRF check failed, drop $_POST
         */
        foreach($_POST as $key => $value){
            if(substr($key, -6, 6) === 'submit'){
                unset($_POST[$key]);
            }
        }
log_file('aaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
log_file($core->callType('http'));
        log_file($e);
        html_flash_set(tr('The form data was too old, please try again'), 'warning');
    }
}



/*
 * Update the session with values directly from $_REQUEST
 */
function session_request_register($key, $valid = null){
    try{
        $_SESSION[$key] = isset_get($_REQUEST[$key], isset_get($_SESSION[$key]));

        if($valid){
            /*
             * Only accept values in this valid list (AND empty!)
             * Invalid values will be set to null
             */
            if(!in_array($_SESSION[$key], array_force($valid))){
                $_SESSION[$key] = null;
            }
        }

        if(empty($_SESSION[$key])){
            unset($_SESSION[$key]);
            return null;
        }

        return $_SESSION[$key];

    }catch(Exception $e){
        throw new BException('session_request_register(): Failed', $e);
    }
}



/*
 * Will return $return if the specified item id is in the specified source.
 */
function in_source($source, $key, $return = true){
    try{
        if(!is_array($source)){
            throw new BException(tr('in_source(): Specified source ":source" should be an array', array(':source' => $source)), 'invalid');
        }

        if(isset_get($source[$key])){
            return $return;
        }

        return '';

    }catch(Exception $e){
        throw new BException('in_source(): Failed', $e);
    }
}



/*
 *
 */
function is_natural($number, $start = 1){
    try{
        if(!is_numeric($number)){
            return false;
        }

        if($number < $start){
            return false;
        }

        if($number != (integer) $number){
            return false;
        }

        return true;

    }catch(Exception $e){
        throw new BException('is_natural(): Failed', $e);
    }
}



/*
 * Returns true if the specified string is a version, or false if it is not
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @version 2.5.46: Added function and documentation
 *
 * @param string $version The version to be validated
 * @return boolean True if the specified $version is an N.N.N version string
 */
function is_version($version){
    try{
        $valid = preg_match('/\d+\.\d+\.\d+/', $version);
        return $valid;

    }catch(Exception $e){
        throw new BException('is_version(): Failed', $e);
    }
}



/*
 *
 */
function is_new($entry){
    try{
        if(!is_array($entry)){
            throw new BException(tr('is_new(): Specified entry is not an array'), 'invalid');
        }

        if(isset_get($entry['status']) === '_new'){
            return true;
        }

        if(isset_get($entry['id']) === null){
            return true;
        }

        return false;

    }catch(Exception $e){
        throw new BException('is_new(): Failed', $e);
    }
}



/*
 *
 */
function force_natural($number, $default = 1, $start = 1){
    try{
        if(!is_numeric($number)){
            return (integer) $default;
        }

        if($number < $start){
            return (integer) $default;
        }

        if(!is_int($number)){
            return (integer) round($number);
        }

        return (integer) $number;

    }catch(Exception $e){
        throw new BException('force_natural(): Failed', $e);
    }
}



/*
 * Return a code that is guaranteed unique
 */
function unique_code($hash = 'sha512'){
    global $_CONFIG;

    try{
        return hash($hash, uniqid('', true).microtime(true).$_CONFIG['security']['seed']);

    }catch(Exception $e){
        throw new BException('unique_code(): Failed', $e);
    }
}



/*
 *
 */
function name($user = null, $key_prefix = '', $default = null){
    try{
        if($user){
            if($key_prefix){
                $key_prefix = str_ends($key_prefix, '_');
            }

            if(is_scalar($user)){
                if(!is_numeric($user)){
                    /*
                     * String, assume its a username
                     */
                    return $user;
                }

                /*
                 * This is not a user assoc array, but a user ID.
                 * Fetch user data from DB, then treat it as an array
                 */
                if(!$user = sql_get('SELECT `nickname`, `name`, `username`, `email` FROM `users` WHERE `id` = :id', array(':id' => $user))){
                    throw new BException('name(): Specified user id ":id" does not exist', array(':id' => str_log($user)), 'not-exists');
                }
            }

            if(!is_array($user)){
                throw new BException(tr('name(): Invalid data specified, please specify either user id, name, or an array containing username, email and or id'), 'invalid');
            }

            $user = not_empty(isset_get($user[$key_prefix.'nickname']), isset_get($user[$key_prefix.'name']), isset_get($user[$key_prefix.'username']), isset_get($user[$key_prefix.'email']));
            $user = trim($user);

            if($user){
                return $user;
            }
        }

        if($default === null){
            $default = tr('Guest');
        }

        /*
         * No user data found, assume guest user.
         */
        return $default;

    }catch(Exception $e){
        throw new BException(tr('name(): Failed'), $e);
    }
}



/*
 * Show the specified page
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 */
function page_show($pagename, $params = null, $get = null){
    global $_CONFIG, $core;

    try{
        array_ensure($params, 'message');

        if($get){
            if(!is_array($get)){
                throw new BException(tr('page_show(): Specified $get MUST be an array, but is an ":type"', array(':type' => gettype($get))), 'invalid');
            }

            $_GET = $get;
        }

        if(defined('LANGUAGE')){
            $language = LANGUAGE;

        }else{
            $language = 'en';
        }

        $params['page'] = $pagename;

        if(is_numeric($pagename)){
            /*
             * This is a system page, HTTP code. Use the page code as http code as well
             */
            $core->register['http_code'] = $pagename;
        }

        $core->register['real_script'] = $pagename;

        switch($core->callType()){
            case 'ajax':
                $include = ROOT.'www/'.$language.'/ajax/'.$pagename.'.php';

                if(isset_get($params['exists'])){
                    return file_exists($include);
                }

                /*
                 * Execute ajax page
                 */
                log_file(tr('Showing ":language" language ajax page ":page"', array(':page' => $pagename, ':language' => $language)), 'page-show', 'VERBOSE/cyan');
                return include($include);

            case 'api':
                $include = ROOT.'www/api/'.(is_numeric($pagename) ? 'system/' : '').$pagename.'.php';

                if(isset_get($params['exists'])){
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
                if(is_numeric($pagename)){
                    $include = ROOT.'www/'.$language.isset_get($admin).'/system/'.$pagename.'.php';

                    if(isset_get($params['exists'])){
                        return file_exists($include);
                    }

                    log_file(tr('Showing ":language" language system page ":page"', array(':page' => $pagename, ':language' => $language)), 'page-show', 'warning');

                    /*
                     * Wait a small random time to avoid timing attacks on
                     * system pages
                     */
                    usleep(mt_rand(1, 250));

                }else{
                    $include = ROOT.'www/'.$language.isset_get($admin).'/'.$pagename.'.php';

                    if(isset_get($params['exists'])){
                        return file_exists($include);
                    }

                    log_file(tr('Showing ":language" language http page ":page"', array(':page' => $pagename, ':language' => $language)), 'page-show', 'VERBOSE/cyan');
                }

                $result = include($include);

                if(isset_get($params['return'])){
                    return $result;
                }
        }

        die();

    }catch(Exception $e){
        if(isset($include) and !file_exists($include)){
            throw new BException(tr('page_show(): The requested page ":page" does not exist', array(':page' => $pagename)), 'not-exists');
        }

        throw new BException(tr('page_show(): Failed to show page ":page"', array(':page' => $pagename)), $e);
    }
}



/*
 * Throw an "under-construction" exception
 */
function under_construction($functionality = ''){
    if($functionality){
        throw new BException(tr('The functionality ":f" is under construction!', array(':f' => $functionality)), 'under-construction');
    }

    throw new BException(tr('This function is under construction!'), 'under-construction');
}



/*
 * Throw an "obsolete" exception
 */
function obsolete($functionality = ''){
    notify(array('code'    => 'obsolete',
                 'groups'  => 'developers',
                 'title'   => tr('Obsolete function used'),
                 'message' => tr('Function ":function" is used in ":file@:@line" in project ":project"', array(':function' => current_function(),
                                                                                                               ':file'     => current_file(),
                                                                                                               ':line'     => current_line(),
                                                                                                               ':project'  => PROJECT))));

    if($functionality){
        throw new BException(tr('The functionality ":f" is obsolete!', array(':f' => $functionality)), 'obsolete');
    }

    throw new BException(tr('This function is obsolete!'), 'obsolete');
}



/*
 * Throw an "not-supported" exception
 */
function not_supported($functionality = ''){
    if($functionality){
        throw new BException(tr('The functionality ":f" is not support!', array(':f' => $functionality)), 'not-supported');
    }

    throw new BException(tr('This function is not supported!'), 'not-supported');
}



/*
 * Return $source if $source is not considered "empty". Return null if specified variable is considered "empty", like 0, "", array(), etc.
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see get_empty()
 * @note This function is a wrapper for get_empty($source, null);
 * @version 2.6.27: Added documentation
 * @example
 * code
 * $result = get_null(false);
 * showdie($result);
 * /code
 *
 * This would return
 * code
 * null
 * /code
 *
 * @param mixed $source The value to be tested. If this value doesn't evaluate to empty, it will be returned
 * @return mixed Either $source or null, depending on if $source is empty or not
 */
function get_null($source){
    try{
        return get_empty($source, null);

    }catch(Exception $e){
        throw new BException(tr('get_null(): Failed'), $e);
    }
}



/*
 * Return $source if $source is not considered "empty". Return $default if specified variable is considered "empty", like 0, "", array(), etc.
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see get_null()
 * @version 2.6.27: Added function and documentation
 * @example
 * code
 * $result = get_empty(null, false);
 * showdie($result);
 * /code
 *
 * This would return
 * code
 * false
 * /code
 *
 * @param mixed $source The value to be tested. If this value doesn't evaluate to empty, it will be returned
 * @param mixed $default The value to be returned if $source evalidates to empty
 * @return mixed Either $source or $default, depending on if $source is empty or not
 */
function get_empty($source, $default){
    try{
        if($source){
            return $source;
        }

        return $default;

    }catch(Exception $e){
        throw new BException(tr('get_empty(): Failed'), $e);
    }
}



/*
 * Return the value quoted if non numeric string
 */
function quote($value){
    try{
        if(!is_numeric($value) and is_string($value)){
            return '"'.$value.'"';
        }

        return $value;

    }catch(Exception $e){
        throw new BException(tr('quote(): Failed'), $e);
    }
}



/*
 * Ensure that the specifed library is installed. If not, install it before
 * continuing
 */
function ensure_installed($params){
    try{
        array_ensure($params);

        /*
         * Check if specified library is installed
         */
        if(!isset($params['name'])){
            throw new BException(tr('ensure_installed(): No name specified for library'), 'not-specified');
        }

        /*
         * Test available files
         */
        if(isset($params['checks'])){
            foreach(array_force($params['checks']) as $path){
                if(!file_exists($path)){
                    $fail = 'path '.$path;
                    break;
                }
            }
        }

        /*
         * Test available functions
         */
        if(isset($params['functions']) and !isset($fail)){
            foreach(array_force($params['functions']) as $function){
                if(!function_exists($function)){
                    $fail = 'function '.$function;
                    break;
                }
            }
        }

        /*
         * Test available functions
         */
        if(isset($params['which']) and !isset($fail)){
            foreach(array_force($params['which']) as $program){
                if(!file_which($program)){
                    $fail = 'which '.$program;
                    break;
                }
            }
        }

        /*
         * If a test failed, run the installer for this function
         */
        if(!empty($fail)){
            log_file(tr('Installation test ":test" failed, running installer ":installer"', array(':test' => $fail, ':installer' => $params['callback'])), 'ensure-installed', 'yellow');
            return $params['callback']($params);
        }

    }catch(Exception $e){
        throw new BException(tr('ensure_installed(): Failed'), $e);
    }
}



/*
 *
 */
function ensure_value($value, $enum, $default){
    try{
        if(in_array($value, $enum)){
           return $value;
        }

        return $default;

    }catch(Exception $e){
        throw new BException(tr('ensure_value(): Failed'), $e);
    }
}



/*
 * Disconnect from webserver but continue working
 */
function disconnect(){
    try{
        switch(php_sapi_name()){
            case 'fpm-fcgi':
                fastcgi_finish_request();
                break;

            case '':
                throw new BException(tr('disconnect(): No SAPI detected'), 'unknown');

            default:
                throw new BException(tr('disconnect(): Unknown SAPI ":sapi" detected', array(':sapi' => php_sapi_name())), 'unknown');
        }

    }catch(Exception $e){
        throw new BException(tr('disconnect(): Failed'), $e);
    }
}



/*
 * Execute the specified callback function with the specified $params only if the callback has been set with an executable function
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
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
function execute_callback($callback, $params = null){
    try{
        if(is_callable($callback)){
            return $callback($params);
        }

        return null;

    }catch(Exception $e){
        throw new BException(tr('execute_callback(): Failed'), $e);
    }
}



/*
 *
 */
function get_boolean($value){
    try{
        switch(strtolower($value)){
            case 'off':
                return false;

            case 'on':
                return true;

            case 'true':
                return true;

            case 'false':
                return false;

            case '1':
                return true;

            case '0':
                return false;

            default:
                throw new BException(tr('get_boolean(): Unknown value ":value"', array(':value' => $value)), 'unknown');
        }

    }catch(Exception $e){
        throw new BException(tr('get_boolean(): Failed'), $e);
    }
}



/*
 *
 */
function language_lock($language, $script = null){
    global $core;

    static $checked   = false;
    static $incorrect = false;

    try{
        if(is_array($script)){
            /*
             * Script here will contain actually a list of all scripts for
             * each language. This can then be used to determine the name
             * of the script in the correct language to build linksx
             */
            $core->register['scripts'] = $script;
        }

        /*
         *
         */
        if(!$checked){
            $checked = true;

            if($language and (LANGUAGE !== $language)){
                $incorrect = true;
            }
        }

        if(!is_array($script)){
            /*
             * Show the specified script, it will create the content for
             * this $core->register['script']
             */
            page_show($script);
        }

        /*
         * Script and language match, continue
         */
        if($incorrect){
            page_show(404);
        }

    }catch(Exception $e){
        throw new BException(tr('language_lock(): Failed'), $e);
    }
}



/*
 * Read value for specified key from $_SESSION[cache][$key]
 *
 * If $_SESSION[cache][$key] does not exist, then execute the callback and
 * store the resulting value in $_SESSION[cache][$key]
 */
function session_cache($key, $callback){
    try{
        if(empty($_SESSION)){
            return null;
        }

        if(!isset($_SESSION['cache'])){
            $_SESSION['cache'] = array();
        }

        if(!isset($_SESSION['cache'][$key])){
            $_SESSION['cache'][$key] = $callback();
        }

        return $_SESSION['cache'][$key];

    }catch(Exception $e){
        throw new BException(tr('session_cache(): Failed'), $e);
    }
}



/*
 * BELOW ARE FUNCTIONS FROM EXTERNAL LIBRARIES THAT ARE INCLUDED IN STARTUP BECAUSE THEY ARE USED BEFORE THOSE OTHER LIBRARIES ARE LOADED
 */



/*
 * Adds the required amount of copies of the specified file to random CDN servers
 */
function cdn_add_files($files, $section = 'pub', $group = null, $delete = true){
    global $_CONFIG;

    try{
        if(!$_CONFIG['cdn']['enabled']){
            return false;
        }

        log_file(tr('cdn_add_files(): Adding files ":files"', array(':files' => $files)), 'DEBUG/cdn');

        if(!$section){
            throw new BException(tr('cdn_add_files(): No section specified'), 'not-specified');
        }

        if(!$files){
            throw new BException(tr('cdn_add_files(): No files specified'), 'not-specified');
        }

        /*
         * In what servers are we going to store these files?
         */
        $files       = array_force($files);
        $servers     = cdn_assign_servers();
        $file_insert = sql_prepare('INSERT IGNORE INTO `cdn_files` (`servers_id`, `section`, `group`, `file`)
                                    VALUES                         (:servers_id , :section , :group , :file )');

        /*
         * Register at what CDN servers the files will be uploaded, and send the
         * files there
         */
        foreach($servers as $servers_id => $server){
            foreach($files as $url => $file){
                log_file(tr('cdn_add_files(): Added file ":file" with url ":url" to CDN server ":server"', array(':file' => $file, ':url' => $url, ':server' => $server)), 'DEBUG/cdn');

                $file_insert->execute(array(':servers_id' => $servers_id,
                                            ':section'    => $section,
                                            ':group'      => $group,
                                            ':file'       => str_starts($url, '/')));
            }

            /*
             * Send the files
             */
            cdn_send_files($files, $server, $section, $group);
        }

        /*
         * Now that the file has been sent to the CDN system delete the file
         * locally
         */
        if($delete){
            foreach($files as $url => $file){
                file_delete($file, ROOT);
            }
        }

        return count($files);

    }catch(Exception $e){
        throw new BException('cdn_add_files(): Failed', $e);
    }
}



/*
 * Return a correct URL for CDN objects like css, javascript, image, video, downloadable files and more.
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see domain()
 * @see mapped_domain()
 * @version 2.4.9: Added documentation
 *
 * @params string $file
 * @params string $section
 * @params string $default If specified, use this default image if the specified file has not been found
 * @params boolean $force_cdn
 * @return string The result
 */
function cdn_domain($file = '', $section = 'pub', $default = null, $force_cdn = false){
    global $_CONFIG;
// :TODO: Database CDN servers (for the CDN network) have their own protocol (http or https) specified and thus will ignore the $_CONFIG[session][secure] directive, fix this!!!
    try{
        if(!$_CONFIG['cdn']['enabled'] and !$force_cdn){
            if($section == 'pub'){
                $section = not_empty($_CONFIG['cdn']['prefix'], '/');
            }

            return domain($file, null, $section, $_CONFIG['cdn']['domain'], null, false);
        }

        if($section == 'pub'){
            /*
             * Process pub files, "system" files like .css, .js, static website
             * images ,etc
             */
            if(!isset($_SESSION['cdn'])){
                /*
                 * Get a CDN server for this session
                 */
                $_SESSION['cdn'] = sql_get('SELECT   `baseurl`

                                            FROM     `cdn_servers`

                                            WHERE    `status` IS NULL

                                            ORDER BY RAND() LIMIT 1', true);

                if(empty($_SESSION['cdn'])){
                    /*
                     * There are no CDN servers available!
                     * Switch to working without CDN servers
                     */
                    notify(array('code'    => 'invalid',
                                 'groups'  => 'developers',
                                 'title'   => tr('Invalid configuration'),
                                 'message' => tr('cdn_domain(): The CDN system is enabled but there are no CDN servers configured')));

                    $_CONFIG['cdn']['enabled'] = false;
                    return cdn_domain($file, $section);

                }else{
                    $_SESSION['cdn'] = slash($_SESSION['cdn']).'pub/'.strtolower(str_replace('_', '-', PROJECT).'/');
                }
            }

            if(!empty($_CONFIG['cdn']['prefix'])){
                $file = $_CONFIG['cdn']['prefix'].$file;
            }

            return $_SESSION['cdn'].str_starts_not($file, '/');
        }

        /*
         * Get this URL from the CDN system
         */
        $url = sql_get('SELECT    `cdn_files`.`file`,
                                  `cdn_files`.`servers_id`,

                                  `cdn_servers`.`baseurl`

                        FROM      `cdn_files`

                        LEFT JOIN `cdn_servers`
                        ON        `cdn_files`.`servers_id` = `cdn_servers`.`id`

                        WHERE     `cdn_files`.`file` = :file
                        AND       `cdn_servers`.`status` IS NULL

                        ORDER BY  RAND()

                        LIMIT     1',

                        array(':file' => $file));

        if($url){
            /*
             * Yay, found the file in the CDN database!
             */
            return slash($url['baseurl']).strtolower(str_replace('_', '-', PROJECT)).$url['file'];
        }

        /*
         * The specified file is not found in the CDN system, return a default
         * image instead
         */
        if(!$default){
            $default = $_CONFIG['cdn']['img']['default'];
        }

        return cdn_domain($default, 'pub');

// :TODO: What why where?
        ///*
        // * We have a CDN server in session? If not, get one.
        // */
        //if(isset_get($_SESSION['cdn']) === null){
        //    $server = sql_get('SELECT `baseurl` FROM `cdn_servers` WHERE `status` IS NULL ORDER BY RAND() LIMIT 1', true);
        //
        //    if(!$server){
        //        /*
        //         * Err we have no CDN servers, though CDN is configured.. Just
        //         * continue locally?
        //         */
        //        notify('no-cdn-servers', tr('CDN system is enabled, but no availabe CDN servers were found'), 'developers');
        //        $_SESSION['cdn'] = false;
        //        return domain($url, $query, $prefix);
        //    }
        //
        //    $_SESSION['cdn'] = slash($server).strtolower(str_replace('_', '-', PROJECT));
        //}
        //
        //return $_SESSION['cdn'].$url;

    }catch(Exception $e){
        throw new BException('cdn_domain(): Failed', $e);
    }
}



/*
 * Cut and return a piece out of the source string, starting from the start string, stopping at the stop string.
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package str
 * @see str_from()
 * @see str_until()
 * @version 2.0.0: Moved to system library, added documentation
 * @example
 * code
 * $result = str_cut('support@capmega.com', '@', '.');
 * showdie($result);
 * /code
 *
 * This would return
 * code
 * capmega
 * /code
 *
 * @param string $source The string to be cut
 * @params string $start The character(s) to start the cut
 * @params string $stop The character(s) to stop the cut
 * @return string The $source string between the first occurrences of start and $stop
 */
function str_cut($source, $start, $stop){
    try{
        return str_until(str_from($source, $start), $stop);

    }catch(Exception $e){
        throw new BException(tr('str_cut(): Failed'), $e);
    }
}



/*
 * Returns true if the specified $needle exists in the specified $haystack
 *
 * This is a simple wrapper function to strpos() which does not require testing for false, as the output is boolean. If the $needle exists in the $haystack, true will be returned, else false will be returned.
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package str
 * @see strpos()
 * @see strstr()
 * @version 1.26.1: Added function and documentation
 * @example
 * code
 * $result = str_exists('This function is completely foobar', 'foobar');
 * showdie($result);
 * /code
 *
 * This would return
 * code
 * true
 * /code
 *
 * @param string $haystack The source string in which this function needs to find $needle
 * @params string $needle The string that will be searched for in $haystack
 * @return boolean True if the $needle exists in $haystack, false otherwise
 */
function str_exists($haystack, $needle){
    try{
        return (strpos($haystack, $needle) !== false);

    }catch(Exception $e){
        throw new BException(tr('str_exists(): Failed'), $e);
    }
}



/*
 * Cleanup string
 */
function str_clean($source, $utf8 = true){
    try{
        if(!is_scalar($source)){
            if(!is_null($source)){
                throw new BException(tr('str_clean(): Specified source ":source" from ":location" should be datatype "string" but has datatype ":datatype"', array(':source' => $source, ':datatype' => gettype($source), ':location' => current_file(1).'@'.current_line(1))), 'invalid');
            }
        }

        if($utf8){
            load_libs('utf8');

            $source = mb_trim(html_entity_decode(utf8_unescape(strip_tags(utf8_escape($source)))));
// :TODO: Check if the next line should also be added!
//            $source = preg_replace('/\s|\/|\?|&+/u', $replace, $source);

            return $source;
        }

        return mb_trim(html_entity_decode(strip_tags($source)));

    }catch(Exception $e){
        throw new BException(tr('str_clean(): Failed with string ":string"', array(':string' => $source)), $e);
    }
// :TODO:SVEN:20130709: Check if we should be using mysqli_escape_string() or addslashes(), since the former requires SQL connection, but the latter does NOT have correct UTF8 support!!
//    return mysqli_escape_string(trim(decode_entities(mb_strip_tags($str))));
}



/*
 * Return the given string from the specified needle
 */
function str_from($source, $needle, $more = 0, $require = false){
    try{
        if(!$needle){
            throw new BException('str_from(): No needle specified', 'not-specified');
        }

        $pos = mb_strpos($source, $needle);

        if($pos === false){
            if($require){
                return '';
            }

            return $source;
        }

        return mb_substr($source, $pos + mb_strlen($needle) - $more);

    }catch(Exception $e){
        throw new BException(tr('str_from(): Failed for string ":string"', array(':string' => $source)), $e);
    }
}



/*
 * Return the given string from 0 until the specified needle
 */
function str_until($source, $needle, $more = 0, $start = 0, $require = false){
    try{
        if(!$needle){
            throw new BException('str_until(): No needle specified', 'not-specified');
        }

        $pos = mb_strpos($source, $needle);

        if($pos === false){
            if($require){
                return '';
            }

            return $source;
        }

        return mb_substr($source, $start, $pos + $more);

    }catch(Exception $e){
        throw new BException(tr('str_until(): Failed for string ":string"', array(':string' => $source)), $e);
    }
}



/*
 * Return the given string from the specified needle, starting from the end
 */
function str_rfrom($source, $needle, $more = 0){
    try{
        if(!$needle){
            throw new BException('str_rfrom(): No needle specified', 'not-specified');
        }

        $pos = mb_strrpos($source, $needle);

        if($pos === false) return $source;

        return mb_substr($source, $pos + mb_strlen($needle) - $more);

    }catch(Exception $e){
        throw new BException(tr('str_rfrom(): Failed for string ":string"', array(':string' => $source)), $e);
    }
}



/*
 * Return the given string from 0 until the specified needle, starting from the end
 */
function str_runtil($source, $needle, $more = 0, $start = 0){
    try{
        if(!$needle){
            throw new BException('str_runtil(): No needle specified', 'not-specified');
        }

        $pos = mb_strrpos($source, $needle);

        if($pos === false) return $source;

        return mb_substr($source, $start, $pos + $more);

    }catch(Exception $e){
        throw new BException(tr('str_runtil(): Failed for string ":string"', array(':string' => $source)), $e);
    }
}



/*
 * Ensure that specified source string starts with specified string
 */
function str_starts($source, $string){
    try{
        if(mb_substr($source, 0, mb_strlen($string)) == $string){
            return $source;
        }

        return $string.$source;

    }catch(Exception $e){
        throw new BException(tr('str_starts(): Failed for ":source"', array(':source' => $source)), $e);
    }
}



/*
 * Ensure that specified source string starts NOT with specified string
 */
function str_starts_not($source, $string){
    try{
        while(mb_substr($source, 0, mb_strlen($string)) == $string){
            $source = mb_substr($source, mb_strlen($string));
        }

        return $source;

    }catch(Exception $e){
        throw new BException(tr('str_starts_not(): Failed for ":source"', array(':source' => $source)), $e);
    }
}



/*
 * Ensure that specified string ends with specified character
 */
function str_ends($source, $string){
    try{
        $length = mb_strlen($string);

        if(mb_substr($source, -$length, $length) == $string){
            return $source;
        }

        return $source.$string;

    }catch(Exception $e){
        throw new BException('str_ends(): Failed', $e);
    }
}



/*
 * Ensure that specified string ends NOT with specified character
 */
function str_ends_not($source, $strings, $loop = true){
    try{
        if(is_array($strings)){
            /*
             * For array test, we always loop
             */
            $redo = true;

            while($redo){
                $redo = false;

                foreach($strings as $string){
                    $new = str_ends_not($source, $string, true);

                    if($new != $source){
                        // A change was made, we have to rerun over it.
                        $redo = true;
                    }

                    $source = $new;
                }
            }

        }else{
            /*
             * Check for only one character
             */
            $length = mb_strlen($strings);

            while(mb_substr($source, -$length, $length) == $strings){
                $source = mb_substr($source, 0, -$length);
                if(!$loop) break;
            }
        }

        return $source;

    }catch(Exception $e){
        throw new BException('str_ends_not(): Failed', $e);
    }
}



/*
 * Ensure that specified string ends with slash
 */
function slash($string){
    try{
        return str_ends($string, '/');

    }catch(Exception $e){
        throw new BException('slash(): Failed', $e);
    }
}



/*
 * Ensure that specified string ends NOT with slash
 */
function unslash($string, $loop = true){
    try{
        return str_ends_not($string, '/', $loop);

    }catch(Exception $e){
        throw new BException('unslash(): Failed', $e);
    }
}



/*
 * Remove double "replace" chars
 */
function str_nodouble($source, $replace = '\1', $character = null, $case_insensitive = true){
    try{
        if($character){
            /*
             * Remove specific character
             */
            return preg_replace('/('.$character.')\\1+/u'.($case_insensitive ? 'i' : ''), $replace, $source);
        }

        /*
         * Remove ALL double characters
         */
        return preg_replace('/(.)\\1+/u'.($case_insensitive ? 'i' : ''), $replace, $source);

    }catch(Exception $e){
        throw new BException('str_nodouble(): Failed', $e);
    }
}



/*
 * Truncate string using the specified fill and method
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @note While log_console() will log towards the ROOT/data/log/ log files, cli_dot() will only log one single dot even though on the command line multiple dots may be shown
 * @see str_log()
 * @example
 * code
 * echo str_truncate('This is a long long long long test text!', 10);
 * }
 * /code
 *
 * This will return something like
 *
 * code
 * This is...
 * /code
 *
 * @param string $source
 * @param natural $length
 * @param string $fill
 * @param string $method
 * @param booelan $on_word
 * @return string The string, truncated if required, according to the specified truncating rules
 */
function str_truncate($source, $length, $fill = ' ... ', $method = 'right', $on_word = false){
    try{
        if(!$length or ($length < (mb_strlen($fill) + 1))){
            throw new BException('str_truncate(): No length or insufficient length specified. You must specify a length of minimal $fill length + 1', 'invalid');
        }

        if($length >= mb_strlen($source)){
            /*
             * No need to truncate, the string is short enough
             */
            return $source;
        }

        /*
         * Correct length
         */
        $length -= mb_strlen($fill);

        switch($method){
            case 'right':
                $retval = mb_substr($source, 0, $length);
                if($on_word and (strpos(substr($source, $length, 2), ' ') === false)){
                    if($pos = strrpos($retval, ' ')){
                        $retval = substr($retval, 0, $pos);
                    }
                }

                return trim($retval).$fill;

            case 'center':
                return mb_substr($source, 0, floor($length / 2)).$fill.mb_substr($source, -ceil($length / 2));

            case 'left':
                $retval = mb_substr($source, -$length, $length);

                if($on_word and substr($retval)){
                    if($pos = strpos($retval, ' ')){
                        $retval = substr($retval, $pos);
                    }
                }

                return $fill.trim($retval);

            default:
                throw new BException(tr('str_truncate(): Unknown method ":method" specified, please use "left", "center", or "right" or undefined which will default to "right"', array(':method' => $method)), 'unknown');
        }

    }catch(Exception $e){
        throw new BException(tr('str_truncate(): Failed for ":source"', array(':source' => $source)), $e);
    }
}



/*
 * Return a string that is suitable for logging.
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @note While log_console() will log towards the ROOT/data/log/ log files, cli_dot() will only log one single dot even though on the command line multiple dots may be shown
 * @see str_log()
 * @example
 * code
 * echo str_truncate('This is a long long long long test text!', 10);
 * }
 * /code
 *
 * This will return something like
 *
 * code
 * This is...
 * /code
 *
 * @param string $source
 * @param natural $length
 * @param string $fill
 * @param string $method
 * @param booelan $on_word
 * @return string The string, truncated if required, according to the specified truncating rules
 */
function str_log($source, $truncate = 8187, $separator = ', '){
    try{
        try{
            $json_encode = 'json_encode_custom';

        }catch(Exception $e){
            /*
             * Fuck...
             */
            $json_encode = 'json_encode';
        }

        if(!$source){
            if(is_numeric($source)){
                return 0;
            }

            return '';
        }

        if(!is_scalar($source)){
            if(is_array($source)){
                foreach($source as $key => &$value){
                    if(strstr($key, 'password')){
                        $value = '*** HIDDEN ***';
                        continue;
                    }

                    if(strstr($key, 'ssh_key')){
                        $value = '*** HIDDEN ***';
                        continue;
                    }
                }

                unset($value);

                $source = mb_trim($json_encode($source));

            }elseif(is_object($source) and ($source instanceof BException)){
                $source = $source->getCode().' / '.$source->getMessage();

            }else{
                $source = mb_trim($json_encode($source));
            }
        }

        return str_nodouble(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', str_replace('  ', ' ', str_replace("\n", ' ', str_truncate($source, $truncate, ' ... ', 'center')))), '\1', ' ');

    }catch(Exception $e){
        if($e->getRealCode() === 'invalid'){
            notify($e->makeWarning(true));
            return "Data converted using print_r() instead of json_encode() because json_encode_custom() failed on this data: ".print_r($source, true);
        }

        throw new BException('str_log(): Failed', $e);
    }
}



/*
 * Returns the value of the first element of the specified array
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package array
 * @see array_last()
 * @version 1.27.0: Added function and documentation
 *
 * @param array $source The source array from which the first value must be returned
 * @return mixed The first value of the specified source array
 */
function array_first($source){
    try{
        reset($source);
        return current($source);

    }catch(Exception $e){
        throw new BException('array_first(): Failed', $e);
    }
}



/*
 * Returns the value of the last element of the specified array
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package array
 * @see array_first()
 * @see date_convert() Used to convert the sitemap entry dates
 * @version 1.27.0: Added function and documentation
 *
 * @param array $source The source array from which the last value must be returned
 * @return mixed The last value of the specified source array
 */
function array_last($source){
    try{
        return end($source);

    }catch(Exception $e){
        throw new BException('array_last(): Failed', $e);
    }
}



/*
 * Make sure the specified keys are available on the array
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package array
 *
 * @param array $source
 * @param mixed (optional) $keys
 * @param mixed (optional) $default_value
 * @param mixed (optional) $trim_existing
 * @return array
 */
function array_ensure(&$source, $keys = '', $default_value = null, $trim_existing = false){
    try{
        if(!$source){
            $source = array();

        }elseif(!is_array($source)){
            if(is_object($source)){
                throw new BException(tr('array_ensure(): Specified source is not an array but an object of the class ":class"', array(':class' => get_class($source))), 'invalid');
            }

            throw new BException(tr('array_ensure(): Specified source is not an array but a ":type"', array(':type' => gettype($source))), 'invalid');
        }

        if($keys){
            foreach(array_force($keys) as $key){
                if(!$key){
                    continue;
                }

                if(array_key_exists($key, $source)){
                    if($trim_existing and is_string($source[$key])){
                        /*
                         * Automatically trim the found value
                         */
                        $source[$key] = trim($source[$key], (is_bool($trim_existing) ? ' ' : $trim_existing));
                    }

                }else{
                    $source[$key] = $default_value;
                }
            }
        }

    }catch(Exception $e){
        throw new BException('array_ensure(): Failed', $e);
    }
}



/*
 * Specified variable may be either string or array, but ensure that its returned as an array.
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see str_force()
 * @example
 * code
 * print_r(array_force(array('test')));
 * /code
 *
 * This will return something like
 *
 * code
 * array('test')
 * /code
 *
 * code
 * print_r(array_force('test'));
 * /code
 *
 * This will return something like
 *
 * code
 * array('test')
 * /code
 *
 * @param string $source The variable that should be forced to be an array
 * @param string $separator
 * @return array The specified $source, but now converted to an array data type (if it was not an array yet)
 */
function array_force($source, $separator = ','){
    try{
        if(($source === '') or ($source === null)){
            return array();
        }

        if(!is_array($source)){
            if(!is_string($source)){
                return array($source);
            }

            return explode($separator, $source);
        }

        return $source;

    }catch(Exception $e){
        throw new BException('array_force(): Failed', $e);
    }
}



/*
 * Show a dot on the console each $each call if $each is false, "DONE" will be printed, with next line. Internal counter will reset if a different $each is received.
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @note While log_console() will log towards the ROOT/data/log/ log files, cli_dot() will only log one single dot even though on the command line multiple dots may be shown
 * @see log_console()
 * @example
 * code
 * for($i=0; $i < 100; $i++){
 *     cli_dot();
 * }
 * /code
 *
 * This will return something like
 *
 * code
 * ..........
 * /code
 *
 * @param natural $each
 * @param string $color
 * @param string $dot
 * @param boolean $quiet
 * @return boolean True if a dot was printed, false if not
 */
function cli_dot($each = 10, $color = 'green', $dot = '.', $quiet = false){
    static $count  = 0,
           $l_each = 0;

    try{
        if(!PLATFORM_CLI){
            return false;
        }

        if($quiet and QUIET){
            /*
             * Don't show this in QUIET mode
             */
            return false;
        }

        if($each === false){
            if($count){
                /*
                 * Only show "Done" if we have shown any dot at all
                 */
                log_console(tr('Done'), $color);

            }else{
                log_console('');
            }

            $l_each = 0;
            $count  = 0;
            return true;
        }

        $count++;

        if($l_each != $each){
            $l_each = $each;
            $count  = 0;
        }

        if($count >= $l_each){
            $count = 0;
            log_console($dot, $color, false);
            return true;
        }

    }catch(Exception $e){
        throw new BException('cli_dot(): Failed', $e);
    }
}



/*
 * CLI color code management class
 * Taken from http://www.if-not-true-then-false.com/2010/php-class-for-coloring-php-command-line-cli-scripts-output-php-output-colorizing-using-bash-shell-colors/
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 */
class Colors {
    private $foreground_colors = array();
    private $background_colors = array();

    public function __construct() {
        try{
            /*
             * Set up shell colors
             */
            $this->foreground_colors['black']        = '0;30';
            $this->foreground_colors['dark_gray']    = '1;30';
            $this->foreground_colors['blue']         = '0;34';
            $this->foreground_colors['light_blue']   = '1;34';
            $this->foreground_colors['info']         = '1;34';
            $this->foreground_colors['green']        = '0;32';
            $this->foreground_colors['light_green']  = '1;32';
            $this->foreground_colors['success']      = '1;32';
            $this->foreground_colors['cyan']         = '0;36';
            $this->foreground_colors['light_cyan']   = '1;36';
            $this->foreground_colors['red']          = '0;31';
            $this->foreground_colors['light_red']    = '1;31';
            $this->foreground_colors['error']        = '1;31';
            $this->foreground_colors['exception']    = '1;31';
            $this->foreground_colors['bexception']   = '1;31';
            $this->foreground_colors['purple']       = '0;35';
            $this->foreground_colors['light_purple'] = '1;35';
            $this->foreground_colors['brown']        = '0;33';
            $this->foreground_colors['yellow']       = '1;33';
            $this->foreground_colors['warning']      = '1;33';
            $this->foreground_colors['light_gray']   = '0;37';
            $this->foreground_colors['white']        = '1;37';

            $this->background_colors['black']        = '40';
            $this->background_colors['red']          = '41';
            $this->background_colors['green']        = '42';
            $this->background_colors['yellow']       = '43';
            $this->background_colors['blue']         = '44';
            $this->background_colors['magenta']      = '45';
            $this->background_colors['cyan']         = '46';
            $this->background_colors['light_gray']   = '47';

        }catch(Exception $e){
            throw new BException(tr('Color::__construct(): Failed'), $e);
        }
    }

    /*
     * Returns colored string
     */
    public function getColoredString($string, $foreground_color = null, $background_color = null, $force = false, $reset = true) {
        try{
            $colored_string = '';

            if(!is_scalar($string)){
                log_console(tr('[ WARNING ] colors::getColoredString(): Specified text ":text" is not a string or scalar. Forcing text to string', array(':text' => $string)), 'warning');
                $string = str_log($string);
            }

            if(NOCOLOR and !$force){
                /*
                 * Do NOT apply color
                 */
                return $string;
            }

            if($foreground_color){
                if(!is_string($foreground_color) or !isset($this->foreground_colors[$foreground_color])){
                    /*
                     * If requested colors do not exist, return no
                     */
                    log_console(tr('[ WARNING ] colors::getColoredString(): specified foreground color ":color" for the next line does not exist. The line will be displayed without colors', array(':color' => $foreground_color)), 'warning');
                    return $string;
                }

                // Check if given foreground color found
                if(isset($this->foreground_colors[$foreground_color])) {
                    $colored_string .= "\033[".$this->foreground_colors[$foreground_color].'m';
                }
            }

            if($background_color){
                if(!is_string($background_color) or !isset($this->background_colors[$background_color])){
                    /*
                     * If requested colors do not exist, return no
                     */
                    log_console(tr('[ WARNING ] getColoredString(): specified background color ":color" for the next line does not exist. The line will be displayed without colors', array(':color' => $background_color)), 'warning');
                    return $string;
                }

                /*
                 * Check if given background color found
                 */
                if(isset($this->background_colors[$background_color])) {
                    $colored_string .= "\033[".$this->background_colors[$background_color].'m';
                }
            }

            /*
             * Add string and end coloring
             */
            $colored_string .=  $string;

            if($reset){
                $colored_string .= cli_reset_color();
            }

            return $colored_string;

        }catch(Exception $e){
            throw new BException(tr('Color::getColoredString(): Failed'), $e);
        }
    }

    /*
     * Returns all foreground color names
     */
    public function getForegroundColors(){
        return array_keys($this->foreground_colors);
    }

    /*
     * Returns all background color names
     */
    public function getBackgroundColors() {
        return array_keys($this->background_colors);
    }

    /*
     * Returns all background color names
     */
    public function resetColors() {

    }
}



/*
 * Return the specified string in the specified color
 */
function cli_color($string, $fore_color = null, $back_color = null, $force = false, $reset = true){
    try{
        static $color;

        if(!$color){
            $color = new Colors();
        }

        return $color->getColoredString($string, $fore_color, $back_color, $force, $reset);

    }catch(Exception $e){
        throw new BException('cli_color(): Failed', $e);
    }
}



/*
 * Return or echo CLI code to reset all colors
 */
function cli_reset_color($echo = false){
    try{
        if(!$echo){
            return "\033[0m";
        }

        echo "\033[0m";

    }catch(Exception $e){
        throw new BException('cli_reset_color(): Failed', $e);
    }
}



/*
 *
 */
function date_convert($date = null, $requested_format = 'human_datetime', $to_timezone = null, $from_timezone = null){
    global $_CONFIG;

    try{
        /*
         * Ensure we have some valid date string
         */
        if($date === null){
            $date = date('Y-m-d H:i:s');

        }elseif(!$date){
            return '';

        }elseif(is_numeric($date)){
            $date = date('Y-m-d H:i:s', $date);
        }

        /*
         * Compatibility check!
         * Older systems will still have the timezone specified as a single string, newer as an array
         * The difference between these two can result in systems no longer starting up after an update
         */
        if($to_timezone === null){
            $to_timezone = TIMEZONE;
        }

        if($from_timezone === null){
            $from_timezone = $_CONFIG['timezone']['system'];
        }

        /*
         * Ensure we have a valid format
         */
        if($requested_format == 'mysql'){
            /*
             * Use mysql format
             */
            $format = 'Y-m-d H:i:s';

        }elseif(isset($_CONFIG['formats'][$requested_format])){
            /*
             * Use predefined format
             */
            $format = $_CONFIG['formats'][$requested_format];

        }else{
            /*
             * Use custom format
             */
            $format = $requested_format;
        }

        /*
         * Force 12 or 24 hour format?
         */
        if($requested_format == 'object'){
            /*
             * Return a PHP DateTime object
             */
            $format = $requested_format;

        }else{
            switch($_CONFIG['formats']['force1224']){
                case false:
                    break;

                case '12':
                    /*
                     * Only add AM/PM in case original spec has 24H and no AM/PM
                     */
                    if(($requested_format != 'mysql') and strstr($format, 'g')){
                        $format = str_replace('H', 'g', $format);

                        if(!strstr($format, 'a')){
                            $format .= ' a';
                        }
                    }

                    break;

                case '24':
                    $format = str_replace('g', 'H', $format);
                    $format = trim(str_replace('a', '', $format));
                    break;

                default:
                    throw new BException(tr('date_convert(): Invalid force1224 hour format ":format" specified. Must be either false, "12", or "24". See $_CONFIG[formats][force1224]', array(':format' => $_CONFIG['formats']['force1224'])), 'invalid');
            }
        }

        /*
         * Create date in specified timezone (if specifed)
         * Return formatted date
         *
         * If specified date is already a DateTime object, then from_timezone will not work
         */
        if(is_scalar($date)){
            $date = new DateTime($date, ($from_timezone ? new DateTimeZone($from_timezone) : null));

        }else{
            if(!($date instanceof DateTime)){
                throw new BException(tr('date_convert(): Specified date variable is a ":type" which is invalid. Should be either scalar or a DateTime object', array(':type' => gettype($date))), 'invalid');
            }
        }

        if($to_timezone){
            /*
             * Output to specified timezone
             */
            $date->setTimezone(new DateTimeZone($to_timezone));
        }

        try{
            if($format === 'object'){
                return $date;
            }

            $retval = $date->format($format);

            if(LANGUAGE === 'en'){
                return $retval;
            }

            load_libs('date');
            return date_translate($retval);

        }catch(Exception $e){
            throw new BException(tr('date_convert(): Failed to convert to format ":format" because ":e"', array(':format' => $format, ':e' => $e)), 'invalid');
        }

    }catch(Exception $e){
        throw new BException('date_convert(): Failed', $e);
    }
}



/*
 * This function will check the specified $source variable, estimate what datatype it should be, and cast it as that datatype. Empty strings will be returned as null
 *
 * @param mixed $source
 * @return mixed The variable with the datatype interpreted by this function
 */
function force_datatype($source){
    try{
        if(!is_scalar($source)){
            return $source;
        }

        if(is_numeric($source)){
            if((int) $source === $source){
                return (int) $source;
            }

            return (float) $source;
        }

        if($source === true){
            return true;
        }

        if($source === false){
            return false;
        }

        if($source === 'true'){
            return true;
        }

        if($source === 'false'){
            return false;
        }

        if($source === 'null'){
            return null;
        }

        if(!$source){
            /*
             * Assume null
             */
            return null;
        }

        return (string) $source;

    }catch(Exception $e){
        throw new BException('force_datatype(): Failed', $e);
    }
}



/*
 * BELOW ARE IMPORTANT BUT RARELY USED FUNCTIONS THAT HAVE CONTENTS IN HANDLER FILES
 */



/*
 * Show the correct HTML flash error message
 */
function error_message($e, $messages = array(), $default = null){
    return include(__DIR__.'/handlers/system-error-message.php');
}



/*
 * Switch to specified site type, and redirect back
 */
function switch_type($type, $redirect = ''){
    return include(__DIR__.'/handlers/system-switch-type.php');
}



/*
 *
 */
function get_global_data_path($section = '', $writable = true){
    return include(__DIR__.'/handlers/system-get-global-data-path.php');
}



/*
 * Execute the specified command as a background process
 *
 * The specified command will be executed in the background in a separate process and run_background() will immediately return control back to BASE.
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see show()
 * @example
 * code
 * echo current_file();
 * /code
 *
 * This will return something like
 *
 * code
 * custom.php
 * /code
 *
 * @param string $cmd The command to be executed
 * @param boolean $log If set to true, the output of the command will be logged to
 * @param boolean $single If set to true,
 * @param string $term
 * @return natural The PID of the background process executing the requested command
 */
function run_background($cmd, $log = true, $single = true, $term = 'xterm', $wait = 0){
    return include(__DIR__.'/handlers/system-run-background.php');
}



/*
 * DEBUG FUNCTIONS BELOW HERE
 */



/*
 * Return the file where this call was made
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see show()
 * @example
 * code
 * echo current_file();
 * /code
 *
 * This will return something like
 *
 * code
 * custom.php
 * /code
 *
 * @param integer $trace
 * @return string The file where this call was made. If $trace is specified and not zero, it will return the file $trace amount of function calls before of after that call was made
 */
function current_file($trace = 0){
    return include(__DIR__.'/handlers/debug-current-file.php');
}



/*
 * Return the line number where this call was made
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see show()
 * @example
 * code
 * echo current_line();
 * /code
 *
 * This will return something like
 *
 * code
 * 275
 * /code
 *
 * @param integer $trace
 * @return string The line number in the file where this call was made. If $trace is specified and not zero, it will return the line number in the file $trace amount of function calls before of after that call was made
 */
function current_line($trace = 0){
    return include(__DIR__.'/handlers/debug-current-line.php');
}



/*
 * Return the function where this call was made
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see show()
 * @example
 * code
 * echo current_function();
 * /code
 *
 * This will return something like
 *
 * code
 * c_page
 * /code
 *
 * @param integer $trace
 * @return string The name of the function where this call was made. If $trace is specified and not zero, it will return the function name $trace amount of functions before of after that call was made
 */
function current_function($trace = 0){
    return include(__DIR__.'/handlers/debug-current-function.php');
}



/*
 * Auto fill in values in HTML forms (very useful for debugging and testing)
 *
 * In environments where debug is enabled, this function can pre-fill large HTML forms with test data
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @note This function will NOT return any values when not running in debug mode
 * @see debug()
 * @example
 * code
 * echo '<input type="text" name="username" value="'.value('username').'">';
 * /code
 *
 * This will show something like

 * code
 * <input type="text" name="username" value="YtiukrtyeG">
 * /code
 *
 * @param mixed $format
 * @param natural $size
 * @return string The value to be inserted.
 */
function value($format, $size = null){
    if(!debug()) return '';
    return include(__DIR__.'/handlers/debug-value.php');
}



/*
 * Show data, function results and variables in a readable format
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see showdie()
 *
 * @param mixed $data
 * @param integer $trace_offset
 * @param boolean $quiet
 * @return void
 */
function show($data = null, $trace_offset = null, $quiet = false){
    return include(__DIR__.'/handlers/debug-show.php');
}



/*
 * Short hand for show and then die
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see show()
 * @see shutdown()
 * @note This function will show() and then die(). This will cause the execution of your web page or command line script to stop, but any and all registered shutdown functions (see shutdown() for more) will still execute
 *
 * @param mixed $data
 * @param integer $trace_offset
 * @return void
 */
function showdie($data = null, $trace_offset = null){
    return include(__DIR__.'/handlers/debug-showdie.php');
}



/*
 * Show nice HTML table with all debug data
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see show()
 *
 * @param mixed $value
 * @param scalar $key
 * @param integer $trace_offset
 * @return
 */
function debug_html($value, $key = null, $trace_offset = 0){
    return include(__DIR__.'/handlers/debug-html.php');
}



/*
 * Show HTML <tr> for the specified debug data
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see show()
 *
 * @param mixed $value
 * @param scalar $key
 * @param string $type
 * @return
 */
function debug_html_row($value, $key = null, $type = null){
    return include(__DIR__.'/handlers/debug-html-row.php');
}



/*
 * Displays the specified query in a show() output
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see show()
 *
 * @param string $query
 * @param array $execute
 * @param boolean $return_only
 * @return
 */
function debug_sql($query, $execute = null, $return_only = false){
    return include(__DIR__.'/handlers/debug-sql.php');
}



/*
 * Returns a filtered debug_backtrace()
 *
 * debug_backtrace() contains all function arguments and can get very clutered. debug_trace() will by default filter the function arguments and return a much cleaner back trace for displaying in debug traces. The function allows other keys to be filtered out if specified
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param mixed $filters A list of keys that should be filtered from the debug_backtrace() output
 * @param boolean $skip_own If specified as true, will skip the debug_trace() call and its handler inclusion from the trace
 * @return array The debug_backtrace() output with the specified keys filtered out
 */
function debug_trace($filters = 'args', $skip_own = true){
    return include(__DIR__.'/handlers/debug-trace.php');
}



/*
 * Return an HTML bar with debug information that can be used to monitor site and fix issues
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @return string The HTML that can be included at the end of the web page which will show the debug bar.
 */
function debug_bar(){
    return include(__DIR__.'/handlers/debug-bar.php');
}



/*
 * Used for ordering entries on the debug bar
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @return
 */
function debug_bar_sort($a, $b){
    try{
        if($a['time'] > $b['time']){
            return -1;

        }elseif($a['time'] < $b['time']){
            return 1;

        }else{
            /*
             * They're the same, so ordering doesn't matter
             */
            return 0;
        }

    }catch(Exception $e){
        throw new BException(tr('debug_bar_sort(): Failed'), $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param mixed $variable
 * @return
 */
function die_in($count, $message = null){
    return include(__DIR__.'/handlers/debug-die-in.php');
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param mixed $variable
 * @return
 */
function variable_zts_safe($variable, $level = 0){
    return include(__DIR__.'/handlers/variable-zts-safe.php');
}



/*
 * Force the specified $source variable to be a clean string
 *
 * A clean string, in this case, means a string data type which contains no HTML code
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
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
function cfm($source, $utf8 = true){
    try{
        return str_clean($source, $utf8);

    }catch(Exception $e){
        throw new BException(tr('cfm(): Failed'), $e);
    }
}



/*
 * Force the specified $source variable to be an integer
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param mixed $source The variable that should be forced to be a integer data type
 * @return float The specified $source variable being a integer datatype
 */
function cfi($source, $allow_null = true){
    try{
        if(!$source and $allow_null){
            return null;
        }

        return (integer) $source;

    }catch(Exception $e){
        throw new BException(tr('cfi(): Failed'), $e);
    }
}



/*
 * Force the specified $source variable to be a float
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param mixed $source The variable that should be forced to be a float data type
 * @return float The specified $source variable being a float datatype
 */
function cf($source, $allow_null = true){
    try{
        if(!$source and $allow_null){
            return null;
        }

        return (float) $source;

    }catch(Exception $e){
        throw new BException(tr('cf(): Failed'), $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param string $value
 * @param boolean $default
 * @return boolean
 */
function get_true_false($value, $default){
    try{
        switch($value){
            case '':
                return $default;

            case tr('n'):
                // FALLTRHOUGH
            case tr('no'):
                return false;

            case tr('y'):
                // FALLTRHOUGH
            case tr('yes'):
                return true;

            default:
                throw new BException(tr('get_true_false(): Please specify y / yes or n / no, or nothing for the default value.'), 'warning');
        }

    }catch(Exception $e){
        throw new BException(tr('get_true_false(): Failed'), $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param boolean $value The true or false value to be asserted
 * @return string "yes" for boolean true, "no" for boolean false
 */
function get_yes_no($value){
    try{
        if($value === true){
            return 'yes';
        }

        if($value === false){
            return 'no';
        }

        throw new BException(tr('get_yes_no(): Please specify true or false'), 'warning');

    }catch(Exception $e){
        throw new BException(tr('get_yes_no(): Failed'), $e);
    }
}



/*
 * THIS FUNCTION SHOULD NOT BE RUN BY ANYBODY! IT IS EXECUTED AUTOMATICALLY ON
 * SHUTDOWN
 *
 * This function facilitates execution of multiple registered shutdown functions
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param boolean $value The true or false value to be asserted
 * @return void
 */
function shutdown(){
    global $core, $_CONFIG;

    try{
        /*
         * Do we need to run other shutdown functions?
         */
        if(empty($core->register['script'])){
           error_log(tr('Shutdown procedure started before $core->register[script] was ready, possibly on script ":script"', array(':script' => $_SERVER['PHP_SELF'])));
           return;
       }

        log_console(tr('Starting shutdown procedure for script ":script"', array(':script' => $core->register['script'])), 'VERYVERBOSE/cyan');

        foreach($core->register as $key => $value){
            try{
                if(substr($key, 0, 9) !== 'shutdown_'){
                    continue;
                }

                $key = substr($key, 9);

                /*
                 * Execute this shutdown function with the specified value
                 */
                if(is_array($value)){
                    /*
                     * Shutdown function value is an array. Execute it for each entry
                     */
                    foreach($value as $entry){
                        log_console(tr('shutdown(): Executing shutdown function ":function" with value ":value"', array(':function' => $key.'()', ':value' => $entry)), 'VERBOSE/cyan');
                        $key($entry);
                    }

                }else{
                    log_console(tr('shutdown(): Executing shutdown function ":function" with value ":value"', array(':function' => $key.'()', ':value' => $value)), 'VERBOSE/cyan');
                    $key($value);
                }

            }catch(Exception $e){
                notify($e);
            }
        }

        /*
         * Periodically execute the following functions
         */
        $level = mt_rand(0, 100);

        if(!empty($_CONFIG['shutdown'])){
            if(!is_array($_CONFIG['shutdown'])){
                throw new BException(tr('shutdown(): Invalid $_CONFIG[shutdown], it should be an array'), 'invalid');
            }

            foreach($_CONFIG['shutdown'] as $name => $parameters){
                if($parameters['interval'] and ($level < $parameters['interval'])){
                    log_file(tr('Executing periodical shutdown function ":function()"', array(':function' => $name)), 'shutdown', 'cyan');
                    load_libs($parameters['library']);
                    $parameters['function']();
                }
            }
        }

    }catch(Exception $e){
        throw new BException(tr('shutdown(): Failed'), $e);
    }
}



/*
 * Register the specified shutdown function
 *
 * This function will ensure that the specified function will be executed on shutdown with the specified value.
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
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
function register_shutdown($name, $value = null){
    global $core;

    try{
        return $core->register('shutdown_'.$name, $value);

    }catch(Exception $e){
        throw new BException('register_shutdown(): Failed', $e);
    }
}



/*
 * Unregister the specified shutdown function
 *
 * This function will ensure that the specified function will not be executed on shutdown
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
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
function unregister_shutdown($name){
    global $core;

    try{
        $value = $core->register('shutdown_'.$name);
        unset($core->register['shutdown_'.$name]);
        return $value;

    }catch(Exception $e){
        throw new BException(tr('unregister_shutdown(): Failed'), $e);
    }
}



/*
 * Set the timeout value for this script
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
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

function set_timeout($timeout = null){
    global $core, $_CONFIG;

    try{
        if($timeout === null){
            $timeout = getenv('TIMEOUT') ? getenv('TIMEOUT') : $_CONFIG['exec']['timeout'];
        }

        $core->register['timeout'] = $timeout;
        set_time_limit($timeout);

    }catch(Exception $e){
        throw new BException(tr('set_timeout(): Failed'), $e);
    }
}



/*
 * Limit the HTTP request to the specified request type, typically GET or POST
 *
 * If the HTTP request is not of the specified type, this function will throw an exception
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @version 2.7.98: Added function and documentation
 *
 * @param params $params A parameters array
 * @return void
 */
function limit_request_method($method){
    try{
        if($_SERVER['REQUEST_METHOD'] !== $method){
            throw new BException(tr('limit_request_method(): This request was made with HTTP method ":server_method" but for this page or call only HTTP method ":method" is allowed', array(':method' => $method, ':server_method' => $_SERVER['REQUEST_METHOD'])), 'warning/method-not-allowed');
        }

    }catch(Exception $e){
        throw new BException(tr('limit_request_method(): Failed'), $e);
    }
}



/*
 * Set PHP locale data from specified configuration. If no configuration was specified, use $_CONFIG[locale] instead
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @version 2.8.15: Added function and documentation
 * @note If LC_ALL is specified, it will be applied first to set the default value for all other parameters. Then all the other parameters will be applied, if specified
 * @note Each LC_* value can contain a :LANGUAGE and :COUNTRY tag. These tags will be replaced with an ISO_639-1 language code / ISO 3166-1 alpha-2 country code respectitively
 * @note If LANGUAGE is not available, the default language in $_CONFIG[language][default] will be used
 * @note If the current country is not available, the default country code US will be assumed
 *
 * @param params $params A parameters array
 * @param optional $string LC_ALL      Default value for all locale parameters
 * @param optional $string LC_COLLATE  Default value for LC_COLLATE parameter
 * @param optional $string LC_CTYPE    Default value for LC_CTYPE parameter
 * @param optional $string LC_MONETARY Default value for LC_MONETARY parameter
 * @param optional $string LC_NUMERIC  Default value for LC_NUMERIC parameter
 * @param optional $string LC_TIME     Default value for all LC_TIME parameter
 * @param optional $string LC_MESSAGES Default value for LC_MESSAGES parameter
 * @return void
 */
function set_locale($data = null){
    global $_CONFIG;

    try{
        if(!$data){
            $data = $_CONFIG['locale'];
        }

        if(!is_array($data)){
            throw new BException(tr('set_locale(): Specified $data should be an array but is an ":type"', array(':type' => gettype($data))), 'invalid');
        }

        /*
         * Determine language and location
         */
        if(defined('LANGUAGE')){
            $language = LANGUAGE;

        }else{
            $language = $_CONFIG['language']['default'];
        }

        if(isset($_SESSION['location']['country']['code'])){
            $country = strtoupper($_SESSION['location']['country']['code']);

        }else{
            $country = $_CONFIG['location']['default_country'];
        }

        /*
         * First set LC_ALL as a baseline, then each individual entry
         */
        if(isset($data[LC_ALL])){
            $data[LC_ALL] = str_replace(':LANGUAGE', $language, $data[LC_ALL]);
            $data[LC_ALL] = str_replace(':COUNTRY' , $country , $data[LC_ALL]);

            setlocale(LC_ALL, $data[LC_ALL]);
            unset($data[LC_ALL]);
        }

        /*
         * Apply all parameters
         */
        foreach($data as $key => $value){
            if($key === 'country'){
                /*
                 * Ignore this key
                 */
                continue;
            }

            if($value){
                /*
                 * Ignore this empty value
                 */
                continue;
            }

            $value = str_replace(':LANGUAGE', $language, $value);
            $value = str_replace(':COUNTRY' , $country , $value);

            setlocale($key, $value);
        }

    }catch(Exception $e){
        throw new BException(tr('set_locale(): Failed'), $e);
    }
}



/*
 * BELOW FOLLOW OBSOLETE FUNCTIONS
 */



/*
 * OBSOLETE
 */
function log_database($messages, $class = 'syslog'){
    try{
        return str_force(log_file($messages, $class));

    }catch(Exception $e){
        throw new BException('log_database(): Failed', $e);
    }
}

function get_config($file = null, $environment = null){
    try{
        return read_config($file, $environment);

    }catch(Exception $e){
        throw new BException('get_config(): Failed', $e);
    }
}

function mapped_domain($url = null, $query = null, $prefix = null, $domain = null, $language = null, $allow_url_cloak = true){
    try{
        return domain($url, $query, $prefix, $domain, $language, $allow_url_cloak);

    }catch(Exception $e){
        throw new BException('mapped_domain(): Failed', $e);
    }
}
