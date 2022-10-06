<?php

namespace Phoundation\Core;

use Phoundation\Cli\Cli;
use Phoundation\Cli\Scripts;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Developer\Debug;
use Phoundation\Exception\Exceptions;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhpException;
use Phoundation\Http\Html\Html;
use Phoundation\Http\Http;
use Phoundation\Notify\Notification;
use Phoundation\Utils\Json;
use Phoundation\Web\Route;
use Phoundation\Web\Web;
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
        'js_header' => [],
        'js_footer' => [],
        'css' => [],
        'quiet' => true,
        'footer' => '',
        'debug_queries' => []
    ];

    /**
     * Keeps track of if the core is ready or not
     *
     * @var bool
     */
    protected static bool $ready = false;



    /**
     * Initialize the class object through the constructor.
     *
     * Core constructor.
     */
    protected function __construct()
    {
        try {
            // Register the process start
            Timer::create('process');

            /*
             * Define a unique process request ID
             * Define project paths.
             *
             * ROOT   is the root directory of this project and should be used as the root for all other paths
             * TMP    is a private temporary directory
             * PUBTMP is a public (accessible by web server) temporary directory
             */
            define('REQUEST', substr(uniqid(), 7));
            define('ROOT', realpath(__DIR__ . '/../..') . '/');
            define('TMP', ROOT . 'data/tmp/');
            define('PUBTMP', ROOT . 'data/content/tmp/');
            define('CRLF', "\r\n");

            /*
             * Setup error handling, report ALL errors
             */
            error_reporting(E_ALL);
            set_error_handler(['\Phoundation\Core\Core', 'phpErrorHandler']);
            set_exception_handler(['\Phoundation\Core\Core', 'uncaughtException']);

            // Load the functions file
            require(ROOT . 'Phoundation/functions.php');

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
                    self::$ready = true;

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

                // Died in CLI
                die('startup: Failed with "' . $e->getMessage() . '"');
            }

            // Wowza things went to @#*$@( really fast! The standard defines aren't even available yet
            error_log('startup: Failed with "' . $e->getMessage() . '"');
            die('startup: Failed, see error log' . PHP_EOL);
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
     * The core::startup() method will start up the core class
     *
     * This method starts the correct call type handler
     *
     * @return void
     */
    public static function startup(): void
    {
        try {
            self::getInstance();
            self::$register['startup'] = microtime(true);

            // Detect platform and execute specific platform startup sequence
            switch (PLATFORM) {
                case 'http':
                    /*
                     * Determine what our target file is. With direct execution,
                     * $_SERVER[PHP_SELF] would contain this, with route
                     * execution, $_SERVER[PHP_SELF] would be route, so we
                     * cannot use that. Route will store the file being executed
                     * in self::$register['script_path'] instead
                     */
                    if (isset(self::$register['script_path'])) {
                        $file = '/' . self::$register['script_path'];

                    } else {
                        $file = '/' . $_SERVER['PHP_SELF'];
                    }

                    /*
                     * Auto detect what http call type we're on from the script
                     * being executed
                     */
                    if (str_contains($file, '/admin/')) {
                        self::$call_type = 'admin';

                    } elseif (str_contains($file, '/ajax/')) {
                        self::$call_type = 'ajax';

                    } elseif (str_contains($file, '/api/')) {
                        self::$call_type = 'api';

                    } elseif ((str_starts_with($_SERVER['SERVER_NAME'], 'api')) and preg_match('/^api(?:-[0-9]+)?\./', $_SERVER['SERVER_NAME'])) {
                        self::$call_type = 'api';

                    } elseif ((str_starts_with($_SERVER['SERVER_NAME'], 'cdn')) and preg_match('/^cdn(?:-[0-9]+)?\./', $_SERVER['SERVER_NAME'])) {
                        self::$call_type = 'api';

                    } elseif (Config::get('web.html.amp.enabled', false) and !empty($_GET['amp'])) {
                        self::$call_type = 'amp';

                    } elseif (is_numeric(substr($file, -3, 3))) {
                        self::$register['http']['code'] = substr($file, -3, 3);
                        self::$call_type = 'system';

                    } else {
                        self::$call_type = 'http';
                    }

                    // Set timeout and define basic platform constants
                    self::setTimeout();

                    define('ADMIN'   , '');
                    define('PWD'     , Strings::slash(isset_get($_SERVER['PWD'])));
                    define('STARTDIR', Strings::slash(getcwd()));
                    define('FORCE'   , (getenv('FORCE')   ? 'FORCE'   : null));
                    define('TEST'    , (getenv('TEST')    ? 'TEST'    : null));
                    define('QUIET'   , (getenv('QUIET')   ? 'QUIET'   : null));
                    define('LIMIT'   , (getenv('LIMIT')   ? 'LIMIT'   : Config::get('paging.limit', 50)));
                    define('ORDERBY' , (getenv('ORDERBY') ? 'ORDERBY' : null));
                    define('ALL'     , (getenv('ALL')     ? 'ALL'     : null));
                    define('DELETED' , (getenv('DELETED') ? 'DELETED' : null));
                    define('STATUS'  , (getenv('STATUS')  ? 'STATUS'  : null));

                    // Check HEAD and OPTIONS requests. If HEAD was requested, just return basic HTTP headers
// :TODO: Should pages themselves not check for this and perhaps send other headers?
                    switch ($_SERVER['REQUEST_METHOD'] ) {
                        case 'OPTIONS':
                            Exceptions::underConstruction();
                    }

                    // Set security umask
                    umask(Config::get('filesystem.umask', 0007));

                    /*
                     * Set language data
                     *
                     * This is normally done by checking the current dirname of the startup file, this will be
                     * LANGUAGECODE/libs/handlers/system-webpage.php
                     */
                    try {
                        $supported = Config::get('languages.supported', ['en' => []]);

                        if ($supported) {
                            // Language is defined by the www/LANGUAGE dir that is used.
                            $url = Route::getRequestUri();

                            if (empty($url)) {
                                $url      = $_SERVER['REQUEST_URI'];
                                $url      = Strings::startsNotWith($url, '/');
                                $language = Strings::until($url, '/');

                                if (!array_key_exists($language, $supported)) {
                                    Log::warning(tr('Detected language ":language" is not supported, falling back to default. See $_CONFIG[language][supported]', [':language' => $language]));
                                    $language = Config::get('languages.default', 'en');
                                }

                            } else {
                                $language = substr($url, 0, 2);

                                if (!array_key_exists($language, $supported)) {
                                    Log::warning(tr('Detected language ":language" is not supported, falling back to default. See $_CONFIG[language][supported]', [':language' => $language]));
                                    $language = Config::get('languages.default', 'en');
                                }
                            }

                        } else {
                            $language = Config::get('languages.default', 'en');
                        }

                        define('LANGUAGE', $language);
                        define('LOCALE'  , $language.(empty($_SESSION['location']['country']['code']) ? '' : '_'.$_SESSION['location']['country']['code']));

                        /*
                         * Ensure $_SESSION['language'] available
                         */
                        if (empty($_SESSION['language'])) {
                            $_SESSION['language'] = LANGUAGE;
                        }

                    }catch(Throwable $e) {
                        /*
                         * Language selection failed
                         */
                        if (!defined('LANGUAGE')) {
                            define('LANGUAGE', 'en');
                        }

                        $e = new OutOfBoundsException('Language selection failed', $e);
                    }

                    // Setup locale and character encoding
                    // TODO Check this mess!
                    ini_set('default_charset', Config::get('encoding.charset', 'UTF8'));
                    self::$register['system']['locale'] = self::setLocale();

                    // Prepare for unicode usage
                    if (Config::get('encoding.charset', 'UTF8') === 'UTF-8') {
                        mb_init(not_empty($_CONFIG['locale'][LC_CTYPE], $_CONFIG['locale'][LC_ALL]));

                        if (function_exists('mb_internal_encoding')) {
                            mb_internal_encoding('UTF-8');
                        }
                    }

                    // Check for configured maintenance mode
                    if (Config::get('system.maintenance', false)) {
                        // We are in maintenance mode, have to show mainenance page.
                        Web::execute(503);
                    }

                    // Set cookie, start session where needed, etc.
                    include(ROOT.'libs/handlers/system-manage-session.php');

                    // Set timezone, see http://www.php.net/manual/en/timezones.php for more info
                    try {
                        date_default_timezone_set($_CONFIG['timezone']['system']);

                    }catch(Throwable $e) {
                        /*
                         * Users timezone failed, use the configured one
                         */
                        Notification::getInstance()
                            ->setException($e)
                            ->send();
                    }

                    define('TIMEZONE', isset_get($_SESSION['user']['timezone'], $_CONFIG['timezone']['display']));

                    // If POST request, automatically untranslate translated POST entries
                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        Html::untranslate();
                        Html::fixCheckboxValues();

                        if ($_CONFIG['security']['csrf']['enabled'] === 'force') {
                            /*
                             * Force CSRF checks on every submit!
                             */
                            Http::checkCsrf();
                        }
                    }

                    // Set the CDN url for javascript and validate HTTP GET request data
                    Html::setJsCdnUrl();
                    Http::validateGet();

                    // Did the startup sequence encounter reasons for us to actually show another page?
                    if (isset(self::$register['web']['page_show'])) {
                        Web::execute(self::$register['page_show']);
                    }

                    break;

                case 'cli':
                    self::$call_type = 'cli';
                    // Make sure we have the original arguments available
                    putenv('TIMEOUT='.cli_argument('--timeout', true));

                    // Define basic platform constants
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

                    // Check what environment we're in
                    $environment = cli_argument('-E,--env,--environment', true);

                    if (empty($environment)) {
                        $env = getenv(PROJECT.'_ENVIRONMENT');

                        if (empty($env)) {
                            echo "\033[0;31mstartup: No required environment specified for project \"".PROJECT."\"\033[0m\n";
                            self::$register['exit_code'] = 2;
                            die(2);
                        }

                    } else {
                        $env = $environment;
                    }

                    if (str_contains($env, '_')) {
                        echo "\033[0;31mstartup: Specified environment \"$env\" is invalid, environment names cannot contain the underscore character\033[0m\n";
                        Scripts::setExitCode(4);
                        die(4);
                    }

                    define('ENVIRONMENT', $env);

                    if (!file_exists(ROOT.'config/'.$env.'.php')) {
                        echo "\033[0;31mstartup: Configuration file \"ROOT/config/".$env.".php\" for specified environment\"".$env."\" not found\033[0m\n";
                        Scripts::setExitCode(5);
                        die(5);
                    }

                    // Set protocol
                    define('PROTOCOL', Config::get('web.protocol', 'https://'));

                    // Process command line system arguments if we have no exception so far
                    if (empty($e)) {
                        // Correct $_SERVER['PHP_SELF'], sometimes seems empty
                        if (empty($_SERVER['PHP_SELF'])) {
                            if (!isset($_SERVER['_'])) {
                                $e = new OutOfBoundsException('No $_SERVER[PHP_SELF] or $_SERVER[_] found');
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
                                    Log::information(tr('BASE framework code version ":fv", project code version ":pv"', [':fv' => FRAMEWORKCODEVERSION, ':pv' => PROJECTCODEVERSION]));
                                    $die = 0;
                                    break;

                                case '-U':
                                    // no-break
                                case '--usage':
                                    // no-break
                                case 'usage':
                                    Cli::showUsage(isset_get($GLOBALS['usage']), 'white');
                                    $die = 0;
                                    break;

                                case '-H':
                                    // no-break
                                case '--help':
                                    // no-break
                                case 'help':
                                    if (isset_get($GLOBALS['argv'][$argid + 1]) == 'system') {
                                        Cli::showHelp('system');

                                    } else {
                                        if (empty($GLOBALS['help'])) {
                                            $e = new CoreException(tr('Sorry, this script has no help text defined'), 'warning');
                                        }

                                        $GLOBALS['help'] = Arrays::force($GLOBALS['help'], "\n");

                                        if (count($GLOBALS['help']) == 1) {
                                            Log::information(array_shift($GLOBALS['help']));

                                        } else {
                                            foreach (Arrays::force($GLOBALS['help'], "\n") as $line) {
                                                Log::information($line);
                                            }

                                            Log::information();
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
                                        $e = new CoreException(tr('Language has been specified twice'));
                                    }

                                    if (!isset($GLOBALS['argv'][$argid + 1])) {
                                        $e = new CoreException(tr('The "language" argument requires a two letter language core right after it'));
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
                                //        $e = new CoreException(tr('Environment has been specified twice'), 'exists');
                                //    }
                                //
                                //    if (!isset($GLOBALS['argv'][$argid + 1])) {
                                //        $e = new CoreException(tr('The "environment" argument requires an existing environment name right after it'), 'invalid');
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
                                        $e = new CoreException(tr('The specified orderby argument ":argument" is invalid', [':argument' => ORDERBY]));
                                    }

                                    unset($GLOBALS['argv'][$argid]);
                                    unset($GLOBALS['argv'][$argid + 1]);
                                    break;

                                case '--timezone':
                                    // Set timezone
                                    if (isset($timezone)) {
                                        $e = new CoreException(tr('Timezone has been specified twice'), 'exists');
                                    }

                                    if (!isset($GLOBALS['argv'][$argid + 1])) {
                                        $e = new CoreException(tr('The "timezone" argument requires a valid and existing timezone name right after it'));

                                    }

                                    $timezone = $GLOBALS['argv'][$argid + 1];

                                    unset($GLOBALS['argv'][$argid]);
                                    unset($GLOBALS['argv'][$argid + 1]);
                                    break;

                                case '-I':
                                    // no-break
                                case '--skip-init-check':
                                    // Skip init check for the core database
                                    self::register['system']['skip_init_check'] = true;
                                    break;

                                default:
                                    // This is not a system parameter, ignore for now as it will be processed later
                                    break;
                            }
                        }

                        unset($arg);
                        unset($argid);

                        if (!defined('ORDERBY')) {
                            define('ORDERBY', '');
                        }
                    }

                    // Remove the command itself from the argv array
                    array_shift($GLOBALS['argv']);

                    // Set timeout
                    self::setTimeout();

                    // Something failed?
                    if (isset($e)) {
                        echo "startup-cli: Command line parser failed with \"".$e->getMessage()."\"\n";
                        Scripts::setExitCode(1);
                        die(1);
                    }

                    if (isset($die)) {
                        Core::$ready = true;
                        Scripts::setExitCode($die);
                        die($die);
                    }

                    // set terminal data
                    self::$register['cli'] = ['term' => Cli::getTerm()];

                    if (self::$register['cli']['term']) {
                        self::$register['cli']['columns'] = Cli::getColumns();
                        self::$register['cli']['lines']   = Cli::getLines();

                        if (!self::$register['cli']['columns']) {
                            self::$register['cli']['size'] = 'unknown';

                        } elseif (self::$register['cli']['columns'] <= 80) {
                            self::$register['cli']['size'] = 'small';

                        } elseif (self::$register['cli']['columns'] <= 160) {
                            self::$register['cli']['size'] = 'medium';

                        } else {
                            self::$register['cli']['size'] = 'large';
                        }
                    }

                    // Set security umask
                    umask(Config::get('filesystem.umask', 0007);

                    // Ensure that the process UID matches the file UID
                    Cli::processFileUidMatches(true);
                    log_file(tr('Running script ":script"', array(':script' => $_SERVER['PHP_SELF'])), 'startup', 'cyan');



                    /*
                     * Get required language.
                     */
                    try {
                        $language = not_empty(cli_argument('--language'), cli_argument('L'), $_CONFIG['language']['default']);

                        if ($_CONFIG['language']['supported'] and !isset($_CONFIG['language']['supported'][$language])) {
                            throw new CoreException(tr('Unknown language ":language" specified', array(':language' => $language)), 'unknown');
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

                        $e = new CoreException('Language selection failed', $e);
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
                    self::$register['ready'] = true;

                    if (cli_argument('-D,--debug')) {
                        Debug::enabled();
                    }



                    /*
                     * Set more system parameters
                     */
                    self::$register['all']         = cli_argument('-A,--all');
                    self::$register['page']        = not_empty(cli_argument('-P,--page', true), 1);
                    self::$register['limit']       = not_empty(cli_argument('--limit'  , true), $_CONFIG['paging']['limit']);
                    self::$register['clean_debug'] = cli_argument('--clean-debug');



                    /*
                     * Validate parameters
                     * Give some startup messages, if needed
                     */
                    if (VERBOSE) {
                        if (QUIET) {
                            throw new CoreException(tr('Both QUIET and VERBOSE have been specified but these options are mutually exclusive. Please specify either one or the other'), 'warning/invalid');
                        }

                        if (VERYVERBOSE) {
                            log_console(tr('Running in VERYVERBOSE mode, started @ ":datetime"', array(':datetime' => date_convert(STARTTIME, 'human_datetime'))), 'white');

                        } else {
                            log_console(tr('Running in VERBOSE mode, started @ ":datetime"', array(':datetime' => date_convert(STARTTIME, 'human_datetime'))), 'white');
                        }

                        log_console(tr('Detected ":size" terminal with ":columns" columns and ":lines" lines', array(':size' => self::$register['cli']['size'], ':columns' => self::$register['cli']['columns'], ':lines' => self::$register['cli']['lines'])));
                    }

                    if (FORCE) {
                        if (TEST) {
                            throw new CoreException(tr('Both FORCE and TEST modes where specified, these modes are mutually exclusive'), 'invalid');
                        }

                        log_console(tr('Running in FORCE mode'), 'yellow');

                    } elseif (TEST) {
                        log_console(tr('Running in TEST mode'), 'yellow');
                    }

                    if (Debug::enabled()) {
                        log_console(tr('Running in DEBUG mode'), 'VERBOSE/yellow');
                    }

                    if (!is_natural(self::$register['page'])) {
                        throw new CoreException(tr('paging_library_init(): Specified -P or --page ":page" is not a natural number', array(':page' => self::$register['page'])), 'invalid');
                    }

                    if (!is_natural(self::$register['limit'])) {
                        throw new CoreException(tr('paging_library_init(): Specified --limit":limit" is not a natural number', array(':limit' => self::$register['limit'])), 'invalid');
                    }

                    if (self::$register['all']) {
                        if (self::$register['page'] > 1) {
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
                    if (isset(self::$register['page_show'])) {
                        page_show(self::$register['page_show']);
                    }

                    /*
                     * Setup language map in case domain() calls are used
                     */
                    load_libs('route');
                    route_map();
                    break;
            }


            /*
             * Set timeout for this request
             */
            self::setTimeout();

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

        } catch (Throwable $e) {
            if (PLATFORM_HTTP and headers_sent($file, $line)) {
                if (preg_match('/debug-.+\.php$/', $file)) {
                    throw new OutOfBoundsException(tr('Failed because headers were already sent on ":location", so probably some added debug code caused this issue', array(':location' => $file . '@' . $line)), $e);
                }

                throw new OutOfBoundsException(tr('Failed because headers were already sent on ":location"', array(':location' => $file . '@' . $line)), $e);
            }

            throw new OutOfBoundsException(tr('Failed calltype ":calltype"', array(':calltype' => self::$call_type)), $e);
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
    public static function readRegister(string $key, string $subkey): mixed
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
     * Compare the specified value with the registered value for the specified key / sub key in the core register.
     *
     * @note Will return NULL if the specified key does not exist
     * @param mixed $value
     * @param string $key
     * @param string|null $subkey
     * @return bool
     */
    public static function compareRegister(mixed $value, string $key,?string $subkey = null): bool
    {
        return $value === isset_get(self::$register[$key][$subkey]);
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
    public static function executedQuery($query_data)
    {
        self::$register['debug_queries'][] = $query_data;
        return count(self::$register['debug_queries']);
    }



    /**
     * This method will return the calltype for this call, as is stored in the private variable core::callType
     *
     * @return string Returns core::callType
     */
    public static function getCallType(): string
    {
        return self::$call_type;
    }



    /**
     * Will return true if $call_type is equal to core::callType, false if not.
     *
     * @param string $type The call type you wish to compare to
     * @return bool This function will return true if $type matches core::callType, or false if it does not.
     */
    public static function isCallType(string $type): bool
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
    function static getLanguage($language)
    {
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
    }



    /**
     * Convert all PHP errors in exceptions. With this function the entirety of base works only with exceptions, and
     * function output normally does not need to be checked for errors.
     *
     * NOTE: This method should never be called directly
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return void
     * @throws PhpException
     */
    public static function phpErrorHandler(int $errno, string $errstr, string $errfile, int $errline): void
    {
        if (!self::$ready) {
            throw new PhpException('Pre system ready PHP ERROR [' . $errno . '] "' . $errstr . '" in "' . $errfile . '@' . $errline . '"', array(':errstr' => $errstr, ':errno' => $errno, ':errfile' => $errfile, ':errline' => $errline));
        }

        $trace = Debug::backtrace();
        unset($trace[0]);
        unset($trace[1]);

        Notification::getInstance()
            ->setCode('PHP-ERROR-' . $errno)
            ->addGroup('developers')
            ->setTitle(tr('PHP ERROR ":errno"', [':errno' => $errno]))
            ->setMessage(tr('PHP ERROR [:errno] ":errstr" in ":errfile@:errline"', [':errstr' => $errstr, ':errno' => $errno, ':errfile' => $errfile, ':errline' => $errline]))
            ->setData([
                'errno' => $errno,
                'errstr' => $errstr,
                'errfile' => $errfile,
                'errline' => $errline,
                'trace' => $trace
            ])->send();

        throw new PhpException(tr('PHP ERROR [:errno] ":errstr" in ":errfile@:errline"', [':errstr' => $errstr, ':errno' => $errno, ':errfile' => $errfile, ':errline' => $errline]), [':errstr' => $errstr, ':errno' => $errno, ':errfile' => $errfile, ':errline' => $errline, ':trace' => $trace], 'PHP'.$errno);
    }



    /**
     * This function is called automaticaly
     *
     * @param Throwable $e
     * @param boolean $die Specify false if this exception should be a warning and continue, true if it should die
     * @return void
     * @note: This function should never be called directly
     */
    public static function uncaughtException(Throwable $e, bool $die = true): void
    {
die('UNCAUGHTEXCEPTION');
//if (!headers_sent()) {header_remove('Content-Type'); header('Content-Type: text/html', true);} echo "<pre>\nEXCEPTION CODE: "; print_r($e->getCode()); echo "\n\nEXCEPTION:\n"; print_r($e); echo "\n\nBACKTRACE:\n"; print_r(debug_backtrace()); die();
        /*
         * Phoundation uncaught exception handler
         *
         * IMPORTANT! IF YOU ARE FACED WITH AN UNCAUGHT EXCEPTION, OR WEIRD EFFECTS LIKE
         * WHITE SCREEN, ALWAYS FOLLOW THESE STEPS:
         *
         *    Check the ROOT/data/log/syslog (or exception log if you have single_log
         *    disabled). In here you can find 99% of the issues
         *
         *    If the syslog did not contain information, then check your apache / nginx
         *    or PHP error logs. Typically you will find these in /var/log/php and
         *    /var/log/apache2 or /var/log/nginx
         *
         *    If that gives you nothing, then try uncommenting the line in the section
         *    right below these comments. This will forcibly display the error
         */

        /*
         * If you are faced with an uncaught exception that does not give any
         * information (for example, "exception before platform detection", or
         * "pre ready exception"), uncomment the files line of this file to see whats up.
         *
         * The reason that this is normally commented out and that logging or displaying
         * your errors might fail is for security, as Phoundation may not know at the
         * point where your error occurred if it is on a production environment or not.
         *
         * For cases like these, uncomment the following lines and you should see your
         * error displayed on your browser.
         */
        static $executed = false;

        try {
            try {
                if ($executed) {
                    /*
                     * We seem to be stuck in an uncaught exception loop, cut it out now!
                     */
                    // :TODO: ADD NOTIFICATIONS OF STUFF GOING FUBAR HERE!
                    die('exception loop detected');
                }

                $executed = true;

                if (empty(self::readRegister('script'))) {
                    Core::readRegister('script', 'unknown');
                }

                if (self::$ready) {
                    Log::error(tr('*** UNCAUGHT EXCEPTION ":code" IN ":type" TYPE SCRIPT ":script" ***', [':code' => $e->getCode(), ':type' => Core::getCallType(), ':script' => isset_get(self::readRegister('script'))]));
                    Log::error($e, 'uncaught-exception', 'exception');

                } else {
                    /*
                     * System is not ready, we cannot log to syslog
                     */
                    error_log(tr('*** UNCAUGHT PRE-CORE-READY EXCEPTION ":code" ***', array(':code' => $e->getCode())));
                    error_log($e->getMessage());
                    die(1);
                }

                if (!defined('PLATFORM')) {
                    /*
                     * Wow, system crashed before platform detection.
                     */
                    die('exception before platform detection');
                }

                switch (PLATFORM) {
                    case 'cli':
                        /*
                         * Ensure that required defines are available
                         */
                        if (!defined('VERYVERBOSE')) {
                            define('VERYVERBOSE', (cli_argument('-VV,--very-verbose') ? 'VERYVERBOSE' : null));
                        }

                        self::setTimeout(1);

                        $defines = [
                            'ADMIN'    => '',
                            'PWD'      => Strings::slash(isset_get($_SERVER['PWD'])),
                            'VERBOSE'  => ((VERYVERBOSE or cli_argument('-V,--verbose,-V2,--very-verbose')) ? 'VERBOSE' : null),
                            'QUIET'    => cli_argument('-Q,--quiet'),
                            'FORCE'    => cli_argument('-F,--force'),
                            'TEST'     => cli_argument('-T,--test'),
                            'LIMIT'    => not_empty(cli_argument('--limit'  , true), $_CONFIG['paging']['limit']),
                            'ALL'      => cli_argument('-A,--all'),
                            'DELETED'  => cli_argument('--deleted'),
                            'STATUS'   => cli_argument('-S,--status' , true),
                            'STARTDIR' => Strings::slash(getcwd())
                        ];

                        foreach ($defines as $key => $value) {
                            if (!defined($key)) {
                                define($key, $value);
                            }
                        }

                        Notification::getInstance()
                            ->setException($e)
                            ->send();

                        // Specified arguments were wrong
                        // TODO CHANGE PARAMETERS TO "ARGUMENTS"
                        if ($e->getCode() === 'parameters') {
                            Log::warning(trim(Strings::from($e->getMessage(), '():')));
                            $GLOBALS['core'] = false;
                            die(1);
                        }

                        if (!self::$ready) {
                            /*
                             * Configuration hasn't been loaded yet, we cannot even know if
                             * we are in debug mode or not!
                             *
                             * Log to the webserver error log files at the very least
                             */
                            if (method_exists($e, 'getMessages')) {
                                foreach ($e->getMessages() as $message) {
                                    error_log($message);
                                }

                            } else {
                                error_log($e->getMessage());
                            }

                            echo "\033[1;31mPre ready exception. Please check your ROOT/data/log directory or application or webserver error log files, or enable the first line in the exception handler file for more information\033[0m\n";
                            print_r($e);
                            die("\033[1;31mPre ready exception. Please check your ROOT/data/log directory or application or webserver error log files, or enable the first line in the exception handler file for more information\033[0m\n");
                        }

                        /*
                         * Command line script crashed.
                         *
                         * If not using VERBOSE mode, then try to give nice error messages
                         * for known issues
                         */
                        if (!VERBOSE) {
                            if (Strings::until($e->getCode(), '/') === 'warning') {
                                /*
                                 * This is just a simple general warning, no backtrace and
                                 * such needed, only show the principal message
                                 */
                                Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
                                Scripts::setExitCode(255);
                                die(Scripts::getExitCode());
                            }

                            switch ((string) $e->getCode()) {
                                case 'already-running':
                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
                                    Scripts::setExitCode(254);
                                    die(Scripts::getExitCode());

                                case 'no-method':
                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
                                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                                    Scripts::setExitCode(253);
                                    die(Scripts::getExitCode());

                                case 'unknown-method':
                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
                                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                                    Scripts::setExitCode(252);
                                    die(Scripts::getExitCode());

                                case 'missing-arguments':
                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
                                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                                    Scripts::setExitCode(253);
                                    die(Scripts::getExitCode());

                                case 'invalid-arguments':
                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
                                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                                    Scripts::setExitCode(251);
                                    die(Scripts::getExitCode());

                                case 'validation':
                                    if (self::readRegister('script') === 'init') {
                                        /*
                                         * In the init script, all validations are fatal!
                                         */
                                        $e->makeWarning(false);
                                        break;
                                    }

                                    if (method_exists($e, 'getMessages')) {
                                        $messages = $e->getMessages();

                                    } else {
                                        $messages = $e->getMessage();
                                    }

                                    if (count($messages) > 2) {
                                        array_pop($messages);
                                        array_pop($messages);
                                        Log::warning(tr('Validation failed'));
                                        Log::warning($messages, 'yellow');

                                    } else {
                                        Log::warning($messages);
                                    }

                                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                                    Scripts::setExitCode(250);
                                    die(Scripts::getExitCode());
                            }
                        }

                        Log::error(tr('*** UNCAUGHT EXCEPTION ":code" IN CONSOLE SCRIPT ":script" ***', array(':code' => $e->getCode(), ':script' => self::readRegister('script'))));
                        Debug::enabled(true);

                        if ($e instanceof CoreException) {
                            if ($e->getCode() === 'no-trace') {
                                $messages = $e->getMessages();
                                Log::error(array_pop($messages));

                            } else {
                                /*
                                 * Show the entire exception
                                 */
                                $messages = $e->getMessages();
                                $data     = $e->getData();
                                $code     = $e->getCode();
                                $file     = $e->getFile();
                                $line     = $e->getLine();
                                $trace    = $e->getTrace();

                                Log::error(tr('Exception code    : ":code"'      , array(':code' => $code))                  );
                                Log::error(tr('Exception location: ":file@:line"', array(':file' => $file, ':line' => $line)));
                                Log::error(tr('Exception messages trace:'));

                                foreach ($messages as $message) {
                                    Log::error('    '.$message);
                                }

                                Log::error('    '.self::readRegister('script').': Failed');
                                Log::error(tr('Exception function trace:'));

                                if ($trace) {
                                    Log::error(Strings::Log($trace));

                                } else {
                                    Log::error(tr('N/A'));
                                }

                                if ($data) {
                                    Log::error(tr('Exception data:'));
                                    Log::error(Strings::Log($data));
                                }
                            }

                        } else {
                            /*
                             * Treat this as a normal PHP Exception object
                             */
                            if ($e->getCode() === 'no-trace') {
                                Log::error($e->getMessage());

                            } else {
                                /*
                                 * Show the entire exception
                                 */
                                show($e, null, true);
                            }
                        }

                        Scripts::setExitCode(64);
                        die(8);

                    case 'http':
                        /*
                         * Remove all caching headers
                         */
                        if (!headers_sent()) {
                            header_remove('ETag');
                            header_remove('Cache-Control');
                            header_remove('Expires');
                            header_remove('Content-Type');
                        }

                        //
                        Http::setStatusCode(500);
                        self::unregisterShutdown(['Route', '404']);

                        // Ensure that required defines are available
                        if (!defined('VERYVERBOSE')) {
                            define('VERYVERBOSE', (getenv('VERYVERBOSE') ? 'VERYVERBOSE' : null));
                        }

                        Log::error($e);

                        $defines = [
                            'ADMIN'    => '',
                            'PWD'      => Strings::slash(isset_get($_SERVER['PWD'])),
                            'STARTDIR' => Strings::slash(getcwd()),
                            'FORCE'    => (getenv('FORCE')                    ? 'FORCE'   : null),
                            'TEST'     => (getenv('TEST')                     ? 'TEST'    : null),
                            'VERBOSE'  => ((VERYVERBOSE or getenv('VERBOSE')) ? 'VERBOSE' : null),
                            'QUIET'    => (getenv('QUIET')                    ? 'QUIET'   : null),
                            'LIMIT'    => (getenv('LIMIT')                    ? 'LIMIT'   : $_CONFIG['paging']['limit']),
                            'ORDERBY'  => (getenv('ORDERBY')                  ? 'ORDERBY' : null),
                            'ALL'      => (getenv('ALL')                      ? 'ALL'     : null),
                            'DELETED'  => (getenv('DELETED')                  ? 'DELETED' : null),
                            'STATUS'   => (getenv('STATUS')                   ? 'STATUS'  : null)
                        ];

                        foreach ($defines as $key => $value) {
                            if (!defined($key)) {
                                define($key, $value);
                            }
                        }

                        Notification::getInstance()
                            ->setException($e)
                            ->send();

                        if (!self::$ready) {
                            /*
                             * Configuration hasn't been loaded yet, we cannot even know
                             * if we are in debug mode or not!
                             *
                             * Try sending the right response code and content type
                             * headers so that at least there will be a visible page
                             * with the right mimetype
                             */
                            if (!headers_sent()) {
                                header('Content-Type: text/html', true);
                            }


                            if (method_exists($e, 'getMessages')) {
                                foreach ($e->getMessages() as $message) {
                                    error_log($message);
                                }

                            } else {
                                error_log($e->getMessage());
                            }

                            die(tr('Pre ready exception. Please check your ROOT/data/log directory or application or webserver error log files, or enable the first line in the exception handler file for more information'));
                        }

                        if ($e->getCode() === 'validation') {
                            $e->setCode(400);
                        }

                        if (($e instanceof CoreException) and is_numeric($e->getRealCode()) and ($e->getRealCode() > 100) and page_show($e->getRealCode(), array('exists' => true))) {
                            if ($e->isWarning()) {
                                html_flash_set($e->getMessage(), 'warning', $e->getRealCode());
                            }

                            log_file(tr('Displaying exception page ":page"', array(':page' => $e->getRealCode())), 'exceptions', 'error');
                            page_show($e->getRealCode(), array('message' =>$e->getMessage()));
                        }

                        if (Debug::enabled()) {
                            /*
                             * We're trying to show an html error here!
                             */
                            if (!headers_sent()) {
                                http_response_code(500);
                                header('Content-Type: text/html', true);
                            }

                            switch (Core::getCallType()) {
                                case 'api':
                                    // no-break
                                case 'ajax':
                                    echo "UNCAUGHT EXCEPTION\n\n";
                                    showdie($e);
                            }

                            $retval = ' <style type="text/css">
                                table.exception{
                                    font-family: sans-serif;
                                    width:99%;
                                    background:#AAAAAA;
                                    border-collapse:collapse;
                                    border-spacing:2px;
                                    margin: 5px auto 5px auto;
                                }
                                td.center{
                                    text-align: center;
                                }
                                table.exception thead{
                                    background: #CE0000;
                                    color: white;
                                    font-weight: bold;
                                }
                                table.exception td{
                                    border: 1px solid black;
                                    padding: 15px;
                                }
                                table.exception td.value{
                                    word-break: break-all;
                                }
                                table.debug{
                                    background:#AAAAAA !important;
                                }
                                table.debug thead{
                                    background: #CE0000 !important;
                                    color: white;
                                }
                                table.debug .debug-header{
                                    display: none;
                                }
                                </style>
                                <table class="exception">
                                    <thead>
                                        <td colspan="2" class="center">
                                            '.tr('*** UNCAUGHT EXCEPTION ":code" IN ":type" TYPE SCRIPT ":script" ***', array(':code' => $e->getCode(), ':script' => self::readRegister('script'), 'type' => Core::getCallType())).'
                                        </td>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="2" class="center">
                                                '.tr('An uncaught exception with code ":code" occured in script ":script". See the exception core dump below for more information on how to fix this issue', array(':code' => $e->getCode(), ':script' => self::readRegister('script'))).'
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                '.tr('File').'
                                            </td>
                                            <td>
                                                '.$e->getFile().'
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                '.tr('Line').'
                                            </td>
                                            <td>
                                                '.$e->getLine().'
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>';

                            echo $retval;

                            if ($e instanceof CoreException) {
                                // Clean data
                                $e->setData(Arrays::hide(Arrays::force($e->getData()), 'GLOBALS,%pass,ssh_key'));
                            }

                            showdie($e);
                        }

                        // We're not in debug mode.
                        Notification::getInstance()
                            ->setException($e)
                            ->send();

                        switch (Core::getCallType()) {
                            case 'api':
                                // no-break
                            case 'ajax':
                                if ($e instanceof CoreException) {
                                    Json::message($e->getRealCode(), ['reason' => ($e->isWarning() ? trim(Strings::from($e->getMessage(), ':')) : '')]);
                                }

                                /*
                                 * Assume that all non CoreException exceptions are not
                                 * warnings!
                                 */
                            Json::message($e->getCode(), ['reason' => '']);
                        }

                        Web::execute($e->getCode());
                }

            }catch(Throwable $f) {
//                if (!isset($core)) {
//                    error_log(tr('*** UNCAUGHT PRE CORE AVAILABLE EXCEPTION HANDLER CRASHED ***'));
//                    error_log(tr('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***'));
//                    error_log($f->getMessage());
//                    die('Pre core available exception with handling failure. Please your application or webserver error log files, or enable the first line in the exception handler file for more information');
//                }

                if (!defined('PLATFORM') or !self::$ready) {
                    error_log(tr('*** UNCAUGHT PRE READY EXCEPTION HANDLER CRASHED FOR SCRIPT ":script" ***', array(':script' => self::readRegister('script'))));
                    error_log(tr('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***'));
                    error_log($f->getMessage());
                    die('Pre core ready exception with handling failure. Please check your ROOT/data/log directory or application or webserver error log files, or enable the first line in the exception handler file for more information');
                }

                Log::error('STARTUP-UNCAUGHT-EXCEPTION HANDLER CRASHED!');
                Log::error($f);

                switch (PLATFORM) {
                    case 'cli':
                        Log::error(tr('*** UNCAUGHT EXCEPTION HANDLER CRASHED FOR SCRIPT ":script" ***', array(':script' => self::readRegister('script'))));
                        Log::error(tr('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***'));

                        Debug::enabled(true);
                        show($f);
                        showdie($e);

                    case 'http':
                        if (!headers_sent()) {
                            http_response_code(500);
                            header('Content-Type: text/html');
                        }

                        if (!Debug::enabled()) {
                            notify($f, false, false);
                            notify($e, false, false);
                            page_show(500);
                        }

                        show(tr('*** UNCAUGHT EXCEPTION HANDLER CRASHED FOR SCRIPT ":script" ***', array(':script' => self::readRegister('script'))));
                        show('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***');

                        show($f);
                        showdie($e);
                }
            }

        }catch(Throwable $g) {
            /*
             * Well, we tried. Here we just give up all together
             */
            die("Fatal error. check ROOT/data/syslog, application server logs, or webserver logs for more information\n");
        }
    }



    /**
     * Set the timeout value for this script
     *
     * @see set_time_limit()
     * @version 2.7.5: Added function and documentation
     *
     * @param null|int $timeout The amount of seconds this script can run until it is aborted automatically
     * @return int The previous timeout value
     */
    public static function setTimeout(int $timeout = null): int
    {
        if ($timeout === null) {
            // Default timeout to either system configuration system.timeout, or environment variable TIMEOUT
            $timeout = Config::get('system.timeout', getenv('TIMEOUT') ?? 30);
        }

        self::$register['system']['timeout'] = $timeout;
        return set_time_limit($timeout);
    }



    public static function setLocale()
    {
        $retval = '';

        if (!$data) {
            $data = $_CONFIG['locale'];
        }

        if (!is_array($data)) {
            throw new CoreException(tr('set_locale(): Specified $data should be an array but is an ":type"', array(':type' => gettype($data))), 'invalid');
        }

        /*
         * Determine language and location
         */
        if (defined('LANGUAGE')) {
            $language = LANGUAGE;

        } else {
            $language = $_CONFIG['language']['default'];
        }

        if (isset($_SESSION['location']['country']['code'])) {
            $country = strtoupper($_SESSION['location']['country']['code']);

        } else {
            $country = $_CONFIG['location']['default_country'];
        }

        /*
         * First set LC_ALL as a baseline, then each individual entry
         */
        if (isset($data[LC_ALL])) {
            $data[LC_ALL] = str_replace(':LANGUAGE', $language, $data[LC_ALL]);
            $data[LC_ALL] = str_replace(':COUNTRY' , $country , $data[LC_ALL]);

            setlocale(LC_ALL, $data[LC_ALL]);
            $retval = $data[LC_ALL];
            unset($data[LC_ALL]);
        }

        /*
         * Apply all parameters
         */
        foreach ($data as $key => $value) {
            if ($key === 'country') {
                /*
                 * Ignore this key
                 */
                continue;
            }

            if ($value) {
                /*
                 * Ignore this empty value
                 */
                continue;
            }

            $value = str_replace(':LANGUAGE', $language, $value);
            $value = str_replace(':COUNTRY' , $country , $value);

            setlocale($key, $value);
        }

        return $retval;
    }

    /*
     *
     */
    public static function getGlobalDataPath($section = '', $writable = true)
    {
        /*
         * First find the global data path. For now, either same height as this
         * project, OR one up the filesystem tree
         */
        $paths = array('/var/lib/data/',
            '/var/www/data/',
            ROOT.'../data/',
            ROOT.'../../data/'
        );

        if (!empty($_SERVER['HOME'])) {
            /*
             * Also check the users home directory
             */
            $paths[] = $_SERVER['HOME'].'/projects/data/';
            $paths[] = $_SERVER['HOME'].'/data/';
        }

        $found = false;

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $found = $path;
                break;
            }
        }

        if ($found) {
            /*
             * Cleanup path. If realpath fails, we know something is amiss
             */
            if (!$found = realpath($found)) {
                throw new CoreException('get_global_data_path(): Found path "'.$path.'" failed realpath() check', 'path-failed');
            }
        }

        if (!$found) {
            if (!PLATFORM_CLI) {
                throw new CoreException('get_global_data_path(): Global data path not found', 'not-exists');
            }

            try {
                log_console('Warning: Global data path not found. Normally this path should exist either 1 directory up, 2 directories up, in /var/lib/data, /var/www/data, $USER_HOME/projects/data, or $USER_HOME/data', 'yellow');
                log_console('Warning: If you are sure this simply does not exist yet, it can be created now automatically. If it should exist already, then abort this script and check the location!', 'yellow');

                $path = script_exec(array('commands' => array('base/init_global_data_path')));

                if (!file_exists($path)) {
                    /*
                     * Something went wrong and it was not created anyway
                     */
                    throw new CoreException('get_global_data_path(): ./script/base/init_global_data_path reported path "'.Strings::Log($path).'" was created but it could not be found', 'failed');
                }

                /*
                 * Its now created!
                 * Strip "data/"
                 */
                $path = Strings::slash($path);

            }catch(Exception $e) {
                throw new CoreException('get_global_data_path(): Global data path not found, or init_global_data_path failed / aborted', $e);
            }
        }

        /*
         * Now check if the specified section exists
         */
        if ($section and !file_exists($path.$section)) {
            file_ensure_path($path.$section);
        }

        if ($writable and !is_writable($path.$section)) {
            throw new CoreException(tr('The global path ":path" is not writable', array(':path' => $path.$section)), 'not-writable');
        }

        if (!$global_path = realpath($path.$section)) {
            /*
             * Curious, the path exists, but realpath failed and returned false..
             * This should never happen since we ensured the path above! This is just an extra check in case of.. weird problems :)
             */
            throw new CoreException('The found global data path "'.Strings::Log($path).'" is invalid (realpath returned false)', 'invalid');
        }

        return Strings::slash($global_path);
    }



    /**
     * Register a shutdown function
     *
     * @param array|string $function_name
     * @return void
     */
    public static function registerShutdown(array|string $function_name): void
    {
        self::$register['shutdown'][Strings::force($function_name)] = $function_name;
    }



    /**
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
     * @param array|string $function_name
     * @return bool
     */
    public static function unregisterShutdown(array|string $function_name): bool
    {
        $key = Strings::force($function_name);

        if (array_key_exists($key, self::$register['shutdown'])) {
            unset(self::$register['shutdown'][$key]);
            return true;
        }

        return false;
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
        // Do we need to run other shutdown functions?
        if (empty(self::$register['script'])) {
            error_log(tr('Shutdown procedure started before self::$register[script] was ready, possibly on script ":script"', [':script' => $_SERVER['PHP_SELF']]));
            return;
        }

        Log::notice(tr('Starting shutdown procedure for script ":script"', [':script' => self::$register['script']]));

        foreach (self::$register as $key => $value) {
            try {
                if (!str_starts_with($key, 'shutdown_')) {
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
                        Log::notice(tr('Executing shutdown function ":function" with value ":value"', [':function' => $key . '()', ':value' => $entry]));
                        $key($entry);
                    }

                } else {
                    Log::notice(tr('Executing shutdown function ":function" with value ":value"', [':function' => $key . '()', ':value' => $value]));
                    $key($value);
                }

            } catch (Throwable $e) {
                Notification::getInstance()
                    ->setException($e)
                    ->send();
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
                    Log::notice(tr('Executing periodical shutdown function ":function()"', [':function' => $name]));
                    $parameters['function']();
                }
            }
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






