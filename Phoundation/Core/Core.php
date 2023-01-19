<?php

namespace Phoundation\Core;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Cli\Cli;
use Phoundation\Cli\Script;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Exception\NoProjectException;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Validator;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Date\Date;
use Phoundation\Developer\Debug;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Commands\SystemCommands;
use Phoundation\Processes\Process;
use Phoundation\Servers\Server;
use Phoundation\Web\Http\Http;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Json;
use Phoundation\Web\Route;
use Phoundation\Web\WebPage;
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
    public const PHP_MINIMUM_VERSION  = '8.1.0';

    /**
     * Singleton variable
     *
     * @var Core|null $instance
     */
    protected static ?Core $instance = null;

    /**
     * The Core default server object
     *
     * @var Server $server_restrictions
     */
    protected static Server $server_restrictions;

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
    protected static ?string $request_type = null;

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
        'tabindex'      => 0,
        'js_header'     => [],
        'js_footer'     => [],
        'css'           => [],
        'quiet'         => true,
        'footer'        => '',
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
     * Internal flag indicating if there is a failure or not
     *
     * @var bool $failed
     */
    protected static bool $failed = false;



    /**
     * Core class constructor
     */
    protected function __construct()
    {
        try {
            self::$state = 'startup';
            self::$register['system']['startup'] = microtime(true);
            self::$register['system']['script']  = Strings::until(Strings::fromReverse($_SERVER['PHP_SELF'], '/'), '.');

            // Register the process start
            define('STARTTIME', Timer::create('process')->getStart());

            /*
             * Define a unique process request ID
             * Define project paths.
             *
             * PATH_ROOT   is the root directory of this project and should be used as the root for all other paths
             * PATH_TMP    is a private temporary directory
             * PATH_PUBTMP is a public (accessible by web server) temporary directory
             */
            define('REQUEST'     , substr(uniqid(), 7));
            define('PATH_ROOT'   , realpath(__DIR__ . '/../..') . '/');
            define('PATH_WWW'    , PATH_ROOT . 'www/');
            define('PATH_DATA'   , PATH_ROOT . 'data/');
            define('PATH_CDN'    , PATH_DATA . 'cdn/');
            define('PATH_TMP'    , PATH_DATA . 'tmp/');
            define('PATH_PUBTMP' , PATH_DATA . 'cdn/tmp/');
            define('PATH_SCRIPTS', PATH_ROOT . 'scripts/');

            // Setup error handling, report ALL errors, setup shutdown functions
            error_reporting(E_ALL);
            set_error_handler(['\Phoundation\Core\Core'         , 'phpErrorHandler']);
            set_exception_handler(['\Phoundation\Core\Core'     , 'uncaughtException']);
            register_shutdown_function(['\Phoundation\Core\Core', 'shutdown']);

// TODO Implement PCNTL functions
//            pcntl_signal(SIGTERM, ['\Phoundation\Core\Core', 'shutdown']);
//            pcntl_signal(SIGINT , ['\Phoundation\Core\Core', 'shutdown']);
//            pcntl_signal(SIGHUP , ['\Phoundation\Core\Core', 'shutdown']);

            // Load the functions and mb files
            require(PATH_ROOT . 'Phoundation/functions.php');
            require(PATH_ROOT . 'Phoundation/mb.php');

            // Set timeout and request type, ensure safe PHP configuration, apply general server restrictions, set the
            // project name, platform and request type
            self::securePhpSettings();
            self::setServerRestrictions();
            self::setProject();
            self::setPlatform();
            self::setRequestType();
            self::setTimeout();

        } catch (SqlException|NoProjectException $e) {
            throw $e;

        } catch (Throwable $e) {
            try {
                // Startup failed miserably. Don't use anything fancy here, we're dying!
                if (defined('PLATFORM_HTTP')) {
                    if (PLATFORM_HTTP) {
                        // Died in browser
                        Log::error('startup: Failed with "' . $e->getMessage() . '"');
                        WebPage::die('startup: Failed, see web server error log');
                    }

                    // Died in CLI
                    Script::shutdown(1, 'startup: Failed with "' . $e->getMessage() . '"');
                }

            } catch (Throwable $e) {
                // Even a semi proper shutdown went to crap, wut?
                @error_log($e);
            }

            // Wowza things went to @#*$@( really fast! The standard defines aren't even available yet
            @error_log('Startup failed with "' . $e->getMessage() . '", see exception below');
            @error_log($e);
            die('Startup: Failed, see error log');
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
            if (self::$state !== 'init') {
                throw new CoreException(tr('Core::startup() was run in the ":state" state. Check backtrace to see what caused this', [
                    ':state' => self::$state
                ]));
            }

            self::getInstance();
            self::selectStartup();

        } catch (Throwable $e) {
            if (PLATFORM_HTTP and headers_sent($file, $line)) {
                if (preg_match('/debug-.+\.php$/', $file)) {
                    throw new CoreException(tr('Failed because headers were already sent on ":location", so probably some added debug code caused this issue', [
                        ':location' => $file . '@' . $line
                    ]), $e);
                }

                throw new CoreException(tr('Failed because headers were already sent on ":location"', [
                    ':location' => $file . '@' . $line
                ]), $e);
            }

            throw $e;
        }
    }



    /**
     * Select what startup should be executed
     *
     * @return void
     */
    protected static function selectStartup(): void
    {
        // Detect platform and execute specific platform startup sequence
        switch (PLATFORM) {
            case 'http':
                self::startupHttp();
                break;

            case 'cli':
                self::startupCli();
        }

        // We're done, transfer control to script
        self::$state = 'script';
    }



    /**
     * Startup for HTTP requests
     *
     * @return void
     */
    protected static function startupHttp(): void
    {
        // Define basic platform constants
        define('ADMIN'   , '');
        define('PWD'     , Strings::slash(isset_get($_SERVER['PWD'])));
        define('STARTDIR', Strings::slash(getcwd()));
        define('PAGE'    , isset_get($_GET['page'], 1));
        define('ALL'     , (getenv('ALL')     ? 'ALL'     : false));
        define('DELETED' , (getenv('DELETED') ? 'DELETED' : false));
        define('FORCE'   , (getenv('FORCE')   ? 'FORCE'   : false));
        define('ORDERBY' , (getenv('ORDERBY') ? 'ORDERBY' : ''));
        define('STATUS'  , (getenv('STATUS')  ? 'STATUS'  : ''));
        define('QUIET'   , (getenv('QUIET')   ? 'QUIET'   : false));
        define('TEST'    , (getenv('TEST')    ? 'TEST'    : false));
        define('VERBOSE' , (getenv('VERBOSE') ? 'VERBOSE' : false));
        define('LIMIT'   , (getenv('LIMIT')   ? 'LIMIT'   : Config::getNatural('paging.limit', 50)));

        // Check HEAD and OPTIONS requests. If HEAD was requested, just return basic HTTP headers
// :TODO: Should pages themselves not check for this and perhaps send other headers?
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'OPTIONS':
                throw new UnderConstructionException();
        }

        // Set security umask
        umask(Config::get('filesystem.umask', 0007));

        // Set language and locale
        self::setLanguage();
        self::setLocale();

        // Prepare for unicode usage
        if (Config::get('languages.encoding.charset', 'UTF-8') === 'UTF-8') {
            mb_init(not_empty(Config::get('locale.LC_CTYPE', ''), Config::get('locale.LC_ALL', '')));

            if (function_exists('mb_internal_encoding')) {
                mb_internal_encoding('UTF-8');
            }
        }

        // Check for configured maintenance mode
        if (Config::getBoolean('system.maintenance', false)) {
            // We are in maintenance mode, have to show mainenance page.
            Route::executeSystem(503);
        }

        // Set cookie, start session where needed, etc.
        if (!self::$failed) {
            Session::startup();
        }

        self::setTimeZone();

        // If POST request, automatically untranslate translated POST entries
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//                        Html::untranslate();
//                        Html::fixCheckboxValues();

            if (Config::get('security.csrf.enabled', true) === 'force') {
                // Force CSRF checks on every submit!
//                            Http::checkCsrf();
            }
        }

        // Set the CDN url for javascript and validate HTTP GET request data
