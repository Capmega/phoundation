<?php

declare(strict_types=1);

namespace Phoundation\Core;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Audio\Audio;
use Phoundation\Cache\Cache;
use Phoundation\Cli\Cli;
use Phoundation\Cli\CliAutoComplete;
use Phoundation\Cli\CliCommand;
use Phoundation\Cli\Exception\CliArgumentsException;
use Phoundation\Cli\Exception\CliCommandNotFoundException;
use Phoundation\Cli\Exception\CliNoCommandSpecifiedException;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Exception\CoreReadonlyException;
use Phoundation\Core\Exception\CoreStartupFailedException;
use Phoundation\Core\Exception\Interfaces\CoreStartupFailedExceptionInterface;
use Phoundation\Core\Exception\NoProjectException;
use Phoundation\Core\Interfaces\CoreInterface;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Libraries\Library;
use Phoundation\Core\Libraries\Version;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Meta\Meta;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\Exception\DataEntryReadonlyException;
use Phoundation\Data\Traits\TraitDataStaticIsExecutedPath;
use Phoundation\Data\Traits\TraitDataStaticReadonly;
use Phoundation\Data\Traits\TraitGetInstance;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Validator;
use Phoundation\Date\Date;
use Phoundation\Date\DateTimeZone;
use Phoundation\Developer\Debug;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhpException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Notifications\Notification;
use Phoundation\Os\Processes\Commands\Free;
use Phoundation\Os\Processes\Commands\Id;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Exception\ConfigException;
use Phoundation\Utils\Json;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;
use Phoundation\Web\Http\Http;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use Throwable;

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
class Core implements CoreInterface
{
    use TraitGetInstance;
    use TraitDataStaticReadonly;
    use TraitDataStaticIsExecutedPath;

    /**
     * Framework version and minimum required PHP version
     */
    public const FRAMEWORK_CODE_VERSION = '4.2.0';

    public const PHP_MINIMUM_VERSION    = '8.2.0';

