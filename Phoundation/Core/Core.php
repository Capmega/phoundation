<?php

namespace Phoundation\Core;

use DateTimeZone;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Cli\Cli;
use Phoundation\Cli\Scripts;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Data\Exception\ValidationFailedException;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Developer\Debug;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\Exception;
use Phoundation\Exception\Exceptions;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Processes\Processes;
use Phoundation\Web\Client;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Http;
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
     * Keep track of system status
     *
     * Can be one of:
     *
     * init     Core is initializing
     * startup  Core is starting up
     * script   Script execution is now running
     * shutdown Core is shutting down after normal script execution
     * error    Core is processing an uncaught exception and will die soon
     * phperror Core encountered a PHP error, which (typically, but not always) will end un an uncaught exception,
     *          switching system state to "error"
     *
     * @var string $state
     */
    protected static string $state = 'init';



    /**
     * Initialize the class object through the constructor.
     *
     * Core constructor.
     */
    protected function __construct()
    {
        try {
            // Register the process start
            define('STARTTIME', Timer::create('process')->getStart());

            /*
             * Define a unique process request ID
             * Define project paths.
             *
             * ROOT   is the root directory of this project and should be used as the root for all other paths
             * TMP    is a private temporary directory
             * PUBTMP is a public (accessible by web server) temporary directory
             */
            define('REQUEST', substr(uniqid(), 7));
            define('ROOT'   , realpath(__DIR__ . '/../..') . '/');
            define('TMP'    , ROOT . 'data/tmp/');
            define('PUBTMP' , ROOT . 'data/content/tmp/');
            define('CRLF'   , "\r\n");

            // Setup error handling, report ALL errors
            error_reporting(E_ALL);
            set_error_handler(['\Phoundation\Core\Core', 'phpErrorHandler']);
            set_exception_handler(['\Phoundation\Core\Core', 'uncaughtException']);

            // Load the functions and mb files
            require(ROOT . 'Phoundation/functions.php');
            require(ROOT . 'Phoundation/mb.php');

            // Ensure safe PHP configuration
            self::securePhpSettings();

            // Get the project name
            try {
                define('PROJECT', strtoupper(trim(file_get_contents( ROOT . 'config/project'))));

                if (!PROJECT) {
                    throw new OutOfBoundsException('No project defined in ROOT/config/project file');
                }
            } catch (Throwable $e) {
                if ($e instanceof  OutOfBoundsException) {
                    throw $e;
                }

                // Project file is not readable
                File::checkReadable(ROOT . 'config/project');
            }

            // Check what platform we're in
            switch (php_sapi_name()) {
                case 'cli':
                    define('PLATFORM'     , 'cli');
                    define('PLATFORM_HTTP', false);
                    define('PLATFORM_CLI' , true);
                    break;

                default:
                    define('PLATFORM'     , 'http');
                    define('PLATFORM_HTTP', true);
                    define('PLATFORM_CLI' , false);
                    define('NOCOLOR'      , (getenv('NOCOLOR') ? 'NOCOLOR' : null));

                    // Check what environment we're in
                    $env = getenv('PHOUNDATION_' . PROJECT . '_ENVIRONMENT');

                    if (empty($env)) {
                        // No environment set in ENV, maybe given by parameter?
                        Web::die(1, 'startup: No required environment specified for project "' . PROJECT . '"');
                    }

                    if (str_contains($env, '_')) {
                        Web::die(1, 'startup: Specified environment "' . $env . '" is invalid, environment names cannot contain the underscore character');
                    }

                    define('ENVIRONMENT', $env);

                    // Set protocol
                    define('PROTOCOL', Config::get('web.protocol', 'http'));

                    // Register basic HTTP information
                    // TODO MOVE TO HTTP CLASS
                    self::$register['http']['code'] = 200;
//                    self::$register['http']['accepts'] = Http::accepts();
//                    self::$register['http']['accepts_languages'] = Http::acceptsLanguages();
                    break;
            }

        } catch (Throwable $e) {
            try {
                // Startup failed miserably. Don't use anything fancy here, we're dying!
                if (defined('PLATFORM_HTTP')) {
                    if (PLATFORM_HTTP) {
                        /*
                         * Died in browser
                         */
                        Log::error('startup: Failed with "' . $e->getMessage() . '"');
                        Web::die('startup: Failed, see web server error log');
                    }

                    // Died in CLI
                    Scripts::die(1, 'startup: Failed with "' . $e->getMessage() . '"');
                }

            } catch (Throwable $e) {
                // Even a semi proper shutdown went to crap, wut?
                @error_log($e);
            }

            // Wowza things went to @#*$@( really fast! The standard defines aren't even available yet
            @error_log('startup: Failed with "' . $e->getMessage() . '"');
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
     * The core::startup() method will start up the core class
     *
     * This method starts the correct call type handler
     *
     * @return void
     * @throws Throwable
     */
    public static function startup(): void
    {
        try {
            self::$state = 'startup';

            self::getInstance();
            self::$register['system']['startup'] = microtime(true);
            self::$register['system']['script']  = Strings::until(Strings::fromReverse($_SERVER['PHP_SELF'], '/'), '.');

            // Detect platform and execute specific platform startup sequence
            switch (PLATFORM) {
                case 'http':
                    /*
                     * Determine what our target file is. With direct execution, $_SERVER[PHP_SELF] would contain this,
                     * with route execution, $_SERVER[PHP_SELF] would be route, so we cannot use that. Route will store
                     * the file being executed in self::$register['script_path'] instead
                     */
                    if (isset(self::$register['script_path'])) {
                        $file = '/' . self::$register['script_path'];

                    } else {
                        $file = '/' . $_SERVER['PHP_SELF'];
                    }

                    // Autodetect what http call type we're on from the script being executed
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
                                    Log::warning(tr('Detected language ":language" is not supported, falling back to default. See configuration languages.supported', [':language' => $language]));
                                    $language = Config::get('languages.default', 'en');
                                }

                            } else {
                                $language = substr($url, 0, 2);

                                if (!array_key_exists($language, $supported)) {
                                    Log::warning(tr('Detected language ":language" is not supported, falling back to default. See configuration languages.supported', [':language' => $language]));
                                    $language = Config::get('languages.default', 'en');
                                }
                            }

                        } else {
                            $language = Config::get('languages.default', 'en');
                        }

                        define('LANGUAGE', $language);
                        define('LOCALE'  , $language.(empty($_SESSION['location']['country']['code']) ? '' : '_' . $_SESSION['location']['country']['code']));

                        // Ensure $_SESSION['language'] available
                        if (empty($_SESSION['language'])) {
                            $_SESSION['language'] = LANGUAGE;
                        }

                    }catch(Throwable $e) {
                        // Language selection failed
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
                        mb_init(not_empty(Config::get('locale.LC_CTYPE', ''), Config::get('locale.LC_ALL', '')));

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
                    self::initializeUserSession();
                    self::setTimeZone();

                    // If POST request, automatically untranslate translated POST entries
                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        Html::untranslate();
                        Html::fixCheckboxValues();

                        if (Config::get('security.csrf.enabled') === 'force') {
                            // Force CSRF checks on every submit!
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
                    putenv('TIMEOUT='.Cli::argument('--timeout', true));

                    // Define basic platform constants
                    define('ADMIN'      , '');
                    define('PWD'        , Strings::slash(isset_get($_SERVER['PWD'])));
                    define('VERYVERBOSE', (Cli::argument('-VV,--very-verbose')                               ? 'VERYVERBOSE' : null));
                    define('VERBOSE'    , ((VERYVERBOSE or Cli::argument('-V,--verbose,-V2,--very-verbose')) ? 'VERBOSE'     : null));
                    define('QUIET'      , Cli::argument('-Q,--quiet'));
                    define('FORCE'      , Cli::argument('-F,--force'));
                    define('NOCOLOR'    , Cli::argument('-C,--no-color'));
                    define('TEST'       , Cli::argument('-T,--test'));
                    define('DELETED'    , Cli::argument('--deleted'));
                    define('STATUS'     , Cli::argument('-S,--status', true));
                    define('STARTDIR'   , Strings::slash(getcwd()));

                    // Check what environment we're in
                    $environment = Cli::argument('-E,--env,--environment', true);

                    if (empty($environment)) {
                        $env = getenv('PHOUNDATION_' . PROJECT . '_ENVIRONMENT');

                        if (empty($env)) {
                            Scripts::die(2, 'startup: No required environment specified for project "' . PROJECT . '"');
                        }

                    } else {
                        $env = $environment;
                    }

                    if (str_contains($env, '_')) {
                        Scripts::die(4, 'startup: Specified environment "' . $env . '" is invalid, environment names cannot contain the underscore character');
                    }

                    define('ENVIRONMENT', $env);

                    if (!file_exists(ROOT.'config/' . $env.'.php')) {
                        Scripts::die(5, 'startup: Configuration file "ROOT/config/' . $env . '.php" for specified environment "' . $env . '" not found');
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
                                    Log::information(tr('BASE framework code version ":fv", project code version ":pv"', [':fv' => self::FRAMEWORKCODEVERSION, ':pv' => PROJECTCODEVERSION]));
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
                                //        $e = new CoreException(tr('The "environment" argument requires an existing environment name right after it'));
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
                                    self::$register['system']['skip_init_check'] = true;
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
                        Scripts::die($die);
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
                    umask(Config::get('filesystem.umask', 0007));

                    // Ensure that the process UID matches the file UID
                    self::processFileUidMatches(true);
                    Log::notice(tr('Running script ":script"', [':script' => $_SERVER['PHP_SELF']]), 1);

                    // Get required language.
                    try {
                        $language = not_empty(Cli::argument('--language'), Cli::argument('L'), Config::get('language.default', 'en'));

                        if (Config::get('language.default', ['en']) and Config::exists('language.supported.' . $language)) {
                            throw new CoreException(tr('Unknown language ":language" specified', array(':language' => $language)), 'unknown');
                        }

                        define('LANGUAGE', $language);
                        define('LOCALE'  , $language.(empty($_SESSION['location']['country']['code']) ? '' : '_' . $_SESSION['location']['country']['code']));

                        $_SESSION['language'] = $language;

                    }catch(Throwable $e) {
                        /*
                         * Language selection failed
                         */
                        if (!defined('LANGUAGE')) {
                            define('LANGUAGE', 'en');
                        }

                        $e = new CoreException('Language selection failed', $e);
                    }

                    // Setup locale and character encoding
                    // TODO Check this mess!
                    ini_set('default_charset', Config::get('encoding.charset', 'UTF8'));
                    self::$register['system']['locale'] = self::setLocale();

                    // Prepare for unicode usage
                    if (Config::get('encoding.charset', 'UTF-8') === 'UTF-8') {
// TOOD Fix this godawful mess!
                        mb_init(not_empty(Config::get('locale.LC_CTYPE', ''), Config::get('locale.LC_ALL', '')));

                        if (function_exists('mb_internal_encoding')) {
                            mb_internal_encoding('UTF-8');
                        }
                    }

                    self::setTimeZone();

                    //
                    self::$register['ready'] = true;

                    // Set more system parameters
                    if (Cli::argument('-D,--debug')) {
                        Debug::enabled();
                    }

                    self::$register['all']         = Cli::argument('-A,--all');
                    self::$register['page']        = not_empty(Cli::argument('-P,--page', true), 1);
                    self::$register['limit']       = not_empty(Cli::argument('--limit'  , true), Config::get('paging.limit', 50));
                    self::$register['clean_debug'] = Cli::argument('--clean-debug');

                    // Validate parameters and give some startup messages, if needed
                    if (VERBOSE) {
                        if (QUIET) {
                            throw new CoreException(tr('Both QUIET and VERBOSE have been specified but these options are mutually exclusive. Please specify either one or the other'));
                        }

                        if (VERYVERBOSE) {
                            Log::information(tr('Running in VERYVERBOSE mode, started @ ":datetime"', array(':datetime' => Date::convert(STARTTIME, 'human_datetime'))));

                        } else {
                            Log::information(tr('Running in VERBOSE mode, started @ ":datetime"', array(':datetime' => Date::convert(STARTTIME, 'human_datetime'))));
                        }

                        Log::notice(tr('Detected ":size" terminal with ":columns" columns and ":lines" lines', [':size' => self::$register['cli']['size'], ':columns' => self::$register['cli']['columns'], ':lines' => self::$register['cli']['lines']]));
                    }

                    if (FORCE) {
                        if (TEST) {
                            throw new CoreException(tr('Both FORCE and TEST modes where specified, these modes are mutually exclusive'));
                        }

                        Log::warning(tr('Running in FORCE mode'));

                    } elseif (TEST) {
                        Log::warning(tr('Running in TEST mode'));
                    }

                    if (Debug::enabled()) {
                        Log::warning(tr('Running in DEBUG mode'));
                    }

                    if (!is_natural(self::$register['page'])) {
                        throw new CoreException(tr('Specified -P or --page ":page" is not a natural number', array(':page' => self::$register['page'])));
                    }

                    if (!is_natural(self::$register['limit'])) {
                        throw new CoreException(tr('Specified --limit":limit" is not a natural number', array(':limit' => self::$register['limit'])));
                    }

                    if (self::$register['all']) {
                        if (self::$register['page'] > 1) {
                            throw new CoreException(tr('Both -A or --all and -P or --page have been specified, these options are mutually exclusive'));
                        }

                        if (DELETED) {
                            throw new CoreException(tr('Both -A or --all and -D or --deleted have been specified, these options are mutually exclusive'));
                        }

                        if (STATUS) {
                            throw new CoreException(tr('Both -A or --all and -S or --status have been specified, these options are mutually exclusive'));
                        }

                    }

                    // Setup language map in case domain() calls are used
//                    Route::map();
                    break;
            }

            self::$state = 'script';

        } catch (Throwable $e) {
            if (PLATFORM_HTTP and headers_sent($file, $line)) {
                if (preg_match('/debug-.+\.php$/', $file)) {
                    throw new CoreException(tr('Failed because headers were already sent on ":location", so probably some added debug code caused this issue', array(':location' => $file . '@' . $line)), $e);
                }

                throw new CoreException(tr('Failed because headers were already sent on ":location"', [':location' => $file . '@' . $line]), $e);
            }

            throw $e;
        }
    }



    /**
     * Read and return the specified key / sub key from the core register.
     *
     * @note Will return NULL if the specified key does not exist
     * @param string $key
     * @param string|null $subkey
     * @return mixed
     */
    public static function readRegister(string $key, ?string $subkey = null): mixed
    {
        if ($subkey) {
            return isset_get(self::$register[$key][$subkey]);
        }

        return isset_get(self::$register[$key]);
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
        if ($key === 'system') {
            throw new AccessDeniedException('The "system" register cannot be written to');
        }

        if ($subkey) {
            // We want to write to a sub key. Ensure that the key exists and is an array
            if (array_key_exists($key, self::$register)) {
                if (!is_array(self::$register[$key])) {
                    // Key exists but is not an array so cannot handle sub keys
                    throw new CoreException('Cannot write to register key ":key.:subkey" as register key ":key" already exist as a value instead of an array', [':key' => $key, 'subkey' => $subkey]);
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
     * Delete the specified variable from the core register
     *
     * @param string $key
     * @param string|null $subkey
     * @return void
     */
    public static function deleteRegister(string $key, ?string $subkey = null): void
    {
        if ($key === 'system') {
            throw new AccessDeniedException('The "system" register cannot be written to');
        }

        if ($subkey) {
            // We want to write to a sub key. Ensure that the key exists and is an array
            if (array_key_exists($key, self::$register)) {
                if (!is_array(self::$register[$key])) {
                    // Key exists but is not an array so cannot handle sub keys
                    throw new CoreException('Cannot write to register key ":key.:subkey" as register key ":key" already exist as a value instead of an array', [':key' => $key, 'subkey' => $subkey]);
                }
            } else {
                // The key doesn't exist, so we don't have to worry about the sub key
                return;
            }

            // Delete the key / subkey
            unset(self::$register[$key][$subkey]);
        } else {
            // Delete the key
            unset(self::$register[$key]);
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
        if ($subkey === null) {
            return $value === isset_get(self::$register[$key]);
        }

        return $value === isset_get(self::$register[$key][$subkey]);
    }



    /**
     * Returns Core system state
     *
     * Can be one of
     *
     * startup  System is starting up
     * script   Script execution is now running
     * shutdown System is shutting down after normal script execution
     * error    System is processing an uncaught exception and will die soon
     * phperror System encountered a PHP error, which (typically, but not always) will end un an uncaught exception,
     *          switching system state to "error"
     *
     * @return string
     */
    public static function getState(): string
    {
        return self::$state;
    }



    /**
     * Allows to change the Core class state
     *
     * @note This method only allows a change to the states "error" or "phperror"
     * @param string $state
     * @return void
     */
    public static function setState(string $state): void
    {
        switch ($state) {
            case 'error':
                // no-break
            case 'phperror':
                self::$state = $state;
                break;

            case 'init':
                // no-break
            case 'startup':
                // no-break
            case 'shutdown':
                // These are not allowed
                throw new OutOfBoundsException(tr('Core state update to ":state" is not allowed. Core state can only be updated to "error" or "phperror"', [':state' => $state]));

            default:
                // Wut?
                throw new OutOfBoundsException(tr('Unknown core state ":state" specified. Core state can only be updated to "error" or "phperror"', [':state' => $state]));
        }
    }



    /**
     * Returns true if the system is still starting up
     *
     * @param string|null $state If specified will return the startup state for the specified state instead of the
     *                           internal Core state
     * @return bool
     * @see Core::getState()
     */
    public static function startupState(?string $state = null): bool
    {
        if ($state === null) {
            $state = self::$state;
        }

        return match ($state) {
            'init', 'startup' => true,
            default           => false,
        };
    }



    /**
     * Returns true if the system is running in PHPUnit
     *
     * @return bool
     */
    public static function isPhpUnitTest(): bool
    {
        return self::readRegister('system', 'script') === 'phpunit';
    }



    /**
     * Returns true if the system has finished starting up
     *
     * @param string|null $state If specified will return the startup state for the specified state instead of the
     *                           internal Core state
     * @see Core::getState()
     * @return bool
     */
    public static function readyState(?string $state = null): bool
    {
        return !self::startupState($state);
    }



    /**
     * Returns true if the system is in error state
     *
     * @see Core::getState()
     * @return bool
     */
    public static function errorState(): bool
    {
        return match (self::$state) {
            'error', 'phperror' => true,
            default             => false,
        };
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
     * @param null|string $language a language code
     * @return string a valid language that is supported by the systems configuration
     */
    public static function getLanguage(?string $language): string
    {
        if (empty(Config::get('languages.supported', ''))) {
            // No multi languages supported for this site
            return '';
        }

        // This is a multilingual site
        if ($language === null) {
            $language = LANGUAGE;
        }

        if ($language) {
            // This is a multilingual website. Ensure language is supported and add language selection to the URL.
            if (empty(Config::get('language.default' . $language))) {
                $language = Config::get('language.default', 'en');

                Notification::getInstance()
                    ->setCode('unknown-language')
                    ->setGroups('developers')
                    ->setTitle(tr('Unknown language specified'))
                    ->setMessage(tr('The specified language ":language" is not known', [':language' => $language]))
                    ->send();
            }
        }

        return $language;
    }



    /**
     * Convert all PHP errors in exceptions. With this function the entirety of base works only with exceptions, and
     * function output normally does not need to be checked for errors.
     *
     * @note This method should never be called directly
     * @note This method uses untranslated texts as using tr() could potentially cause other issues
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return void
     * @throws \Exception
     */
    public static function phpErrorHandler(int $errno, string $errstr, string $errfile, int $errline): void
    {
        if (self::startupState()) {
            // Wut? We're not even ready to go! Likely we don't have configuration available so we cannot even send out
            // notifications. Just crash with a standard PHP exception
            throw new \Exception('Core startup PHP ERROR [' . $errno . '] "' . $errstr . '" in "' . $errfile . '@' . $errline . '"');
        }

        self::$state = 'phperror';

        $trace = Debug::backtrace();
        unset($trace[0]);
        unset($trace[1]);

        Notification::getInstance()
            ->setCode('PHP-ERROR-' . $errno)
            ->addGroup('developers')
            ->setTitle('PHP ERROR "' . $errno . '"')
            ->setMessage(tr('PHP ERROR [' . $errno . '] "' . $errstr . '" in "' . $errfile . '@' . $errline .'"'))
            ->setData([
                'errno' => $errno,
                'errstr' => $errstr,
                'errfile' => $errfile,
                'errline' => $errline,
                'trace' => $trace
            ])->send();

        throw new \Exception('PHP ERROR [' .$errno . '] "' . $errstr . '" in "' . $errfile . '@' . $errline . '"', $errno);
    }



    /**
     * This function is called automatically
     *
     * @param Throwable $e
     * @param boolean $die Specify false if this exception should be a warning and continue, true if it should die
     * @return void
     * @note: This function should never be called directly
     */
    #[NoReturn] public static function uncaughtException(Throwable $e, bool $die = true): void
    {
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

        $state = self::$state;
        self::$state = 'error';

        try {
            try {
                if ($executed) {
                    // We seem to be stuck in an uncaught exception loop, cut it out now!
                    // This basically means that the unhandledException handler also is causing exceptions.
                    // :TODO: ADD NOTIFICATIONS OF STUFF GOING FUBAR HERE!
                    die('uncaught exception handler loop detected');
                }

                $executed = true;

                if (empty(self::$register['system']['script'])) {
                    self::$register['system']['script'] = 'unknown';
                }

                if (!defined('PLATFORM')) {
                    // System crashed before platform detection.
                    Log::error(tr('*** UNCAUGHT EXCEPTION ":code" IN ":type" TYPE SCRIPT ":script" ***', [':code' => $e->getCode(), ':type' => self::getCallType(), ':script' => self::readRegister('system', 'script')]));
                    Log::error($e);
                    die('exception before platform detection');
                }

                switch (PLATFORM) {
                    case 'cli':
//                        // Ensure that required defines are available
//                        if (!defined('VERYVERBOSE')) {
//                            define('VERYVERBOSE', (Cli::argument('-VV,--very-verbose') ? 'VERYVERBOSE' : null));
//                        }
//
//                        self::setTimeout(1);
//
//                        $defines = [
//                            'ADMIN'    => '',
//                            'PWD'      => Strings::slash(isset_get($_SERVER['PWD'])),
//                            'VERBOSE'  => ((VERYVERBOSE or Cli::argument('-V,--verbose,-V2,--very-verbose')) ? 'VERBOSE' : null),
//                            'QUIET'    => Cli::argument('-Q,--quiet'),
//                            'FORCE'    => Cli::argument('-F,--force'),
//                            'TEST'     => Cli::argument('-T,--test'),
//                            'LIMIT'    => not_empty(Cli::argument('--limit'  , true), Config::get('paging.limit', 50)),
//                            'ALL'      => Cli::argument('-A,--all'),
//                            'DELETED'  => Cli::argument('--deleted'),
//                            'STATUS'   => Cli::argument('-S,--status' , true),
//                            'STARTDIR' => Strings::slash(getcwd())
//                        ];
//
//                        foreach ($defines as $key => $value) {
//                            if (!defined($key)) {
//                                define($key, $value);
//                            }
//                        }
//
//                        Notification::getInstance()
//                            ->setException($e)
//                            ->send();
//
//                        // Specified arguments were wrong
//                        // TODO CHANGE PARAMETERS TO "ARGUMENTS"
//                        if ($e->getCode() === 'parameters') {
//                            Log::warning(trim(Strings::from($e->getMessage(), '():')));
//                            $GLOBALS['core'] = false;
//                            die(1);
//                        }
//
//                        if (self::startupState($state)) {
//                            /*
//                             * Configuration hasn't been loaded yet, we cannot even know if
//                             * we are in debug mode or not!
//                             *
//                             * Log to the webserver error log files at the very least
//                             */
//                            if (method_exists($e, 'getMessages')) {
//                                foreach ($e->getMessages() as $message) {
//                                    Log::error($message);
//                                }
//
//                            } else {
//                                Log::error($e->getMessage());
//                            }
//
//                            Scripts::die(1);
//                        }

                        /*
                         * Command line script crashed.
                         *
                         * If not using VERBOSE mode, then try to give nice error messages
                         * for known issues
                         */
                        if (($e instanceof Exception) and ($e->isWarning() or $e instanceof ValidationFailedException)) {
                            // This is just a simple general warning, no backtrace and such needed, only show the
                            // principal message
                            Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
                            Scripts::die(255);
                        }

// TODO Remplement this with proper exception classes
//                            switch ((string) $e->getCode()) {
//                                case 'already-running':
//                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
//                                    Scripts::setExitCode(254);
//                                    die(Scripts::getExitCode());
//
//                                case 'no-method':
//                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
//                                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
//                                    Scripts::setExitCode(253);
//                                    die(Scripts::getExitCode());
//
//                                case 'unknown-method':
//                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
//                                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
//                                    Scripts::setExitCode(252);
//                                    die(Scripts::getExitCode());
//
//                                case 'missing-arguments':
//                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
//                                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
//                                    Scripts::setExitCode(253);
//                                    die(Scripts::getExitCode());
//
//                                case 'invalid-arguments':
//                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
//                                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
//                                    Scripts::setExitCode(251);
//                                    die(Scripts::getExitCode());
//
//                                case 'validation':
//                                    if (self::readRegister('system', 'script') === 'init') {
//                                        /*
//                                         * In the init script, all validations are fatal!
//                                         */
//                                        $e->makeWarning(false);
//                                        break;
//                                    }
//
//                                    if (method_exists($e, 'getMessages')) {
//                                        $messages = $e->getMessages();
//
//                                    } else {
//                                        $messages = $e->getMessage();
//                                    }
//
//                                    if (count($messages) > 2) {
//                                        array_pop($messages);
//                                        array_pop($messages);
//                                        Log::warning(tr('Validation failed'));
//                                        Log::warning($messages, 'yellow');
//
//                                    } else {
//                                        Log::warning($messages);
//                                    }
//
//                                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
//                                    Scripts::setExitCode(250);
//                                    die(Scripts::getExitCode());
//                            }

                        Log::error(tr('*** UNCAUGHT EXCEPTION ":code" IN ":type" TYPE SCRIPT ":script" ***', [':code' => $e->getCode(), ':type' => self::getCallType(), ':script' => self::readRegister('system', 'script')]));
                        Log::error(tr('Exception data:'));
                        Log::error($e);
                        Scripts::die(1);

                    case 'http':
                        // Log exception data
                        Log::error(tr('*** UNCAUGHT EXCEPTION ":code" IN ":type" TYPE SCRIPT ":script" ***', [':code' => $e->getCode(), ':type' => self::getCallType(), ':script' => self::readRegister('system', 'script')]));
                        Log::error(tr('Exception data:'));
                        Log::error($e);

                        // Remove all caching headers
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

                        $defines = [
                            'ADMIN'    => '',
                            'PWD'      => Strings::slash(isset_get($_SERVER['PWD'])),
                            'STARTDIR' => Strings::slash(getcwd()),
                            'FORCE'    => (getenv('FORCE')                    ? 'FORCE'   : null),
                            'TEST'     => (getenv('TEST')                     ? 'TEST'    : null),
                            'VERBOSE'  => ((VERYVERBOSE or getenv('VERBOSE')) ? 'VERBOSE' : null),
                            'QUIET'    => (getenv('QUIET')                    ? 'QUIET'   : null),
                            'LIMIT'    => (getenv('LIMIT')                    ? 'LIMIT'   : Config::get('paging.limit', 50)),
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

                        if (self::startupState($state)) {
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
                                    Log::error($message);
                                }

                            } else {
                                Log::error($e->getMessage());
                            }

                            Web::die(tr('System startup exception. Please check your ROOT/data/log directory or application or webserver error log files, or enable the first line in the exception handler file for more information'));
                        }

                        if ($e->getCode() === 'validation') {
                            $e->setCode(400);
                        }

                        if (($e instanceof CoreException) and is_numeric($e->getCode()) and ($e->getCode() > 100) and page_show($e->getCode(), array('exists' => true))) {
                            if ($e->isWarning()) {
                                html_flash_set($e->getMessage(), 'warning', $e->getCode());
                            }

                            Log::error(tr('Displaying exception page ":page"', [':page' => $e->getCode()]));
                            Web::execute($e->getCode(), ['message' =>$e->getMessage()]);
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
                                            '.tr('*** UNCAUGHT EXCEPTION ":code" IN ":type" TYPE SCRIPT ":script" ***', array(':code' => $e->getCode(), ':script' => self::readRegister('system', 'script'), 'type' => Core::getCallType())).'
                                        </td>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="2" class="center">
                                                '.tr('An uncaught exception with code ":code" occured in script ":script". See the exception core dump below for more information on how to fix this issue', array(':code' => $e->getCode(), ':script' => self::readRegister('system', 'script'))).'
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                '.tr('File').'
                                            </td>
                                            <td>
                                                ' . $e->getFile().'
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                '.tr('Line').'
                                            </td>
                                            <td>
                                                ' . $e->getLine().'
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
                                    Json::message($e->getCode(), ['reason' => ($e->isWarning() ? trim(Strings::from($e->getMessage(), ':')) : '')]);
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
//                    Log::error(tr('*** UNCAUGHT PRE CORE AVAILABLE EXCEPTION HANDLER CRASHED ***'));
//                    Log::error(tr('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***'));
//                    Log::error($f->getMessage());
//                    die('Pre core available exception with handling failure. Please your application or webserver error log files, or enable the first line in the exception handler file for more information');
//                }

                if (!defined('PLATFORM') or self::startupState($state)) {
                    Log::error(tr('*** UNCAUGHT SYSTEM STARTUP EXCEPTION HANDLER CRASHED FOR SCRIPT ":script" ***', array(':script' => self::readRegister('system', 'script'))));
                    Log::error(tr('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***'));
                    Log::error($f->getMessage());
                    Log::error($f->getTrace());
                    die('System startup exception with handling failure. Please check your ROOT/data/log directory or application or webserver error log files, or enable the first line in the exception handler file for more information');
                }

                Log::error('STARTUP-UNCAUGHT-EXCEPTION HANDLER CRASHED!');
                Log::error($f);

                switch (PLATFORM) {
                    case 'cli':
                        Log::error(tr('*** UNCAUGHT EXCEPTION HANDLER CRASHED FOR SCRIPT ":script" ***', array(':script' => self::readRegister('system', 'script'))));
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

                        show(tr('*** UNCAUGHT EXCEPTION HANDLER CRASHED FOR SCRIPT ":script" ***', array(':script' => self::readRegister('system', 'script'))));
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
            $timeout = Config::get('system.timeout', get_null(getenv('TIMEOUT')) ?? 30);
        }

        self::$register['system']['timeout'] = $timeout;
        return set_time_limit($timeout);
    }



    /**
     * Apply the specified or configured locale
     *
     * @todo what is this supposed to return anyway?
     * @param array $locale
     * @return string
     */
    public static function setLocale(array $locale = null): string
    {
        $retval = '';

        if (!$locale) {
            $locale = Config::get('locale', [
                LC_ALL      => ':LANGUAGE_:COUNTRY.UTF8',
                LC_COLLATE  => null,
                LC_CTYPE    => null,
                LC_MONETARY => null,
                LC_NUMERIC  => null,
                LC_TIME     => null,
                LC_MESSAGES => null
            ]);
        }

        if (!is_array($locale)) {
            throw new CoreException(tr('Specified $data should be an array but is an ":type"', [':type' => gettype($locale)]));
        }

        /*
         * Determine language and location
         */
        if (defined('LANGUAGE')) {
            $language = LANGUAGE;

        } else {
            $language = Config::get('language.default', 'en');
        }

        if (isset($_SESSION['location']['country']['code'])) {
            $country = strtoupper($_SESSION['location']['country']['code']);

        } else {
            $country = Config::get('location.default-country', 'us');
        }

        // First set LC_ALL as a baseline, then each individual entry
        if (isset($locale[LC_ALL])) {
            $locale[LC_ALL] = str_replace(':LANGUAGE', $language, $locale[LC_ALL]);
            $locale[LC_ALL] = str_replace(':COUNTRY' , $country , $locale[LC_ALL]);

            setlocale(LC_ALL, $locale[LC_ALL]);
            $retval = $locale[LC_ALL];
            unset($locale[LC_ALL]);
        }

        // Apply all parameters
        foreach ($locale as $key => $value) {
            if ($key === 'country') {
                // Ignore this key
                continue;
            }

            if ($value) {
                // Ignore this empty value
                continue;
            }

            $value = str_replace(':LANGUAGE', $language, (string) $value);
            $value = str_replace(':COUNTRY' , $country , (string) $value);

            setlocale($key, $value);
        }

        return $retval;
    }



    /**
     * ???
     *
     * @param string $section
     * @param bool $writable
     * @return string
     */
    public static function getGlobalDataPath(string $section = '', bool $writable = true): string
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
                throw new CoreException('get_global_data_path(): Found path "' . $path.'" failed realpath() check', 'path-failed');
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
            throw new CoreException('The found global data path "'.Strings::Log($path).'" is invalid (realpath returned false)');
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
     * @param int|null $error_code
     * @return void
     */
    public static function shutdown(?int $error_code = null): void
    {
        self::$state = 'shutdown';

        // Do we need to run other shutdown functions?
        if (self::startupState()) {
            if (!$error_code) {
                Log::error(tr('Shutdown procedure started before self::$register[script] was ready, possibly on script ":script"', [':script' => $_SERVER['PHP_SELF']]));
                return;
            }

            // We're in error mode and already know it, don't do normal shutdown
            return;
        }

        Log::notice(tr('Starting shutdown procedure for script ":script"', [':script' => self::$register['system']['script']]), 2);

        foreach (self::$register as $key => $value) {
            try {
                if (!str_starts_with($key, 'shutdown_')) {
                    continue;
                }

                $key = substr($key, 9);

                // Execute this shutdown function with the specified value
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

        // Periodically execute the following functions
        if (!$error_code) {
            $level = mt_rand(0, 100);

            if (Config::get('system.shutdown', false)) {
                if (!is_array(Config::get('system.shutdown', false))) {
                    throw new OutOfBoundsException(tr('Invalid system.shutdown configuration, it should be an array'));
                }

                foreach (Config::get('system.shutdown', false) as $name => $parameters) {
                    if ($parameters['interval'] and ($level < $parameters['interval'])) {
                        Log::notice(tr('Executing periodical shutdown function ":function()"', [':function' => $name]));
                        $parameters['function']();
                    }
                }
            }
        }
    }



    /**
     * Returns the framework database version
     *
     * @param string $type
     * @return string
     */
    public static function getVersion(string $type): string
    {
// TODO implement
        return 'unknown';
    }



    /**
     * Ensures that the UID of the user executing this script is the same as the UID of this libraries' owner
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package cli
     *
     * @param boolean $auto_switch If set to true, the script will automatically restart with the correct user, instead of causing an exception
     * @param boolean $permit_root If set to true, and the script was run by root, it will be authorized anyway
     * @return void
     */
    protected static function processFileUidMatches(bool $auto_switch = false, bool $permit_root = true): void
    {
        if (self::isPhpUnitTest()) {
            // Don't restart PHPUnit
            return;
        }

        if (Scripts::getProcessUid() !== getmyuid()) {
            if (!Scripts::getProcessUid() and $permit_root) {
                // Root is authorized!
                return;
            }

            if (!$auto_switch) {
                throw new CoreException(tr('The user ":puser" is not allowed to execute these scripts, only user ":fuser" can do this. use "sudo -u :fuser COMMANDS instead.', array(':puser' => get_current_user(), ':fuser' => cli_get_process_user())), 'not-authorized');
            }

            // Re-execute this command as the specified user
            Log::warning(tr('Current user ":user" is not authorized to execute this script, re-executing script as user ":reuser"', [':user' => Scripts::getProcessUid(), ':reuser' => getmyuid()]));

            $argv = $GLOBALS['argv'];
            array_shift($argv);

            $arguments   = ['sudo' => 'sudo -Eu \''.get_current_user().'\''];
            $arguments   = array_merge($arguments, $argv);

            // Ensure --timeout is added to the script
            if (!in_array('--timeout', $arguments)) {
                $arguments[] = '--timeout';
                $arguments[] = self::$register['timeout'];
            }

            // Execute the process
            Processes::create(ROOT . '/cli')
                ->setWait(1)
                ->setTimeout(self::readRegister('system', 'timeout'))
                ->setArguments($arguments)
                ->executePassthru();

            Log::success(tr('Finished re-executed script ":script"', [':script' => self::$register['system']['script']]));
            die();
        }
    }



    /**
     * Sets timezone, see http://www.php.net/manual/en/timezones.php for more info
     *
     * @return void
     */
    protected static function setTimeZone(): void
    {
        // Set system timezone
        $timezone = isset_get($_SESSION['user']['timezone'], Config::get('system.timezone.system', 'UTC'));

        try {
            date_default_timezone_set($timezone);

        }catch(Throwable $e) {
            // Users timezone failed, use the configured one
            Notification::getInstance()
                ->setException($e)
                ->send();
        }

        // Set user timezone
        define('TIMEZONE', $timezone);
        ensure_variable($_SESSION['user']['timezone'], 'UTC');
    }



    /**
     * Apply various settings to ensure this process is running as secure as possible
     *
     * @todo Should these issues be detected and logged if found, instead? What if somebody, for example, would need yaml.decode_php?
     * @return void
     */
    protected static function securePhpSettings(): void
    {
        ini_set('yaml.decode_php', 'off'); // Do this to avoid the ability to unserialize PHP code
    }



    /**
     * Initialize the user session
     *
     * @return void
     */
    protected static function initializeUserSession(): void
    {
        // Correctly detect the remote IP
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        // New session? Detect client type, language, and mobile device
        if (empty($_COOKIE[Config::get('sessions.cookies.name', '')])) {
            Client::detect();
        }

        // Add a powered-by header
        switch (Config::get('security.expose.phoundation', 'limited')) {
            case 'limited':
                header('Powered-By: Phoundation');
                break;

            case 'full':
                header('Powered-By: Phoundation version "' . Core::FRAMEWORKCODEVERSION . '"');

            case 'none':
                break;

            default:
                throw new OutOfBoundsException(tr('Invalid configuration value ":value" for "security.signature" Please use one of "none", "limited", or "full"'));
        }

        // :TODO: The next section may be included in the whitelabel domain check
        // Check if the requested domain is allowed
        $domain = $_SERVER['HTTP_HOST'];

        if (!$domain) {
            // No domain was requested at all, so probably instead of a domain name, an IP was requested. Redirect to
            // the domain name
            Http::redirect(PROTOCOL.Web::getDomain());
        }



        // Check the detected domain against the configured domain. If it doesn't match then check if it's a registered
        // whitelabel domain
        if ($domain === Web::getDomain()) {
            // This is the primary domain

        } else {
            // This is not the registered domain!
            switch (Config::get('web.domains.whitelabels', false)) {
                case '':
                    // White label domains are disabled, so the requested domain MUST match the configured domain
                    Log::warning(tr('Whitelabels are disabled, redirecting domain ":source" to ":target"', [':source' => $_SERVER['HTTP_HOST'], ':target' => Web::getDomain()]));
                    Http::redirect(PROTOCOL . Web::getDomain());
                    break;

                case 'all':
                    // All domains are allowed
                    break;

                case 'sub':
                    // White label domains are disabled, but subdomains from the primary domain are allowed
                    if (Strings::from($domain, '.') !== Web::getDomain()) {
                        Log::warning(tr('Whitelabels are set to subdomains only, redirecting domain ":source" to ":target"', [':source' => $_SERVER['HTTP_HOST'], ':target' => Web::getDomain()]));
                        redirect(PROTOCOL . Web::getDomain());
                    }

                    break;

                case 'list':
                    // This domain must be registered in the whitelabels list
                    $domain = Sql::db()->getColumn('SELECT `domain` 
                                                          FROM   `whitelabels` 
                                                          WHERE  `domain` = :domain 
                                                          AND `status` IS NULL',
                        [':domain' => $_SERVER['HTTP_HOST']]);

                    if (empty($domain)) {
                        Log::warning(tr('Whitelabel check failed because domain was not found in database, redirecting domain ":source" to ":target"', [':source' => $_SERVER['HTTP_HOST'], ':target' => Web::getDomain()]));
                        redirect(PROTOCOL . Web::getDomain());
                    }

                    break;

                default:
                    if (is_array(Config::get('web.domains.whitelabels', false))) {
                        // Domain must be specified in one of the array entries
                        if (!in_array($domain, Config::get('web.domains.whitelabels', false))) {
                            Log::warning(tr('Whitelabel check failed because domain was not found in configured array, redirecting domain ":source" to ":target"', [':source' => $_SERVER['HTTP_HOST'], ':target' => Web::getDomain()]));
                            redirect(PROTOCOL . Web::getDomain());
                        }

                    } else {
                        // The domain must match either domain configuration or the domain specified in configuration
                        // "whitelabels.enabled"
                        if ($domain !== Config::get('web.domains.whitelabels', false)) {
                            Log::warning(tr('Whitelabel check failed because domain did not match only configured alternative, redirecting domain ":source" to ":target"', [
                                ':source' => $_SERVER['HTTP_HOST'],
                                ':target' => Web::getDomain()
                            ]));

                            redirect(PROTOCOL . Web::getDomain());
                        }
                    }
            }
        }

        // Check the cookie domain configuration to see if it's valid.
        // NOTE: In case whitelabel domains are used, $_CONFIG[cookie][domain] must be one of "auto" or ".auto"
        switch (Config::get('sessions.cookies.domain', '.auto')) {
            case false:
                // This domain has no cookies
                break;

            case 'auto':
                Config::set('sessions.cookies.domain', $domain);
                ini_set('session.cookie_domain', $domain);
                break;

            case '.auto':
                Config::get('sessions.cookies.domain', '.'.$domain);
                ini_set('session.cookie_domain', '.'.$domain);
                break;

            default:
                /*
                 * Test cookie domain limitation
                 *
                 * If the configured cookie domain is different from the current domain then all cookie will inexplicably fail without warning,
                 * so this must be detected to avoid lots of hair pulling and throwing arturo off the balcony incidents :)
                 */
                if (Config::get('sessions.cookies.domain')[0] == '.') {
                    $test = substr(Config::get('sessions.cookies.domain'), 1);

                } else {
                    $test = Config::get('sessions.cookies.domain');
                }

                if (!str_contains($domain, $test)) {
                    Notification::getInstance()
                        ->setCode('configuration')
                        ->setGroups('developers')
                        ->setTitle(tr('Invalid cookie domain'))
                        ->setMessage(tr('Specified cookie domain ":cookie_domain" is invalid for current domain ":current_domain". Please fix $_CONFIG[cookie][domain]! Redirecting to ":domain"', [
                            ':domain'         => Strings::startsNotWith(Config::get('sessions.cookies.domain'), '.'),
                            ':cookie_domain'  => Config::get('sessions.cookies.domain'),
                            ':current_domain' => $domain]))
                        ->send();

                    redirect(PROTOCOL.Strings::startsNotWith(Config::get('sessions.cookies.domain'), '.'));
                }

                ini_set('session.cookie_domain', Config::get('sessions.cookies.domain'));
                unset($test);
                unset($length);
        }

        // Set session and cookie parameters
        try {
            if (Config::get('sessions.enabled', true)) {
                // Force session cookie configuration
                ini_set('session.gc_maxlifetime' , Config::get('sessions.timeout'            , true));
                ini_set('session.cookie_lifetime', Config::get('sessions.cookies.lifetime'   , 0));
                ini_set('session.use_strict_mode', Config::get('sessions.cookies.strict_mode', true));
                ini_set('session.name'           , Config::get('sessions.cookies.name'       , ''));
                ini_set('session.cookie_httponly', Config::get('sessions.cookies.http-only'  , true));
                ini_set('session.cookie_secure'  , Config::get('sessions.cookies.secure'     , true));
                ini_set('session.cookie_samesite', Config::get('sessions.cookies.same-site'  , true));

                if (Config::get('sessions.check-referrer', true)) {
                    ini_set('session.referer_check', $domain);
                }

                if (Debug::enabled() or !Config::get('cache.http.enabled', true)) {
                    ini_set('session.cache_limiter', 'nocache');

                } else {
                    if (Config::get('cache.http.enabled', true) === 'auto') {
                        ini_set('session.cache_limiter', Config::get('cache.http.php-cache-limiter'    , true));
                        ini_set('session.cache_expire' , Config::get('cache.http.php-cache-php_cache_expire', true));
                    }
                }

                // Do not send cookies to crawlers!
                if (self::readRegister('session', 'client')['type'] === 'crawler') {
                    Log::information(tr('Crawler ":crawler" on URL ":url"', [':crawler' => self::readRegister('session', 'client'), ':url' => (empty($_SERVER['HTTPS']) ? 'http' : 'https').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']]));

                } else {
                    // Setup session handlers
                    switch (Config::get('sessions.handler', 'sql')) {
                        case false:
                            file_ensure_path(ROOT.'data/cookies/');
                            ini_set('session.save_path', ROOT.'data/cookies/');
                            break;

                        case 'sql':
                            // Store session data in MySQL
                            session_set_save_handler('sessions_sql_open', 'sessions_sql_close', 'sessions_sql_read', 'sessions_sql_write', 'sessions_sql_destroy', 'sessions_sql_gc', 'sessions_sql_create_sid');
                            register_shutdown_function('session_write_close');

                        case 'mc':
                            // Store session data in memcached
                            session_set_save_handler('sessions_memcached_open', 'sessions_memcached_close', 'sessions_memcached_read', 'sessions_memcached_write', 'sessions_memcached_destroy', 'sessions_memcached_gc', 'sessions_memcached_create_sid');
                            register_shutdown_function('session_write_close');

                        case 'mm':
                            // Store session data in shared memory
                            session_set_save_handler('sessions_mm_open', 'sessions_mm_close', 'sessions_mm_read', 'sessions_mm_write', 'sessions_mm_destroy', 'sessions_mm_gc', 'sessions_mm_create_sid');
                            register_shutdown_function('session_write_close');
                    }



                    // Set cookie, but only if page is not API and domain has cookie configured
                    if (Config::get('sessions.cookies.europe', true) and !Config::get('sessions.cookies.name', '')) {
                        if (GeoIP::isEuropean()) {
                            // All first visits to european countries require cookie permissions given!
                            $_SESSION['euro_cookie'] = true;
                            return;
                        }
                    }

                    if (!Core::getCallType('api')) {
                        //
                        try {
                            if (Config::get('sessions.cookies.name', '')) {
                                if (!is_string(Config::get('sessions.cookies.name', '')) or !preg_match('/[a-z0-9]{22,128}/i', $_COOKIE[Config::get('sessions.cookies.name', '')])) {
                                    Log::warning(tr('Received invalid cookie ":cookie", dropping', [':cookie' => $_COOKIE[Config::get('sessions.cookies.name', '')]]));
                                    unset($_COOKIE[Config::get('sessions.cookies.name', '')]);
                                    $_POST = array();

                                    // Received cookie but it didn't pass. Start a new session without a cookie
                                    session_start();

                                } elseif (!file_exists(ROOT.'data/cookies/sess_'.$_COOKIE[Config::get('sessions.cookies.name', '')])) {
                                    /*
                                     * Cookie code is valid, but it doesn't exist.
                                     *
                                     * Start a session with this non-existing cookie. Rename
                                     * our session after the cookie, as deleting the cookie
                                     * from the browser turned out to be problematic to say
                                     * the least
                                     */
                                    Log::information(tr('Received non existing cookie ":cookie", recreating', [':cookie' => $_COOKIE[Config::get('sessions.cookies.name', '')]]));

                                    session_start();

                                    if (Config::get('sessions.cookies.notify-expired', '')) {
                                        Html::flash()->add(tr('Your browser cookie was expired, or does not exist. You may have to sign in again'), 'warning');
                                    }

                                    $_POST = [];

                                } else {
                                    // Cookie valid and found. Start a normal session with whit cookie
                                    session_start();
                                }

                            } else {
                                // No cookie received, start a fresh session
                                session_start();
                            }

                        }catch(Exception $e) {
                            // Session startup failed. Clear session and try again
                            try {
                                session_regenerate_id(true);

                            }catch(Exception $e) {
                                /*
                                 * Woah, something really went wrong..
                                 *
                                 * This may be
                                 * headers already sent (the $core->register['script'] file has a space or BOM at the beginning maybe?)
                                 * permissions of PHP session directory?
                                 */
// :TODO: Add check on $core->register['script'] file if it contains BOM!
                                throw new CoreException('startup-webpage(): session start and session regenerate both failed, check PHP session directory', $e);
                            }
                        }

                        if (Config::get('security.url-cloaking.enabled', false) and Config::get('security.url-cloaking.strict', false)) {
                            /*
                             * URL cloaking was enabled and requires strict checking.
                             *
                             * Ensure that we have a cloaked URL users_id and that it
                             * matches the sessions users_id
                             *
                             * Only check cloaking rules if we are NOT displaying a
                             * system page
                             */
                            if (!Core::getCallType('system')) {
                                if (empty($core->register['url_cloak_users_id'])) {
                                    throw new CoreException(tr('startup-webpage(): Failed cloaked URL strict checking, no cloaked URL users_id registered'), 403);
                                }

                                if ($core->register['url_cloak_users_id'] !== $_SESSION['user']['id']) {
                                    throw new CoreException(tr('startup-webpage(): Failed cloaked URL strict checking, cloaked URL users_id ":cloak_users_id" did not match the users_id ":session_users_id" of this session', array(':session_users_id' => $_SESSION['user']['id'], ':cloak_users_id' => $core->register['url_cloak_users_id'])), 403);
                                }
                            }
                        }

                        if (Config::get('sessions.regenerate-id', false)) {
                            if (isset($_SESSION['created']) and (time() - $_SESSION['created'] > Config::get('sessions.regenerate_id', false))) {
                                /*
                                 * Use "created" to monitor session id age and
                                 * refresh it periodically to mitigate attacks on
                                 * sessions like session fixation
                                 */
                                session_regenerate_id(true);
                                $_SESSION['created'] = time();
                            }
                        }

                        if (Config::get('sessions.cookies.lifetime', 0)) {
                            if (isset($_SESSION['last_activity']) and (time() - $_SESSION['last_activity'] > Config::get('sessions.cookies.lifetime', 0))) {
                                // Session expired!
                                session_unset();
                                session_destroy();
                                session_start();
                                session_regenerate_id(true);
                            }
                        }

                        // Set last activity, and first_visit variables
                        $_SESSION['last_activity'] = time();

                        if (isset($_SESSION['first_visit'])) {
                            if ($_SESSION['first_visit']) {
                                $_SESSION['first_visit']--;
                            }

                        } else {
                            $_SESSION['first_visit'] = 1;
                        }

                        // Auto extended sessions?
                        Web::session()->checkExtended();

                        // Set users timezone
                        if (empty($_SESSION['user']['timezone'])) {
                            $_SESSION['user']['timezone'] = Config::get('timezone.display', 0);

                        } else {
                            try {
                                $check = new DateTimeZone($_SESSION['user']['timezone']);

                            }catch(Exception $e) {
                                // Timezone invalid for this user. Notify developers, and fix timezone for user
                                $_SESSION['user']['timezone'] = Config::get('timezone.display', 0);

                                user_update($_SESSION['user']);

                                $e = new CoreException(tr('core::manage_session(): Reset timezone for user ":user" to ":timezone"', array(':user' => name($_SESSION['user']), ':timezone' => $_SESSION['user']['timezone'])), $e);
                                $e->makeWarning(true);

                                Notification::getInstance()
                                    ->setException($e)
                                    ->send();
                            }
                        }
                    }

                    if (empty($_SESSION['init'])) {
                        // Initialize the session
                        $_SESSION['init']         = time();
                        $_SESSION['first_domain'] = $domain;
// :TODO: Make a permanent fix for this isset_get() use. These client, location, and language indices should be set, but sometimes location is NOT set for unknown reasons. Find out why it is not set, and fix that instead!
                        $_SESSION['client']       = isset_get($core->register['session']['client']);
                        $_SESSION['mobile']       = isset_get($core->register['session']['mobile']);
                        $_SESSION['location']     = isset_get($core->register['session']['location']);
                        $_SESSION['language']     = isset_get($core->register['session']['language']);
                    }
                }

                if (!isset($_SESSION['cache'])) {
                    $_SESSION['cache'] = array();
                }
            }

            $_SESSION['domain'] = $domain;

        }catch(Exception $e) {
            if ($e->getCode() == 403) {
                Log::warning($e->getMessage());
                $core->register['page_show'] = 403;

            } else {
                if (!is_writable(session_save_path())) {
                    throw new CoreException('core::manage_session(): Session startup failed because the session path ":path" is not writable for platform ":platform"', array(':path' => session_save_path(), ':platform' => PLATFORM), $e);
                }

                throw new CoreException('core::manage_session(): Session startup failed', $e);
            }
        }

        Http::setSslDefaultContext();
    }
}