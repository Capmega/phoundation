<?php

/**
 * Class Core
 *
 * This is the core class for the entire system.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Core;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Audio\Audio;
use Phoundation\Cli\CliAutoComplete;
use Phoundation\Cli\CliCommand;
use Phoundation\Cli\Exception\CliCommandNotFoundException;
use Phoundation\Cli\Exception\CliNoCommandSpecifiedException;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Exception\CoreReadonlyException;
use Phoundation\Core\Exception\CoreStartupFailedException;
use Phoundation\Core\Exception\ProcessRequiresRootException;
use Phoundation\Core\Exception\ProjectException;
use Phoundation\Core\Exception\StartupException;
use Phoundation\Core\Interfaces\CoreInterface;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Libraries\Library;
use Phoundation\Core\Libraries\Version;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Meta\Meta;
use Phoundation\Core\Modes\Interfaces\ModeInterface;
use Phoundation\Core\Modes\Mode;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\Exception\DataEntryReadonlyException;
use Phoundation\Data\Traits\TraitDataStaticIsExecutedPath;
use Phoundation\Data\Traits\TraitDataStaticReadonly;
use Phoundation\Data\Traits\TraitGetInstance;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Date\PhoDateTimeZone;
use Phoundation\Developer\Debug;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\EnvironmentException;
use Phoundation\Exception\EnvironmentNotDefinedException;
use Phoundation\Exception\EnvironmentNotExistsException;
use Phoundation\Exception\PhoException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhpException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Notifications\Notification;
use Phoundation\Os\Processes\Commands\Free;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Exception\ConfigException;
use Phoundation\Utils\Exception\ConfigFileDoesNotExistsException;
use Phoundation\Utils\Exception\ConfigParseFailedException;
use Phoundation\Utils\Exception\ConfigurationInvalidException;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\FlashMessage;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Http\Http;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\JsonPage;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use Phoundation\Web\Routing\Route;
use Phoundation\Web\Uploads\UploadHandlers;
use Throwable;


class Core implements CoreInterface
{
    use TraitGetInstance;
    use TraitDataStaticReadonly;
    use TraitDataStaticIsExecutedPath;

    /**
     * Framework version and minimum required PHP version
     */
    public const string FRAMEWORK_CODE_VERSION = '4.12.0';

    public const string PHP_MINIMUM_VERSION    = '8.3.0';


    /**
     * Singleton variable
     *
     * @var CoreInterface|null $instance
     */
    protected static ?CoreInterface $instance = null;

    /**
     * A unique local code for this log entry
     *
     * @var string
     */
    protected static string $local_id;

    /**
     * A unique global code for this log entry that is the same code over multiple machines to be able to follow
     * multi-machine requests more easily
     *
     * @var string
     */
    protected static string $global_id;

    /**
     * The generic system register to store data
     *
     * @var bool $debug
     */
    protected static bool $debug = false;

    /**
     * @var array $register
     * @TODO Get rid of this internal register completely
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
        'debug_queries' => [],
    ];

    /**
     * Keeps track of if the core is ready for script execution or not
     *
     * @var bool
     * @TODO Replace this with using Core state
     */
    protected static bool $ready = false;

    /**
     * Keep track of system state
     *
     * Can be one of:
     *
     * NULL        The system state has not yet been defined
     * boot        The system Core is booting, no configuration available yet
     * startup     The system Core is starting up
     * script      The script execution is now running
     * maintenance The System is in maintenance state
     * setup       The system is in setup state
     * shutdown    The system is shutting down after normal script execution
     *
     * @var string|null $state
     */
    protected static ?string $state = null;

    /**
     * Keep track of system error state. If true, system is in error
     *
     * @var bool $error_state
     * @todo Merge $error_state and $failed
     */
    protected static bool $error_state = false;

    /**
     * Internal flag indicating if there is a failure or not
     *
     * @var bool $failed
     * @todo Merge $error_state and $failed
     */
    protected static bool $failed = false;

    /**
     * If true, script processing has started
     *
     * @var bool $script
     */
    protected static bool $script = false;

    /**
     * Usleep timestamp for the Core::usleep() call
     *
     * @var int|null $usleep
     */
    protected static ?int $usleep = null;

    /**
     * Sleep timestamp for the Core::usleep() call
     *
     * @var int|null $sleep
     */
    protected static ?int $sleep = null;

    /**
     * Temporary storage for any data
     *
     * @todo Remove this internal storage completely
     * @var array $storage
     */
    protected static array $storage = [];

    /**
     * The Core main timer
     *
     * @var Timer
     * @todo Remove this, use Timers class
     */
    protected static Timer $timer;

    /**
     * Tracks if the system is in an init state or not
     *
     * @var bool $init
     */
    protected static bool $init = false;

    /**
     * Contains a list of functions with identifiers to be executed on shutdown
     *
     * @var array $shutdown_callbacks
     */
    protected static array $shutdown_callbacks = [];

    /**
     * Tracks if Core handles error or not
     *
     * @var bool $error_handling
     */
    protected static bool $error_handling = true;

    /**
     * Tracks if Core handles shutdown or not
     *
     * @var bool $exception_handling
     */
    protected static bool $exception_handling = true;

    /**
     * Tracks if Core handles shutdown or not
     *
     * @var bool $shutdown_handling
     */
    protected static bool $shutdown_handling = true;

    /**
     * Tracks if the core will ignore the readonly mode file
     *
     * @var bool $ignore_readonly
     */
    protected static bool $ignore_readonly = false;

    /**
     * Tracks if we're running unit Tests
     *
     * @var bool $unit_test_mode
     */
    protected static bool $unit_test_mode = false;


    /**
     * Core constructor
     *
     * Core class is a singleton and should NEVER be instantiated
     */
    protected function __construct()
    {
    }


    /**
     * Boot up low level Core functions
     *
     * @return void
     */
    public static function boot(): void
    {
        static::$state                         = 'boot';
        static::$register['system']['startup'] = microtime(true);

        // Set local and global process identifiers
        // TODO Implement support for global process identifier
        static::resetGlobalId();
        static::setLocalId(static::getGlobalId());

        // Set the platform, constants, load basic library functions, initialize error and signal handling
        static::detectPlatform();
        static::ensureModules();
        static::loadLibraries();
        static::setConstants();
        static::initializeErrorHandlers();
        static::initializeGarbageCollection();

        // Register the process start
        static::$timer = Timers::new('core', 'system');
        define('STARTTIME', static::$timer->getStart());
    }


    /**
     * Initializes the garbage collection depending on configuration
     *
     * @see https://www.php.net/manual/en/features.gc.php
     * @see https://www.php.net/manual/en/features.gc.performance-considerations.php
     *
     * @todo implement this
     * @return void
     */
    protected static function initializeGarbageCollection(): void
    {
        gc_enable();
    }


    /**
     * Ensures basic required PHP modules are loaded
     *
     * @return void
     */
    protected static function ensureModules(): void
    {
        $modules = [
            'mbstring',
            'posix',
            PLATFORM_CLI ? 'pcntl' : null,
        ];

        foreach ($modules as $module) {
            if ($module) {
                if (!extension_loaded($module)) {
                    throw new CoreException('The PHP "' . $module . '" module is required for Phoundation to run');
                }

            }
        }
    }


    /**
     * Sets core constants
     *
     * @return void
     */
    protected static function setConstants(): void
    {
        // Define a unique process request ID
        // Define project paths.

        // PHO_DIRECTORY      is the directory where the PHO command is located
        // DIRECTORY_START    is the CWD from the moment this process started
        // DIRECTORY_ROOT     is the root directory of this project, and should be used as the root for all other paths

        // DIRECTORY_SYSTEM   is a system directory in which one typically should not have to work around
        // DIRECTORY_TMP      is a private temporary directory
        // DIRECTORY_PUBTMP   is a public (accessible by web server) temporary directory
        // DIRECTORY_WEB      is the system cache location for all web pages
        // DIRECTORY_COMMANDS is the system cache location for all commands

        define('REQUEST'        , substr(uniqid(), 7));
        define('DIRECTORY_START', Strings::slash(getcwd()));
        define('DIRECTORY_ROOT' , static::getRootPath());

        // Find project data directory
        $data = DIRECTORY_ROOT . 'data/';
        $data = realpath($data);

        if (empty($data)) {
            throw CoreException::new(tr('Could not find system "data/" directory'))
                               ->addFix(tr('Please ensure that the directory "data" exists in the root of your project'))
                               ->addFix(tr('If the "data/" directory is a symlink to another directory, please ensure that the symlink is valid and points to a directory that exists'));
        }

        define('DIRECTORY_DATA'    , $data . '/');
        define('DIRECTORY_SYSTEM'  , DIRECTORY_DATA   . 'system/');
        define('DIRECTORY_CDN'     , DIRECTORY_DATA   . 'content/cdn/');
        define('DIRECTORY_PUBTMP'  , DIRECTORY_CDN    . 'content/cdn/tmp/');
        define('DIRECTORY_TMP'     , DIRECTORY_SYSTEM . 'tmp/');
        define('DIRECTORY_COMMANDS', DIRECTORY_SYSTEM . 'cache/system/commands/');
        define('DIRECTORY_HOOKS'   , DIRECTORY_SYSTEM . 'cache/system/hooks/');
        define('DIRECTORY_WEB'     , DIRECTORY_SYSTEM . 'cache/system/web/');
        define('DIRECTORY_CRON'    , DIRECTORY_SYSTEM . 'cache/system/cron/');
        define('DIRECTORY_TESTS'   , DIRECTORY_SYSTEM . 'cache/system/Tests/');
    }


    /**
     * Loads function library files
     *
     * @return void
     */
    protected static function loadLibraries(): void
    {
        // Load the system function files
        try {
            include_once(__DIR__ . '/../functions.php');
            include_once(__DIR__ . '/../mb.php');

        } catch (Throwable $e) {
            error_log($e->getMessage());
            die('Failed to load system function files, see logs for more information' . PHP_EOL);
        }
    }


    /**
     * Initializes error / exception handling and process control signals
     *
     * @return void
     */
    protected static function initializeErrorHandlers(): void
    {
        // Setup error handling, report ALL errors, setup shutdown functions
        static::setErrorHandling(true);
        static::setExceptionHandling(true);

        register_shutdown_function([
            '\Phoundation\Core\Core',
            'exit',
        ]);
    }


    /**
     * Detects and returns the root path for this project
     *
     * Root path will be detected by determining the location of the executed script (pho) and returning its directory
     *
     * @return string
     */
    protected static function getRootPath(): string
    {
        if (PLATFORM_CLI) {
            // PHO_DIRECTORY MUST exist here, use that.
            $path = PHO_DIRECTORY;

        } else {
            // Web client here. Should ALWAYS go to web/index.php, return its grandparent directory.
            $path = dirname(dirname($_SERVER['SCRIPT_FILENAME']));

            while (str_contains($path, '/data')) {
                // We're inside a data directory. Keep moving up
                $path = dirname($path);
            }

            // Ensure PHO_DIRECTORY is defined, as on CLI the "pho" command would do this
            define('PHO_DIRECTORY', $path);
        }

        // Return realpath ending with a slash so that we always base everything on realpath
        return realpath($path) . '/';
    }


    /**
     * This method will start up the core class and with it the entire system
     *
     * @return void
     * @throws StartupException
     */
    public static function startup(): void
    {
        try {
            if (static::$init) {
                throw new CoreException(tr('Core::startup() was run in the ":state" state. Check backtrace to see what caused this', [
                    ':state' => static::$state,
                ]));
            }

            // Set timeout and request type, ensure safe PHP configuration, apply general server restrictions, set the
            // project name, platform and request type
            static::securePhpSettings();
            static::startPlatform();

            // Check if we're in readonly mode
            static::$readonly = (bool) static::getReadonlyMode();

        } catch (Throwable $e) {
            Config::allowNoEnvironment();
            Core::ensureDefines();

            if (defined('PLATFORM_WEB')) {
                if (PLATFORM_WEB and headers_sent($file, $line)) {
                    if (preg_match('/debug-.+\.php$/', $file)) {
                        throw new CoreStartupFailedException(tr('Core->startup() failed because headers were already sent on ":location", so probably some added debug code caused this issue', [
                            ':location' => $file . '@' . $line,
                        ]), $e);
                    }

                    throw new CoreStartupFailedException(tr('Core::startup() Failed because headers were already sent on ":location"', [
                        ':location' => $file . '@' . $line,
                    ]), $e);
                }
            }

            throw $e;
        }
    }


    /**
     * Apply various settings to ensure this process is running as secure as possible
     *
     * @return void
     * @todo Should these issues be detected and logged if found, instead? What if somebody, for example, would need
     *       yaml.decode_php?
     */
    protected static function securePhpSettings(): void
    {
        ini_set('yaml.decode_php', 'off'); // Do this to avoid the ability to unserialize PHP code
    }


    /**
     * Detect and set the project name
     *
     * The project name is configured in the project file, located in config/project
     *
     * A valid project name matches regex /^[a-z_]+$/i
     *
     * @return void
     */
    public static function detectProject(): void
    {
        // Get the project name
        try {
            $project = strtoupper(trim(file_get_contents(DIRECTORY_ROOT . 'config/project')));

            if (!$project) {
                throw new OutOfBoundsException('No project definition found in DIRECTORY_ROOT/config/project file');
            }

            if (!preg_match('/^[A-Z_]+$/', $project)) {
                // Invalid project name
                throw new OutOfBoundsException('The project name "' . $project . '" specified in config/project is invalid. Please make sure it matches the regular expression /^[a-z_]+$/');
            }

            define('PROJECT', $project);

        } catch (Throwable $e) {
            static::$failed = true;

            define('PROJECT'          , 'UNKNOWN');
            define('DIRECTORY_PROJECT', DIRECTORY_DATA . 'sources/' . PROJECT . '/');

            if ($e instanceof OutOfBoundsException) {
                throw new ProjectException(tr('Project file is empty. Please ensure that the file "' . DIRECTORY_ROOT . 'config/project" has a valid project name (only letters and dashes)'));
            }

            // Project file is not readable
            if (!is_readable(DIRECTORY_ROOT . 'config/project')) {
                if (file_exists(DIRECTORY_ROOT . 'config/project')) {
                    // Okay, we have a problem here! The project file DOES exist but is not readable. This is either
                    // (likely) a security file owner / group / mode issue, or a filesystem problem. Either way, we
                    // won't be able to work our way around this.
                    throw new ProjectException(tr('Project file "config/project" does exist but is not readable. Please check the owner, group and mode for this file'));
                }

                // The file doesn't exist, that is good. Go to setup mode
                Log::toAlternateLog('Project file "config/project" does not exist, entering setup mode');

                static::startPlatform();
                static::$state = 'setup';

                throw new ProjectException('Project file "' . DIRECTORY_ROOT . 'config/project" cannot be read. Please ensure it exists');
            }

            // Unknown error
            throw new ProjectException(tr('Failed to get project name, please ensure that the file "' . DIRECTORY_ROOT . 'config/project" is readable'), $e);
        }
    }


    /**
     * Checks what platform we're running on and sets definitions for those
     *
     * @return void
     */
    protected static function detectPlatform(): void
    {
        switch (php_sapi_name()) {
            case 'cli':
                define('PLATFORM'    , 'cli');
                define('PLATFORM_WEB', false);
                define('PLATFORM_CLI', true);
                break;

            default:
                define('PLATFORM'    , 'web');
                define('PLATFORM_WEB', true);
                define('PLATFORM_CLI', false);
                define('NOCOLOR'     , (getenv('NOCOLOR') ? 'NOCOLOR' : null));
                break;
        }
    }


    /**
     * Select what startup should be executed
     *
     * @return void
     * @todo Get rid of this. Core::startWeb must move to the web library
     */
    protected static function startPlatform(): void
    {
        // Detect platform and execute the specific platform startup sequence
        try {
            switch (PLATFORM) {
                case 'web':
                    static::startWeb();
                    break;
            }

            static::$state = 'startup';

        } catch (ConfigFileDoesNotExistsException $e) {
            throw new EnvironmentNotExistsException(tr('Failed to start platform ":platform", the configured or requested environment ":environment" does not exist', [
                ':environment' => ENVIRONMENT,
                ':platform'    => PLATFORM
            ]), $e);

        } catch (ConfigParseFailedException $e) {
            throw new EnvironmentNotExistsException(tr('Failed to start platform ":platform", the configuration could not be parsed', [
                ':platform' => PLATFORM
            ]), $e);

        } catch (Throwable $e) {
            throw new StartupException(tr('Failed to start because: :e', [':e' => $e->getMessage()]), $e);
        }
    }


    /**
     * Startup for HTTP requests
     *
     * @return void
     * @todo Core::startWeb must move to the web library
     */
    protected static function startWeb(): void
    {
        // Check what environment we're in
        $env = get_null(getenv('PHOUNDATION_' . PROJECT . '_ENVIRONMENT'));

        if (empty($env)) {
            // No environment set in ENV, maybe given by parameter?
            Config::allowNoEnvironment();
            throw EnvironmentNotDefinedException::new('No required web environment specified for project "' . PROJECT . '"')
                                                ->setCode(500);
        }

        // Set environment and protocol
        define('ENVIRONMENT', $env);

        Config::setEnvironment(ENVIRONMENT);

        if (str_ends_with($_SERVER['REQUEST_URI'], 'favicon.ico')) {
            // By default, increase logger threshold on all favicon.ico requests to avoid log clutter
            Log::setThreshold(Config::getInteger('log.levels.web.favicon', 10));
        }

        // Register basic HTTP information
//                    static::$register['http']['accepts'] = Request::accepts();
//                    static::$register['http']['accepts_languages'] = Request::acceptsLanguages();

        // Define basic platform constants
        define('ADMIN'     , '');
        define('PROTOCOL'  , Config::get('web.protocol', 'https://'));
        define('PWD'       , Strings::slash(isset_get($_SERVER['PWD'])));
        define('PAGE'      , $_GET['page'] ?? 1);
        define('QUIET'     , (get_null(getenv('QUIET')) or get_null(getenv('VERY_QUIET'))) ?? false);
        define('ALL'       , get_null(getenv('ALL'))        ?? false);
        define('DELETED'   , get_null(getenv('DELETED'))    ?? false);
        define('FORCE'     , get_null(getenv('FORCE'))      ?? false);
        define('ORDERBY'   , get_null(getenv('ORDERBY'))    ?? '');
        define('STATUS'    , get_null(getenv('STATUS'))     ?? '');
        define('VERY_QUIET', get_null(getenv('VERY_QUIET')) ?? false);
        define('TEST'      , get_null(getenv('TEST'))       ?? false);
        define('VERBOSE'   , get_null(getenv('VERBOSE'))    ?? false);
        define('NOAUDIO'   , get_null(getenv('NOAUDIO'))    ?? false);
        define('LIMIT'     , get_null(getenv('LIMIT'))      ?? Config::getNatural('paging.limit', 50));
        define('NOWARNINGS', get_null(getenv('NOWARNINGS')) ?? false);

        // Check HEAD and OPTIONS requests. If HEAD was requested, just return basic HTTP headers
// :TODO: Should pages themselves not check for this and perhaps send other headers?
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'OPTIONS':
                throw new UnderConstructionException();
        }

        // Set security umask
        umask(Config::get('filesystem.umask', 0007));

        // Set language and locale
        static::setLanguage();
        static::setLocale();

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
            Request::executeSystem(503);
        }

        static::setTimeZone();

        // If POST request, automatically untranslate translated POST entries
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//                        Html::untranslate();
//                        Html::fixCheckboxValues();
            if (Config::get('security.web.csrf.enabled', true) === 'force') {
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
     * THIS METHOD SHOULD NOT BE RUN BY ANYBODY! IT IS EXECUTED AUTOMATICALLY ON SHUTDOWN
     *
     * This function facilitates the execution of multiple registered shutdown functions
     *
     * @param Throwable|int $exit_code    The exit code for this process once it terminates
     * @param string|null   $exit_message Message to be printed upon exit, only works for CLI processes
     * @param bool          $sig_kill     If true, the process is being terminated due to an external KILL signal
     * @param bool          $direct_exit  If true, will exit the process immediately without logging, cleaning, etc.
     *
     * @return never
     */
    #[NoReturn] public static function exit(Throwable|int $exit_code = 0, ?string $exit_message = null, bool $sig_kill = false, bool $direct_exit = false): never
    {
        static $exit = false;

        if (!$exit) {
            $exit = true;

            Core::setShutdownState();

            if (static::$shutdown_handling) {
                static::setErrorHandling(true);

                if ($direct_exit) {
                    // Exit without logging, cleaning, etc.
                    exit($exit_code);
                }

                if ($sig_kill) {
                    Log::warning(tr('Not cleaning up due to kill signal!'), 3);

                } else {
                    // Try shutdown with cleanup
                    try {
                        if (Config::getEnvironment()) {
                            static::executeShutdownCallbacks();
                            static::executePeriodicals($exit_code);
                            static::exitCleanup();
                        }

                    } catch (Throwable $e) {
                        // Uncaught exception handler for exit
                        Core::uncaughtExceptionHandler($e);
                    }
                }

                // Execute platform specific exit
                if (PLATFORM_WEB) {
                    // Kill a web page
                    Response::setHttpCode($exit_code);
                    Response::exit($exit_message, $sig_kill);
                }

                // Kill a CLI command
                CliCommand::exit($exit_code, $exit_message, $sig_kill);
            }
        }

        if (Log::syslogIsOpen()) {
            // Close the syslog
            closelog();
        }

        exit();
    }


    /**
     * Lets the core know that the system is now executing user level scripts
     *
     * @return void
     */
    public static function setScriptState(): void
    {
        // We're done, transfer control to script
        static::$state = 'script';
        static::$script = true;
    }


    /**
     * Lets the core know that the system is now in a shutdown state
     *
     * @return void
     * @todo Get rid of this method. ALL methods (including showdie()) should call exit() which Core will then handle
     */
    public static function setShutdownState(): void
    {
        static::$script = false;
        static::$state = 'shutdown';
    }


    /**
     * Returns true if the system state (or the specified state) is "boot"
     *
     * @param string|null $state If specified, will return the startup state for the specified state instead of the
     *                           internal Core state
     *
     * @return bool
     * @see Core::getState()
     * @see Core::inInitState()
     */
    public static function inBootState(?string $state = null): bool
    {
        return ($state ?? static::$state) === 'boot';
    }


    /**
     * Returns true if the system state (or the specified state) is "startup"
     *
     * @param string|null $state If specified, will return the startup state for the specified state instead of the
     *                           internal Core state
     *
     * @return bool
     * @see Core::getState()
     * @see Core::inInitState()
     */
    public static function inStartupState(?string $state = null): bool
    {
        return ($state ?? static::$state) === 'startup';
    }


    /**
     * This method will execute all registered shutdown callback functions
     *
     * @return void
     * @throws Throwable
     */
    protected static function executeShutdownCallbacks(): void
    {
        if (empty(static::$shutdown_callbacks)) {
            return;
        }

        Log::action(tr('Executing shutdown callbacks'), 3);

        // Reverse the shutdown calls to execute them last added first, first added last
        static::$shutdown_callbacks = array_reverse(static::$shutdown_callbacks);

        foreach (static::$shutdown_callbacks as $identifier => $data) {
            try {
                $function = $data['function'];
                $data     = Arrays::force($data['data'], null);

                // If no data was specified at all, then ensure at least one NULL value
                if (!$data) {
                    $data = [null];
                }

                // Execute this shutdown function for each data value
                foreach ($data as $value) {
                    Log::action(tr('Executing shutdown function ":identifier" with data value ":value"', [
                        ':identifier' => $identifier,
                        ':value'      => $value,
                    ]), 1);

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
                                // no break
                            } elseif (is_string($function[0])) {
                                if (is_string($function[1])) {
                                    // Ensure the class file is loaded
                                    Library::includeClassFile($function[0]);
                                    // Execute this shutdown function with the specified value
                                    $function[0]::{$function[1]}($value);
                                    continue;
                                }
                                // no break
                            }
                            // no break
                        }
                        // no break
                    }

                    Log::warning(tr('Unknown function information ":function" encountered, quietly skipping', [
                        ':function' => $function,
                    ]));
                }
            } catch (Throwable $e) {
                Notification::new()
                    ->setException($e)
                    ->send(true);
                throw $e;
            }
        }
    }


    /**
     * Returns the executed path
     *
     * @return string
     */
    public static function getExecutedPath(): string
    {
        if (PLATFORM_WEB) {
            return Request::getExecutedPath();
        }

        return CliCommand::getExecutedPath();
    }


    /**
     * This method will execute all registered shutdown callback functions
     *
     * @param Throwable|int $exit_code
     *
     * @return void
     * @throws Throwable
     */
    protected static function executePeriodicals(Throwable|int $exit_code = 0): void
    {
        // Periodically execute the following functions
        if (!$exit_code) {
            $level = random_int(0, 100);

            if (Config::get('system.shutdown', false)) {
                if (!is_array(Config::get('system.shutdown', false))) {
                    throw new OutOfBoundsException(tr('Invalid system.shutdown configuration, it should be an array'));
                }

                foreach (Config::get('system.shutdown', false) as $name => $parameters) {
                    if ($parameters['interval'] and ($level < $parameters['interval'])) {
                        Log::notice(tr('Executing periodical shutdown function ":function()"', [
                            ':function' => $name,
                        ]));

                        $parameters['function']();
                    }
                }
            }
        }
    }


    /**
     * Runs cleanup functions when exiting the process
     *
     * @return void
     */
    protected static function exitCleanup(): void
    {
        // Only cleanup if the Config object has an environment set
        if (Config::getEnvironment()) {
            if (sql(connect: false)->isConnected()) {
                Log::action(tr('Performing exit cleanup'), 2);

                // Flush the metadata
                Meta::flush();

                // Stop time measuring here
                static::$timer->stop();

                // Log debug information?
                if (Debug::isEnabled() and Debug::printStatistics()) {
                    // Only when auto complete is not active!
                    if (!CliAutoComplete::isActive()) {
                        static::logDebug();
                    }
                }

                // Cleanup
                Session::exit();
                PhoDirectory::removeTemporary();
            }
        }

        // If we get here...
        // The Config object has no environment and won't be able to load configuration. This means that the process is
        // exiting during startup. As such, we won't have logging either. Don't do cleanup, don't do anything. Just exit
    }


    /**
     * Log debug information
     *
     * @return void
     */
    protected static function logDebug(): void
    {
        // Log debug information
        Log::information(tr('DEBUG INFORMATION:'), 10);
        Log::information(tr('Query timers [:count]:', [
            ':count' => count(Timers::get('sql', false)) ?? 0,
        ]), 10);

        Timers::stop(true);

        if (Timers::exists('sql')) {
            Timers::sortHighLow('sql', false);
            foreach (Timers::pop('sql', false) as $timer) {
                Log::write('[' . number_format($timer->getTotal(), 6) . '] ' . $timer->getLabel(), 'debug', 10);
            }
        } else {
            Log::warning('-', 10);
        }

        Log::information(tr('Other timers [:count]:', [
            ':count' => Timers::getCount(),
        ]), 10);

        if (Timers::getCount()) {
            foreach (Timers::getAll() as $group => $timers) {
                foreach ($timers as $timer) {
                    Log::write('[' . number_format($timer->getTotal(), 6) . '] ' . $group . ' > ' . $timer->getLabel(), 'debug', 10);
                }
            }

        } else {
            Log::warning('-', 10);
        }
    }


    /**
     * Phoundation uncaught exception handler
     * *
     * This function is called automatically by PHP when an exception was not caught by Phoundation itself.
     *
     *
     * IMPORTANT! IF YOU ARE FACED WITH AN UNCAUGHT EXCEPTION, OR WEIRD EFFECTS LIKE WHITE SCREEN, ALWAYS FOLLOW THESE
     * STEPS:
     *
     * When faced with an exception on the web platform, check the DIRECTORY_ROOT/data/log/syslog (or exception log if
     * you have single_log disabled). In here you can find 99% of the issues. If the syslog file does not give any
     * information, then try the web server logs as failures that cannot be logged in the syslog will be logged there.
     * Typically you will find these in /var/log/php and /var/log/apache2 or /var/log/nginx
     *
     * When facing exceptions on the command line, all exception data will always be printed on the command line itself.
     *
     * If that gives you nothing, then try uncommenting the first line in the method. This will force display the error
     *
     * The reason that this is normally commented out and that logging or displaying your errors might fail is for
     * security. Phoundation may not know at the point where your error occurred if it is in a production environment
     * or not. Either way, you should never need this unless shit somehow really has hit the fan.
     *
     * @param Throwable $e
     *
     * @return never
     * @note : This function should never be called directly
     * @todo Refactor uncaught exception handling, its a bloody godawful mess!
     */
    #[NoReturn] public static function uncaughtExceptionHandler(Throwable $e): never
    {
        //if (!headers_sent()) {header_remove('Content-Type'); header('Content-Type: text/html', true);} echo "<pre>\nEXCEPTION CODE: "; print_r($e->getCode()); echo "\n\nEXCEPTION:\n"; print_r($e); echo "\n\nBACKTRACE:\n"; print_r(debug_backtrace()); exit();

        static $executed = false;

        if ($executed) {
            // WTF? This should never happen. We seem to be stuck in an uncaught exception loop, cut it out now!
            // This basically means that the unhandledException handler also is causing uncaught exceptions,
            // which should be impossible as it catches Throwable for the entire method. This extra check is just added
            // to ensure we never get in an endless loop for some unforseen reason
            if (CliAutoComplete::isActive()) {
                exit('uncaught-exception-handler-loop-detected-please-check-logs' . PHP_EOL);
            }

            exit('uncaught exception handler loop detected, please check logs' . PHP_EOL);
        }

        $executed = true;

        // Track state
        $state               = static::$state;
        static::$error_state = true;

        // We MAY not have an environment yet, tell configuration that it can just return default values from here on.
        // Ensure all defines are available to avoid next crashes because of missing defines.
        // When on commandline, ring an alarm to notify the user
        Config::allowNoEnvironment();
        static::ensureDefines();
        static::playUncaughtExceptionAudio($e);

        // Ensure the exception is a Phoundation exception
        $e = PhoException::ensurePhoundationException($e);

        // When in CLI auto complete mode, log and display a standard exception message
        if (CliAutoComplete::isActive()) {
            Log::error($e, 10, echo_screen: false);
            echo 'auto-complete-failed-see-system-log';
            exit(1);
        }

        // Register exception incident in the database
        static::registerUncaughtExceptionIncident($e);

        // Start processing the uncaught exception
        try {
            try {
                if (!defined('PLATFORM')) {
                    // The system crashed before platform detection.
                    Log::error(tr('*** UNCAUGHT EXCEPTION ":class" IN ":type" TYPE SCRIPT ":command" ***', [
                        ':class'   => get_class($e),
                        ':type'    => Request::getRequestType()->value,
                        ':command' => Strings::from(static::getExecutedPath(), DIRECTORY_COMMANDS),
                    ]));

                    Log::error($e);

                    exit('exception before platform detection');
                }

                switch (PLATFORM) {
                    case 'cli':
                        static::processUncaughtCliException($e, $state);

                    case 'web':
                        static::processUncaughtWebException($e, $state);
                }

            } catch (Throwable $f) {
                // Great! The uncaught exception handler caused an exception itself! Try to log / notify both
                static::processUncaughtExceptionException($e, $f, $state);
            }
        } catch (Throwable $g) {
            // Well, we tried. Here we just give up all together. Just try to log to error_log, then exit the process
            echo 'Fatal error. check data/syslog, application server logs, or webserver logs for more information' . PHP_EOL;

            try {
                error_log($g->getMessage());
            } catch (Throwable) {
                echo 'Failed to log' . PHP_EOL;
            }
        }

        exit(1);
    }


    /**
     * Returns true if the system is running in production environment
     *
     * @param bool|null $production
     *
     * @return bool
     */
    public static function isProductionEnvironment(?bool $production = null): bool
    {
        static $loop = false;

        if ($loop) {
            // We're in a loop!
            return false;
        }

        $loop = true;

        try {
            if ($production === null) {
                if (!defined('ENVIRONMENT')) {
                    // Oops, we're so early in startup that we don't have an environment available yet!
                    // Assume production!
                    $loop = false;

                    return true;
                }

                // Return the setting
                $return = Config::getBoolean('debug.production', false);
                $loop = false;

                return $return;
            }

            // Set the value
            Config::set('debug.production', $production);
            $loop = false;

            return $production;

        } catch (ConfigException) {
            // Failed to get (or write) config. Assume production
            $loop = false;

            return true;
        }
    }


    /**
     * Unregister the specified shutdown function
     *
     * This function will ensure that the specified function will not be executed on shutdown
     *
     * @param string|int $identifier
     * @param bool       $exception
     *
     * @return bool
     * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @package   system
     * @see       exit()
     * @see       Core::addShutdownCallback()
     * @version   1.27.0: Added function and documentation
     */
    public static function removeShutdownCallback(string|int $identifier, bool $exception = false): bool
    {
        if (array_key_exists($identifier, static::$shutdown_callbacks)) {
            unset(static::$shutdown_callbacks[$identifier]);

            return true;
        }

        if ($exception) {
            throw new OutOfBoundsException(tr('Cannot remove shutdown callback ":identifier", it does not exist', [
                ':identifier' => $identifier
            ]));
        }

        return false;
    }


    /**
     * Set the language for this request
     *
     * @return void
     */
    protected static function setLanguage(): void
    {
        try {
            $supported = Config::get('language.supported', [
                'en',
                'es',
            ]);

            if ($supported) {
                // Language is defined by the www/LANGUAGE dir that is used.
                $url      = $_SERVER['REQUEST_URI'];
                $url      = Strings::ensureStartsNotWith($url, '/');
                $language = Strings::until($url, '/');

                if (!in_array($language, $supported)) {
                    $language = Config::get('languages.default', 'en');
                    Log::warning(tr('Detected language ":language" is not supported, falling back to default. See configuration path "language.supported"', [
                        ':language' => $language,
                    ]));
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

            throw new OutOfBoundsException(tr('Language selection failed'), $e);
        }
    }


    /**
     * Apply the specified or configured locale
     *
     * @return void
     * @todo what is this supposed to return anyway?
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
            LC_MESSAGES => null,
        ]);

        if (!is_array($locale)) {
            throw new CoreException(tr('Specified $data should be an array but is an ":type"', [
                ':type' => gettype($locale),
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

        // First set LC_ALL as a baseline, then each entry
        if (isset($locale[LC_ALL])) {
            $locale[LC_ALL] = str_replace(':LANGUAGE', $language, $locale[LC_ALL]);
            $locale[LC_ALL] = str_replace(':COUNTRY', $country, $locale[LC_ALL]);

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

            $value = str_replace(':LANGUAGE', $language, (string)$value);
            $value = str_replace(':COUNTRY', $country, (string)$value);

            setlocale($key, $value);
        }

        static::$register['system']['locale'] = $locale;
    }


    /**
     * Sets timezone, see http://www.php.net/manual/en/timezones.php for more info
     *
     * @param string|null $timezone
     *
     * @return void
     */
    public static function setTimeZone(?string $timezone = null): void
    {
        // Set system timezone
        $timezone = isset_get($_SESSION['user']['timezone'], Config::get('system.timezone.system', 'UTC'));

        try {
            date_default_timezone_set(PhoDateTimeZone::new($timezone)->getName());

        } catch (Throwable $e) {
            // Accounts timezone failed, default to UTC
            date_default_timezone_set('UTC');
            Notification::new()
                ->setException($e)
                ->send();
        }

        // Set user timezone
        define('TIMEZONE', $timezone);
        ensure_variable($_SESSION['user']['timezone'], 'UTC');
    }


    /**
     * write the specified variable to the specified key / sub key in the core register
     *
     * @param mixed       $value
     * @param string      $key
     * @param string|null $subkey
     *
     * @return void
     */
    public static function writeRegister(mixed $value, string $key, ?string $subkey = null): void
    {
        if ($key === 'system') {
// TODO Check how to fix this later
//            throw new AccessDeniedException('The "system" register cannot be written to');
        }

        if ($subkey) {
            // We want to write to a subkey. Ensure that the key exists and is an array
            if (array_key_exists($key, static::$register)) {
                if (!is_array(static::$register[$key])) {
                    // Key exists but is not an array so cannot handle sub keys
                    throw new CoreException(tr('Cannot write to register key ":key.:subkey" as register key ":key" already exist as a value instead of an array', [
                        ':key' => $key,
                        'subkey' => $subkey,
                    ]));
                }
            } else {
                // Libraries the register subarray
                static::$register[$key] = [];
            }

            // Write the key / subkey
            static::$register[$key][$subkey] = $value;

        } else {
            // Write the key
            static::$register[$key] = $value;
        }
    }


    /**
     * Set the timeout value for this script
     *
     * @param null|int $timeout The number of seconds this script can run until it is aborted automatically
     *
     * @return bool Returns TRUE on success, or FALSE on failure.
     * @see     set_time_limit()
     */
    public static function setTimeout(?int $timeout = null): bool
    {
        if ($timeout === null) {
            if (PLATFORM_WEB) {
                // Default timeout to either system configuration web.timeout, or environment variable TIMEOUT
                $timeout = Config::get('web.timeout', get_null(getenv('TIMEOUT')) ?? 5);

            } else {
                // Default timeout to either system configuration cli.timeout, or environment variable TIMEOUT
                $timeout = Config::get('cli.timeout', get_null(getenv('TIMEOUT')) ?? 30);
            }
        }

        return set_time_limit($timeout);
    }


    /**
     * Adds the specified number of seconds to the process timeout
     *
     * @param int $timeout The number of extra seconds this script can run until it is aborted automatically
     *
     * @return bool Returns TRUE on success, or FALSE on failure.
     * @see     set_time_limit()
     */
    public static function addTimeout(int $timeout): bool
    {
        return static::setTimeout(static::getTimeout() + $timeout);
    }


    /**
     * Returns the process timeout for this process
     *
     * @return int
     */
    public static function getTimeout(): int
    {
        return (int) ini_get('max_execution_time');
    }


    /**
     * Ensures that the timeout is the specified amount, or more
     *
     * @param int $timeout
     *
     * @return bool
     */
    public static function setMinimumTimeout(int $timeout): bool
    {
        if (Core::getTimeout() >= $timeout) {
            return false;
        }

        return Core::setTimeout($timeout);
    }


    /**
     * Sets the internal INIT state to true.
     *
     * @return void
     * @see Core::inInitState()
     */
    public static function enableInitState(): void
    {
        Log::warning(tr('Enabling init state'), 4);

        static::$init = true;
    }


    /**
     * Sets the internal INIT state to true. Can NOT be disabled!
     *
     * @return void
     * @see Core::inInitState()
     */
    public static function disableInitState(): void
    {
        Log::warning(tr('Disabling init state'), 4);

        static::$init = false;
    }


    /**
     * Throws an exception for the given action if Core (and thus the entire system) is readonly
     *
     * @note The system will ignore readonly state while in init mode, and this method will return false, even if
     *       readonly more has been enabled
     *
     * @param string $action
     *
     * @return void
     * @throws DataEntryReadonlyException
     */
    public static function checkReadonly(string $action): void
    {
        if (static::$readonly and !static::$init) {
            throw new CoreReadonlyException(tr('Unable to perform action ":action", the entire system is readonly', [
                ':action' => $action,
            ]));
        }
    }


    /**
     * Returns the local log id value
     *
     * The local log id is a unique ID for this process only to identify log messages generated by THIS process in a log
     * file that contains log messages from multiple processes at the same time
     *
     * @return string
     */
    public static function getLocalId(): string
    {
        return static::$local_id;
    }


    /**
     * Set the local id parameter.
     *
     * The local log id is a unique ID for this process only to identify log messages generated by THIS process in a log
     * file that contains log messages from multiple processes at the same time
     *
     * @note The global_id can be set only once to avoid log discrepancies
     *
     * @param string $local_id
     *
     * @return void
     */
    protected static function setLocalId(string $local_id): void
    {
        static::$local_id = $local_id;
    }


    /**
     * Returns the local log id value
     *
     * The global log id is a unique ID for a multi-server process to identify log messages generated by multiple
     * processes over (optionally) multiple servers to identify all messages that are relevant to a single request.
     *
     * @return string
     */
    public static function getGlobalId(): string
    {
        return static::$global_id;
    }


    /**
     * Set the global id parameter.
     *
     * The global log id is a unique ID for a multi-server process to identify log messages generated by multiple
     * processes over (optionally) multiple servers to identify all messages that are relevant to a single request.
     *
     * @note The global_id can be set only once to avoid log discrepancies
     *
     * @param string $global_id
     *
     * @return void
     */
    public static function setGlobalId(string $global_id): void
    {
        static::$global_id = $global_id;
    }


    /**
     * Resets the global ID
     *
     * @return string
     */
    public static function resetGlobalId(): string
    {
        static::setGlobalId(substr(Strings::getUuid(), 0, 8));
        return static::getGlobalId();
    }


    /**
     * A sleep() method that is process interrupt signal safe.
     *
     * The sleep() and usleep() calls can be messed up with pcntl signals, as these stop the sleep commands.
     * This Core::sleep() method is pcntl safe
     *
     * @see https://www.php.net/manual/en/function.pcntl-signal.php#124049
     *
     * @param int $seconds
     *
     * @return void
     */
    public static function sleep(int $seconds): void
    {
        static::doSleep($seconds);
    }


//    /**
//     * Allows to change the Core class state
//     *
//     * @note This method only allows a change to the states "error" or "phperror"
//     * @param string|null $state
//     * @return void
//     */
//    public static function setState(#[ExpectedValues(values: ['error', 'phperror'])] ?string $state): void
//    {
//        switch ($state) {
//            case 'startup':
//                // no break
//            case 'script':
//                // no break
//            case 'shutdown':
//                // These are not allowed
//                throw new OutOfBoundsException(tr('Core state update to ":state" is not allowed. Core state can only be updated to "error" or "phperror"', [
//                    ':state' => $state
//                ]));
//
//            default:
//                // Wut?
//                throw new OutOfBoundsException(tr('Unknown core state ":state" specified. Core state can only be updated to "error" or "phperror"', [
//                    ':state' => $state
//                ]));
//        }
//    }
    /**
     * Implementation of the sleep() method that is process interrupt signal safe.
     *
     * The sleep() and usleep() calls can be messed up with pcntl signals, as these stop the sleep commands.
     * This Core::usleep() method is pcntl safe
     *
     * This method implements the Core::usleep() method, adding $offset which can be used to add some extra seconds
     * because those were spent in signal processing
     *
     * @see https://www.php.net/manual/en/function.pcntl-signal.php#124049
     *
     * @param int      $seconds
     * @param int|null $offset The number of seconds to add to the sleep as they were lost
     *
     * @return void
     */
    protected static function doSleep(int $seconds, int $offset = null): void
    {
        if (Core::$usleep) {
            // Ups, we were sleeping, but it got interrupted. Resume
            sleep(Core::$usleep - time() + $offset);

        } else {
            Core::$usleep = (time()) + $seconds;
            sleep($seconds);
        }

        Core::$usleep = null;
    }


    /**
     * A usleep() method that is process interrupt signal safe.
     *
     * The sleep() and usleep() calls can be messed up with pcntl signals, as these stop the sleep commands.
     * This Core::usleep() method is pcntl safe
     *
     * @see https://www.php.net/manual/en/function.pcntl-signal.php#124049
     *
     * @param int $micro_seconds
     *
     * @return void
     */
    public static function usleep(int $micro_seconds): void
    {
        static::doUsleep($micro_seconds);
    }


    /**
     * A usleep() method that is process interrupt signal safe.
     *
     * The sleep() and usleep() calls can be messed up with pcntl signals, as these stop the sleep commands.
     * This Core::usleep() method is pcntl safe
     *
     * This method implements the Core::usleep() method, adding $offset which can be used to add some extra microseconds
     * because those were spent in signal processing
     *
     * @see https://www.php.net/manual/en/function.pcntl-signal.php#124049
     *
     * @param int      $micro_seconds
     * @param int|null $offset
     *
     * @return void
     */
    protected static function doUsleep(int $micro_seconds, int $offset = null): void
    {
        if (Core::$usleep) {
            // Ups, we were sleeping, but it got interrupted. Resume
            usleep(Core::$usleep - (microtime(true) * 1000000) + $offset);

        } else {
            Core::$usleep = (microtime(true) * 1000000) + $micro_seconds;
            usleep($micro_seconds);
        }

        Core::$usleep = null;
    }


    /**
     * Returns true if the system is in maintenance mode
     *
     * @note This mode is global, and will immediately block all future web requests and block all future commands with
     * the exception of commands under ./pho system. Maintenance mode will remain enabled until disabled either by this
     * call or manually with ./pho project modes maintenance disable
     *
     * @param bool $enable
     *
     * @return void
     */
    public static function setMaintenanceMode(bool $enable): void
    {
        $enabled = static::getMaintenanceMode();
        $directory = PhoDirectory::new(DIRECTORY_SYSTEM . 'maintenance', PhoRestrictions::newSystem(true));

        if ($enable) {
            // Enable maintenance mode
            if ($enabled) {
                Log::warning(tr('Not placing system in maintenance mode, the system was already placed in maintenance mode by ":user"', [
                    ':user' => $enabled,
                ]));

                return;
            }

            $directory->ensure()->addFile(Session::getUserObject()->getEmail() ?? get_current_user())->touch();

            Log::warning(tr('System has been placed in maintenance mode. All web requests will be blocked, all commands (except those under ./pho system ...) are blocked'));

            return;
        }

        // Disable maintenance mode
        if (!$enabled) {
            Log::Warning(tr('Not disabling maintenance mode, the system is not in maintenance mode'));

            return;
        }

        $directory->delete();

        Log::warning(tr('System has been relieved from maintenance mode. All web requests will now again be answered, all commands are available'), 10);
    }


    /**
     * Returns information on if the system is in maintenance mode or not.
     *
     * This method will return null if the system is not in maintenance mode
     *
     * This method will return an email address if the system is in maintenance mode. The email address will be the
     * email for the person who placed the system in maintenance mode
     *
     * @return ModeInterface|null
     */
    public static function getMaintenanceMode(): ?ModeInterface
    {
        static $maintenance = null;

        if ($maintenance) {
            return $maintenance;
        }

        $directory = PhoDirectory::new(DIRECTORY_SYSTEM . 'maintenance', PhoRestrictions::newSystem())
                                 ->setAutoMount(false);

        if ($directory->exists()) {
            // The system is in maintenance mode, show who put it there
            $files = $directory->scan();

            if ($files->getCount()) {
                return new Mode('maintenance', $files->getFirstValue());
            }

            // ??? The maintenance directory is empty? It should contain a file with the email address of who locked it
            $maintenance = new Mode('maintenance', $files->getFirstValue());
        }

        return $maintenance;
    }


    /**
     * Returns true if the system is in readonly mode
     *
     * @note This mode is global, and will immediately block all future web requests and block all future commands with
     * the exception to commands under ./pho system. Readonly mode will remain enabled until disabled either by this
     * call or manually with ./pho system readonly disable
     *
     * @param bool $enable
     *
     * @return void
     */
    public static function setReadonlyMode(bool $enable): void
    {
        $enabled   = static::getReadonlyMode();
        $directory = PhoDirectory::new(DIRECTORY_SYSTEM . 'readonly', PhoRestrictions::newSystem(true));

        if ($enable) {
            // Enable readonly mode
            if ($enabled) {
                Log::warning(tr('Cannot place the system in readonly mode, the system was already placed in readonly mode by ":user"', [
                    ':user' => $enabled->getUserObject()->getLogId(),
                ]));

                return;
            }

            $directory->ensure()->addFile(Session::getUserObject()->getEmail() ?? get_current_user())->touch();

            Log::warning(tr('System has been placed in readonly mode. All web requests will be blocked, all commands (except those under ./pho system ...) are blocked'));

            return;
        }

        // Disable readonly mode
        if (!$enabled) {
            Log::warning(tr('Cannot disable readonly mode, the system is not in readonly mode'));

        } else {
            $directory->delete();

            Log::warning(tr('System has been relieved from readonly mode. All web POST requests will now again be processed, queries can write data again'), 10);
        }
    }


    /**
     * Returns true if the core is ignoring its internal readonly mode
     *
     * @return bool
     */
    public static function getIgnoreReadonly(): bool
    {
        return static::$ignore_readonly;
    }


    /**
     * Sets the core ignore_readonly flag
     *
     * @param bool $ignore_readonly
     *
     * @return void
     */
    public static function setIgnoreReadonly(bool $ignore_readonly): void
    {
        static::$ignore_readonly = $ignore_readonly;

        if (static::$ignore_readonly) {
            Log::warning('Core is ignoring readonly mode!');
        }
    }


    /**
     * Returns information on if the system is in readonly mode or not.
     *
     * This method will return null if the system is not in readonly mode
     *
     * This method will return an email address if the system is in maintenance mode. The email address will be the
     * email for the person who placed the system in readonly mode
     *
     * @return ModeInterface|null
     */
    public static function getReadonlyMode(): ?ModeInterface
    {
        static $readonly = null;

        if (static::$init or static::$ignore_readonly) {
            // Init state ignores readonly mode
            return null;
        }

        if (static::$init) {
            // Init state ignores readonly mode
            return null;
        }

        if ($readonly) {
            return $readonly;
        }

        $directory = PhoDirectory::new(DIRECTORY_SYSTEM . 'readonly', PhoRestrictions::newSystem());

        if ($directory->exists()) {
            // The system is in readonly mode, show who put it there
            $files = $directory->scan();

            if ($files->getCount()) {
                return new Mode('readonly', $files->getFirstValue());
            }

            // ??? The readonly directory is empty? It should contain a file with the email address of who locked it
            $readonly = new Mode('readonly', $files->getFirstValue());
        }

        return $readonly;
    }


    /**
     * Removes both maintenance and readonly modes
     *
     * @return void
     */
    public static function resetModes(): void
    {
        $restrictions = PhoRestrictions::newSystem(true);
        $maintenance  = static::getMaintenanceMode();
        $readonly     = static::getReadonlyMode();

        if ($maintenance) {
            PhoFile::new(DIRECTORY_SYSTEM . 'maintenance', $restrictions)->delete();
            Log::warning(tr('System has been relieved from maintenace mode. All web requests will now again be processed, all commands are available'), 10);
        }

        if ($readonly) {
            PhoFile::new(DIRECTORY_SYSTEM . 'readonly', $restrictions)->delete();
            Log::warning(tr('System has been relieved from readonly mode. All write requests will now again be answered, all commands are available'), 10);
        }

        if (!$maintenance and !$readonly) {
            Log::success(tr('System was neither in maintenance or readonly mode'));
        }
    }


    /**
     * Returns project version
     *
     * @return string
     */
    public static function getProjectVersion(): string
    {
        static $version;

        if (empty($version)) {
            // Get the project version
            try {
                $version = strtolower(trim(file_get_contents(DIRECTORY_ROOT . 'config/version')));
                if (!strlen($version)) {
                    throw new OutOfBoundsException(tr('No version defined in DIRECTORY_ROOT/config/project file'));
                }

                if (!is_version($version)) {
                    throw new OutOfBoundsException(tr('Invalid version ":version" defined in DIRECTORY_ROOT/config/project file', [
                        ':version' => $version,
                    ]));
                }

                return $version;

            } catch (Throwable $e) {
                static::$failed = true;

                if ($e instanceof OutOfBoundsException) {
                    throw $e;
                }

                // Project file is not readable
                if (!is_readable(DIRECTORY_ROOT . 'config/version')) {
                    if (file_exists(DIRECTORY_ROOT . 'config/version')) {
                        // Okay, we have a problem here! The project file DOES exist but is not readable. This is either
                        // (likely) a security file owner / group / mode issue, or a filesystem problem. Either way, we
                        // won't be able to work our way around this.
                        throw new CoreException(tr('Project version file "config/version" does exist but is not readable. Please check the owner, group and mode for this file'));
                    }

                    // The file doesn't exist, that is good. Go to setup mode
                    Log::toAlternateLog('Project version file "config/version" does not exist, entering setup mode');

                    static::startPlatform();
                    static::$state = 'setup';

                    throw new ProjectException(tr('Project version file ":path" cannot be read. Please ensure it exists', [
                        ':path' => DIRECTORY_ROOT . 'config/version',
                    ]));
                }
            }
        }

        return $version;
    }


    /**
     * Returns true if the Core is running in failed state
     *
     * @return bool
     */
    public static function getFailed(): bool
    {
        return static::$failed;
    }


    /**
     * Read and return the specified key / sub key from the core register.
     *
     * @note Will return NULL if the specified key does not exist
     *
     * @param string      $key
     * @param string|null $subkey
     * @param mixed|null  $default
     *
     * @return mixed
     */
    public static function readRegister(string $key, ?string $subkey = null, mixed $default = null): mixed
    {
        if ($subkey) {
            $return = isset_get(static::$register[$key][$subkey]);

        } else {
            $return = isset_get(static::$register[$key]);
        }

        if ($return === null) {
            // Specified key / subkey doesn't exist or is NULL, return default
            return $default;
        }

        return $return;
    }


    /**
     * Delete the specified variable from the core register
     *
     * @param string      $key
     * @param string|null $subkey
     *
     * @return void
     */
    public static function deleteRegister(string $key, ?string $subkey = null): void
    {
        if ($key === 'system') {
            throw new AccessDeniedException('The "system" register cannot be written to');
        }

        if ($subkey) {
            // We want to write to a sub key. Ensure that the key exists and is an array
            if (array_key_exists($key, static::$register)) {
                if (!is_array(static::$register[$key])) {
                    // Key exists but is not an array so cannot handle sub keys
                    throw new CoreException(tr('Cannot write to register key ":key.:subkey" as register key ":key" already exist as a value instead of an array', [
                        ':key' => $key,
                        'subkey' => $subkey,
                    ]));
                }

            } else {
                // The key doesn't exist, so we don't have to worry about the sub key
                return;
            }

            // Delete the key / subkey
            unset(static::$register[$key][$subkey]);

        } else {
            // Delete the key
            unset(static::$register[$key]);
        }
    }


    /**
     * Compare the specified value with the registered value for the specified key / sub key in the core register.
     *
     * @note Will return NULL if the specified key does not exist
     *
     * @param mixed       $value
     * @param string      $key
     * @param string|null $subkey
     *
     * @return bool
     */
    public static function compareRegister(mixed $value, string $key, ?string $subkey = null): bool
    {
        if ($subkey === null) {
            return $value === isset_get(static::$register[$key]);
        }

        return $value === isset_get(static::$register[$key][$subkey]);
    }


    /**
     * Returns Core system state
     *
     * Can be one of
     *
     * setup    System is in setup mode
     * startup  System is starting up
     * script   Script execution is now running
     * shutdown System is shutting down after normal script execution
     * error    System is processing an uncaught exception and will die soon
     * phperror System encountered a PHP error, which (typically, but not always) will end un an uncaught exception,
     *          switching system state to "error"
     *
     * @return string|null
     */
    #[ExpectedValues(values: [null, 'setup', 'startup', 'script', 'shutdown', 'maintenance'])]
    public static function getState(): ?string
    {
        return static::$state;
    }


    /**
     * Returns true if the Core class is in error state
     *
     * @return bool
     */
    public static function getErrorState(): bool
    {
        return static::$error_state;
    }


    /**
     * Returns true once script processing has started
     *
     * @return bool
     */
    public static function userScriptRunning(): bool
    {
        return static::$script;
    }


    /**
     * Returns true if the Core state is the same as the specified state
     *
     * @param string|null $state
     *
     * @return bool
     */
    public static function isState(#[ExpectedValues(values: [null, 'setup', 'boot', 'startup', 'script', 'shutdown', 'maintenance'])] ?string $state): bool
    {
        return static::$state === $state;
    }


    /**
     * Returns true if the Core state is "shutdown"
     *
     * @return bool
     */
    public static function isShuttingDown(): bool
    {
        return static::isState('shutdown');
    }


    //    /**
//     * ???
//     *
//     * @param string $section
//     * @param bool $writable
//     * @return string
//     */
//    public static function getGlobalDataDirectory(string $section = '', bool $writable = true): string
//    {
//        // First find the global data path.
//        // For now, either the same height as this project, OR one up the filesystem tree
//        $directories = [
//            '/var/lib/data/',
//            '/var/www/data/',
//            DIRECTORY_ROOT . '../data/',
//            DIRECTORY_ROOT . '../../data/'
//        ];
//
//        if (!empty($_SERVER['HOME'])) {
//            // Also check the users home directory
//            $directories[] = $_SERVER['HOME'] . '/projects/data/';
//            $directories[] = $_SERVER['HOME'] . '/data/';
//        }
//
//        $found = false;
//
//        foreach ($directories as $directory) {
//            if (file_exists($directory)) {
//                $found = $directory;
//                break;
//            }
//        }
//
//        if ($found) {
//            // Cleanup path. If realpath fails, we know something is amiss
//            if (!$found = realpath($found)) {
//                throw new CoreException(tr('Found directory ":directory" failed realpath() check', [
//                    ':directory' => $directory
//                ]));
//            }
//        }
//
//        if (!$found) {
//            if (!PLATFORM_CLI) {
//                throw new CoreException('Global data path not found');
//            }
//
//            try {
//                Log::warning(tr('Warning: Global data path not found. Normally this path should exist either 1 directory up, 2 directories up, in /var/lib/data, /var/www/data, $USER_HOME/projects/data, or $USER_HOME/data'));
//                Log::warning(tr('Warning: If you are sure this simply does not exist yet, it can be created now automatically. If it should exist already, then abort this script and check the location!'));
//
//                // TODO Do this better, this is crap
//                $directory = Process::newCliScript('base/init_global_data_path')->executeReturnArray();
//
//                if (!file_exists($directory)) {
//                    // Something went wrong and it was not created anyway
//                    throw new CoreException(tr('Configured directory ":directory" was created but it could not be found', [
//                        ':directory' => $directory
//                    ]));
//                }
//
//                // Its now created! Strip "data/"
//                $directory = Strings::slash($directory);
//
//            } catch (Exception $e) {
//                throw new CoreException('get_global_data_path(): Global data path not found, or init_global_data_path failed / aborted', $e);
//            }
//        }
//
//        // Now check if the specified section exists
//        if ($section and !file_exists($directory . $section)) {
//            Directory::ensure($directory . $section);
//        }
//
//        if ($writable and !is_writable($directory . $section)) {
//            throw new CoreException(tr('The global directory ":directory" is not writable', [
//                ':directory' => $directory . $section
//            ]));
//        }
//
//        if (!$global_path = realpath($directory . $section)) {
//            // Curious, the path exists, but realpath failed and returned false. This should never happen since we
//            // ensured the path above! This is just an extra check in case of.. Weird problems :)
//            throw new CoreException(tr('The found global data directory ":directory" is invalid (realpath returned false)', [
//                ':directory' => $directory
//            ]));
//        }
//
//        return Strings::slash($global_path);
//    }
    /**
     * Returns true if the system is shutting down
     *
     * @param string|null $state If specified, will return the startup state for the specified state instead of the
     *                           internal Core state
     *
     * @return bool
     * @see Core::getState()
     * @see Core::inInitState()
     */
    public static function inShutdownState(?string $state = null): bool
    {
        return ($state ?? static::$state) === 'shutdown';
    }


    /**
     * Returns true if the system is executing a script
     *
     * @param string|null $state If specified, will return the startup state for the specified state instead of the
     *                           internal Core state
     *
     * @return bool
     * @see Core::getState()
     * @see Core::inInitState()
     */
    public static function inScriptExecutionState(?string $state = null): bool
    {
        return ($state ?? static::$state) === 'script';
    }


    /**
     * Returns true if the system is in initialization mode
     *
     * @return bool
     * @see Core::getState()
     * @see Core::inStartupState()
     */
    public static function inInitState(): bool
    {
        return static::$init;
    }


    /**
     * Returns true if the system is running in PHPUnit
     *
     * @return bool
     */
    public static function isPhpUnitTest(): bool
    {
        // TODO Chang this. Detection should not be a command or page name that might change in the future
        return static::isExecutedPath('dev/phpunit') or static::isExecutedPath('developer/phpunit');
    }


    /**
     * Returns true if the system has finished starting up
     *
     * @param string|null $state If specified, will return the startup state for the specified state instead of the
     *                           internal Core state
     *
     * @return bool
     * @see Core::getState()
     */
    public static function readyState(?string $state = null): bool
    {
        return !static::inStartupState($state);
    }


    /**
     * Returns true if the system is in error state
     *
     * @return bool
     * @see Core::getState()
     */
    public static function errorState(): bool
    {
        return static::$error_state;
    }


    /**
     * Convert all PHP errors in exceptions. With this function the entirety of base works only with exceptions, and
     * function output normally does not need to be checked for errors.
     *
     * @note This method should never be called directly
     * @note This method uses untranslated texts as using tr() could potentially cause other issues
     *
     * @param int    $errno
     * @param string $errstr
     * @param string $errfile
     * @param int    $errline
     *
     * @return void
     * @throws \Exception
     */
    public static function phpErrorHandler(int $errno, string $errstr, string $errfile, int $errline): void
    {
        if (static::inStartupState()) {
            // Wut? We're not even ready to go! Likely we don't have configuration available, so we cannot even send out
            // notifications. Just crash with a standard PHP exception
            throw PhpException::new('Core startup PHP ERROR: ' . $errstr)
                ->setCode($errno)
                ->setFile($errfile)
                ->setLine($errline);
        }

        throw PhpException::new('PHP ERROR: ' . $errstr)
            ->setCode($errno)
            ->setFile($errfile)
            ->setLine($errline);
    }


    /**
     * Returns the executed file
     *
     * @return string
     */
    public static function getExecutedFile(): string
    {
        if (PLATFORM_WEB) {
            return Request::getExecutedFile();
        }

        return CliCommand::getExecutedFile();
    }


    /**
     * Register a shutdown function
     *
     * @note Function can be either a function name, a callable function, or an array with static object::method or an
     *       array with [$object, 'methodname']
     *
     * @param string|int            $identifier
     * @param array|string|callable $function
     * @param mixed                 $data
     *
     * @return void
     */
    public static function addShutdownCallback(string|int $identifier, array|string|callable $function, mixed $data = null): void
    {
        static::$shutdown_callbacks[$identifier] = [
            'function' => $function,
            'data' => $data,
        ];
    }


    /**
     * Returns the memory limit in bytes
     *
     * @return int
     */
    public static function getMemoryAvailable(): int
    {
        $limit     = static::getMemoryLimit();
        $used      = memory_get_usage();
        $available = $limit - $used;

        if ($available < 128) {
            Log::warning(tr('Failed to properly allocate memory, available memory reported as ":memory" with limit being ":limit" and ":used" being used. Trying default of 4096', [
                ':limit'  => $limit,
                ':used'   => $used,
                ':memory' => $available,
            ]), 2);

            return 4096;
        }

        return $available;
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
            $free = Free::new()->free();
            $limit = ceil($free['memory']['available'] * .8);
        }

        return (int)floor($limit);
    }


    /**
     * Will execute the specified callback only when not running in TEST mode
     *
     * @param callable $function
     * @param string   $task
     *
     * @return void
     */
    public static function ExecuteIfNotInTestMode(callable $function, string $task): void
    {
        if (defined('TEST') and TEST) {
            Log::warning(tr('Not executing ":task" while running in test mode', [
                ':task' => $task,
            ]), 3);

        } else {
            if ($function()) {
                Log::success($task, 3);
            }
        }
    }


    /**
     * Returns true if the current process is running as root
     *
     * @return bool
     */
    public static function processIsRoot(): bool
    {
        return !static::getProcessUid();
    }


    /**
     * Checks if the current process is running as root, or throws a ProcessRequiresRootException
     *
     * @return void
     * @throws ProcessRequiresRootException
     */
    public static function checkProcessIsRoot(): void
    {
        // This class requires running with root privileges
        if (!Core::processIsRoot()) {
            throw ProcessRequiresRootException::new(tr('This process requires root privileges to execute correctly.'))
                                              ->setWarning(!Core::inBootState());
        }
    }


    /**
     * Checks if the current process is running as root. If not, the current process will be restarted with sudo
     *
     * @return void
     * @throws ProcessRequiresRootException
     */
    public static function ensureProcessIsRoot(): void
    {
        // This class requires running with root privileges
        if (!Core::processIsRoot()) {
            CliCommand::restartAsRoot();
        }
    }


    /**
     * Returns the UID for the pho command
     *
     * @return int The user id for this process
     */
    public static function getPhoUid(): int
    {
        $uid = fileowner(PHO_DIRECTORY . 'pho');

        if ($uid === false) {
            throw new CoreException(tr('Failed to get UID for pho command'));
        }

        return $uid;
    }


    /**
     * Returns the username for the pho command
     *
     * @return string|null The username for this process
     */
    public static function getPhoUsername(): ?string
    {
        $user = posix_getpwuid(Core::getPhoUid());

        if ($user) {
            return $user['name'];
        }

        return null;
    }


    /**
     * Returns the UID for the current process
     *
     * @return int The user id for this process
     */
    public static function getProcessUid(): int
    {
        return posix_getuid();
    }


    /**
     * Returns the username for the current process
     *
     * @return string|null The username for this process
     */
    public static function getProcessUsername(): ?string
    {
        $user = posix_getpwuid(Core::getProcessUid());

        if ($user) {
            return $user['name'];
        }

        return null;
    }


    /**
     * Returns the SEO optimized version of the project name
     *
     * @return string
     */
    public static function getProjectSeoName(): string
    {
        static $return;

        if (empty($return)) {
            $return = str_replace('_', '-', strtolower(PROJECT));
        }

        return $return;
    }


    /**
     * Returns current state of Core error handling
     *
     * @return bool
     */
    public static function getErrorHandling(): bool
    {
        return static::$error_handling;
    }


    /**
     * Resets error handling to be managed by Phoundation
     *
     * @param bool $enabled
     *
     * @return void
     */
    public static function setErrorHandling(bool $enabled): void
    {
        static::$error_handling = $enabled;

        if ($enabled) {
            error_reporting(E_ALL);
            set_error_handler([
                '\Phoundation\Core\Core',
                'phpErrorHandler',
            ]);

        } else {
            error_reporting(0);
            set_error_handler(null);
        }
    }


    /**
     * Returns if Core manages shutdown handling
     *
     * @return bool
     */
    public static function getExceptionHandling(): bool
    {
        return static::$exception_handling;
    }


    /**
     * Resets shutdown handling to be managed by Phoundation
     *
     * @param bool $enabled
     *
     * @return void
     */
    public static function setExceptionHandling(bool $enabled): void
    {
        static::$exception_handling = $enabled;

        set_exception_handler($enabled ? [
            '\Phoundation\Core\Core',
            'uncaughtExceptionHandler',
        ] : null);
    }


    /**
     * Returns if Core manages shutdown handling
     *
     * @return bool
     */
    public static function getShutdownHandling(): bool
    {
        return static::$shutdown_handling;
    }


    /**
     * Sets if Core manages shutdown handling
     *
     * @param bool $enabled
     *
     * @return void
     */
    public static function setShutdownHandling(bool $enabled): void
    {
        static::$shutdown_handling = $enabled;
    }


    /**
     * Will sleep this process for X number of nanoseconds depending on user input to avoid timing attacks
     *
     * @param string      $input
     * @param string|null $secret_key
     *
     * @return void
     */
    public static function delayFromInput(string $input, ?string $secret_key = null): void
    {
        if (!$secret_key) {
            $secret_key = static::getLocalId() . static::getGlobalId();
        }

        $hash = crc32(serialize($secret_key . $input . $secret_key));

        // make it take a maximum of 0.1 milliseconds
        time_nanosleep(0, abs($hash % 100000));
    }


    /**
     * Displays the correct "environment required" message for normal and CLI auto complete mode
     *
     * @param bool $auto_complete
     *
     * @return never
     */
    #[NoReturn] public static function requireCliEnvironment(bool $auto_complete): never
    {
        $message = 'No required cli environment specified for project "' . PROJECT . '". Use -E ENVIRONMENT or ensure that your ~/.bashrc file contains a line like "export PHOUNDATION_' . PROJECT . '_ENVIRONMENT=ENVIRONMENT" and then execute "source ~/.bashrc"';

        if ($auto_complete) {
            Core::exit(2, str_replace(' ', '-', $message));
        }

        Core::exit(2, $message);
    }


    /**
     * Registers the specified exception incident in the database
     *
     * @param Throwable $e
     *
     * @return void
     */
    protected static function registerUncaughtExceptionIncident(Throwable $e): void
    {
        // Don't register warning exceptions
        if (!$e->isWarning()) {
            if (Core::getReadonly()) {
                Log::error('Not attempting to register the following uncaught exception incident, system is in readonly mode');

            } else {
                if (defined('ENVIRONMENT')) {
                    if ($e instanceof EnvironmentNotExistsException) {
                        // Don't register the uncaught exception incident, the exception is the environment does not exist
                        Log::error(tr('Not attempting to register the following uncaught exception incident, environment ":environment" does not exist', [
                            ':environment' => ENVIRONMENT
                        ]));

                    } else {
                        // Only notify and register developer incident if we're on production
                        if (Core::isProductionEnvironment()) {
                            // We CAN only notify after startup!
                            if (!static::inStartupState()) {
                                try {
                                    $e->registerIncident(EnumSeverity::severe);

                                } catch (Throwable $f) {
                                    Log::error(tr('Failed to register uncaught exception because of the following exception'));
                                    Log::error($f);
                                }

                                try {
                                    $e->getNotificationObject()
                                      ->send(false);

                                } catch (Throwable $f) {
                                    Log::error(tr('Failed to notify developers of uncaught exception because of the following exception'));
                                    Log::error($f);
                                }
                            }
                        }
                    }

                } else {
                    Log::error('Not attempting to register the following uncaught exception incident, environment has not yet been defined');
                }
            }
        }
    }


    /**
     * Plays an exception audio file if we are on CLI platform
     *
     * @param Throwable $e
     *
     * @return void
     */
    protected static function playUncaughtExceptionAudio(Throwable $e): void
    {
        if (!defined('PLATFORM_CLI') or PLATFORM_CLI) {
            try {
                if (defined('ENVIRONMENT')) {
                    if ($e instanceof PhoException) {
                        if ($e->isWarning()) {
                            Audio::new('warning.mp3')
                                 ->setTimeout(5)
                                 ->playLocal(true);

                        } else {
                            Audio::new('critical.mp3')
                                 ->setTimeout(5)
                                 ->playLocal(true);
                        }

                    } else {
                        Audio::new('critical.mp3')
                             ->setTimeout(5)
                             ->playLocal(true);
                    }

                } else {
                    Log::warning('Not attempting to play exception audio, environment has not yet been defined');
                }

            } catch (Throwable $f) {
                if (!CliAutoComplete::isActive()) {
                    // Do NOT use tr() over here because we might be in failed mode where tr() is not available
                    Log::warning('Failed to play uncaught exception audio because "' . $f->getMessage() . '"');
                }
            }
        }
    }


    /**
     * This method ensures that all required system defines are available
     *
     * @return void
     */
    protected static function ensureDefines(): void
    {
        // Ensure that definitions exist
        $defines = [
            'ADMIN'      => '',
            'PWD'        => Strings::slash(isset_get($_SERVER['PWD'])),
            'FORCE'      => get_null(getenv('FORCE'))      ?? false,
            'TEST'       => get_null(getenv('TEST'))       ?? false,
            'QUIET'      => get_null(getenv('QUIET'))      ?? false,
            'VERY_QUIET' => get_null(getenv('VERY_QUIET')) ?? false,
            'LIMIT'      => get_null(getenv('LIMIT'))      ?? null,
            'ORDERBY'    => get_null(getenv('ORDERBY'))    ?? null,
            'ALL'        => get_null(getenv('ALL'))        ?? false,
            'DELETED'    => get_null(getenv('DELETED'))    ?? false,
            'STATUS'     => get_null(getenv('STATUS'))     ?? null,
            'LOCALE'     => get_null(getenv('LOCALE'))     ?? 'en',
            'LANGUAGE'   => get_null(getenv('LANGUAGE'))   ?? 'en-us'
        ];

        foreach ($defines as $key => $value) {
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }


    /**
     * This method processes uncaught exceptions on the command line platform
     *
     * @param Throwable $e
     * @param string    $state
     *
     * @return void
     */
    #[NoReturn] protected static function processUncaughtCliException(Throwable $e, string $state): void
    {
        // Command line command crashed.
        // If not using Debug::enabled() mode, then try to give nice error messages for known issues
        if (($e instanceof ValidationFailedException) and $e->isWarning()) {
            // This is just a simple validation warning, show warning messages in the exception data
            Log::warning($e->getMessage(), 10);

            if ($e->getDataKey('failures')) {
                foreach ($e->getDataKey('failures') as $failure) {
                    Log::printr($failure, 10, echo_header: false);
                }
            }

            Core::exit(255);
        }

        if (($e instanceof PhoException) and $e->isWarning()) {
            // This is just a simple general warning, no backtrace and such needed, only show the
            // principal message
            Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]), 10);

            if ($e instanceof CliNoCommandSpecifiedException) {
                if ($data = $e->getData()) {
                    Log::information('Available methods:', 9);
                    foreach ($data['commands'] as $file) {
                        Log::notice($file, 10);
                    }
                }
            } elseif ($e instanceof CliCommandNotFoundException) {
                if ($data = $e->getData()) {
                    Log::information('Available sub methods:', 9, echo_prefix: false);
                    foreach ($data['commands'] as $method) {
                        Log::notice($method, 10, echo_prefix: false);
                    }
                }
            }

            Core::exit(255);
        }

        Log::error(tr('*** UNCAUGHT EXCEPTION ":class" IN ":type" CLI PLATFORM COMMAND ":command" WITH ENVIRONMENT ":environment" DURING CORE STATE ":state" ***', [
            ':class'       => get_class($e),
            ':type'        => Request::getRequestType()->value,
            ':state'       => static::$state,
            ':command'     => Strings::from(static::getExecutedPath(), DIRECTORY_COMMANDS),
            ':environment' => (defined('ENVIRONMENT') ? ENVIRONMENT : null),
        ]));

        Log::error($e);

        //                        Log::error();
        //                        Log::write(tr('Extended trace:'), 'debug', 10, false);
        //                        Log::write(print_r($e->getTrace(), true), 'debug', 10, false);
        //                        Log::error();
        //                        Log::write(tr('Super extended trace:'), 'debug', 10, false);
        //                        Log::write(print_r(debug_backtrace(), true), 'debug', 10, false);
        //                        Log::printr(debug_backtrace());

        Core::exit(1);
    }


    /**
     * This method processes uncaught exceptions on the web platform
     *
     * @param Throwable $e
     * @param string    $state
     *
     * @return void
     */
    #[NoReturn] protected static function processUncaughtWebException(Throwable $e, string $state): void
    {
        if ($e instanceof ValidationFailedException) {
            // This is just a simple validation warning, show warning messages in the exception data
            Log::warning($e->getMessage());

            if ($e->hasData()) {
                Log::warning($e->getData());
            }

            static::executeUncaughtExceptionSystemPage(400, $e);

        } elseif (($e instanceof PhoException) and ($e->isWarning())) {
            // This is just a simple general warning, no backtrace and such needed, only show the principal message
            Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]), 10);

        } else {
            // Log full exception data
            Log::error(tr('*** UNCAUGHT EXCEPTION ":class" IN ":type" WEB PAGE ":command" WITH ENVIRONMENT ":environment" DURING CORE STATE ":state" ***', [
                ':class'       => get_class($e),
                ':type'        => Request::getRequestType()->value,
                ':state'       => static::$state,
                ':command'     => Strings::from(static::getExecutedPath(), DIRECTORY_COMMANDS),
                ':environment' => (defined('ENVIRONMENT') ? ENVIRONMENT : null),
            ]));

            Log::error(tr('Exception data:'));
            Log::error($e);
        }

        // Remove all caching headers
        if (!headers_sent()) {
            header_remove('ETag');
            header_remove('Cache-Control');
            header_remove('Expires');
            header_remove('Content-Type');
            Response::setHttpCode(500);
            http_response_code(500);
            header('Content-Type: text/html');
            header('Content-length: 1048576'); // Required or browser won't show half the information
        }

        try {
            if (sql(connect: false)->isConnected()) {
                Notification::new()
                            ->setException($e)
                            ->send();

            } else {
                // System database is not available, we cannot send or store notifications!
                Log::error('Not sending notification for this uncaught exception, the system database is not available');
            }

        } catch (OutOfBoundsException $f) {
            Log::error('Failed to generate notification of uncaught exception, see following exception');
            Log::exception($f);
        }

        // Make sure the Router shutdown won't happen, so it won't send a 404
        // TODO Clean this mess up
        Core::removeShutdownCallback('route[postprocess]');
        Core::removeShutdownCallback('route_postprocess');

        if (static::inStartupState($state)) {
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

            Core::exit(1, tr('System startup exception. Please check your DIRECTORY_ROOT/data/log directory or application or webserver error log files, or enable the first line in the exception handler file for more information'));
        }

        if ($e->getCode() === 'validation') {
            $e->setCode(400);
        }

        // Show nice error page
        static::executeUncaughtExceptionSystemPage(500, $e);

        // TODO Change this so that we only return HTML for HTML requests, NOT json requests. With debug on, JsonPage should return full data reports!
        switch (Request::getRequestType()) {
            case EnumRequestTypes::api:
                // no break
                if (!headers_sent()) {
                    header('Content-Type: application/json', true);
                }

                echo "UNCAUGHT EXCEPTION\n\n";
                showdie($e);

            case EnumRequestTypes::ajax:
                if ($e->getWarning()) {
                    if ($e instanceof ValidationFailedException) {
                        $message = Strings::force($e->getFailures(), ', ');
                    } else {
                        $message = $e->getMessage();
                    }
                } else {
                    $message = tr('Something went wrong on the server, please notify your IT department and try again later');
                }

                JsonPage::new()
                        ->addFlashMessageSections(FlashMessage::new()
                            ->setMode($e->getWarning() ? EnumDisplayMode::warning : EnumDisplayMode::error)
                            ->setTitle(tr('Error!'))
                            ->setMessage($message))
                        ->reply();
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
                        pre {
                            white-space: break-spaces;
                        }
                        </style>
                        <table class="exception">
                            <thead>
                                <td colspan="2" class="center">
                                    ' . tr('*** UNCAUGHT EXCEPTION ":class" IN ":type" TYPE COMMAND ":command" ***', [
                                            ':class'   => get_class($e),
                                            ':command' => Strings::from(static::getExecutedPath(), DIRECTORY_COMMANDS),
                                            ':type'    => Request::getRequestType()->value,
                                        ]) . '
                                </td>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="2" class="center">
                                        ' . tr('An uncaught exception with code ":code" occurred in web page ":command". See the exception core dump below for more information on how to fix this issue', [
                ':code' => $e->getCode(),
                ':command' => Strings::from(static::getExecutedPath(), DIRECTORY_COMMANDS),
            ]) . '
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        ' . tr('Message') . '
                                    </td>
                                    <td>
                                        ' . $e->getMessage() . '
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        ' . tr('File') . '
                                    </td>
                                    <td>
                                        ' . $e->getFile() . '
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        ' . tr('Line') . '
                                    </td>
                                    <td>
                                        ' . $e->getLine() . '
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <a href="' . Url::new('signout')->makeWww() . '">Sign out</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';

        if (!headers_sent()) {
            header_remove('Content-Type');
            header('Content-Type: text/html', true);
        }

        echo $return;

        if ($e instanceof PhoException) {
            // Clean data
            $e->addData(Arrays::hideSensitive(Arrays::force($e->getData()), 'GLOBALS,%pass,ssh_key'));
        }

        Notification::new()
                    ->setException($e)
                    ->send();

        showdie($e);
    }


    /**
     * This method processes exceptions caused by the Core::uncaughtException() method
     *
     * @param Throwable $e
     * @param Throwable $f
     * @param string    $state
     *
     * @return never
     * @throws Throwable
     */
    protected static function processUncaughtExceptionException(Throwable $e, Throwable $f, string $state): never
    {
        //                if (!isset($core)) {
        //                    Log::error(tr('*** UNCAUGHT PRE CORE AVAILABLE EXCEPTION HANDLER CRASHED ***'));
        //                    Log::error(tr('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***'));
        //                    Log::error($f->getMessage());
        //                    exit('Pre core available exception with handling failure. Please your application or webserver error log files, or enable the first line in the exception handler file for more information');
        //                }

        if (!defined('PLATFORM') or static::inStartupState($state)) {
            Log::error(tr('*** UNCAUGHT SYSTEM STARTUP EXCEPTION HANDLER CRASHED FOR COMMAND ":command" ***', [
                ':command' => Strings::from(static::getExecutedPath(), DIRECTORY_COMMANDS),
            ]));
            Log::error(tr('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***'));
            Log::exception($f);

            exit('System startup exception with handling failure. Please check your DIRECTORY_ROOT/data/log directory or application or webserver error log files, or enable the first line in the exception handler file for more information');
        }

        Log::error('STARTUP-UNCAUGHT-EXCEPTION HANDLER CRASHED!');
        Log::error($f);

        switch (PLATFORM) {
            case 'cli':
                Log::error(tr('*** UNCAUGHT EXCEPTION HANDLER CRASHED FOR COMMAND ":command" ***', [
                    ':command' => Strings::from(static::getExecutedPath(), DIRECTORY_COMMANDS),
                ]));

                Log::error(tr('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***'));

                Debug::setEnabled(true);

                show($f);
                showdie($e);

            case 'web':
                if (!headers_sent()) {
                    http_response_code(500);
                    header('Content-Type: text/html');
                }

                if (!Debug::isEnabled()) {
                    if (sql(connect: false)->isConnected()) {
                        Notification::new()
                            ->setException($f)
                            ->send();

                        Notification::new()
                            ->setException($e)
                            ->send();
                    } else {
                        Log::error(tr('Not sending notifications for failed uncaught exception handling, the system database is not available'));
                    }

                    static::executeUncaughtExceptionSystemPage(500, $e);
                }

                show(tr('*** UNCAUGHT EXCEPTION HANDLER CRASHED FOR COMMAND ":command" ***', [
                    ':command' => Strings::from(static::getExecutedPath(), DIRECTORY_COMMANDS),
                ]));
                show('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***');
                show($f);
                showdie($e);
        }

        exit();
    }


    /**
     * Tries to execute the specified system page on the web platform, returns void if unable to do so due to Debug mode
     *
     * @param int       $page
     * @param Throwable $e
     *
     * @return void
     */
    protected static function executeUncaughtExceptionSystemPage(int $page, Throwable $e): void
    {
        // Any of these exceptions will be too severe to show a pretty error page
        $classes = [
            EnvironmentException::class,
            ProjectException::class,
        ];

        if (!Debug::isEnabled()) {
            foreach ($classes as $class) {
                if (($e instanceof $class)) {
                    // Don't try to display a pretty error page, this exception is too severe
                    static::exit(1, $e->getMessage());
                }
            }

            // Try to show a pretty error page
            Request::executeSystem($page, $e);
        }
    }


    /**
     * Returns how much PHP is allowed to expose itself
     *
     * @return string
     */
    public static function getExposePhp(): string
    {
        $expose = Config::getString('security.expose.php', 'limited');

        switch ($expose) {
            case 'limited':
            case 'full':
            case 'none':
            case 'fake':
                return $expose;
        }

        throw new ConfigurationInvalidException(tr('Invalid configuration value ":value" for "security.expose.php" Please use one of "none", "limited", "full", or "fake"', [
            ':value' => $expose,
        ]));
    }


    /**
     * Returns how much Phoundation is allowed to expose itself
     *
     * @return string
     */
    public static function getExposePhoundation(): string
    {
        $expose = Config::getString('security.expose.phoundation', 'limited');

        switch ($expose) {
            case 'limited':
            case 'full':
            case 'none':
            case 'fake':
                return $expose;
        }

        throw new ConfigurationInvalidException(tr('Invalid configuration value ":value" for "security.expose.phoundation" Please use one of "none", "limited", "full", or "fake"', [
            ':value' => $expose,
        ]));
    }


    /**
     * Returns the current process id
     *
     * @return int
     */
    public static function getPid(): int
    {
        return getmypid();
    }


    /**
     * Returns the current parent process id
     *
     * @return int
     */
    public static function getPpid(): int
    {
        return posix_getppid();
    }


    /**
     * Generates and returns a full exception data array
     *
     * @return array
     */
    public static function getProcessDetails(): array
    {
        $connected   = sql(connect: false)->isConnected();
        $initialized = Session::isInitialized();

        try {
            return [
                'project'               => PROJECT,
                'environment'           => ENVIRONMENT,
                'platform'              => PLATFORM,
                'session_id'            => Session::getUUID(),
                'ip'                    => Session::getIpAddress(),
                'project_version'       => Core::getProjectVersion(),
                'database_version'      => $connected                    ? Version::getString(Libraries::getMaximumVersion()) : tr('NO SYSTEM DATABASE CONNECTION AVAILABLE'),
                'user'                  => ($connected and $initialized) ? Session::getUserObject()->getLogId() : 'system',
                'command'               => PLATFORM_CLI                  ? CliCommand::getCommandsString()      : null,
                'url'                   => PLATFORM_WEB                  ? Route::getRequest()                  : null,
                'method'                => PLATFORM_WEB                  ? Route::getMethod()                   : null,
                'environment_variables' => $_ENV,
                'server'                => $_SERVER,
                'session'               => Session::getSource(),
                'argv'                  => ArgvValidator::getBackup(),
                'get'                   => GetValidator::getBackup(),
                'post'                  => PostValidator::getBackup(),
                'files'                 => UploadHandlers::getBackup(),
            ];

        } catch (Throwable $e) {
            $e = PhoException::ensurePhoundationException($e);

            Log::error(tr('Failed to generate exception detail, see following details'));
            Log::error($e);

            return [
                'oops'                  => [
                    'oops'     => 'Failed to generate full process details, limited process information will be returned instead',
                    'message'  => $e->getMessage(),
                    'messages' => $e->getMessages(),
                    'trace'    => $e->getTrace(),
                    'data'     => $e->getData(),
                ],
                'project'               => (defined('PROJECT') ? PROJECT : null),
                'project_version'       => Core::getProjectVersion(),
                'session_id'            => Session::getUUID(),
                'ip'                    => Session::getIpAddress(),
                'database_version'      => null,
                'environment'           => (defined('ENVIRONMENT') ? ENVIRONMENT : null),
                'platform'              => (defined('PLATFORM')    ? PLATFORM    : null),
                'session'               => Session::getSource(),
                'user'                  => null,
                'command'               => null,
                'url'                   => null,
                'method'                => PLATFORM_WEB ? Route::getMethod() : null,
                'environment_variables' => $_ENV,
                'server'                => $_SERVER,
                'argv'                  => null,
                'get'                   => null,
                'post'                  => null,
                'files'                 => null,
            ];
        }
    }


    /**
     * This method will fork the current process
     *
     * @see https://github.com/spatie/fork
     * @param callable $parent_callback
     * @param callable $child_callback
     *
     * @return void
     */
    public static function fork(callable $parent_callback, callable $child_callback): void
    {
        $pid = pcntl_fork();

        switch ($pid) {
            case -1:
                throw new CoreException(tr('Failed to fork the current exception'));

            case null:
                $parent_callback();
                break;

            default:
                $child_callback($pid);
        }
    }


    /**
     * Returns the value of the Unit Testing Mode flag
     *
     * @return bool
     */
    public static function getUnitTestMode(): bool
    {
        return static::$unit_test_mode;
    }


    /**
     * Sets or unsets the Unit Testing Mode flag
     *
     * @param bool $mode
     *
     * @return void
     */
    public static function setUnitTestMode(bool $mode): void
    {
        static::$unit_test_mode = $mode;
    }
}