// TODO Below
//                    Html::setJsCdnUrl();
        Http::validateGet();
    }



    /**
     * Startup for Command Line Interface
     *
     * @return void
     */
    protected static function startupCli(): void
    {
        self::$request_type = 'cli';

        ArgvValidator::hideData($GLOBALS['argv']);

        $argv = ArgvValidator::new()
            ->select('--deleted')->isOptional(false)->isBoolean()
            ->select('-A,--all')->isOptional(false)->isBoolean()
            ->select('-C,--no-color')->isOptional(false)->isBoolean()
            ->select('-D,--debug')->isOptional(false)->isBoolean()
            ->select('-F,--force')->isOptional(false)->isBoolean()
            ->select('-H,--help')->isOptional(false)->isBoolean()
            ->select('-Q,--quiet')->isOptional(false)->isBoolean()
            ->select('-T,--test')->isOptional(false)->isBoolean()
            ->select('-U,--usage')->isOptional(false)->isBoolean()
            ->select('-V,--verbose')->isOptional(false)->isBoolean()
            ->select('-W,--no-warnings')->isOptional(false)->isBoolean()
            ->select('--version')->isOptional(false)->isBoolean()
            ->select('-E,--environment', true)->isOptional(null)->hasMinCharacters(1)->hasMaxCharacters(64)
            ->select('-L,--limit', true)->isOptional(Config::get('paging.limit', 50))->isId()
            ->select('-O,--order-by', true)->isOptional(null)->hasMinCharacters(1)->hasMaxCharacters(128)
            ->select('-P,--page', true)->isOptional(1)->isId()
            ->select('-S,--status', true)->isOptional(null)->hasMinCharacters(1)->hasMaxCharacters(16)
            ->select('--language', true)->isOptional(null)->isCode()
            ->select('--timezone', true)->isOptional(false)->isBoolean()
            ->select('--show-passwords')->isOptional(false)->isBoolean()
            ->select('--no-validation')->isOptional(false)->isBoolean()
            ->select('--no-password-validation')->isOptional(false)->isBoolean()
            ->validate()
            ->getArgv();

        // Define basic platform constants
        define('ADMIN'   , '');
        define('PWD'     , Strings::slash(isset_get($_SERVER['PWD'])));
        define('STARTDIR', Strings::slash(getcwd()));
        define('QUIET'   , $argv['quiet']);
        define('VERBOSE' , $argv['verbose']);
        define('FORCE'   , $argv['force']);
        define('NOCOLOR' , $argv['no_color']);
        define('TEST'    , $argv['test']);
        define('DELETED' , $argv['deleted']);
        define('ALL'     , $argv['all']);
        define('STATUS'  , $argv['status']);
        define('PAGE'    , $argv['page']);
        define('LIMIT'   , $argv['limit']);

        // Check what environment we're in
        if (isset($argv['environment'])) {
            // Environment was manually specified on command line
            $env = $argv['environment'];
        } else {
            $env = getenv('PHOUNDATION_' . PROJECT . '_ENVIRONMENT');

            if (empty($env)) {
                define('ENVIRONMENT', 'production');
                Script::shutdown(2, 'startup: No required environment specified for project "' . PROJECT . '"');
            }
        }

        // Set environment and protocol
        define('ENVIRONMENT', $env);
        define('PROTOCOL', Config::get('web.protocol', 'https://'));

        // Correct $_SERVER['PHP_SELF'], sometimes seems empty
        if (empty($_SERVER['PHP_SELF'])) {
            if (!isset($_SERVER['_'])) {
                $e = new OutOfBoundsException('No $_SERVER[PHP_SELF] or $_SERVER[_] found');
            }

            $_SERVER['PHP_SELF'] = $_SERVER['_'];
        }

        // Process command line system arguments if we have no exception so far
        if ($argv['version']) {
            Log::information(tr('Phoundation framework code version ":fv"', [
                ':fv' => self::FRAMEWORKCODEVERSION
            ]));
            $die = 0;
        }

        // Set more system parameters
        if ($argv['debug']) {
            Debug::enabled();
        }

        if ($argv['usage']) {
            Script::showUsage(isset_get($GLOBALS['usage']), 'white');
            $die = 0;
        }

        if ($argv['help']) {
            if (isset_get($GLOBALS['argv'][$argid + 1]) == 'system') {
                Script::showHelp('system');

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
        }

        if ($argv['language']) {
            // Set language to be used
            if (isset($language)) {
                $e = new CoreException(tr('Language has been specified twice'));
            }

            if (!isset($GLOBALS['argv'][$argid + 1])) {
                $e = new CoreException(tr('The "language" argument requires a two letter language core right after it'));
            }

            $language = $GLOBALS['argv'][$argid + 1];

            unset($GLOBALS['argv'][$argid]);
            unset($GLOBALS['argv'][$argid + 1]);
        }

        if ($argv['order_by']) {
            define('ORDERBY', ' ORDER BY `' . Strings::until($argv['order_by'], ' ') . '` ' . Strings::from($argv['order_by'], ' ') . ' ');

            $valid = preg_match('/^ ORDER BY `[a-z0-9_]+`(?:\s+(?:DESC|ASC))? $/', ORDERBY);

            if (!$valid) {
                // The specified column ordering is NOT valid
                $e = new CoreException(tr('The specified orderby argument ":argument" is invalid', [':argument' => ORDERBY]));
            }

            unset($GLOBALS['argv'][$argid]);
            unset($GLOBALS['argv'][$argid + 1]);
        }

        if ($argv['timezone']) {
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
        }

        if ($argv['no_warnings']) {
            define('NOWARNINGS', true);
        }

        if ($argv['show_passwords']) {
            Cli::showPasswords(true);
        }

        if ($argv['no_validation']) {
            Validator::disable();
        }

        if ($argv['no_password_validation']) {
            Validator::disablePasswords();
        }

        // Remove the command itself from the argv array
        array_shift($GLOBALS['argv']);

        // Set timeout
        self::setTimeout();

        // Something failed?
        if (isset($e)) {
            echo "startup-cli: Command line parser failed with \"" . $e->getMessage() . "\"\n";
            Script::setExitCode(1);
            die(1);
        }

        if (isset($die)) {
            Script::shutdown($die);
        }

        // set terminal data
        self::$register['cli'] = ['term' => Cli::getTerm()];

        if (self::$register['cli']['term']) {
            self::$register['cli']['columns'] = Cli::getColumns();
            self::$register['cli']['lines'] = Cli::getLines();

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
            $language = not_empty($argv['language'], Config::get('language.default', 'en'));

            if (Config::get('language.default', ['en']) and Config::exists('language.supported.' . $language)) {
                throw new CoreException(tr('Unknown language ":language" specified', array(':language' => $language)), 'unknown');
            }

            define('LANGUAGE', $language);
            define('LOCALE', $language . (empty($_SESSION['location']['country']['code']) ? '' : '_' . $_SESSION['location']['country']['code']));

            $_SESSION['language'] = $language;

        } catch (Throwable $e) {
            // Language selection failed
            if (!defined('LANGUAGE')) {
                define('LANGUAGE', 'en');
            }

            $e = new CoreException('Language selection failed', $e);
        }

        // Setup locale and character encoding
        // TODO Check this mess!
        ini_set('default_charset', Config::get('languages.encoding.charset', 'UTF-8'));
        self::$register['system']['locale'] = self::setLocale();

        // Prepare for unicode usage
        if (Config::get('languages.encoding.charset', 'UTF-8') === 'UTF-8') {
// TOOD Fix this godawful mess!
            mb_init(not_empty(Config::get('locale.LC_CTYPE', ''), Config::get('locale.LC_ALL', '')));

            if (function_exists('mb_internal_encoding')) {
                mb_internal_encoding('UTF-8');
            }
        }

        self::setTimeZone();

        //
        self::$register['ready'] = true;

        // Validate parameters and give some startup messages, if needed
        if (Debug::enabled()) {
            if (QUIET) {
                throw new CoreException(tr('Both QUIET and Debug::enabled() have been specified but these options are mutually exclusive. Please specify either one or the other'));
            }

            if (Debug::enabled()) {
                Log::information(tr('Running in Debug::enabled() mode, started @ ":datetime"', array(':datetime' => Date::convert(STARTTIME, 'human_datetime'))));

            } else {
                Log::information(tr('Running in Debug::enabled() mode, started @ ":datetime"', array(':datetime' => Date::convert(STARTTIME, 'human_datetime'))));
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
            Log::warning(tr('Running in DEBUG mode'), 8);
        }

        if (!is_natural(PAGE)) {
            throw new CoreException(tr('Specified -P or --page ":page" is not a natural number', [
                ':page' => PAGE
            ]));
        }

        if (!is_natural(LIMIT)) {
            throw new CoreException(tr('Specified --limit":limit" is not a natural number', [
                ':limit' => LIMIT
            ]));
        }

        if (ALL) {
            if (PAGE > 1) {
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
        // Route::map();
    }



    /**
     * Checks what platform we're running on and sets definitions for those
     *
     * @return void
     */
    protected static function setPlatform(): void
    {
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

                if (PROJECT === 'UNKNOWN') {
                    $env = 'UNKNOWN';
                } else {
                    // Check what environment we're in
                    $env = getenv('PHOUNDATION_' . PROJECT . '_ENVIRONMENT');

                    if (empty($env)) {
                        // No environment set in ENV, maybe given by parameter?
                        WebPage::die(1, 'startup: No required environment specified for project "' . PROJECT . '"');
                    }

                    if (str_contains($env, '_')) {
                        WebPage::die(1, 'startup: Specified environment "' . $env . '" is invalid, environment names cannot contain the underscore character');
                    }
                }

                // Set environment and protocol
                define('ENVIRONMENT', $env);
                define('PROTOCOL', Config::get('web.protocol', 'https://'));

                // Register basic HTTP information
                // TODO MOVE TO HTTP CLASS
                self::$register['http']['code'] = 200;
//                    self::$register['http']['accepts'] = Page::accepts();
//                    self::$register['http']['accepts_languages'] = Page::acceptsLanguages();
                break;
        }
    }



    /**
     * Set general file access restrictions
     *
     * @return void
     */
    protected static function setServerRestrictions(): void
    {
        // Set up the Core restrictions object with default file access restrictions
        self::$server_restrictions = new Server(new Restrictions(PATH_DATA, false, 'Core'));
    }



    /**
     * Detect and set the project name
     *
     * @return void
     */
    protected static function setProject(): void
    {
        // Get the project name
        try {
            define('PROJECT', strtoupper(trim(file_get_contents( PATH_ROOT . 'config/project'))));

            if (!PROJECT) {
                throw new OutOfBoundsException('No project defined in PATH_ROOT/config/project file');
            }
        } catch (Throwable $e) {
            self::$failed = true;
            define('PROJECT', 'UNKNOWN');

            if ($e instanceof OutOfBoundsException) {
                throw $e;
            }

            // Project file is not readable
            if(!is_readable(PATH_ROOT . 'config/project')) {
                self::setPlatform();
                self::setRequestType();
                self::selectStartup();

                throw new NoProjectException('Project file "' . PATH_ROOT . 'config/project" cannot be read. Please ensure it exists');
            }
        }

    }



    /**
     * Determine the request type
     *
     * @return void
     */
    protected static function setRequestType(): void
    {
        if (PLATFORM_HTTP) {
            // Determine what our target file is. With direct execution, $_SERVER[PHP_SELF] would contain this, with
            // route execution, $_SERVER[PHP_SELF] would be route, so we cannot use that. Route will store the file
            // being executed in self::$register['script_path'] instead
            $file = $_SERVER['REQUEST_URI'];

            // Autodetect what http call type we're on from the script being executed
            if (str_contains($file, '/admin/')) {
                self::$request_type = 'admin';

            } elseif (str_contains($file, '/ajax/')) {
                self::$request_type = 'ajax';

            } elseif (str_contains($file, '/api/')) {
                self::$request_type = 'api';

            } elseif ((str_starts_with($_SERVER['SERVER_NAME'], 'api')) and preg_match('/^api(?:-[0-9]+)?\./', $_SERVER['SERVER_NAME'])) {
                self::$request_type = 'api';

            } elseif ((str_starts_with($_SERVER['SERVER_NAME'], 'cdn')) and preg_match('/^cdn(?:-[0-9]+)?\./', $_SERVER['SERVER_NAME'])) {
                self::$request_type = 'api';

            } elseif (Config::get('web.html.amp.enabled', false) and !empty($_GET['amp'])) {
                self::$request_type = 'amp';

            } elseif (is_numeric(substr($file, -3, 3))) {
                self::$register['http']['code'] = substr($file, -3, 3);
                self::$request_type = 'system';

            } else {
                self::$request_type = 'http';
            }

        } else {
            // We're running on the command line
            self::$request_type = 'cli';
        }
    }



    /**
     * Returns true if the Core is running in failed state
     *
     * @return bool
     */
    public static function getFailed(): bool
    {
        return self::$failed;
    }



    /**
     * Read and return the specified key / sub key from the core register.
     *
     * @note Will return NULL if the specified key does not exist
     * @param string $key
     * @param string|null $subkey
     * @param mixed|null $default
     * @return mixed
     */
    public static function readRegister(string $key, ?string $subkey = null, mixed $default = null): mixed
    {
        if ($subkey) {
            $return = isset_get(self::$register[$key][$subkey]);
        } else {
            $return = isset_get(self::$register[$key]);
        }

        if ($return === null) {
            // Specified key / subkey doesn't exist or is NULL, return default
            return $default;
        }

        return $return;
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
// TODO Check how to fix this later
//            throw new AccessDeniedException('The "system" register cannot be written to');
        }

        if ($subkey) {
            // We want to write to a sub key. Ensure that the key exists and is an array
            if (array_key_exists($key, self::$register)) {
                if (!is_array(self::$register[$key])) {
                    // Key exists but is not an array so cannot handle sub keys
                    throw new CoreException('Cannot write to register key ":key.:subkey" as register key ":key" already exist as a value instead of an array', [':key' => $key, 'subkey' => $subkey]);
                }
            } else {
                // Libraries the register sub array
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
     * @param string|null $state
     * @return void
     */
    public static function setState(#[ExpectedValues(values: ["error", "phperror", "init", "startup", "script", "shutdow"])] ?string $state): void
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
            case 'script':
                // no-break
            case 'shutdown':
                // These are not allowed
                throw new OutOfBoundsException(tr('Core state update to ":state" is not allowed. Core state can only be updated to "error" or "phperror"', [
                    ':state' => $state
                ]));

            default:
                // Wut?
                throw new OutOfBoundsException(tr('Unknown core state ":state" specified. Core state can only be updated to "error" or "phperror"', [
                    ':state' => $state
                ]));
        }
    }



    /**
     * Returns true if the system is still starting up
     *
     * @param string|null $state If specified will return the startup state for the specified state instead of the
     *                           internal Core state
     * @return bool
     * @see Core::getState()
     * @see Core::initState()
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
     * Returns true if the system is still starting up
     *
     * @param string|null $state If specified will return the startup state for the specified state instead of the
     *                           internal Core state
     * @return bool
     * @see Core::getState()
     * @see Core::startupState()
     */
    public static function initState(?string $state = null): bool
    {
        if ($state === null) {
            $state = self::$state;
        }

        return $state === 'init';
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
    public static function getRequestType(): string
    {
        return self::$request_type;
    }



    /**
     * Will return true if $call_type is equal to core::callType, false if not.
     *
     * @param string $type The call type you wish to compare to
     * @return bool This function will return true if $type matches core::callType, or false if it does not.
     */
    public static function isCallType(string $type): bool
    {
        return (self::$request_type === $type);
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

                Notification::new()
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

        Notification::new()
            ->setCode('PHP-ERROR-' . $errno)
            ->addGroup('developers')
            ->setTitle('PHP ERROR "' . $errno . '"')
            ->setMessage(tr('PHP ERROR [' . $errno . '] "' . $errstr . '" in "' . $errfile . '@' . $errline .'"'))
            ->setDetails([
                'errno'   => $errno,
                'errstr'  => $errstr,
                'errfile' => $errfile,
                'errline' => $errline,
                'trace'   => $trace
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
         *    Check the PATH_ROOT/data/log/syslog (or exception log if you have single_log
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

        // Ensure that definitions exist
        $defines = [
            'ADMIN'    => '',
            'PWD'      => Strings::slash(isset_get($_SERVER['PWD'])),
            'STARTDIR' => Strings::slash(getcwd()),
            'FORCE'    => (getenv('FORCE')   ? 'FORCE'   : null),
            'TEST'     => (getenv('TEST')    ? 'TEST'    : null),
            'QUIET'    => (getenv('QUIET')   ? 'QUIET'   : null),
            'LIMIT'    => (getenv('LIMIT')   ? 'LIMIT'   : Config::get('paging.limit', 50)),
            'ORDERBY'  => (getenv('ORDERBY') ? 'ORDERBY' : null),
            'ALL'      => (getenv('ALL')     ? 'ALL'     : null),
            'DELETED'  => (getenv('DELETED') ? 'DELETED' : null),
            'STATUS'   => (getenv('STATUS')  ? 'STATUS'  : null)
        ];

        foreach ($defines as $key => $value) {
            if (!defined($key)) {
                define($key, $value);
            }
        }

        // Start processing the uncaught exception
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
                    Log::error(tr('*** UNCAUGHT EXCEPTION ":code" IN ":type" TYPE SCRIPT ":script" ***', [':code' => $e->getCode(), ':type' => self::getRequestType(), ':script' => self::readRegister('system', 'script')]));
                    Log::error($e);
                    die('exception before platform detection');
                }

                switch (PLATFORM) {
                    case 'cli':
                        /*
                         * Command line script crashed.
                         *
                         * If not using Debug::enabled() mode, then try to give nice error messages
                         * for known issues
                         */
                        if (($e instanceof ValidationFailedException) and $e->isWarning()) {
                            // This is just a simple validation warning, show warning messages in the exception data
                            Log::warning(tr('Validation warning: :warning', [':warning' => $e->getMessage()]));
                            Log::warning($e->getData());
                            Script::shutdown(255);
                        }

                        if (($e instanceof Exception) and $e->isWarning()) {
                            // This is just a simple general warning, no backtrace and such needed, only show the
                            // principal message
                            Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
                            Script::shutdown(255);
                        }

// TODO Remplement this with proper exception classes
//                            switch ((string) $e->getCode()) {
//                                case 'already-running':
//                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
//                                    Script::setExitCode(254);
//                                    die(Script::getExitCode());
//
//                                case 'no-method':
//                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
//                                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
//                                    Script::setExitCode(253);
//                                    die(Script::getExitCode());
//
//                                case 'unknown-method':
//                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
//                                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
//                                    Script::setExitCode(252);
//                                    die(Script::getExitCode());
//
//                                case 'missing-arguments':
//                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
//                                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
//                                    Script::setExitCode(253);
//                                    die(Script::getExitCode());
//
//                                case 'invalid-arguments':
//                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
//                                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
//                                    Script::setExitCode(251);
//                                    die(Script::getExitCode());
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
//                                    Script::setExitCode(250);
//                                    die(Script::getExitCode());
//                            }

                        Log::error(tr('*** UNCAUGHT EXCEPTION ":code" IN ":type" TYPE SCRIPT ":script" ***', [':code' => $e->getCode(), ':type' => self::getRequestType(), ':script' => self::readRegister('system', 'script')]));
                        Log::error(tr('Exception data:'));
                        Log::error($e);
                        Script::shutdown(1);

                    case 'http':
                        if ($e instanceof ValidationFailedException) {
                            // This is just a simple validation warning, show warning messages in the exception data
                            Log::warning(tr('Validation warning: :warning', [':warning' => $e->getMessage()]));
                            Log::warning($e->getData());
                            Script::shutdown(255);
                        } elseif (($e instanceof Exception) and ($e->isWarning())) {
                            // This is just a simple general warning, no backtrace and such needed, only show the
                            // principal message
                            Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
                        } else {
                            // Log exception data
                            Log::error(tr('*** UNCAUGHT EXCEPTION ":code" IN ":type" TYPE SCRIPT ":script" ***', [':code' => $e->getCode(), ':type' => self::getRequestType(), ':script' => self::readRegister('system', 'script')]));
                            Log::error(tr('Exception data:'));
                            Log::error($e);
                        }

                        // Make sure the Router shutdown won't happen so it won't send a 404
                        Core::unregisterShutdown('route_postprocess');

                        // Remove all caching headers
                        if (!headers_sent()) {
                            header_remove('ETag');
                            header_remove('Cache-Control');
                            header_remove('Expires');
                            header_remove('Content-Type');
                        }

                        //
                        WebPage::setHttpCode(500);
                        self::unregisterShutdown('route_postprocess');

                        Notification::new()
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

                            WebPage::die(tr('System startup exception. Please check your PATH_ROOT/data/log directory or application or webserver error log files, or enable the first line in the exception handler file for more information'));
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
                            // We're trying to show an html error here!
                            if (!headers_sent()) {
                                http_response_code(500);
                                header('Content-Type: text/html', true);
                            }

                            switch (Core::getRequestType()) {
                                case 'api':
                                    // no-break
                                case 'ajax':
                                    echo "UNCAUGHT EXCEPTION\n\n";
                                    showdie($e);
                            }

                            $return = ' <style>
                                        table.exception{
                                            font-family: sans-serif;
                                            width:99%;
                                            background:#AAAAAA;
                                            border-collapse:collapse;
                                            border-spacing:2px;
                                            margin: 5px auto 5px auto;
                                            border-radius: 10px;
                                        }
                                        td.center{
                                            text-align: center;
                                        }
                                        table.exception thead{
                                            background: #CE0000;
                                            color: white;
                                            font-weight: bold;
                                        }
                                        table.exception thead td{
                                            border-top-left-radius: 10px;
                                            border-top-right-radius: 10px;
                                        }
                                        table.exception td{
                                            border: 0;
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
                                                    '.tr('*** UNCAUGHT EXCEPTION ":code" IN ":type" TYPE SCRIPT ":script" ***', array(':code' => $e->getCode(), ':script' => self::readRegister('system', 'script'), 'type' => Core::getRequestType())).'
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

                            if (!headers_sent()) {
                                header_remove('Content-Type');
                                header('Content-Type: text/html', true);
                            }

                            echo $return;

                            if ($e instanceof CoreException) {
                                // Clean data
                                $e->setData(Arrays::hide(Arrays::force($e->getData()), 'GLOBALS,%pass,ssh_key'));
                            }

                            showdie($e);
                        }

                        // We're not in debug mode.
                        Notification::new()
                            ->setException($e)
                            ->send();

                        switch (Core::getRequestType()) {
                            case 'api':
                                // no-break
                            case 'ajax':
                                if ($e instanceof CoreException) {
                                    Json::message($e->getCode(), ['reason' => ($e->isWarning() ? trim(Strings::from($e->getMessage(), ':')) : '')]);
                                }

                                // Assume that all non CoreException exceptions are not warnings!
                                Json::message($e->getCode(), ['reason' => '']);
                        }

                        Route::executeSystem($e->getCode());
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
                    die('System startup exception with handling failure. Please check your PATH_ROOT/data/log directory or application or webserver error log files, or enable the first line in the exception handler file for more information');
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
                            Notification::new()->setException($f)->send();
                            Notification::new()->setException($e)->send();
                            page_show(500);
                        }

                        show(tr('*** UNCAUGHT EXCEPTION HANDLER CRASHED FOR SCRIPT ":script" ***', array(':script' => self::readRegister('system', 'script'))));
                        show('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***');

                        show($f);
                        showdie($e);
                }
            }

        }catch(Throwable $g) {
            // Well, we tried. Here we just give up all together. Don't do anything anymore because every step from here
            // will fail anyway. Just die
            die("Fatal error. check data/syslog, application server logs, or webserver logs for more information\n");
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
     * Set the language for this request
     *
     * @return void
     */
    protected static function setLanguage(): void
    {
        try {
            $supported = Config::get('languages.supported', ['en', 'es']);

            if ($supported) {
                // Language is defined by the www/LANGUAGE dir that is used.
                $url = $_SERVER['REQUEST_URI'];
                $url = Strings::startsNotWith($url, '/');
                $language = Strings::until($url, '/');

                if (!in_array($language, $supported)) {
                    $language = Config::get('languages.default', 'en');

                    Log::warning(tr('Detected language ":language" is not supported, falling back to default. See configuration languages.supported', [
                            ':language' => $language]
                    ));
                }

            } else {
                $language = Config::get('languages.default', 'en');
            }

            define('LANGUAGE', $language);
            define('LOCALE', $language . (empty($_SESSION['location']['country']['code']) ? '' : '_' . $_SESSION['location']['country']['code']));

            // Ensure $_SESSION['language'] available
            if (empty($_SESSION['language'])) {
                $_SESSION['language'] = LANGUAGE;
            }

        } catch (Throwable $e) {
            // Language selection failed
            if (!defined('LANGUAGE')) {
                define('LANGUAGE', 'en');
            }

            $e = new OutOfBoundsException('Language selection failed', $e);
        }
    }



    /**
     * Apply the specified or configured locale
     *
     * @todo what is this supposed to return anyway?
     * @return void
     */
    public static function setLocale(): void
    {
        // Setup locale and character encoding
        // TODO Check this mess!
        ini_set('default_charset', Config::get('languages.encoding.charset', 'UTF-8'));

        $locale = Config::get('locale', [
            LC_ALL      => ':LANGUAGE_:COUNTRY.UTF8',
            LC_COLLATE  => null,
            LC_CTYPE    => null,
            LC_MONETARY => null,
            LC_NUMERIC  => null,
            LC_TIME     => null,
            LC_MESSAGES => null
        ]);

        if (!is_array($locale)) {
            throw new CoreException(tr('Specified $data should be an array but is an ":type"', [
                ':type' => gettype($locale)
            ]));
        }

        // Determine language and location
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

        self::$register['system']['locale'] = $locale;
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
        // First find the global data path. For now, either same height as this project, OR one up the filesystem tree
        $paths = [
            '/var/lib/data/',
            '/var/www/data/',
            PATH_ROOT.'../data/',
            PATH_ROOT.'../../data/'
        ];

        if (!empty($_SERVER['HOME'])) {
            // Also check the users home directory
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
            // Cleanup path. If realpath fails, we know something is amiss
            if (!$found = realpath($found)) {
                throw new CoreException(tr('Found path ":path" failed realpath() check', [':path' => $path]));
            }
        }

        if (!$found) {
            if (!PLATFORM_CLI) {
                throw new CoreException('get_global_data_path(): Global data path not found', 'not-exists');
            }

            try {
                Log::warning(tr('Warning: Global data path not found. Normally this path should exist either 1 directory up, 2 directories up, in /var/lib/data, /var/www/data, $USER_HOME/projects/data, or $USER_HOME/data'));
                Log::warning(tr('Warning: If you are sure this simply does not exist yet, it can be created now automatically. If it should exist already, then abort this script and check the location!'));

                // TODO Do this better, this is crap
                $path = Process::newCliScript('base/init_global_data_path')->executeReturnArray();

                if (!file_exists($path)) {
                    // Something went wrong and it was not created anyway
                    throw new CoreException(tr('Configured path ":path" was created but it could not be found', [
                        ':path' => $path
                    ]));
                }

                // Its now created! Strip "data/"
                $path = Strings::slash($path);

            }catch(Exception $e) {
                throw new CoreException('get_global_data_path(): Global data path not found, or init_global_data_path failed / aborted', $e);
            }
        }

        // Now check if the specified section exists
        if ($section and !file_exists($path . $section)) {
            Path::ensure($path . $section);
        }

        if ($writable and !is_writable($path . $section)) {
            throw new CoreException(tr('The global path ":path" is not writable', [
                ':path' => $path . $section
            ]));
        }

        if (!$global_path = realpath($path . $section)) {
            // Curious, the path exists, but realpath failed and returned false. This should never happen since we
            // ensured the path above! This is just an extra check in case of.. weird problems :)
            throw new CoreException(tr('The found global data path ":path" is invalid (realpath returned false)', [
                ':path' => $path
            ]));
        }

        return Strings::slash($global_path);
    }



    /**
     * Register a shutdown function
     *
     * @note Function can be either a function name, a callable function, or an array with static object::method or an
     *       array with [$object, 'methodname']
     *
     * @param string $identifier
     * @param array|string|callable $function
     * @param mixed $data
     * @return void
     */
    public static function registerShutdown(string $identifier, array|string|callable $function, mixed $data = null): void
    {
        if (!is_array(self::readRegister('system', 'shutdown'))) {
            // Libraries shutdown list
            self::$register['system']['shutdown'] = [];
        }

        self::$register['system']['shutdown'][$identifier] = [
            'data'     => $data,
            'function' => $function
        ];
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
     * @see Core::registerShutdown()
     * @version 1.27.0: Added function and documentation
     *
     * @param string $identifier
     * @return bool
     */
    public static function unregisterShutdown(string $identifier): bool
    {
        if (!is_array(self::readRegister('system', 'shutdown'))) {
            // Libraries shutdown list
            self::$register['system']['shutdown'] = [];
        }

        if (array_key_exists($identifier, self::$register['system']['shutdown'])) {
            unset(self::$register['system']['shutdown'][$identifier]);
            return true;
        }

        return false;
    }



    /**
     * Kill this process
     *
     * @todo Add required functionality
     * @return void
     */
    #[NoReturn] public static function die(): void
    {
        // Do we need to run other shutdown functions?
        if (PLATFORM_HTTP) {
            Core::die();
        }

        Core::die();
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
                Log::error(tr('Shutdown procedure started before self::$register[script] was ready, possibly on script ":script"', [
                    ':script' => $_SERVER['PHP_SELF']
                ]));
                return;
            }

            // We're in error mode and already know it, don't do normal shutdown
            return;
        }

        Log::notice(tr('Starting shutdown procedure for script ":script"', [
            ':script' => self::readRegister('system', 'script')
        ]), 2);

        if (!is_array(self::readRegister('system', 'shutdown'))) {
            // Libraries shutdown list
            self::$register['system']['shutdown'] = [];
        }

        // Reverse the shutdown calls to execute them last added first, first added last
        self::$register['system']['shutdown'] = array_reverse(self::$register['system']['shutdown']);

        foreach (self::$register['system']['shutdown'] as $identifier => $data) {
            try {
                $function = $data['function'];
                $data     = Arrays::force($data['data'], null);

                // If no data was specified at all, then ensure at least one NULL value
                if (!$data) {
                    $data = [null];
                }

                // Execute this shutdown function for each data value
                foreach ($data as $value) {
                    Log::notice(tr('Executing shutdown function ":identifier" with data value ":value"', [
                        ':identifier' => $identifier,
                        ':value'      => $value
                    ]));

                    if (is_callable($function)) {
                        // Execute this call directly
                        $function($value);
                        continue;
                    }

                    if (is_string($function)) {
                        if (str_contains($function, ',')) {
                            // This is an array containing components. Explode and treat as array
                            $function = explode(',', $function);
                        } else {
                            $function[0]::{$function[1]}($value);
                            continue;
                        }
                    }

                    // Execute this shutdown function with the specified value
                    if (is_array($function)) {
                        // Decode the array contents. If anything is not correct, it will no-break fall through to the
                        // warning log
                        if (count($function) === 2) {
                            // The first entry can either be a class name string or an object
                            if (is_object($function[0])) {
                                if (is_string($function[1])) {
                                    // Execute the method in the specified object
                                    $function[0]->$function[1]($value);
                                    continue;
                                }

                                // no-break
                            } elseif (is_string($function[0])) {
                                if (is_string($function[1])) {
                                    // Ensure the class file is loaded
                                    Debug::loadClassFile($function[0]);

                                    // Execute this shutdown function with the specified value
                                    $function[0]::{$function[1]}($value);
                                    continue;
                                }

                                // no-break
                            }

                            // no-break
                        }

                        // no-break
                    }

                    Log::warning(tr('Unknown function information ":function" encountered, quietly skipping', [
                        ':function' => $function
                    ]));
                }

            } catch (Throwable $e) {
                Notification::new()
                    ->setException($e)
                    ->send(true);
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
                        Log::notice(tr('Executing periodical shutdown function ":function()"', [
                            ':function' => $name
                        ]));

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
     * Returns the memory limit in bytes
     *
     * @return int
     */
    public static function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        $limit = Numbers::fromBytes($limit, 'b');

        if ($limit === -1) {
            // No memory limit configured, just get how much memory we have available in total
            $free  = SystemCommands::new()->free();
            $limit = ceil($free['memory']['available'] * .8);
        }

        return $limit;
    }



    /**
     * Returns the memory limit in bytes
     *
     * @return int
     */
    public static function getMemoryAvailable(): int
    {
        $limit = self::getMemoryLimit();
        $used  = memory_get_usage();

        return $limit - $used;
    }



    /**
     * Returns either the specified restrictions object or the Core restrictions object
     *
     * With this, availability of restrictions is guaranteed, even if a function did not receive restrictions. If Core
     * restrictions are returned, these core restrictions are the ones that apply
     *
     * @param Restrictions|array|string|null $restrictions
     * @return Restrictions
     */
    public static function ensureRestrictions(Restrictions|array|string|null $restrictions = null): Restrictions
    {
        if ($restrictions) {
            if (!is_object($restrictions)) {
                // Restrictions were specified by simple path string or array of paths. Convert to restrictions object
                $restrictions = new Restrictions($restrictions);
            }

            return $restrictions;
        }

        return self::$server_restrictions->getRestrictions();
    }



    /**
     * Returns either the specified restrictions object or the Core restrictions object
     *
     * With this, availability of restrictions is guaranteed, even if a function did not receive restrictions. If Core
     * restrictions are returned, these core restrictions are the ones that apply
     *
     * @param Server|Restrictions|array|string|null $server_restrictions
     * @param Server|Restrictions|array|string|null $default
     * @return Server
     */
    public static function ensureServer(Server|Restrictions|array|string|null $server_restrictions = null, Server|Restrictions|array|string|null $default = null, ?string $label = null): Server
    {
        if ($server_restrictions) {
            if (!is_object($server_restrictions)) {
                // Restrictions were specified by simple path string or array of paths. Convert to restrictions object
                $server_restrictions = new Server($server_restrictions, 'localhost', $label);
            } elseif ($server_restrictions instanceof Restrictions) {
                    // Server was specified by Restrictions object
                    $server_restrictions = new Server($server_restrictions, 'localhost', $label);
            } else {
                $server_restrictions->setLabel($label);
            }

            return $server_restrictions;
        }

        // Server was not specified. Try the default, if specified?
        if ($default) {
            if (!is_object($default)) {
                // Restrictions were specified by simple path string or array of paths. Convert to restrictions object
                return new Server($default, 'localhost', $label);
            }

            return $default->setLabel($label);
        }

        // Nope, fall back to the default restrictions
        return self::$server_restrictions;
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

        if (Script::getProcessUid() !== getmyuid()) {
            if (!Script::getProcessUid() and $permit_root) {
                // Root is authorized!
                return;
            }

            if (!$auto_switch) {
                throw new CoreException(tr('The user ":puser" is not allowed to execute these scripts, only user ":fuser" can do this. use "sudo -u :fuser COMMANDS instead.', [
                    ':puser' => get_current_user(),
                    ':fuser' => cli_get_process_user()
                ]));
            }

            // Re-execute this command as the specified user
            Log::warning(tr('Current user ":user" is not authorized to execute this script, re-executing script as user ":reuser"', [
                ':user' => Script::getProcessUid(),
                ':reuser' => getmyuid()
            ]));

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
            Process::new(PATH_ROOT . '/cli')
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
            // Accounts timezone failed, use the configured one
            Notification::new()
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
}