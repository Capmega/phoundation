<?php

/**
 * Log class
 *
 * This class is the main event logger class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Core\Log;

use JetBrains\PhpStorm\ExpectedValues;
use PDOStatement;
use Phoundation\Cli\CliAutoComplete;
use Phoundation\Cli\CliColor;
use Phoundation\Core\Core;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Libraries\Library;
use Phoundation\Core\Log\Exception\LogException;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Databases\Sql\SqlQueries;
use Phoundation\Date\DateTime;
use Phoundation\Developer\Debug;
use Phoundation\Exception\Exception;
use Phoundation\Exception\Interfaces\ExceptionInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Enums\EnumFileOpenMode;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Phoundation\Web\Requests\Request;
use Stringable;
use Throwable;
use Traversable;


class Log
{
    /**
     * Used to display only classes and functions in backtraces
     */
    public const BACKTRACE_DISPLAY_FUNCTION = 1;

    /**
     * Used to display only files and line numbers in backtraces
     */
    public const BACKTRACE_DISPLAY_FILE = 2;

    /**
     * Used to display both classes and function and files and line numbers in backtraces
     */
    public const BACKTRACE_DISPLAY_BOTH = 3;

    /**
     * Singleton variable
     *
     * @var Log|null $instance
     */
    protected static ?Log $instance = null;

    /**
     * Sets if logging is enabled or disabled
     *
     * @var bool $enabled
     */
    protected static bool $enabled = true;

    /**
     * Sets if logging to a file is enabled or disabled
     *
     * @var bool $file_enabled
     */
    protected static bool $file_enabled = true;

    /**
     * Sets if logging to a screen is enabled or disabled
     *
     * @var bool $screen_enabled
     */
    protected static bool $screen_enabled = true;

    /**
     * Keeps track of what log files we're logging to
     */
    protected static array $streams = [];

    /**
     * Keeps track of the LOG FAILURE status
     */
    protected static bool $failed = false;

    /**
     * The current threshold level of the log class. The higher this value, the less will be logged
     *
     * @var int $threshold
     */
    protected static int $threshold;

    /**
     * If true, log messages will have a prefix
     *
     * @var bool $echo_prefix
     */
    protected static string|bool $echo_prefix = false;

    /**
     * The current file where the log class will write to.
     *
     * @var string|null $file
     */
    protected static ?string $file = null;

    /**
     * The current backtrace display configuration
     *
     * @var int $display
     */
    protected static int $display = self::BACKTRACE_DISPLAY_BOTH;

    /**
     * Keeps track of if the static object has been initialized or not
     *
     * @var bool $init
     */
    protected static bool $init = false;

    /**
     * The last message that was logged.
     *
     * @var mixed $last_message
     */
    protected static mixed $last_message = null;

    /**
     * Lock the Log class from writing in case it is busy to avoid race conditions
     *
     * @var bool $lock
     */
    protected static bool|array $lock = false;

    /**
     * If true, double log messages will be filtered out (not recommended, this might hide issues)
     *
     * @var bool $filter_double
     */
    protected static bool $filter_double = false;

    /**
     * Log file access restrictions
     *
     * @var FsRestrictionsInterface $restrictions
     */
    protected static FsRestrictionsInterface $restrictions;

    /**
     * Tracks whether the syslog filter ini setting has been applied
     *
     * @var bool $syslog_filter_applied
     */
    protected static bool $syslog_filter_applied = false;

    /**
     * Tracks whether the syslog is open or not
     *
     * @var bool $syslog_open
     */
    protected static bool $syslog_open = false;


    /**
     * Log constructor
     */
    protected function __construct()
    {
        // Ensure that the log class hasn't been initialized yet
        if (static::$init) {
            return;
        }

        static::$init = true;

        // Apply configuration
        try {
            // Determine log threshold
            if (!isset(static::$threshold)) {
                if (defined('QUIET') and QUIET) {
                    // Ssshhhhhhhh..
                    $threshold = 9;

                } elseif (defined('VERBOSE') and VERBOSE) {
                    // BE LOUD!
                    $threshold = 1;

                } else {
                    // Be... normal, I guess
                    if (Debug::isEnabled()) {
                        // Debug shows a bit more
                        $threshold = Config::getInteger('log.threshold', Core::errorState() ? 1 : 3);

                    } else {
                        $threshold = Config::getInteger('log.threshold', Core::errorState() ? 1 : 5);
                    }

                    if ($threshold === 1) {
                        // Threshold is at lowest, this will log a LOT
                        if (Core::isState('boot')) {
                            // Boot time logging should not be too much
                            $threshold = 5;
                        }
                    }
                }

                static::setThreshold($threshold);
            }

            static::$restrictions = FsRestrictions::newWritable(DIRECTORY_DATA . 'log/');
            static::setFile(Config::get('log.file', DIRECTORY_ROOT . 'data/log/syslog'));
            static::setBacktraceDisplay(Config::get('log.backtrace-display', self::BACKTRACE_DISPLAY_BOTH));

        } catch (Throwable $e) {
            // Likely configuration read failed. Set defaults
            static::$restrictions = FsRestrictions::new(DIRECTORY_DATA . 'log/', true, 'Log');
            static::setThreshold(10);
            static::setFile(DIRECTORY_ROOT . 'data/log/syslog');
            static::setBacktraceDisplay(self::BACKTRACE_DISPLAY_BOTH);
        }

        static::$init = false;
    }


    /**
     * Returns if logging is enabled or not
     *
     * @return bool
     */
    public static function getEnabled(): bool
    {
        return static::$enabled;
    }


    /**
     * Set the local id parameter.
     *
     * 1 BACKTRACE_DISPLAY_FUNCTION
     * 2 BACKTRACE_DISPLAY_FILE
     * 3 BACKTRACE_DISPLAY_BOTH
     *
     * @note This method also allows $display defined as their string names (for easy configuration purposes)
     *
     * @param string|int $display The new display configuration
     *
     * @return int The previous value
     */
    public static function setBacktraceDisplay(#[ExpectedValues(values: [
        'BACKTRACE_DISPLAY_FUNCTION',
        'BACKTRACE_DISPLAY_FILE',
        'BACKTRACE_DISPLAY_BOTH',
        Log::BACKTRACE_DISPLAY_FUNCTION,
        Log::BACKTRACE_DISPLAY_FILE,
        Log::BACKTRACE_DISPLAY_BOTH,
    ])] string|int $display): int
    {
        switch ($display) {
            case 'BACKTRACE_DISPLAY_FUNCTION':
                // no break

            case self::BACKTRACE_DISPLAY_FUNCTION:
                $display = self::BACKTRACE_DISPLAY_FUNCTION;
                break;

            case 'BACKTRACE_DISPLAY_FILE':
                // no break

            case self::BACKTRACE_DISPLAY_FILE:
                $display = self::BACKTRACE_DISPLAY_FILE;
                break;

            case 'BACKTRACE_DISPLAY_BOTH':
                // no break

            case self::BACKTRACE_DISPLAY_BOTH:
                $display = self::BACKTRACE_DISPLAY_BOTH;
                break;

            default:
                throw new OutOfBoundsException(tr('Invalid backtrace display value ":display" specified. Please ensure it is one of Log::BACKTRACE_DISPLAY_FUNCTION, Log::BACKTRACE_DISPLAY_FILE, or Log::BACKTRACE_DISPLAY_BOTH', [
                    ':display' => $display,
                ]));
        }

        $return          = static::$display;
        static::$display = $display;

        return $return;
    }


    /**
     * Log to PHP error console
     *
     * @param ArrayableInterface|array|string $messages
     * @param int                             $message_type
     * @param string|null                     $destination
     * @param string|null                     $additional_headers
     *
     * @return void
     * @todo Improve handling of logging that does not go through syslog
     *
     */
    public static function toAlternateLog(ArrayableInterface|array|string $messages, int $message_type = 4, ?string $destination = null, ?string $additional_headers = null): void
    {
        if (!static::$syslog_filter_applied) {
            ini_set('syslog.filter', 'any');
            static::$syslog_filter_applied = true;
        }

        $additional_headers = $additional_headers ?? Config::get('log.headers', '');

        if ($messages instanceof ArrayableInterface) {
            $messages = $messages->__toArray();
        }

        if (is_array($messages)) {
            foreach ($messages as $message) {
                static::toAlternateLog(Strings::force($message, PHP_EOL));
            }

        } else {
            error_log($messages, $message_type, $destination, $additional_headers);
        }

        if (php_sapi_name() !== 'cli') {
            flush();
        }
    }


    /**
     * Log to PHP syslog
     *
     * @todo Under construction
     *
     * @param string $message
     * @param int    $priority_flags
     * @param int    $open_flags
     * @param int    $facility
     *
     * $priority_flags can be a mix of the following flags:
     *
     * LOG_EMERG   system is unusable
     * LOG_ALERT   action must be taken immediately
     * LOG_CRIT    critical conditions
     * LOG_ERR     error conditions
     * LOG_WARNING warning conditions
     * LOG_NOTICE  normal, but significant, condition
     * LOG_INFO    informational message
     * LOG_DEBUG   debug-level message
     *
     * $open_flags can be a mix of the following flags:
     * LOG_CONS    if there is an error while sending data to the system logger, write directly to the system console
     * LOG_NDELAY  open the connection to the logger immediately
     * LOG_ODELAY  (default) delay opening the connection until the first message is logged
     * LOG_PERROR  print log message also to standard error
     * LOG_PID     include PID with each message
     *
     * $faciltiy
     * LOG_AUTH    security/authorization messages (use LOG_AUTHPRIV instead in systems where that constant is defined)
     * LOG_AUTHPRIV    security/authorization messages (private)
     * LOG_CRON    clock daemon (cron and at)
     * LOG_DAEMON    other system daemons
     * LOG_KERN    kernel messages
     * LOG_LOCAL0 ... LOG_LOCAL7    reserved for local use, these are not available in Windows
     * LOG_LPR    line printer subsystem
     * LOG_MAIL    mail subsystem
     * LOG_NEWS    USENET news subsystem
     * LOG_SYSLOG    messages generated internally by syslogd
     * LOG_USER    generic user-level messages
     * LOG_UUCP    UUCP subsystem
     *
     * @return void
     */
    protected static function sysLog(string $message, int $priority_flags = LOG_INFO, int $open_flags = LOG_CONS | LOG_NDELAY | LOG_ODELAY | LOG_PERROR | LOG_PID, int $facility = LOG_USER): void
    {
        if (!static::$syslog_filter_applied) {
            ini_set('syslog.filter', 'all');
            static::$syslog_filter_applied = true;
        }

        static::$syslog_open = true;
        openlog(PROJECT, $priority_flags, $facility);

        if (static::getScreenEnabled()) {
            syslog($priority_flags, $message);
        }

        if (php_sapi_name() !== 'cli') {
            flush();
        }
    }


    /**
     * Returns true if the log is in failed mode and only logging to Log::errorLog()
     *
     * @return bool
     */
    public static function getFailed(): bool
    {
        return static::$failed;
    }


    /**
     * Sets the log into failed mode
     *
     * @return void
     */
    public static function setFailed(): void
    {
        static::$failed = true;
    }


    /**
     * Returns if the static Log object has been initialized or not. This SHOULD always return true.
     *
     * @return bool
     */
    public static function getInit(): bool
    {
        return static::$init;
    }


    /**
     * Returns the last message that was logged
     *
     * @return ?string
     */
    public static function getLastMessage(): ?string
    {
        return static::$last_message;
    }


    /**
     * Returns if log messages have a prefix or not
     *
     * @return bool
     */
    public static function getEchoPrefix(): bool
    {
        return static::$echo_prefix;
    }


    /**
     * Sets if log messages have a prefix or not
     *
     * @param string|bool $echo_prefix
     *
     * @return void
     *
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public static function setEchoPrefix(bool $echo_prefix): void
    {
        static::$echo_prefix = $echo_prefix;
    }


    /**
     * Returns the log threshold on which log messages will pass to log files
     *
     * @return int
     */
    public static function getThreshold(): int
    {
        return static::$threshold;
    }


    /**
     * Sets the log threshold level to the newly specified level and will return the previous level.
     *
     * @param int $threshold
     *
     * @return int
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public static function setThreshold(int $threshold): int
    {
        if (!is_numeric($threshold) or ($threshold < 1) or ($threshold > 10)) {
            throw OutOfBoundsException::new(tr('The specified log threshold level ":level" is invalid. Please ensure the level is between 1 and 10', [
                ':level' => $threshold,
            ]))->makeWarning();
        }

        $return            = $threshold;
        static::$threshold = $threshold;

        return $return;
    }


    /**
     * Enables logging
     *
     * @return void
     */
    public static function enable(): void
    {
        static::$enabled = true;
    }


    /**
     * Disables logging
     *
     * @return void
     */
    public static function disable(): void
    {
        static::$enabled = false;
    }


    /**
     * Enables to file logging
     *
     * @return void
     */
    public static function enableFile(): void
    {
        static::$file_enabled = true;
    }


    /**
     * Disables to file logging
     *
     * @return void
     */
    public static function disableFile(): void
    {
        static::$file_enabled = false;
    }


    /**
     * Returns if logging to file is enabled or not
     *
     * @return bool
     */
    public static function getFileEnabled(): bool
    {
        return static::$enabled and static::$file_enabled and FsFile::getWriteEnabled();
    }


    /**
     * Enables to screen logging
     *
     * @return void
     */
    public static function enableScreen(): void
    {
        static::$screen_enabled = true;
    }


    /**
     * Disables to screen logging
     *
     * @return void
     */
    public static function disableScreen(): void
    {
        static::$screen_enabled = false;
    }


    /**
     * Returns if logging to screen is enabled or not
     *
     * @return bool
     */
    public static function getScreenEnabled(): bool
    {
        return static::$enabled and static::$screen_enabled;
    }


    /**
     * Returns if double messages should be filtered or not
     *
     * @return bool
     */
    public static function getFilterDouble(): bool
    {
        return static::$filter_double;
    }


    /**
     * Sets if double messages shoudl be filtered or not
     *
     * @param bool $filter_double
     */
    public static function setFilterDouble(bool $filter_double): void
    {
        static::$filter_double = $filter_double;
    }


    /**
     * Close the specified log file
     *
     * @param string|null $file
     *
     * @return void
     */
    public static function closeFile(string $file = null): void
    {
        if ($file === null) {
            // Default log file is always the syslog
            $file = DIRECTORY_ROOT . 'data/log/syslog';
        }

        if (empty(static::$streams[$file])) {
            throw new FilesystemException(tr('Cannot close log file ":file", it was never opened', [':file' => $file]));
        }

        static::$streams[$file]->close();
    }


    /**
     * Returns the backtrace display configuration
     *
     * 1 BACKTRACE_DISPLAY_FUNCTION
     * 2 BACKTRACE_DISPLAY_FILE
     * 3 BACKTRACE_DISPLAY_BOTH
     *
     * @return int
     */
    public static function getBacktraceDisplay(): int
    {
        return static::$display;
    }


    /**
     * Write a success message in the log file
     *
     * @param mixed       $messages
     * @param int         $threshold
     * @param bool        $clean
     * @param bool        $echo_newline
     * @param string|bool $echo_prefix
     * @param bool        $echo_screen
     *
     * @return bool
     */
    public static function success(mixed $messages = null, int $threshold = 6, bool $clean = true, bool $echo_newline = true, string|bool $echo_prefix = true, bool $echo_screen = true): bool
    {
        return static::write($messages, 'success', $threshold, $clean, $echo_newline, $echo_prefix, $echo_screen);
    }


    /**
     * Write the specified log message to the current log file for this instance
     *
     * @param mixed       $messages     The messages that are to be logged
     * @param string|null $class        The class of message that will be logged. Different classes will show in
     *                                  different colors
     * @param int         $threshold    The threshold level for this message. If the level is lower than the threshold,
     *                                  the message will be dropped and not appear in the log files to avoid clutter
     * @param bool        $clean        If true, the data will be cleaned before written to log. This will avoid (for
     *                                  example) binary data from corrupting the log file
     * @param bool        $echo_newline If true, a newline will be appended at the end of the log line
     * @param string|bool $echo_prefix  If true (default), all log lines will be prefixed with a string containing
     *                                  date-time, local process id, and global process id
     * @param bool        $echo_screen  If true (default), on CLI, the log line will be printed (without prefix) on the
     *                                  command line as well
     *
     * @return bool                     True if the line was written, false if it was dropped
     * @todo Refactor this method, its become too cluttered over time
     */
    public static function write(mixed $messages = null, ?string $class = null, int $threshold = 10, bool $clean = true, bool $echo_newline = true, string|bool $echo_prefix = true, bool $echo_screen = true): bool
    {
        if (!static::$enabled) {
            return false;
        }

        if (static::$init) {
            // Do not log anything while locked, initializing, or while dealing with a Log internal failure
            if (static::$screen_enabled and static::$file_enabled) {
                foreach (Arrays::force($messages, null) as $message) {
                    if ($message instanceof Throwable) {
                        static::toAlternateLog('Phoundation: exception class    : ' . get_class($message));
                        static::toAlternateLog('Phoundation: exception message  : ' . $message->getMessage());
                        static::toAlternateLog('Phoundation: exception location : ' . $message->getFile() . '@' . $message->getLine());

                        $trace = Debug::formatBackTrace($message->getTrace());
                        foreach ($trace as $step)
                        static::toAlternateLog('Phoundation: exception trace    : ' . $step);

                        if ($message instanceof Exception) {
                            static::toAlternateLog('Phoundation: exception data     : ' . Strings::force($message->getData()));
                        }

                        if ($message->getPrevious()) {
                            static::toAlternateLog('Phoundation: previous exception : ');
                            static::write($message->getPrevious());
                        }

                    } else {
                        static::toAlternateLog('Phoundation: ' . Strings::force($message));
                    }
                }
            }

            return false;
        }

        static::ensureInstance();

        try {
            // Do we have a log file setup?
            if (empty(static::$file) and !static::$failed) {
                if (static::getFileEnabled()) {
                    throw new LogException(tr('Cannot log, no log file specified'));
                }

                // Log file has not been set, but file logging is disabled, so continue
            }

            // If we received an array, then log each line separately
            if (is_array($messages)) {
                $success = true;

                foreach ($messages as $message) {
                    $success = ($success and static::write($message, $class, $threshold, $clean, $echo_newline, $echo_prefix, $echo_screen));
                }

                static::$lock = false;

                return $success;

            }

            if (is_object($messages)) {
                // If the message to be logged is an object, then extract the log information from there
                return static::object($messages, $class, $threshold, $clean, $echo_newline, $echo_prefix, $echo_screen);
            }

            if (static::$lock) {
                static::toAlternateLog(tr('Rejecting next log message to avoid endless loops because Log->write() is locked for another log entry. Check backtrace for Log-> calls within Log->write()'));
                static::toAlternateLog(Strings::force($messages, PHP_EOL));
                static::toAlternateLog(Strings::force(print_r(Debug::getBacktrace(), true), PHP_EOL));

                return false;
            }

            static::$lock = true;

            // Get the real level and check if we passed the threshold. If $threshold was negative, the same message may be
            // logged multiple times
            $real_threshold = abs($threshold);

            if ($real_threshold < static::$threshold) {
                // This log message level did not meet the threshold, discard it
                static::$lock = false;

                return false;
            }

            // Validate the specified log level
            if ($real_threshold > 9) {
                // This is an "always log!" message, which only are displayed if we're running in debug mode
                if (Debug::isEnabled()) {
                    if ($real_threshold > 10) {
                        // Yeah, this is not okay
                        static::warning(tr('Invalid log level ":level" specified for the following log message. This level should be set to 1-10', [
                            ':level' => $threshold,
                        ]), 10);
                    }
                }
            }

            // Make sure the log message is clean and readable.
            // Don't truncate as we might have huge log messages!
            // If no or an empty class was specified, we do not clean
            if ($class and $clean) {
                $messages = Strings::log($messages, 0);
            }

            // Don't log the same message twice in a row
            if (($threshold > 0) and (static::$last_message === $messages) and (static::$filter_double)) {
                static::$lock = false;

                return false;
            }

            static::$last_message = $messages;

            // If logging to the standard log output failed or we're initializing the log, then write to the system log
            if (static::$failed) {
                static::toAlternateLog(Strings::force($messages));
                static::$lock = false;

                return true;
            }

            // Build the message to be logged, clean it and log
            // The log line format is DATE LEVEL PID GLOBALID/LOCALID MESSAGE EOL
            if ($clean) {
                $messages = Strings::cleanWhiteSpace($messages);
            }

            if (!$messages) {
                if (!is_numeric($messages)) {
                    // Do not log empty messages
                    static::$lock = false;

                    if (Debug::isEnabled()) {
                        // Log where this empty log message came from
                        Log::warning(tr('Encountered an empty log message at ":call"', [
                            ':call' => Debug::getCall(null, Log::class)->getLocation()
                        ]));
                    }
                }

                // This is 0 or 0.0
                $messages = (string) $messages;
            }

            // Add coloring for easier reading
            $messages  = CliColor::apply((string) $messages, $class);
            $messages .= ($echo_newline ? PHP_EOL : null);

            // Build message prefix
            // TODO Check max process id in /proc/sys/kernel/pid_max and use that as max length instead of static 7
            if (is_bool($echo_prefix)) {
                // Build the log message with the default prefix
                $prefix = DateTime::new(null, 'server')->format('Y-m-d H:i:s.v') . ' ' .
                          ($threshold === 10 ? 10 : ' ' . $threshold) . ' ' .
                          Strings::size(getmypid(), 7, ' ', true) . ' ' .
                          Core::getGlobalId() . ' / ' . Core::getLocalId() . (Core::isShuttingDown() ? '#' : ' ');
            } else {
                $prefix = $echo_prefix;
            }

            // Write the log message to screen and file
            static::writeMessage($prefix, $messages, $echo_prefix, $echo_screen);
            static::$lock = false;

            return true;

        } catch (Throwable $e) {
            return static::writeExceptionHandler($e, $messages, $threshold);
        }
    }


    /**
     * Writes the log message to screen and file
     *
     * @param string $prefix
     * @param string $message
     * @param bool   $echo_prefix
     * @param bool   $echo_screen
     *
     * @return void
     */
    protected static function writeMessage(string $prefix, string $message, bool $echo_prefix, bool $echo_screen): void
    {
        // Write the message to screen
        if ($echo_screen and (PHP_SAPI === 'cli') and static::getScreenEnabled()) {
            if (static::$echo_prefix and $echo_prefix) {
                echo $prefix, $message;

            } else {
                echo $message;
            }
        }

        // Write the message to the log file
        if (static::getFileEnabled()) {
            if ($echo_prefix) {
                // Write the message to the log file
                static::$streams[static::$file]->write($prefix . $message);

            } else {
                // Write the message to the log file
                static::$streams[static::$file]->write($message);
            }
        }
    }


    /**
     * Singleton, ensure to always return the same Log object.
     *
     * @return void
     */
    protected static function ensureInstance(): void
    {
        try {
            if (!isset(static::$instance)) {
                static::$instance = new static();

                // Log class startup message
                if (Debug::isEnabled()) {
                    static::information(tr('Logger started, threshold set to ":threshold"', [
                        ':threshold' => static::$threshold,
                    ]), 3);
                }
            }

        } catch (Throwable $e) {
            // Crap, we could not get a Log instance
            static::$failed = true;
        }
    }


    /**
     * Write an information message in the log file
     *
     * @param mixed       $messages
     * @param int         $threshold
     * @param bool        $clean
     * @param bool        $echo_newline
     * @param string|bool $echo_prefix
     * @param bool        $echo_screen
     *
     * @return bool
     */
    public static function information(mixed $messages = null, int $threshold = 7, bool $clean = true, bool $echo_newline = true, string|bool $echo_prefix = true, bool $echo_screen = true): bool
    {
        if (VERY_QUIET) {
            return false;
        }

        return static::write($messages, 'information', $threshold, $clean, $echo_newline, $echo_prefix, $echo_screen);
    }


    /**
     * Write a warning message in the log file
     *
     * @param mixed       $messages
     * @param int         $threshold
     * @param bool        $clean
     * @param bool        $echo_newline
     * @param string|bool $echo_prefix
     * @param bool        $echo_screen
     *
     * @return bool
     */
    public static function warning(mixed $messages = null, int $threshold = 8, bool $clean = true, bool $echo_newline = true, string|bool $echo_prefix = true, bool $echo_screen = true): bool
    {
        return static::write($messages, 'warning', $threshold, $clean, $echo_newline, $echo_prefix, $echo_screen);
    }


    /**
     * Logs an object in the log file
     *
     * @param object      $object
     * @param string|null $class
     * @param int         $threshold
     * @param bool        $clean
     * @param bool        $echo_newline
     * @param string|bool $echo_prefix
     * @param bool        $echo_screen
     *
     * @return bool
     */
    public static function object(object $object, ?string $class = null, int $threshold = 10, bool $clean = true, bool $echo_newline = true, string|bool $echo_prefix = true, bool $echo_screen = true): bool
    {
        if ($object instanceof Throwable) {
            // Log exception
            return static::exception($object, $threshold, $clean, $echo_newline, $echo_prefix, $echo_screen);
        }

        if ($object instanceof ArrayableInterface) {
            // Convert to array
            $message = $object->__toArray();

        } elseif ($object instanceof Stringable) {
            // Convert to string
            $message = (string) $object;

        } else {
            // No idea what to do with this object, so log the class name
            $message = 'Object {' . get_class($object) . '}';
        }

        return static::write($message, $class, $threshold, $clean, $echo_newline, $echo_prefix, $echo_screen);
    }


    /**
     * Logs an exception object in the log file
     *
     * @param Throwable|null $exception
     * @param int            $threshold
     * @param bool           $clean
     * @param bool           $echo_newline
     * @param string|bool    $echo_prefix
     * @param bool           $echo_screen
     *
     * @return bool
     */
    public static function exception(?Throwable $exception, int $threshold = 9, bool $clean = true, bool $echo_newline = true, string|bool $echo_prefix = true, bool $echo_screen = true): bool
    {
        if ($exception) {
            // This is an exception object, log the warning or error  message data. PHP exceptions have
            // $e->getMessage() and Phoundation exceptions can have multiple messages using $e->getMessages()
            // Redetermine the log class
            if ($exception instanceof ExceptionInterface) {
                if ($exception->hasBeenLogged()) {
                    // This exception has already been logged, don't log again
                    return false;
                }

                if ($exception->isWarning()) {
                    // This is a warning exception, which can be displayed to user (usually this is caused by user
                    // data validation issues, etc.
                    $class = 'warning';

                } else {
                    // This is an error exception, which is more severe
                    $class = 'error';
                }

            } else {
                // This is a PHP error, which is always a hard error
                $class = 'error';
            }

            // Log the initial exception message
            $has_logged = static::write(tr('Exception : '), 'information', $threshold, false, false, echo_screen: $echo_screen);

            static::write(get_class($exception), $class, $threshold, true, true, false, $echo_screen);
            static::write(tr('Message   : '), 'information', $threshold, false, false, echo_screen: $echo_screen);
            static::write('[E' . ($exception->getCode() ?? 'N/A') . '] ' . $exception->getMessage(), $class, $threshold, false, true, false, $echo_screen);
            static::write(tr('Script    : '), 'information', $threshold, false, false, echo_screen: $echo_screen);
            static::write(Request::getExecutedPath(true), $class, $threshold, true, true, false, $echo_screen);
            static::write(tr('Location  : '), 'information', $threshold, false, false, echo_screen: $echo_screen);
            static::write($exception->getFile() . '@' . $exception->getLine(), $class, $threshold, true, true, false, $echo_screen);

            // Log the exception data, the trace, and previous exception, if any.
            static::logExceptionTrace($exception, $class, $threshold, $clean, $echo_newline, $echo_prefix, $echo_screen);
            static::logExceptionData($exception, $threshold, $clean, $echo_newline, $echo_prefix, $echo_screen);
            static::logPreviousException($exception, $class, $threshold, $clean, $echo_newline, $echo_prefix, $echo_screen);

            if ($exception instanceof ExceptionInterface) {
                $exception->hasBeenLogged($has_logged);
            }

        } else {
            // NULL exception
            static::write(tr('Exception : '), 'information', $threshold, false, false, echo_screen: $echo_screen);
            static::write('NULL (a.k.a. There is no exception)', 'error', $threshold, true, true, false, $echo_screen);
        }

        return true;
    }


    /**
     * Returns the file to which log messages will be written
     *
     * @return FsFileInterface
     */
    public static function getFile(): FsFileInterface
    {
        return new FsFile(static::$file);
    }


    /**
     * Sets the log threshold level to the newly specified level and will return the previous level. Once a log file has
     * been opened, it will remain open until closed with the Log::closeFile() method
     *
     * @param string|null $file
     *
     * @return string|null
     * @throws LogException if the specified threshold is invalid.
     */
    public static function setFile(string $file = null): ?string
    {
        if (!static::getFileEnabled()) {
            // Logging to file is disabled, don't set a file
            return static::$file;
        }

        if (static::$failed) {
            // If the log is in failed mode, we cannot switch file
            static::toAlternateLog(tr('Not switching log file to ":file", log is running in failed mode', [
                ':file' => $file,
            ]));

            return static::$file;
        }

        try {
            $return = static::$file;

            if ($file === null) {
                // Default log file is always the syslog
                $file = DIRECTORY_ROOT . 'data/log/syslog';
            }

            // Log file is already open? Close so re-open will ensure that the file exists
            if (isset(static::$streams[$file])) {
                static::$streams[$file]->close(true);
            }

            // Open the specified log file
            static::$streams[$file] = FsFile::new($file, static::$restrictions)
                                            ->ensureWritable(0640)          // Log file should always be 0640
                                            ->setForceAccess(true)     // Log file must always be accessible
                                            ->open(EnumFileOpenMode::writeOnlyAppend);

            // Set the class file to the specified file and return the old value and
            static::$file = $file;

        } catch (Throwable $e) {
            // Something went wrong trying to open the log file. Log the error but do continue
            static::$failed = true;
            static::error(tr('Failed to open log file ":file" because of exception ":e"', [
                ':file' => $file,
                ':e'    => $e->getMessage(),
            ]));
        }

        return $return;
    }


    /**
     * Logs the data section of an exception
     *
     * @param Throwable   $exception
     * @param int         $threshold
     * @param bool        $clean
     * @param bool        $echo_newline
     * @param string|bool $echo_prefix
     * @param bool        $echo_screen
     *
     * @return void
     */
    protected static function logExceptionData(Throwable $exception, int $threshold = 10, bool $clean = true, bool $echo_newline = true, string|bool $echo_prefix = true, bool $echo_screen = true): void
    {
        if ($exception instanceof Exception) {
            $data = $exception->getData();

            if ($data) {
                static::write(tr('Data      : '), 'information', $threshold, echo_screen: $echo_screen);

                if ($exception->isWarning()) {
                    // Log warning data as individual lines for easier read
                    foreach (Arrays::force($data, null) as $line) {
                        static::write(print_r($line, true), 'warning', $threshold, false, $echo_newline, $echo_prefix, $echo_screen);
                    }

                } else {
                    static::write(print_r($data, true), 'debug', $threshold, false, $echo_newline, $echo_prefix, $echo_screen);
                }

            } else {
                static::write(tr('Data      : '), 'information', $threshold, false, echo_newline: false, echo_screen: $echo_screen);
                static::write('-', 'debug', $threshold, false, $echo_newline, false, $echo_screen);
            }
        }
    }


    /**
     * Logs the trace section of an exception
     *
     * @param Throwable   $exception
     * @param string|null $class
     * @param int         $threshold
     * @param bool        $clean
     * @param bool        $echo_newline
     * @param string|bool $echo_prefix
     * @param bool        $echo_screen
     *
     * @return void
     */
    protected static function logExceptionTrace(Throwable $exception, ?string $class = null, int $threshold = 10, bool $clean = true, bool $echo_newline = true, string|bool $echo_prefix = true, bool $echo_screen = true): void
    {
        // Warning exceptions do not need to show the extra messages, trace, or data or previous exception
        if ($class == 'error') {
            // Log the backtrace
            static::write(tr('Backtrace :'), 'information', $threshold, $clean, $echo_newline, $echo_prefix, $echo_screen);

            if ($exception->getTrace()) {
                static::writeTrace($exception->getTrace(), $threshold, class: $class, echo_screen: $echo_screen);

            } else {
                static::write('-', 'debug', $threshold, false, $echo_newline, $echo_prefix, $echo_screen);
            }
        }
    }




    /**
     * Logs the previous exception from the specified exception, if any
     *
     * @param Throwable   $exception
     * @param string|null $class
     * @param int         $threshold
     * @param bool        $clean
     * @param bool        $echo_newline
     * @param string|bool $echo_prefix
     * @param bool        $echo_screen
     *
     * @return void
     */
    protected static function logPreviousException(Throwable $exception, ?string $class = null, int $threshold = 10, bool $clean = true, bool $echo_newline = true, string|bool $echo_prefix = true, bool $echo_screen = true): void
    {
        // Log all previous exceptions as well
        $previous = $exception->getPrevious();

        if ($previous) {
            if ($previous instanceof ExceptionInterface) {
                // Previous exceptions are always shown
                $previous->hasBeenLogged(false);
            }

            static::write('Previous exception: ', 'information', $threshold, $clean, $echo_newline, $echo_prefix, $echo_screen);
            static::exception($previous, $threshold, $clean, $echo_newline, $echo_prefix, $echo_screen);
        }
    }


    /**
     * Dump the specified backtrace data
     *
     * @param array       $backtrace The backtrace data
     * @param int         $threshold The log level for this backtrace data
     * @param int|null    $display   How to display the backtrace. Must be one of Log::BACKTRACE_DISPLAY_FILE,
     *                               Log::BACKTRACE_DISPLAY_FUNCTION or Log::BACKTRACE_DISPLAY_BOTH.
     * @param string      $class
     * @param bool        $echo_screen
     * @param bool        $from_script
     * @param string|bool $echo_prefix
     *
     * @return int The number of lines that were logged. -1 in case of an exception while trying to log the backtrace.
     */
    protected static function writeTrace(array $backtrace, int $threshold = 9, ?int $display = null, string $class = 'debug', bool $echo_screen = true, bool $from_script = true, string|bool $echo_prefix = true): int
    {
        try {
            $lines = Debug::formatBackTrace($backtrace);

            if ($from_script) {
                // Filter out all entries before the script start
                $copy  = $lines;
                $lines = [];

                foreach ($copy as $line) {
                    if (str_contains($line, 'functions.php') and str_contains($line, 'include()')) {
                        break;
                    }

                    $lines[] = $line;
                }
            }

            foreach ($lines as $line) {
                static::write($line, $class, $threshold, false, echo_prefix: $echo_prefix, echo_screen: $echo_screen);
            }

            return count($lines);

        } catch (Throwable $e) {
            // Don't crash the process because of this, log it and return -1 to indicate an exception
            static::error(tr('Failed to write backtrace to log because of exception ":e"', [
                ':e' => $e->getMessage(),
            ]));

            return -1;
        }
    }


    /**
     * Write an error message in the log file
     *
     * @param mixed       $messages
     * @param int         $threshold
     * @param bool        $clean
     * @param string|bool $echo_prefix
     * @param bool        $echo_screen
     *
     * @return bool
     */
    public static function error(mixed $messages = null, int $threshold = 9, bool $clean = false, string|bool $echo_prefix = true, bool $echo_screen = true): bool
    {
        return static::write($messages, 'error', $threshold, $clean, echo_prefix: $echo_prefix, echo_screen: $echo_screen);
    }


    /**
     * Handles log write exceptions
     *
     * @param Throwable  $e
     * @param mixed|null $messages
     * @param int        $threshold
     *
     * @return bool
     */
    protected static function writeExceptionHandler(Throwable $e, mixed $messages = null, int $threshold = 10): bool
    {
        // Don't ever let the system crash because of a log issue, so we catch all possible exceptions
        static::$failed = true;
        static::$lock   = false;

        try {
            $message = $threshold . ' ' . getmypid() . ' ' . Core::getGlobalId() . '/' . Core::getLocalId() . ' Failed to log message to internal log files because "' . $e->getMessage() . '"';

            static::toAlternateLog($message);

            try {
                foreach (Arrays::force($messages, null) as $message) {
                    $message = CliColor::strip((string) $message);
                    $message = $threshold . ' ' . getmypid() . ' ' . Core::getGlobalId() . '/' . Core::getLocalId() . ' ' . $message;
                    static::toAlternateLog($message);
                }

            } catch (Throwable $g) {
                // Okay, this is messed up, we can't even log to system logs.
                static::toAlternateLog('Failed to log message because: ' . $g->getMessage());
            }

        } catch (Throwable $f) {
            // Okay WT actual F is going on here? We can't log to our own files, we can't log to system files. THIS
            // we won't stand for!
            throw LogException::new('Failed to write to ANY log (Failed to write to both local log files and system log files')
                              ->addData(['original exception' => $e]);
        }

        // We did NOT log
        return false;
    }


    /**
     * Write a notice message in the log file
     *
     * @param mixed       $messages
     * @param int         $threshold
     * @param bool        $clean
     * @param bool        $echo_newline
     * @param string|bool $echo_prefix
     * @param bool        $echo_screen
     *
     * @return bool
     */
    public static function notice(mixed $messages = null, int $threshold = 3, bool $clean = true, bool $echo_newline = true, string|bool $echo_prefix = true, bool $echo_screen = true): bool
    {
        return static::write($messages, 'notice', $threshold, $clean, $echo_newline, $echo_prefix, $echo_screen);
    }


    /**
     * Write a command line interface message in the log file and to the screen
     *
     * @param mixed       $messages
     * @param int         $threshold
     * @param bool        $clean
     * @param bool        $echo_newline
     * @param string|bool $echo_prefix
     *
     * @return bool
     */
    public static function cli(mixed $messages = null, int $threshold = 10, bool $clean = false, bool $echo_newline = true, bool $echo_prefix = false): bool
    {
        return static::write($messages, 'cli', $threshold, $clean, $echo_newline, $echo_prefix);
    }


    /**
     * Write a debug message in the log file
     *
     * @param mixed       $messages
     * @param int         $threshold
     * @param bool        $clean
     * @param bool        $echo_newline
     * @param string|bool $echo_prefix
     * @param bool        $echo_screen
     * @param bool        $echo_header
     *
     * @return bool
     */
    public static function debug(mixed $messages = null, int $threshold = 10, bool $clean = true, bool $echo_newline = true, string|bool $echo_prefix = true, bool $echo_screen = true, bool $echo_header = true): bool
    {
        $type = gettype($messages);

        switch ($type) {
            case 'array':
                $size = count($messages);
                break;

            case 'boolean':
                $size     = '-';
                $messages = strtoupper(Strings::fromBoolean($messages));
                break;

            case 'string':
                $size = strlen($messages);
                break;

            default:
                // For all other types size does not matter
                $size = '-';
        }

        if (!is_scalar($messages)) {
            if (is_object($messages) and $messages instanceof Throwable) {
                // Convert exception in readable message
                if ($messages instanceof Exception) {
                    $messages = [
                        'exception' => get_class($messages),
                        'code'      => $messages->getCode(),
                        'messages'  => $messages->getMessages(),
                        'data'      => $messages->getData(),
                    ];

                } else {
                    $messages = [
                        'exception' => get_class($messages),
                        'code'      => $messages->getCode(),
                        'message'   => $messages->getMessage(),
                    ];
                }
            }

        } else {
            // Build the message
            $messages = strtoupper($type) . ' [' . $size . '] ' . $messages;
        }

        if ($echo_header) {
            static::logDebugHeader('DEBUG', 1, $threshold, echo_screen: $echo_screen);
        }

        if (empty($messages)) {
            $messages = '-';
        }

        return static::write(Strings::log($messages, ensure_visible: true), 'debug', $threshold, $clean, $echo_newline, $echo_prefix, $echo_screen);
    }


    /**
     * Write a debug header message in the log file
     *
     * @param string      $keyword
     * @param int         $trace
     * @param int         $threshold
     * @param bool        $echo_screen
     * @param string|bool $echo_prefix
     *
     * @return bool
     */
    protected static function logDebugHeader(string $keyword, int $trace = 4, int $threshold = 10, bool $echo_screen = true, string|bool $echo_prefix = true): bool
    {
        // Get the class, method, file and line data.
        $class    = Debug::currentClass($trace);
        $function = Debug::currentFunction($trace);
        $file     = Strings::from(Debug::currentFile($trace), DIRECTORY_ROOT);
        $line     = Debug::currentLine($trace);

        if ($class) {
            // Add class - method separator
            $class .= '::';
        }

        return static::write(tr('Showing debug data with :keyword :class:function() in :file@:line', [
            ':keyword'  => $keyword,
            ':class'    => $class,
            ':function' => $function,
            ':file'     => $file,
            ':line'     => $line,
        ]), 'debug', $threshold, echo_prefix: $echo_prefix, echo_screen: $echo_screen);
    }


    /**
     * Write a "FUNCTION IS DEPRECATED" message in the log file
     *
     * @param int  $threshold
     * @param bool $echo_screen
     *
     * @return bool
     */
    public static function deprecated(int $threshold = 8, bool $echo_screen = true): bool
    {
        return static::logDebugHeader('DEPRECATED', 1, $threshold, echo_screen: $echo_screen);
    }


    /**
     * Write a hex encoded message in the log file. All hex codes will be grouped in groups of 2 characters for
     * readability
     *
     * @param mixed       $messages
     * @param int         $threshold
     * @param bool        $clean
     * @param bool        $echo_newline
     * @param string|bool $echo_prefix
     * @param bool        $echo_screen
     * @param bool        $echo_header
     *
     * @return bool
     */
    public static function hex(mixed $messages = null, int $threshold = 3, bool $clean = true, bool $echo_newline = true, string|bool $echo_prefix = true, bool $echo_screen = true, bool $echo_header = true): bool
    {
        if ($echo_header) {
            static::logDebugHeader('HEX', 1, $threshold, echo_screen: $echo_screen);
        }

        $messages = Strings::log($messages);

        return static::write(Strings::interleave(bin2hex(Strings::force($messages)), 10), 'debug', $threshold, $clean, $echo_newline, $echo_prefix, $echo_screen);
    }


    /**
     * Write a checkpoint message in the log file.
     *
     * A checkpoint log entry will show when the checkpoint was passed where (class::function in file@line)
     *
     * @param string|float|int|null $messages
     * @param int                   $threshold
     * @param string|bool           $echo_prefix
     * @param bool                  $echo_screen
     *
     * @return bool
     */
    public static function checkpoint(string|float|int|null $messages = null, int $threshold = 10, string|bool $echo_prefix = true, bool $echo_screen = true): bool
    {
        // Get the class, method, file and line data.
        $trace    = 0;
        $messages = Strings::log($messages);
        $file     = Strings::from(Debug::currentFile($trace), DIRECTORY_ROOT);
        $line     = Debug::currentLine($trace);

        return static::write(tr(':message in :file@:line', [
            ':message'  => trim('CHECKPOINT ' . $messages),
            ':file'     => $file,
            ':line'     => $line,
        ]), 'debug', $threshold, echo_prefix: $echo_prefix, echo_screen: $echo_screen);
    }


    /**
     * Write a debug message trying to format the data in a neat table.
     *
     * @param mixed       $key_value
     * @param int         $indent
     * @param int         $threshold
     * @param string|bool $echo_prefix
     * @param bool        $echo_screen
     *
     * @return bool
     */
    public static function table(array $key_value, int $indent = 4, int $threshold = 10, string|bool $echo_prefix = true, bool $echo_screen = true): bool
    {
        return static::write(Strings::getKeyValueTable($key_value, PHP_EOL, ': ', $indent), 'debug', $threshold, false, false, $echo_prefix, $echo_screen);
    }


    /**
     * Write a debug message using vardump() in the log file
     *
     * @param mixed $messages
     * @param int   $threshold
     * @param bool  $echo_screen
     * @param bool  $echo_header
     *
     * @return bool
     */
    public static function vardump(mixed $messages = null, int $threshold = 10, bool $echo_screen = true, bool $echo_header = true): bool
    {
        if ($echo_header) {
            static::logDebugHeader('VARDUMP', 1, $threshold, echo_screen: $echo_screen);
        }

        return static::write(var_export($messages, true), 'debug', $threshold, false, echo_screen: $echo_screen);
    }


    /**
     * Write a backtrace message in the log file
     *
     * @note This method has $echo_header default to FALSE as the header contains backtrace information which, well,
     *       this method already displays anyway
     *
     * @param ?int        $display
     * @param array|null  $backtrace
     * @param int         $threshold
     * @param bool        $echo_screen
     * @param bool        $echo_header
     * @param string|bool $echo_prefix
     *
     * @return void
     */
    public static function backtrace(?int $display = null, ?array $backtrace = null, int $threshold = 10, bool $echo_screen = true, bool $echo_header = false, string|bool $echo_prefix = false): void
    {
        if ($backtrace === null) {
            $backtrace = Debug::getBacktrace(1);
        }

        if ($echo_header) {
            static::logDebugHeader('BACKTRACE', 1, $threshold, echo_screen: $echo_screen, echo_prefix: $echo_prefix);
        }

        static::writeTrace($backtrace, $threshold, $display, echo_screen: $echo_screen, echo_prefix: $echo_prefix);
    }


    /**
     * Write a debug statistics dump message in the log file
     *
     * @param int $threshold
     *
     * @return bool
     */
    public static function statistics(int $threshold = 10): bool
    {
        // WTH IS THIS? LIBRARY::GETJSON() ???
        return Log::printr(Library::getJson(), $threshold);
    }


    /**
     * Write a debug message using print_r() in the log file
     *
     * @param mixed       $messages
     * @param int         $threshold
     * @param string|bool $echo_prefix
     * @param bool        $echo_screen
     * @param bool        $echo_header
     *
     * @return bool
     */
    public static function printr(mixed $messages = null, int $threshold = 10, string|bool $echo_prefix = true, bool $echo_screen = true, bool $echo_header = true): bool
    {
        if ($echo_header) {
            static::logDebugHeader('PRINTR', 1, $threshold, echo_screen: $echo_screen);
        }

        if (empty($messages)) {
            if ($messages !== 0) {
                $messages = '-';
            }
        }

        return static::write(print_r($messages, true), 'debug', $threshold, false, echo_prefix: $echo_prefix, echo_screen: $echo_screen);
    }


    /**
     * Write the specified SQL query as a message in the log file
     *
     * @param string|PDOStatement $query
     * @param ?array              $execute
     * @param int                 $threshold
     * @param bool                $clean
     * @param bool                $echo_newline
     * @param string|bool         $echo_prefix
     * @param bool                $echo_screen
     *
     * @return bool
     */
    public static function sql(string|PDOStatement $query, ?array $execute = null, int $threshold = 10, bool $clean = true, bool $echo_newline = true, string|bool $echo_prefix = true, bool $echo_screen = true): bool
    {
        $query = SqlQueries::renderQueryString($query, $execute);
        $query = Strings::ensureEndsWith($query, ';');

        return static::write('SQL QUERY: ' . $query, 'debug', $threshold, $clean, $echo_newline, $echo_prefix, $echo_screen);
    }


    /**
     * Show a dot on the console each $each call if $each is false, "DONE" will be printed, with next line. Internal
     * counter will reset if a different $each is received.
     *
     * @note While log_console() will log towards the DIRECTORY_ROOT/data/log/ log files, cli_dot() will only log one
     *       single dot even though on the command line multiple dots may be shown
     *
     * @param int|true $each
     * @param string   $color
     * @param string   $dot
     * @param boolean  $quiet
     *
     * @return boolean True if a dot was printed, false if not
     * @example
     * for($i=0; $i < 100; $i++) {
     *     Log::dot();
     * }
     * /code
     *
     * This will return something like
     *
     * ..........
     *
     * @see  Log::write()
     */
    public static function dot(int|true $each = 10, string $color = 'green', string $dot = '.', bool $quiet = false): bool
    {
        static $count = 0, $l_each = 0;

        if (!PLATFORM_CLI) {
            return false;
        }

        if ($quiet and QUIET) {
            // Don't show this in QUIET mode
            return false;
        }

        if (($each === 0) or ($each === true)) {
            if ($count) {
                // Only show "Done" if we have shown any dot at all
                Log::write(tr('Done'), $color, 10, false, true, false);
            }

            $l_each = 0;
            $count  = 0;

            return true;
        }

        $count++;

        if ($l_each != $each) {
            $l_each = $each;
            $count  = 0;
        }

        if ($count >= $l_each) {
            $count = 0;
            Log::write($dot, $color, 10, false, false, false);

            return true;
        }

        return false;
    }


    /**
     * Rotates the current log file
     *
     * @return FsFileInterface
     */
    public static function rotate(): FsFileInterface
    {
        $current = static::$file;
        $file    = FsFile::new(static::$file, FsRestrictions::newWritable(DIRECTORY_DATA . 'log/'));
        $target  = $file->getSource() . '~' . DateTime::new()->format('Ymd');
        $target  = FsFile::getAvailableVersion($target, '.gz');

        static::action(tr('Rotating to next syslog file'));

        $file = $file->rename($target)->gzip();

        static::setFile($current);
        Log::information(tr('Continuing syslog from file ":file"', [':file' => $file->getSource()]));

        return $file;
    }


    /**
     * Write an action message in the log file
     *
     * @param mixed       $messages
     * @param int         $threshold
     * @param bool        $clean
     * @param bool        $echo_newline
     * @param string|bool $echo_prefix
     * @param bool        $echo_screen
     *
     * @return bool
     */
    public static function action(mixed $messages = null, int $threshold = 5, bool $clean = true, bool $echo_newline = true, string|bool $echo_prefix = true, bool $echo_screen = true): bool
    {
        return static::write($messages, 'action', $threshold, $clean, $echo_newline, $echo_prefix, $echo_screen);
    }


    /**
     * Clean up old log files
     *
     * @param int|null $age_in_days
     *
     * @return void
     */
    public static function clean(?int $age_in_days): void
    {
        if (!$age_in_days) {
            $age_in_days = Config::getInteger('log.clean.age', 30);
        }

        Log::action(tr('Cleaning log files older than ":age" days', [
            ':age' => $age_in_days,
        ]));

        Find::new(new FsDirectory(DIRECTORY_DATA . 'log/', FsRestrictions::newWritable(DIRECTORY_DATA . 'log/')))
            ->setMtime('+' . ($age_in_days * 1440))
            ->setExec('rf {} -rf')
            ->executeNoReturn();
    }


    /**
     * Returns true if the syslog is open
     *
     * @return bool
     */
    public static function syslogIsOpen(): bool
    {
        return static::$syslog_open;
    }
}