    /**
     * Singleton variable
     *
     * @var Core|null $instance
     */
    protected static ?Core $instance = null;

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
     * NULL        state has not yet been defined
     * boot        Core is booting, no configuration available yet
     * startup     Core is starting up
     * script      Script execution is now running
     * maintenance System is in maintenance state
     * setup       System is in setup state
     * shutdown    Core is shutting down after normal script execution
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
     * Core class constructor
     */
    protected function __construct()
    {
        static::$state                         = 'boot';
        static::$register['system']['startup'] = microtime(true);

        // Set local and global process identifiers
        // TODO Implement support for global process identifier
        static::setLocalId(substr(uniqid(), -8, 8));
        static::setGlobalId('');

        // Define a unique process request ID
        // Define project paths.
        // DIRECTORY_START  is the CWD from the moment this process started
        // DIRECTORY_ROOT   is the root directory of this project, and should be used as the root for all other paths
        // DIRECTORY_TMP    is a private temporary directory
        // DIRECTORY_PUBTMP is a public (accessible by web server) temporary directory
        define('REQUEST', substr(uniqid(), 7));
        define('DIRECTORY_START', Strings::slash(getcwd()));
        define('DIRECTORY_ROOT', realpath(__DIR__ . '/../..') . '/');
        define('DIRECTORY_DATA', DIRECTORY_ROOT . 'data/');
        define('DIRECTORY_CDN', DIRECTORY_DATA . 'content/cdn/');
        define('DIRECTORY_COMMANDS', DIRECTORY_DATA . 'system/cache/commands/');
        define('DIRECTORY_PUBTMP', DIRECTORY_DATA . 'content/cdn/tmp/');
        define('DIRECTORY_TMP', DIRECTORY_DATA . 'tmp/');
        define('DIRECTORY_WEB', DIRECTORY_DATA . 'system/cache/web/');

        // Setup error handling, report ALL errors, setup shutdown functions
        static::setErrorHandling(true);
        static::setExceptionHandling(true);

        register_shutdown_function([
            '\Phoundation\Core\Core',
            'exit',
        ]);

        // Catch and handle process control signals
        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, [
                '\Phoundation\Core\ProcessControlSignals',
                'execute',
            ]);
            pcntl_signal(SIGTERM, [
                '\Phoundation\Core\ProcessControlSignals',
                'execute',
            ]);
            pcntl_signal(SIGHUP, [
                '\Phoundation\Core\ProcessControlSignals',
                'execute',
            ]);
        }

        // Load the functions and mb files
        require(DIRECTORY_ROOT . 'Phoundation/functions.php');
        require(DIRECTORY_ROOT . 'Phoundation/mb.php');

        // Register the process start
        static::$timer = Timers::new('core', 'system');
        define('STARTTIME', static::$timer->getStart());
    }


    /**
     * The core::startup() method will start up the core class
     *
     * This method starts the correct call type handler
     *
     * @return void
     * @throws CoreStartupFailedExceptionInterface
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
            static::getInstance();
            static::securePhpSettings();
            static::setProject();
            static::setPlatform();
            static::startupPlatform();
            static::setTimeout();

        } catch (Throwable $e) {
            if (defined('PLATFORM_WEB')) {
                if (PLATFORM_WEB and headers_sent($file, $line)) {
                    if (preg_match('/debug-.+\.php$/', $file)) {
                        throw new CoreException(tr('Failed because headers were already sent on ":location", so probably some added debug code caused this issue', [
                            ':location' => $file . '@' . $line,
                        ]), $e);
                    }

                    throw new CoreException(tr('Failed because headers were already sent on ":location"', [
                        ':location' => $file . '@' . $line,
                    ]), $e);
                }
            }

            if (($e instanceof ValidationFailedException) or ($e instanceof CliArgumentsException)) {
                throw $e;
            }

            throw new CoreStartupFailedException('Failed core startup because "' . $e->getMessage() . '"', $e);
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
     * @return void
     */
    protected static function setProject(): void
    {
        // Get the project name
        try {
            $project = strtoupper(trim(file_get_contents(DIRECTORY_ROOT . 'config/project')));
            if (!$project) {
                throw new OutOfBoundsException('No project defined in DIRECTORY_ROOT/config/project file');
            }
            define('PROJECT', $project);
        } catch (Throwable $e) {
            static::$failed = true;
            define('PROJECT', 'UNKNOWN');
            define('DIRECTORY_PROJECT', DIRECTORY_DATA . 'sources/' . PROJECT . '/');
            if ($e instanceof OutOfBoundsException) {
                throw $e;
            }
            // Project file is not readable
            if (!is_readable(DIRECTORY_ROOT . 'config/project')) {
                if (file_exists(DIRECTORY_ROOT . 'config/project')) {
                    // Okay, we have a problem here! The project file DOES exist but is not readable. This is either
                    // (likely) a security file owner / group / mode issue, or a filesystem problem. Either way, we
                    // won't be able to work our way around this.
                    throw new CoreException(tr('Project file "config/project" does exist but is not readable. Please check the owner, group and mode for this file'));
                }
                // The file doesn't exist, that is good. Go to setup mode
                error_log('Project file "config/project" does not exist, entering setup mode');
                static::setPlatform();
                static::startupPlatform();
                static::$state = 'setup';
                throw new NoProjectException('Project file "' . DIRECTORY_ROOT . 'config/project" cannot be read. Please ensure it exists');
            }
        }
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
     */
    protected static function startupPlatform(): void
    {
        // Detect platform and execute the specific platform startup sequence
        switch (PLATFORM) {
            case 'web':
                static::startupWeb();
                break;

            case 'cli':
                static::startupCli();
        }

        static::$state = 'startup';
    }


    /**
     * Startup for HTTP requests
     *
     * @return void
     */
    protected static function startupWeb(): void
    {
        if (PROJECT === 'UNKNOWN') {
            $env = '';

        } else {
            // Check what environment we're in
            $env = getenv('PHOUNDATION_' . PROJECT . '_ENVIRONMENT');

            if (empty($env)) {
                // No environment set in ENV, maybe given by parameter?
                Core::exit(2, 'startup: No required web environment specified for project "' . PROJECT . '"');
            }
        }

        // Set environment and protocol
        define('ENVIRONMENT', $env);

        Config::setEnvironment(ENVIRONMENT);

        // Register basic HTTP information
        // TODO MOVE TO HTTP CLASS
        static::$register['http']['code'] = 200;
//                    static::$register['http']['accepts'] = Request::accepts();
//                    static::$register['http']['accepts_languages'] = Request::acceptsLanguages();

        // Define basic platform constants
        define('ADMIN'     , '');
        define('PROTOCOL'  , Config::get('web.protocol', 'https://'));
        define('PWD'       , Strings::slash(isset_get($_SERVER['PWD'])));
        define('PAGE'      , $_GET['page'] ?? 1);
        define('QUIET'     , ((getenv('QUIET') or getenv('VERY_QUIET')) ? 'QUIET' : false));
        define('ALL'       , (getenv('ALL')        ? 'ALL'        : false));
        define('DELETED'   , (getenv('DELETED')    ? 'DELETED'    : false));
        define('FORCE'     , (getenv('FORCE')      ? 'FORCE'      : false));
        define('ORDERBY'   , (getenv('ORDERBY')    ? 'ORDERBY'    : ''));
        define('STATUS'    , (getenv('STATUS')     ? 'STATUS'     : ''));
        define('VERY_QUIET', (getenv('VERY_QUIET') ? 'VERY_QUIET' : false));
        define('TEST'      , (getenv('TEST')       ? 'TEST'       : false));
        define('VERBOSE'   , (getenv('VERBOSE')    ? 'VERBOSE'    : false));
        define('NOAUDIO'   , (getenv('NOAUDIO')    ? 'NOAUDIO'    : false));
        define('LIMIT'     , (getenv('LIMIT')      ? 'LIMIT'      : Config::getNatural('paging.limit', 50)));

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
     * THIS METHOD SHOULD NOT BE RUN BY ANYBODY! IT IS EXECUTED AUTOMATICALLY ON SHUTDOWN
     *
     * This function facilitates the execution of multiple registered shutdown functions
     *
     * @param Throwable|int $exit_code    The exit code for this process once it terminates
     * @param string|null   $exit_message Message to be printed upon exit, only works for CLI processes
     * @param bool          $sig_kill     If true, the process is being terminated due to an external KILL signal
     * @param bool          $direct_exit  If true, will exit the process immediately without loging, cleaning, etc.
     *
     * @return void
     */
    #[NoReturn] public static function exit(Throwable|int $exit_code = 0, ?string $exit_message = null, bool $sig_kill = false, bool $direct_exit = false): void
    {
        static $exit = false;

        if ($exit) {
            // In case somebody calls Core::exit(), the exit(); called at the end of this method would cause this method
            // to be called again. Just don't.
            return;
        }

        if (!static::$shutdown_handling) {
            // Shutdown handling by Core has been disabled
            return;
        }

        $exit = true;
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
                static::executeShutdownCallbacks($exit_code, $exit_message, $sig_kill);
                static::executePeriodicals($exit_code, $exit_message, $sig_kill);
                static::exitCleanup();

            } catch (Throwable $e) {
                // Uncaught exception handler for exit
                Core::uncaughtException($e);
            }
        }

        // Execute platform specific exit
        if (PLATFORM_WEB) {
            // Kill a web page
            Request::exit($exit_message, $sig_kill);
        }

        // Kill a CLI command
        CliCommand::exit($exit_code, $exit_message, $sig_kill);
    }


    /**
     * Lets the core know that the system is now executing user level scripts
     *
     * @return void
     */
    public static function setScriptState(): void
    {
        // We're done, transfer control to script
        static::$state  = 'script';
        static::$script = true;
    }


    /**
     * Lets the core know that the system is now in a shutdown state
     *
     * @todo Get rid of this method. ALL methods (including showdie()) should call exit() which Core will then handle
     * @return void
     */
    public static function setShutdownState(): void
    {
        static::$script = false;
        static::$state  = 'shutdown';
    }


    /**
     * Returns true if the system state (or the specified state) is "boot"
     *
     * @param string|null $state If specified will return the startup state for the specified state instead of the
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
     * @param string|null $state If specified will return the startup state for the specified state instead of the
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
     * @param Throwable|int $exit_code
     * @param string|null   $exit_message
     * @param bool          $sig_kill
     *
     * @return void
     * @throws Throwable
     */
    protected static function executeShutdownCallbacks(Throwable|int $exit_code = 0, ?string $exit_message = null, bool $sig_kill = false): void
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
     * @param string|null   $exit_message
     * @param bool          $sig_kill
     *
     * @return void
     * @throws Throwable
     */
    protected static function executePeriodicals(Throwable|int $exit_code = 0, ?string $exit_message = null, bool $sig_kill = false): void
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
        // Flush the metadata
        Meta::flush();

        // Stop time measuring here
        static::$timer->stop();

        // Log debug information?
        if (Debug::getEnabled() and Debug::printStatistics()) {
            // Only when auto complete is not active!
            if (!CliAutoComplete::isActive()) {
                static::logDebug();
            }
        }

        // Cleanup
        Session::exit();
        Directory::removeTemporary();
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
     * This function is called automatically
     *
     * @param Throwable $e
     *
     * @return never
     * @note : This function should never be called directly
     * @todo Refactor this, its a godawful mess
     */
    #[NoReturn] public static function uncaughtException(Throwable $e): never
    {
        // When on commandline, ring an alarm
        if (!defined('PLATFORM_CLI') or PLATFORM_CLI) {
            try {
                if ($e instanceof Exception) {
                    if ($e->isWarning()) {
                        Audio::new('warning.mp3')->playLocal(true);

                    } else {
                        Audio::new('critical.mp3')->playLocal(true);
                    }

                } else {
                    Audio::new('critical.mp3')->playLocal(true);
                }

            } catch (Throwable $f) {
                if (!CliAutoComplete::isActive()) {
                    Log::warning('Failed to play uncaught exception audio because "' . $f->getMessage() . '"');
                }
            }
        }

        if (CliAutoComplete::isActive()) {
            Log::error($e, 10, echo_screen: false);
            echo 'auto-complete-failed-see-system-log';
            exit(1);
        }

        // Ensure the exception is a Phoundation exception and register it
        $e = Exception::ensurePhoundationException($e);

        // Don't register warning exceptions
        if (!$e->isWarning()) {
            // Only notify and register developer incident if we're on production
            if (Core::isProductionEnvironment()) {
                // We CAN only notify after startup!
                if (!static::inStartupState()) {
                    try {
                        $e->registerDeveloperIncident();

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

        //if (!headers_sent()) {header_remove('Content-Type'); header('Content-Type: text/html', true);} echo "<pre>\nEXCEPTION CODE: "; print_r($e->getCode()); echo "\n\nEXCEPTION:\n"; print_r($e); echo "\n\nBACKTRACE:\n"; print_r(debug_backtrace()); exit();
        /*
         * Phoundation uncaught exception handler
         *
         * IMPORTANT! IF YOU ARE FACED WITH AN UNCAUGHT EXCEPTION, OR WEIRD EFFECTS LIKE
         * WHITE SCREEN, ALWAYS FOLLOW THESE STEPS:
         *
         *    Check the DIRECTORY_ROOT/data/log/syslog (or exception log if you have single_log
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
        $state               = static::$state;
        static::$error_state = true;
        // Ensure that definitions exist
        $defines = [
            'ADMIN'      => '',
            'PWD'        => Strings::slash(isset_get($_SERVER['PWD'])),
            'FORCE'      => (getenv('FORCE') ? 'FORCE' : null),
            'TEST'       => (getenv('TEST') ? 'TEST' : null),
            'QUIET'      => (getenv('QUIET') ? 'QUIET' : null),
            'VERY_QUIET' => (getenv('VERY_QUIET') ? 'VERY_QUIET' : null),
            'LIMIT'      => (getenv('LIMIT') ? 'LIMIT' : Config::getNatural('paging.limit', 50)),
            'ORDERBY'    => (getenv('ORDERBY') ? 'ORDERBY' : null),
            'ALL'        => (getenv('ALL') ? 'ALL' : null),
            'DELETED'    => (getenv('DELETED') ? 'DELETED' : null),
            'STATUS'     => (getenv('STATUS') ? 'STATUS' : null),
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
//                    echo '<pre>';
//                    print_r($e);
                    exit('uncaught exception handler loop detected, please check logs');
                }

                $executed = true;

                if (!defined('PLATFORM')) {
                    // The system crashed before platform detection.
                    Log::error(tr('*** UNCAUGHT EXCEPTION ":code" IN ":type" TYPE SCRIPT ":command" ***', [
                        ':code'    => $e->getCode(),
                        ':type'    => Request::getRequestType()->value,
                        ':command' => Strings::from(static::getExecutedPath(), DIRECTORY_COMMANDS),
                    ]));
                    Log::error($e);
                    exit('exception before platform detection');
                }

                switch (PLATFORM) {
                    case 'cli':
                        // Command line command crashed.
                        // If not using Debug::enabled() mode, then try to give nice error messages for known issues
                        if (($e instanceof ValidationFailedException) and $e->isWarning()) {
                            // This is just a simple validation warning, show warning messages in the exception data
                            Log::warning($e->getMessage(), 10);
                            Log::warning($e->getData(), 10);
                            Core::exit(255);
                        }

                        if (($e instanceof Exception) and $e->isWarning()) {
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
                                    Log::information('Available sub methods:', 9, use_prefix: false);
                                    foreach ($data['commands'] as $method) {
                                        Log::notice($method, 10, use_prefix: false);
                                    }
                                }
                            }
                            Core::exit(255);
                        }
// TODO Remplement this with proper exception classes
//                            switch ((string) $e->getCode()) {
//                                case 'already-running':
//                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
//                                    Script::setExitCode(254);
//                                    exit(Script::getExitCode());
//
//                                case 'no-method':
//                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
//                                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
//                                    Script::setExitCode(253);
//                                    exit(Script::getExitCode());
//
//                                case 'unknown-method':
//                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
//                                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
//                                    Script::setExitCode(252);
//                                    exit(Script::getExitCode());
//
//                                case 'missing-arguments':
//                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
//                                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
//                                    Script::setExitCode(253);
//                                    exit(Script::getExitCode());
//
//                                case 'invalid-arguments':
//                                    Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]));
//                                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
//                                    Script::setExitCode(251);
//                                    exit(Script::getExitCode());
//
//                                case 'validation':
//                                    if (static::executedPathIs('system/init')) {
//                                        // In the init command, all validations are fatal!
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
//                                    exit(Script::getExitCode());
//                            }
                        Log::error(tr('*** UNCAUGHT EXCEPTION ":code" IN ":type" CLI PLATFORM COMMAND ":command" WITH ENVIRONMENT ":environment" DURING CORE STATE ":state" ***', [
                            ':code'        => $e->getCode(),
                            ':type'        => Request::getRequestType()->value,
                            ':state'       => static::$state,
                            ':command'     => Strings::from(static::getExecutedPath(), DIRECTORY_COMMANDS),
                            ':environment' => (defined('ENVIRONMENT') ? ENVIRONMENT : null),
                        ]));
                        Log::error(tr('Exception data:'));
                        Log::error($e);
//                        Log::error();
//                        Log::write(tr('Extended trace:'), 'debug', 10, false);
//                        Log::write(print_r($e->getTrace(), true), 'debug', 10, false);
//                        Log::error();
//                        Log::write(tr('Super extended trace:'), 'debug', 10, false);
//                        Log::write(print_r(debug_backtrace(), true), 'debug', 10, false);
//                        Log::printr(debug_backtrace());
                        Core::exit(1);
                    case 'web':
                        if ($e instanceof ValidationFailedException) {
                            // This is just a simple validation warning, show warning messages in the exception data
                            Log::warning($e->getMessage());
                            Log::warning($e->getData());
                            if (!Debug::getEnabled()) {
                                Request::executeSystem(400);
                            }

                        } elseif (($e instanceof Exception) and ($e->isWarning())) {
                            // This is just a simple general warning, no backtrace and such needed, only show the
                            // principal message
                            Log::warning(tr('Warning: :warning', [':warning' => $e->getMessage()]), 10);
                            Request::executeSystem(500);

                        }
                        // Log exception data
                        Log::error(tr('*** UNCAUGHT EXCEPTION ":code" IN ":type" WEB PAGE ":command" WITH ENVIRONMENT ":environment" DURING CORE STATE ":state" ***', [
                            ':code'        => $e->getCode(),
                            ':type'        => Request::getRequestType()->value,
                            ':state'       => static::$state,
                            ':command'     => Strings::from(static::getExecutedPath(), DIRECTORY_COMMANDS),
                            ':environment' => (defined('ENVIRONMENT') ? ENVIRONMENT : null),
                        ]));
                        Log::error(tr('Exception data:'));
                        Log::error($e);
                        if (!Debug::getEnabled()) {
                            Request::executeSystem(500);
                        }
                        // Make sure the Router shutdown won't happen so it won't send a 404
                        Core::removeShutdownCallback('route[postprocess]');
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
                        //
                        static::removeShutdownCallback('route_postprocess');
                        try {
                            Notification::new()
                                        ->setException($e)
                                        ->send();

                        } catch (OutOfBoundsException $f) {
                            Log::error('Failed to generate notification of uncaught exception, see following notification');
                            Notification::new()
                                        ->setException($f)
                                        ->send();
                        }
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
                        if (Debug::getEnabled()) {
                            switch (Request::getRequestType()) {
                                case EnumRequestTypes::api:
                                    // no break
                                case EnumRequestTypes::ajax:
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
                                        pre {
                                            white-space: break-spaces;
                                        }
                                        </style>
                                        <table class="exception">
                                            <thead>
                                                <td colspan="2" class="center">
                                                    ' . tr('*** UNCAUGHT EXCEPTION ":code" IN ":type" TYPE COMMAND ":command" ***', [
                                    ':code'    => $e->getCode(),
                                    ':command' => Strings::from(static::getExecutedPath(), DIRECTORY_COMMANDS),
                                    ':type'    => Request::getRequestType()->value,
                                ]) . '
                                                </td>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td colspan="2" class="center">
                                                        ' . tr('An uncaught exception with code ":code" occurred in web page ":command". See the exception core dump below for more information on how to fix this issue', [
                                    ':code'    => $e->getCode(),
                                    ':command' => Strings::from(static::getExecutedPath(), DIRECTORY_COMMANDS),
                                ]) . '
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
                                                        <a href="' . UrlBuilder::getWww('signout') . '">Sign out</a>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>';
                            if (!headers_sent()) {
                                header_remove('Content-Type');
                                header('Content-Type: text/html', true);
                            }
                            echo $return;
                            if ($e instanceof Exception) {
                                // Clean data
                                $e->addData(Arrays::hide(Arrays::force($e->getData()), 'GLOBALS,%pass,ssh_key'));
                            }
                            showdie($e);
                        }
                        // We're not in debug mode.
                        Notification::new()
                                    ->setException($e)
                                    ->send();
                        switch (Request::getRequestType()) {
                            case EnumRequestTypes::api:
                                // no break
                            case EnumRequestTypes::ajax:
                                if ($e instanceof CoreException) {
                                    Json::message($e->getCode(), ['reason' => ($e->isWarning() ? trim(Strings::from($e->getMessage(), ':')) : '')]);
                                }
                                // Assume that all non CoreException exceptions are not warnings!
                                Json::message($e->getCode(), ['reason' => '']);
                        }
                        Request::executeSystem($e->getCode());
                }

            } catch (Throwable $f) {
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
                    Log::error($f->getMessage());
                    Log::error($f->getTrace());
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
                        if (!Debug::getEnabled()) {
                            Notification::new()
                                        ->setException($f)
                                        ->send();
                            Notification::new()
                                        ->setException($e)
                                        ->send();
                            Request::executeSystem(500);
                        }
                        show(tr('*** UNCAUGHT EXCEPTION HANDLER CRASHED FOR COMMAND ":command" ***', [
                            ':command' => Strings::from(static::getExecutedPath(), DIRECTORY_COMMANDS),
                        ]));
                        show('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***');
                        show($f);
                        showdie($e);
                }
            }

        } catch (Throwable $g) {
            // Well, we tried. Here we just give up all together. Don't do anything anymore because every step from here
            // will fail anyway. Exit the process
            echo 'Fatal error. check data/syslog, application server logs, or webserver logs for more information' . PHP_EOL;
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
                $loop   = false;

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
     * @category  Function reference
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

            $value = str_replace(':LANGUAGE', $language, (string) $value);
            $value = str_replace(':COUNTRY', $country, (string) $value);

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
    protected static function setTimeZone(?string $timezone = null): void
    {
        // Set system timezone
        $timezone = isset_get($_SESSION['user']['timezone'], Config::get('system.timezone.system', 'UTC'));
        try {
            date_default_timezone_set(DateTimeZone::new($timezone)
                                                  ->getName());

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
     * Startup for Command Line Interface
     *
     * @return void
     */
    protected static function startupCli(): void
    {
        // Hide all command line arguments
        ArgvValidator::hideData($GLOBALS['argv']);

        // USe global $argv ONLY if CliCommand::PhoUidMatch() is true because if it isn't we're going to restart and
        // we'll need the $argv as-is
        global $argv;

        // Validate system modifier arguments. Ensure that these variables get stored in the global $argv array because
        // they may be used later down the line by (for example) Documenation class, for example!
        $argv = ArgvValidator::new()
                             ->setTest(!CliCommand::phoUidMatch())
                             ->select('-A,--all')->isOptional(false)->isBoolean()
                             ->select('-C,--no-color')->isOptional(false)->isBoolean()
                             ->select('-D,--debug')->isOptional(false)->isBoolean()
                             ->select('-E,--environment', true)->isOptional()->hasMinCharacters(1)->hasMaxCharacters(64)
                             ->select('-F,--force')->isOptional(false)->isBoolean()
                             ->select('-H,--help')->isOptional(false)->isBoolean()
                             ->select('-L,--log-level', true)->isOptional()->isInteger()->isBetween(1, 10)
                             ->select('-O,--order-by', true)->isOptional()->hasMinCharacters(1)->hasMaxCharacters(128)
                             ->select('-P,--page', true)->isOptional(1)->isDbId()
                             ->select('-Q,--quiet')->isOptional(false)->isBoolean()
                             ->select('-R,--very-quiet')->isOptional(false)->isBoolean()
                             ->select('-G,--no-prefix')->isOptional(false)->isBoolean()
                             ->select('-N,--no-audio')->isOptional(false)->isBoolean()
                             ->select('-S,--status', true)->isOptional()->hasMinCharacters(1)->hasMaxCharacters(16)
                             ->select('-T,--test')->isOptional(false)->isBoolean()
                             ->select('-U,--usage')->isOptional(false)->isBoolean()
                             ->select('-V,--verbose')->isOptional(false)->isBoolean()
                             ->select('-W,--no-warnings')->isOptional(false)->isBoolean()
                             ->select('-Y,--clear-tmp')->isOptional(false)->isBoolean()
                             ->select('-Z,--clear-caches')->isOptional(false)->isBoolean()
                             ->select('--language', true)->isOptional()->isCode()
                             ->select('--deleted')->isOptional(false)->isBoolean()
                             ->select('--version')->isOptional(false)->isBoolean()
                             ->select('--limit', true)->isOptional(0)->isNatural()
                             ->select('--timezone', true)->isOptional()->isString()
                             ->select('--auto-complete', true)->isOptional()->hasMaxCharacters(1024)
                             ->select('--show-passwords')->isOptional(false)->isBoolean()
                             ->select('--no-validation')->isOptional(false)->isBoolean()
                             ->select('--no-password-validation')->isOptional(false)->isBoolean()
                             ->validate(false);
//        $argv = [
//            'all'                    => false,
//            'no_color'               => false,
//            'debug'                  => false,
//            'environment'            => null,
//            'force'                  => false,
//            'help'                   => false,
//            'log_level'              => false,
//            'order_by'               => false,
//            'page'                   => 1,
//            'quiet'                  => false,
//            'very_quiet'             => false,
//            'no_prefix'              => false,
//            'no_sound'               => false,
//            'status'                 => false,
//            'test'                   => false,
//            'usage'                  => false,
//            'verbose'                => false,
//            'no_warnings'            => false,
//            'language'        => false,
//            'deleted'                => false,
//            'version'                => false,
//            'limit'                  => false,
//            'timezone'               => null,
//            'auto_complete'          => null,
//            'show_passwords'         => false,
//            'no_validation'          => false,
//            'no_password_validation' => false
//    ];

        // Check what environment we're in
        if ($argv['environment']) {
            // The Environment was manually specified on the command line
            $env = $argv['environment'];

        } else {
            // Get environment variable from the shell environment
            $env = getenv('PHOUNDATION_' . PROJECT . '_ENVIRONMENT');

            if (empty($env)) {
                if (PROJECT !== 'UNKNOWN') {
                    // If we're in auto complete mode, then we don't need an environment
                    if (!CliAutoComplete::isActive()) {
                        Core::exit(2, 'startup: No required cli environment specified for project "' . PROJECT . '". Use -E PROJECTNAME or check if your .bashrc file contains a line like "export PHOUNDATION_' . PROJECT . '_ENVIRONMENT=PROJECTNAME"');
                    }
                }

                $env = '';
            }
        }

        if (empty($env)) {
            Core::exit(2, 'startup: No required cli environment specified for project "' . PROJECT . '".  Use -E PROJECTNAME or check if your .bashrc file contains a line like "export PHOUNDATION_' . PROJECT . '_ENVIRONMENT=PROJECTNAME"');
        }

        // Set environment and protocol
        define('ENVIRONMENT', $env);

        Config::setEnvironment(ENVIRONMENT);

        // Define basic platform constants
        define('ADMIN'     , '');
        define('PROTOCOL'  , Config::get('web.protocol', 'https://'));
        define('PWD'       , Strings::slash(isset_get($_SERVER['PWD'])));
        define('QUIET'     , ($argv['very_quiet'] or $argv['quiet']));
        define('VERY_QUIET', $argv['very_quiet']);
        define('VERBOSE'   , $argv['verbose']);
        define('FORCE'     , $argv['force']);
        define('NOCOLOR'   , $argv['no_color']);
        define('TEST'      , $argv['test']);
        define('DELETED'   , $argv['deleted']);
        define('ALL'       , $argv['all']);
        define('STATUS'    , $argv['status']);
        define('PAGE'      , $argv['page']);
        define('NOAUDIO'   , $argv['no_audio']);
        define('LIMIT'     , get_null($argv['limit']) ?? Config::getNatural('paging.limit', 50));

        // Set requested language
        Core::writeRegister($argv['language'] ?? Config::getString('languages.default', 'en'), 'system', 'language');

        if ($argv['auto_complete']) {
            // We're in auto complete mode. Show only direct output, don't use any color, don't log to screen
            Log::disableScreen();

            $argv['no_color']      = true;
            $argv['auto_complete'] = explode(' ', trim($argv['auto_complete']));

            $location = (int) array_shift($argv['auto_complete']);

            // Reset the $argv array to the auto complete data
            ArgvValidator::hideData($argv['auto_complete']);
            CliAutoComplete::setPosition($location - 1);
            CliAutoComplete::initSystemArguments();
        }

        // Correct $_SERVER['PHP_SELF'], sometimes seems empty
        if (empty($_SERVER['PHP_SELF'])) {
            if (!isset($_SERVER['_'])) {
                $e = new OutOfBoundsException('No $_SERVER[PHP_SELF] or $_SERVER[_] found');
            }

            $_SERVER['PHP_SELF'] = $_SERVER['_'];
        }

        // Set more system parameters
        if ($argv['debug']) {
            Debug::switch();
        }

        if (!CliCommand::getPhoUidMatch()) {
            // Do NOT do the rest of the CLI startup because we'll restart soon
            return;
        }

        if ($argv['log_level']) {
            Log::setThreshold($argv['log_level']);
        }

        if ($argv['no_prefix']) {
            Log::setUsePrefix(false);
        }

        // Process command line system arguments if we have no exception so far
        if ($argv['version']) {
            Log::cli(tr('Phoundation framework version ":version"', [
                ':version' => static::FRAMEWORK_CODE_VERSION,
            ]), 10);
            Log::cli(tr('Phoundation database version ":version"', [
                ':version' => Version::getString(Libraries::getMaximumVersion()),
            ]), 10);
            Log::cli(tr('Phoundation minimum PHP version ":version"', [
                ':version' => static::PHP_MINIMUM_VERSION,
            ]), 10);

            $exit = 0;
        }

        if ($argv['order_by']) {
            define('ORDERBY', ' ORDER BY `' . Strings::until($argv['order_by'], ' ') . '` ' . Strings::from($argv['order_by'], ' ') . ' ');

            $valid = preg_match('/^ ORDER BY `[a-z0-9_]+`(?:\s+(?:DESC|ASC))? $/', ORDERBY);

            if (!$valid) {
                // The specified column ordering is NOT valid
                $e = new CoreException(tr('The specified orderby argument ":argument" is invalid', [':argument' => ORDERBY]));
            }
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
        static::setTimeout();

        // Something failed?
        if (isset($e)) {
            echo "startup-cli: Command line parser failed with \"" . $e->getMessage() . "\"\n";
            CliCommand::setExitCode(1);
            exit(1);
        }

        if (isset($exit)) {
            Core::exit($exit);
        }

        // set terminal data
        static::$register['cli'] = ['term' => Cli::getTerm()];

        if (static::$register['cli']['term']) {
            static::$register['cli']['columns'] = Cli::getColumns();
            static::$register['cli']['lines']   = Cli::getLines();

            if (!static::$register['cli']['columns']) {
                static::$register['cli']['size'] = 'unknown';

            } elseif (static::$register['cli']['columns'] <= 80) {
                static::$register['cli']['size'] = 'small';

            } elseif (static::$register['cli']['columns'] <= 160) {
                static::$register['cli']['size'] = 'medium';

            } else {
                static::$register['cli']['size'] = 'large';
            }
        }

        // Set security umask
        umask(Config::get('filesystem.umask', 0007));

        // Get required language.
        try {
            $language = not_empty($argv['language'], Config::get('language.default', 'en'));

            if (Config::get('language.default', ['en']) and Config::exists('language.supported.' . $language)) {
                throw new CoreException(tr('Unknown language ":language" specified', [':language' => $language]));
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
        static::setLocale();

        // Prepare for unicode usage
        if (Config::get('languages.encoding.charset', 'UTF-8') === 'UTF-8') {
// TODO Fix this godawful mess!
            mb_init(not_empty(Config::get('locale.LC_CTYPE', ''), Config::get('locale.LC_ALL', '')));
            if (function_exists('mb_internal_encoding')) {
                mb_internal_encoding('UTF-8');
            }
        }

        static::setTimeZone($argv['timezone']);

        //
        static::$register['ready'] = true;

        // Validate parameters and give some startup messages, if needed
        if (Debug::getEnabled()) {
            if (Debug::getEnabled()) {
                Log::warning(tr('Running in DEBUG mode, started @ ":datetime"', [
                    ':datetime' => Date::convert(STARTTIME, 'ISO8601'),
                ]), 8);
                Log::notice(tr('Detected ":size" terminal with ":columns" columns and ":lines" lines', [
                    ':size'    => static::$register['cli']['size'],
                    ':columns' => static::$register['cli']['columns'],
                    ':lines'   => static::$register['cli']['lines'],
                ]));
            }
        }

        if (FORCE) {
            if (TEST) {
                throw new CoreException(tr('Both FORCE and TEST modes where specified, these modes are mutually exclusive'));
            }

            Log::warning(tr('Running in FORCE mode'));

        } elseif (TEST) {
            Log::warning(tr('Running in TEST mode, various modifications may not be executed!'));
        }

        if (!is_natural(PAGE)) {
            throw new CoreException(tr('Specified -P or --page ":page" is not a natural number', [
                ':page' => PAGE,
            ]));
        }

        if (!is_natural(LIMIT)) {
            throw new CoreException(tr('Specified --limit":limit" is not a natural number', [
                ':limit' => LIMIT,
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

        if ($argv['clear_caches']) {
            // Clear all caches
            static::enableInitState();
            Cache::clear();
            CliCommand::setRequireDefault(false);
            static::disableInitState();
        }

        if ($argv['clear_tmp']) {
            // Clear all tmp data
            static::enableInitState();
            Tmp::clear();
            CliCommand::setRequireDefault(false);
            static::disableInitState();
        }

        // Ensure any extra dashed arguments are "undashed"
        ArgvValidator::unDoubleDash();
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
            // We want to write to a sub key. Ensure that the key exists and is an array
            if (array_key_exists($key, static::$register)) {
                if (!is_array(static::$register[$key])) {
                    // Key exists but is not an array so cannot handle sub keys
                    throw new CoreException(tr('Cannot write to register key ":key.:subkey" as register key ":key" already exist as a value instead of an array', [
                        ':key'   => $key,
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
     * @version 2.7.5: Added function and documentation
     *
     */
    public static function setTimeout(int $timeout = null): bool
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

        static::$register['system']['timeout'] = $timeout;

        return set_time_limit($timeout);
    }


    /**
     * Sets the internal INIT state to true.
     *
     * @return void
     * @see Core::inInitState()
     */
    public static function enableInitState(): void
    {
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
        static::$init = false;
    }


    /**
     * Throws an exception for the given action if Core (and thus the entire system) is readonly
     *
     * @param string $action
     *
     * @return void
     * @throws DataEntryReadonlyException
     */
    public static function checkReadonly(string $action): void
    {
        if (static::$readonly) {
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
    protected static function setGlobalId(string $global_id): void
    {
        static::$global_id = $global_id;
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
            // Ups, we were sleeping but it got interrupted. Resume
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
            // Ups, we were sleeping but it got interrupted. Resume
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
     * call or manually with ./pho system maintenance disable
     *
     * @param bool $enable
     *
     * @return void
     */
    public static function setMaintenanceMode(bool $enable): void
    {
        $enabled = static::getMaintenanceMode();

        if ($enable) {
            // Enable maintenance mode
            if ($enabled) {
                Log::warning(tr('Not placing system in maintenance mode, the system was already placed in maintenance mode by ":user"', [
                    ':user' => $enabled,
                ]));

                return;
            }

            Directory::new(DIRECTORY_DATA . 'system/maintenance', Restrictions::new(DIRECTORY_DATA, true))
                     ->ensure();

            touch(DIRECTORY_DATA . 'system/maintenance/' . (Session::getUser()
                                                                           ->getEmail() ?? get_current_user()));
            Log::warning(tr('System has been placed in maintenance mode. All web requests will be blocked, all commands (except those under ./pho system ...) are blocked'));

            return;
        }

        // Disable maintenance mode
        if (!$enabled) {
            Log::Warning(tr('Not disabling maintenance mode, the system is not in maintenance mode'));

            return;
        }

        File::new(DIRECTORY_DATA . 'system/maintenance', Restrictions::new(DIRECTORY_DATA, true))
            ->delete();

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
     * @return string|null
     */
    public static function getMaintenanceMode(): ?string
    {
        static $maintenance = null;

        if ($maintenance) {
            return $maintenance;
        }

        if (file_exists(DIRECTORY_DATA . 'system/maintenance')) {
            // The system is in maintenance mode, show who put it there
            $files = Directory::new(DIRECTORY_DATA . 'system/maintenance')->scan();

            if ($files->getCount()) {
                $maintenance = $files->getFirstValue();
            }

            // ??? The maintenance directory is empty? It should contain a file with the email address of who locked it
            $maintenance = tr('Unknown');
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
        $enabled = static::getReadonlyMode();

        if ($enable) {
            // Enable readonly mode
            if ($enabled) {
                Log::warning(tr('Cannot place the system in readonly mode, the system was already placed in readonly mode by ":user"', [
                    ':user' => $enabled,
                ]));

                return;
            }

            Directory::new(DIRECTORY_DATA . 'system/readonly', Restrictions::new(DIRECTORY_DATA, true))
                     ->ensure();
            touch(DIRECTORY_DATA . 'system/readonly/' . (Session::getUser()
                                                                ->getEmail() ?? get_current_user()));
            Log::warning(tr('System has been placed in readonly mode. All web requests will be blocked, all commands (except those under ./pho system ...) are blocked'));

            return;
        }

        // Disable readonly mode
        if (!$enabled) {
            Log::warning(tr('Cannot disable readonly mode, the system is not in readonly mode'));

        } else {
            File::new(DIRECTORY_DATA . 'system/readonly', Restrictions::new(DIRECTORY_DATA, true))
                ->delete();
            Log::warning(tr('System has been relieved from readonly mode. All web POST requests will now again be processed, queries can write data again'), 10);
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
     * @return string|null
     */
    public static function getReadonlyMode(): ?string
    {
        if (file_exists(DIRECTORY_DATA . 'system/readonly')) {
            // System is in maintenance mode, show who put it there
            $files = Directory::new(DIRECTORY_DATA . 'system/readonly')->scan();

            if ($files->getCount()) {
                return $files->getFirstValue();
            }

            // ??? The maintenance directory is empty? It should contain a file with the email address of who locked it
            return tr('Unknown');
        }

        return null;
    }


    /**
     * Removes both maintenance and readonly modes
     *
     * @return void
     */
    public static function resetModes(): void
    {
        File::new(DIRECTORY_DATA . 'system/maintenace', Restrictions::new(DIRECTORY_DATA, true))->delete();
        File::new(DIRECTORY_DATA . 'system/readonly', Restrictions::new(DIRECTORY_DATA, true))->delete();

        Log::warning(tr('System has been relieved from readonly mode. All web requests will now again be answered, all commands are available'), 10);
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
                    error_log('Project version file "config/version" does not exist, entering setup mode');

                    static::setPlatform();
                    static::startupPlatform();
                    static::$state = 'setup';

                    throw new NoProjectException(tr('Project version file ":path" cannot be read. Please ensure it exists', [
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
                        ':key'   => $key,
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
    #[ExpectedValues(values: [
        null,
        'setup',
        'startup',
        'script',
        'shutdown',
        'maintenance',
    ])]
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
    public static function isState(#[ExpectedValues(values: [
        null,
        'setup',
        'boot',
        'startup',
        'script',
        'shutdown',
        'maintenance',
    ])] ?string $state): bool
    {
        return static::$state === $state;
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
     * @param string|null $state If specified will return the startup state for the specified state instead of the
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
     * @param string|null $state If specified will return the startup state for the specified state instead of the
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
        return static::isExecutedPath('dev/phpunit') or static::isExecutedPath('development/phpunit');
    }


    /**
     * Returns true if the system has finished starting up
     *
     * @param string|null $state If specified will return the startup state for the specified state instead of the
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
            'data'     => $data,
            'function' => $function,
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
            $free  = Free::new()
                         ->free();
            $limit = ceil($free['memory']['available'] * .8);
        }

        return (int) floor($limit);
    }


    /**
     * Will execute the specified callback only when not running in TEST mode
     *
     * @param callable $function
     * @param string   $task
     *
     * @return void
     */
    public static function ExecuteNotInTestMode(callable $function, string $task): void
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
     * Returns the UID for the current process
     *
     * @return int The user id for this process
     */
    public static function getProcessUid(): int
    {
        if (function_exists('posix_getuid')) {
            return posix_getuid();
        }

        return Id::new()
                 ->do('u');
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
            'uncaughtException',
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
}


