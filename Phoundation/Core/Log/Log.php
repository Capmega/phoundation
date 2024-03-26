<?php

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
use Phoundation\Databases\Sql\SqlQueries;
use Phoundation\Date\DateTime;
use Phoundation\Developer\Debug;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Enums\EnumFileOpenMode;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Interfaces\FileInterface;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Stringable;
use Throwable;


/**
 * Log class
 *
 * This class is the main event logger class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
Class Log {
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
     * @var bool $use_prefix
     */
    protected static string|bool $use_prefix = true;

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
    protected static bool $lock = false;

    /**
     * If true, double log messages will be filtered out (not recommended, this might hide issues)
     *
     * @var bool $filter_double
     */
    protected static bool $filter_double = false;

    /**
     * File access restrictions
     *
     * @var Restrictions $restrictions
     */
    protected static Restrictions $restrictions;


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
                    // Be loud!
                    $threshold = 1;
                } else {
                    // Be... normal, I guess
                    if (Debug::getEnabled()) {
                        // Debug shows a bit more
                        $threshold = Config::getInteger('log.threshold', Core::errorState() ? 1 : 3);
                    } else {
                        $threshold = Config::getInteger('log.threshold', Core::errorState() ? 1 : 5);
                    }
                }

                static::setThreshold($threshold);
            }

            static::$restrictions = Restrictions::new(DIRECTORY_DATA . 'log/', true, 'Log');
            static::setFile(Config::get('log.file', DIRECTORY_ROOT . 'data/log/syslog'));
            static::setBacktraceDisplay(Config::get('log.backtrace-display', self::BACKTRACE_DISPLAY_BOTH));

        } catch (Throwable $e) {
            static::errorLog(tr('Configuration read failed with ":e"', [':e' => $e->getMessage()]));

            // Likely configuration read failed. Set defaults
            static::$restrictions = Restrictions::new(DIRECTORY_DATA . 'log/', true, 'Log');
            static::setThreshold(10);
            static::setFile(DIRECTORY_ROOT . 'data/log/syslog');
            static::setBacktraceDisplay(self::BACKTRACE_DISPLAY_BOTH);
        }

        static::$init = false;
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
                if (Debug::getEnabled()) {
                    static::information(tr('Logger started, threshold set to ":threshold"', [
                        ':threshold' => static::$threshold
                    ]),3);
                }
            }

        } catch (Throwable $e) {
            // Crap, we could not get a Log instance
            static::$failed = true;

            static::errorLog('Log constructor failed with the following message. Until the following issue has been resolved, all log entries will be written to the PHP system log only');
            static::errorLog($e->getMessage());
        }
    }


    /**
     * Returns true if the log is in failed mode and only logging to error_log()
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
     * Returns if log messages will have a prefix or not
     *
     * @return bool
     */
    public static function getUsePrefix(): bool
    {
        return static::$use_prefix;
    }


    /**
     * Sets if log messages will have a prefix or not
     *
     * @param string|bool $use_prefix
     * @return int
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public static function setUsePrefix(bool $use_prefix): void
    {
        static::$use_prefix = $use_prefix;
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
     * @return int
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public static function setThreshold(int $threshold): int
    {
        if (!is_numeric($threshold) or ($threshold < 1) or ($threshold > 10)) {
            throw OutOfBoundsException::new(tr('The specified log threshold level ":level" is invalid. Please ensure the level is between 1 and 10', [
                ':level' => $threshold
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
     * Returns if logging is enabled or not
     *
     * @return bool
     */
    public static function getEnabled(): bool
    {
        return static::$enabled;
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
        return static::$file_enabled;
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
     * Returns the file to which log messages will be written
     *
     * @return string
     */
    public static function getFile(): string
    {
        return static::$file;
    }


    /**
     * Sets the log threshold level to the newly specified level and will return the previous level. Once a log file has
     * been opened, it will remain open until closed with the Log::closeFile() method
     *
     * @param string|null $file
     * @return string|null
     * @throws LogException if the specified threshold is invalid.
     */
    public static function setFile(string $file = null): ?string
    {
        if (!static::$file_enabled) {
            // Logging to file is disabled, don't set a file
            return static::$file;
        }

        if (static::$failed) {
            // If the log is in failed mode, we cannot switch file
            error_log(tr('Not switching log file to ":file", log is running in failed mode', [
                ':file' => $file
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
            static::$streams[$file] = File::new($file, static::$restrictions)
                ->ensureWritable(0640)
                ->open(EnumFileOpenMode::writeOnlyAppend);

            // Set the class file to the specified file and return the old value and
            static::$file = $file;

        } catch (Throwable $e) {
            // Something went wrong trying to open the log file. Log the error but do continue
            static::$failed = true;
            static::error(tr('Failed to open log file ":file" because of exception ":e"', [
                ':file' => $file,
                ':e'    => $e->getMessage()
            ]));
        }

        return $return;
    }


    /**
     * Close the specified log file
     *
     * @param string|null $file
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
     * Set the local id parameter.
     *
     * 1 BACKTRACE_DISPLAY_FUNCTION
     * 2 BACKTRACE_DISPLAY_FILE
     * 3 BACKTRACE_DISPLAY_BOTH
     *
     * @note This method also allows $display defined as their string names (for easy configuration purposes)
     * @param string|int $display The new display configuration
     * @return int The previous value
     */
    public static function setBacktraceDisplay(#[ExpectedValues(values: ["BACKTRACE_DISPLAY_FUNCTION", "BACKTRACE_DISPLAY_FILE", "BACKTRACE_DISPLAY_BOTH", Log::BACKTRACE_DISPLAY_FUNCTION, Log::BACKTRACE_DISPLAY_FILE, Log::BACKTRACE_DISPLAY_BOTH])] string|int $display): int
    {
        switch ($display) {
            case 'BACKTRACE_DISPLAY_FUNCTION':
                // no-break
            case self::BACKTRACE_DISPLAY_FUNCTION:
                $display = self::BACKTRACE_DISPLAY_FUNCTION;
                break;

            case 'BACKTRACE_DISPLAY_FILE':
                // no-break
            case self::BACKTRACE_DISPLAY_FILE:
                $display = self::BACKTRACE_DISPLAY_FILE;
                break;

            case 'BACKTRACE_DISPLAY_BOTH':
                // no-break
            case self::BACKTRACE_DISPLAY_BOTH:
                $display = self::BACKTRACE_DISPLAY_BOTH;
                break;

            default:
                throw new OutOfBoundsException(tr('Invalid backtrace display value ":display" specified. Please ensure it is one of Log::BACKTRACE_DISPLAY_FUNCTION, Log::BACKTRACE_DISPLAY_FILE, or Log::BACKTRACE_DISPLAY_BOTH', [
                    ':display' => $display
                ]));
        }

        $return = static::$display;
        static::$display = $display;
        return $return;
    }


    /**
     * Write a success message in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @param bool $clean
     * @param bool $newline
     * @param string|bool $use_prefix
     * @param bool $echo_screen
     * @return bool
     */
    public static function success(mixed $messages = null, int $threshold = 5, bool $clean = true, bool $newline = true, string|bool $use_prefix = true, bool $echo_screen = true): bool
    {
        return static::write($messages, 'success', $threshold, $clean, $newline, $use_prefix, $echo_screen);
    }


    /**
     * Write an error message in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @param string|bool $use_prefix
     * @param bool $echo_screen
     * @return bool
     */
    public static function error(mixed $messages = null, int $threshold = 10, string|bool $use_prefix = true, bool $echo_screen = true): bool
    {
        return static::write($messages, 'error', $threshold, false, use_prefix: $use_prefix, echo_screen: $echo_screen);
    }


    /**
     * Logs an object in the log file
     *
     * @param object $object
     * @param string|null $class
     * @param int $threshold
     * @param bool $clean
     * @param bool $newline
     * @param string|bool $use_prefix
     * @param bool $echo_screen
     * @return bool
     */
    public static function object(object $object, ?string $class = null, int $threshold = 10, bool $clean = true, bool $newline = true, string|bool $use_prefix = true, bool $echo_screen = true): bool
    {
        if ($object instanceof Throwable) {
            // Log exception
            return static::exception($object, $threshold, $clean, $newline, $use_prefix, $echo_screen);
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

        return static::write($message, $class, $threshold, $clean, $newline, $use_prefix, $echo_screen);
    }


    /**
     * Logs an exception object in the log file
     *
     * @param Throwable $exception
     * @param int $threshold
     * @param bool $clean
     * @param bool $newline
     * @param string|bool $use_prefix
     * @param bool $echo_screen
     * @return bool
     */
    public static function exception(Throwable $exception, int $threshold = 10, bool $clean = true, bool $newline = true, string|bool $use_prefix = true, bool $echo_screen = true): bool
    {
        // This is an exception object, log the warning or error  message data. PHP exceptions have
        // $e->getMessage() and Phoundation exceptions can have multiple messages using $e->getMessages()

        // Redetermine the log class
        if ($exception instanceof Exception) {
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
        static::write(tr('Script: '), 'information', $threshold, true, false, echo_screen: $echo_screen);
        static::write(basename(isset_get($_SERVER['SCRIPT_FILENAME'])), $class, $threshold, true, true, false, $echo_screen);
        static::write(tr('Class: '), 'information', $threshold, true, false, echo_screen: $echo_screen);
        static::write(get_class($exception), $class, $threshold, true, true, false, $echo_screen);
        static::write(tr('Location: '), 'information', $threshold, true, false, echo_screen: $echo_screen);
        static::write($exception->getFile() . '@' . $exception->getLine(), $class, $threshold, true, true, false, $echo_screen);
        static::write(tr('Message: '), 'information', $threshold, true, false, echo_screen: $echo_screen);
        static::write('[' . ($exception->getCode() ?? 'N/A') . '] ' . $exception->getMessage(), $class, $threshold, false, true, false, $echo_screen);

        // Log the exception data and trace
        static::logExceptionData($exception, $threshold, $clean, $newline, $use_prefix, $echo_screen);
        static::logExceptionTrace($exception, $class, $threshold, $clean, $newline, $use_prefix, $echo_screen);

        return true;
    }


    /**
     * Logs the data section of an exception
     *
     * @param Throwable $exception
     * @param int $threshold
     * @param bool $clean
     * @param bool $newline
     * @param string|bool $use_prefix
     * @param bool $echo_screen
     * @return void
     */
    protected static function logExceptionData(Throwable $exception, int $threshold = 10, bool $clean = true, bool $newline = true, string|bool $use_prefix = true, bool $echo_screen = true): void
    {
        if ($exception instanceof Exception) {
            $data = $exception->getData();

            static::write(tr('Data: '), 'information', $threshold, echo_screen: $echo_screen);

            if ($data) {
                if ($exception->isWarning()) {
                    // Log warning data as individual lines for easier read
                    foreach (Arrays::force($data, null) as $line) {
                        static::write(print_r($line, true), 'warning', $threshold, false, $newline, $use_prefix, $echo_screen);
                    }

                } else {
                    static::write(print_r($data, true), 'debug', $threshold, false, $newline, $use_prefix, $echo_screen);
                }

            } else {
                static::write('-', 'debug', $threshold, false, $newline, $use_prefix, $echo_screen);
            }
        }
    }


    /**
     * Logs the trace section of an exception
     *
     * @param Throwable $exception
     * @param string|null $class
     * @param int $threshold
     * @param bool $clean
     * @param bool $newline
     * @param string|bool $use_prefix
     * @param bool $echo_screen
     * @return void
     */
    protected static function logExceptionTrace(Throwable $exception, ?string $class = null, int $threshold = 10, bool $clean = true, bool $newline = true, string|bool $use_prefix = true, bool $echo_screen = true): void
    {
        // Warning exceptions do not need to show the extra messages, trace, or data or previous exception
        if ($class == 'error') {
            // Log the backtrace
            static::write(tr('Backtrace:'), 'information', $threshold, $clean, $newline, $use_prefix, $echo_screen);

            if ($exception->getTrace()) {
                static::writeTrace($exception->getTrace(), $threshold, class: $class,  echo_screen: $echo_screen);

            } else {
                static::write('-', 'debug', $threshold, false, $newline, $use_prefix, $echo_screen);
            }

            // Log all previous exceptions as well
            $previous = $exception->getPrevious();

            while ($previous) {
                static::write('Previous exception: ', 'information', $threshold, $clean, $newline, $use_prefix, $echo_screen);
                static::exception($previous, $threshold, $clean, $newline, $use_prefix, $echo_screen);

                $previous = $previous->getPrevious();
            }
        }
    }


    /**
     * Write a warning message in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @param bool $clean
     * @param bool $newline
     * @param string|bool $use_prefix
     * @param bool $echo_screen
     * @return bool
     */
    public static function warning(mixed $messages = null, int $threshold = 9, bool $clean = true, bool $newline = true, string|bool $use_prefix = true, bool $echo_screen = true): bool
    {
        return static::write($messages, 'warning', $threshold, $clean, $newline, $use_prefix, $echo_screen);
    }


    /**
     * Write a notice message in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @param bool $clean
     * @param bool $newline
     * @param string|bool $use_prefix
     * @param bool $echo_screen
     * @return bool
     */
    public static function notice(mixed $messages = null, int $threshold = 3, bool $clean = true, bool $newline = true, string|bool $use_prefix = true, bool $echo_screen = true): bool
    {
        return static::write($messages, 'notice', $threshold, $clean, $newline, $use_prefix, $echo_screen);
    }


    /**
     * Write an action message in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @param bool $clean
     * @param bool $newline
     * @param string|bool $use_prefix
     * @param bool $echo_screen
     * @return bool
     */
    public static function action(mixed $messages = null, int $threshold = 5, bool $clean = true, bool $newline = true, string|bool $use_prefix = true, bool $echo_screen = true): bool
    {
        return static::write($messages, 'action', $threshold, $clean, $newline, $use_prefix, $echo_screen);
    }


    /**
     * Write a command line interface message in the log file and to the screen
     *
     * @param mixed $messages
     * @param int $threshold
     * @param bool $newline
     * @param string|bool $use_prefix
     * @return bool
     */
    public static function cli(mixed $messages = null, int $threshold = 10, bool $newline = true, bool $use_prefix = false): bool
    {
        return static::write($messages, 'cli', $threshold, false, $newline, $use_prefix);
    }


    /**
     * Write an information message in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @param bool $clean
     * @param bool $newline
     * @param string|bool $use_prefix
     * @param bool $echo_screen
     * @return bool
     */
    public static function information(mixed $messages = null, int $threshold = 7, bool $clean = true, bool $newline = true, string|bool $use_prefix = true, bool $echo_screen = true): bool
    {
        if (VERY_QUIET) {
            return false;
        }

        return static::write($messages, 'information', $threshold, $clean, $newline, $use_prefix, $echo_screen);
    }


    /**
     * Write a debug message in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @param bool $echo_screen
     * @param bool $echo_header
     * @return bool
     */
    public static function debug(mixed $messages = null, int $threshold = 10, bool $echo_screen = true, bool $echo_header = true): bool
    {
        $type = gettype($messages);

        switch ($type) {
            case 'array':
                $size = count($messages);
                break;

            case 'boolean':
                $size = '-';
                $message = strtoupper(Strings::fromBoolean($messages));
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
                        'data'      => $messages->getData()
                    ];
                } else{
                    $messages = [
                        'exception' => get_class($messages),
                        'code'      => $messages->getCode(),
                        'message'   => $messages->getMessage()
                    ];
                }
            }

            // We cannot display non-scalar data, encode it with JSON
            try {
                $messages = Json::encode($messages,JSON_INVALID_UTF8_IGNORE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
            } catch (JsonException $e) {
                // Message failed to be JSON encoded
                $messages = tr('JSON data could not be encoded for this log message');
            }
        }

        // Build the message
        $use_prefix = strtoupper($type) . ' [' . $size . '] ';
        $messages = $use_prefix . $messages;

        if ($echo_header) {
            static::logDebugHeader('PRINTR', 1, $threshold, echo_screen: $echo_screen);
        }

        return static::write($messages, 'debug', $threshold, false, echo_screen: $echo_screen);
    }


    /**
     * Write a "FUNCTION IS DEPRECATED" message in the log file
     *
     * @param int $threshold
     * @param bool $echo_screen
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
     * @param mixed $messages
     * @param int $threshold
     * @param bool $clean
     * @param bool $newline
     * @param string|bool $use_prefix
     * @param bool $echo_screen
     * @param bool $echo_header
     * @return bool
     */
    public static function hex(mixed $messages = null, int $threshold = 3, bool $clean = true, bool $newline = true, string|bool $use_prefix = true, bool $echo_screen = true, bool $echo_header = true): bool
    {
        if ($echo_header) {
            static::logDebugHeader('HEX', 1, $threshold, echo_screen: $echo_screen);
        }

        return static::write(Strings::interleave(bin2hex(Strings::force($messages)), 10), 'debug', $threshold, $clean, $newline, $use_prefix, $echo_screen);
    }


    /**
     * Write a checkpoint message in the log file.
     *
     * A checkpoint log entry will show when the checkpoint was passed where (class::function in file@line)
     *
     * @param string|null $message
     * @param int $threshold
     * @param bool $echo_screen
     * @return bool
     */
    public static function checkpoint(?string $message = null, int $threshold = 10, bool $echo_screen = true): bool
    {
        return static::logDebugHeader('CHECKPOINT ' . $message, 1, $threshold, echo_screen: $echo_screen);
    }


    /**
     * Write a debug message using print_r() in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @param bool $echo_screen
     * @param bool $echo_header
     * @return bool
     */
    public static function printr(mixed $messages = null, int $threshold = 10, string|bool $use_prefix = true, bool $echo_screen = true, bool $echo_header = true): bool
    {
        if ($echo_header) {
            static::logDebugHeader('PRINTR', 1, $threshold, echo_screen: $echo_screen);
        }

        return static::write(print_r($messages, true), 'debug', $threshold, false, use_prefix: $use_prefix, echo_screen: $echo_screen);
    }


    /**
     * Write a debug message trying to format the data in a neat table.
     *
     * @param mixed $key_value
     * @param int $indent
     * @param int $threshold
     * @param string|bool $use_prefix
     * @param bool $echo_screen
     * @return bool
     */
    public static function table(array $key_value, int $indent = 4, int $threshold = 10, string|bool $use_prefix = true, bool $echo_screen = true): bool
    {
        return static::write(Strings::getKeyValueTable($key_value, PHP_EOL, ': ', $indent), 'debug', $threshold, false, false, $use_prefix, $echo_screen);
    }


    /**
     * Write a debug message using vardump() in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @param bool $echo_screen
     * @param bool $echo_header
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
     * @param ?int $display
     * @param array|null $backtrace
     * @param int $threshold
     * @param bool $echo_screen
     * @param bool $echo_header
     * @param string|bool $use_prefix
     * @return void
     */
    public static function backtrace(?int $display = null, ?array $backtrace = null, int $threshold = 10, bool $echo_screen = true, bool $echo_header = false, string|bool $use_prefix = false): void
    {
        if ($backtrace === null) {
            $backtrace = Debug::backtrace(1);
        }

        if ($echo_header) {
            static::logDebugHeader('BACKTRACE', 1, $threshold, echo_screen: $echo_screen, use_prefix: $use_prefix);
        }

        static::writeTrace($backtrace, $threshold, $display, echo_screen: $echo_screen, use_prefix: $use_prefix);
    }


    /**
     * Write a debug statistics dump message in the log file
     *
     * @param int $threshold
     * @return bool
     */
    public static function statistics(int $threshold = 10): bool
    {
        // WTH IS THIS? LIBRARY::GETJSON() ???
        return Log::printr(Library::getJson(), $threshold);
    }


    /**
     * Write the specified SQL query as a message in the log file
     *
     * @param string|PDOStatement $query
     * @param ?array $execute
     * @param int $threshold
     * @param bool $clean
     * @param bool $newline
     * @param string|bool $use_prefix
     * @param bool $echo_screen
     * @return bool
     */
    public static function sql(string|PDOStatement $query, ?array $execute = null, int $threshold = 10, bool $clean = true, bool $newline = true, string|bool $use_prefix = true, bool $echo_screen = true): bool
    {
        $query = SqlQueries::renderQueryString($query, $execute);
        $query = Strings::endsWith($query, ';');

        return static::write('SQL QUERY: ' . $query, 'debug', $threshold, $clean, $newline, $use_prefix, $echo_screen);
    }


    /**
     * Log to PHP error console
     *
     * @param $message
     * @return void
     */
    protected static function errorLog($message): void
    {
        error_log($message);

        if (php_sapi_name() !== 'cli') {
            flush();
        }
    }


    /**
     * Write the specified log message to the current log file for this instance
     *
     * @todo Refactor this method, its become too cluttered over time
     * @param mixed $messages         The messages that are to be logged
     * @param string|null $class      The class of message that will be logged. Different classes will show in different
     *                                colors
     * @param int $threshold          The threshold level for this message. If the level is lower than the threshold,
     *                                the message will be dropped and not appear in the log files to avoid clutter
     * @param bool $clean             If true, the data will be cleaned before written to log. This will avoid (for
     *                                example) binary data from corrupting the log file
     * @param bool $newline           If true, a newline will be appended at the end of the log line
     * @param string|bool $use_prefix If true (default), all log lines will be prefixed with a string containing
     *                                date-time, local process id, and global process id
     * @param bool $echo_screen       If true (default), on CLI, the log line will be printed (without prefix) on the
     *                                command line as well
     * @return bool                   True if the line was written, false if it was dropped
     */
    public static function write(mixed $messages = null, ?string $class = null, int $threshold = 10, bool $clean = true, bool $newline = true, string|bool $use_prefix = true, bool $echo_screen = true): bool
    {
        if (!static::$enabled) {
            return false;
        }

        if (static::$init) {
            // Do not log anything while locked, initializing, or while dealing with a Log internal failure
            foreach (Arrays::force($messages, null) as $message) {
                static::errorLog('Phoundation: ' . Strings::force($message));
            }

            return false;
        }

        // TODO Delete static::$lock as it looks like its not needed anymore
        static::$lock = true;
        static::ensureInstance();

        try {
            // Do we have a log file setup?
            if (empty(static::$file) and !static::$failed) {
                throw new LogException(tr('Cannot log, no log file specified'));
            }

            // If we received an array, then log each line separately
            if (is_array($messages)) {
                $success = true;

                foreach ($messages as $message) {
                    $success = ($success and static::write($message, $class, $threshold, $clean, $newline, $use_prefix, $echo_screen));
                }

                static::$lock = false;
                return $success;
            }

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
                if (Debug::getEnabled()) {
                    if ($real_threshold > 10) {
                        // Yeah, this is not okay
                        static::warning(tr('Invalid log level ":level" specified for the following log message. This level should be set to 1-10', [
                            ':level' => $threshold
                        ]), 10);
                    }
                }
            }

            // If the message to be logged is an object, then extract the log information from there
            if (is_object($messages)) {
                return static::object($messages, $class, $threshold, $clean, $newline, $use_prefix, $echo_screen);
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

            // If we're initializing the log, then write to the system log
            if (static::$failed) {
                static::errorLog(Strings::force($messages));
                static::$lock = false;
                return true;
            }

            // Add coloring for easier reading
            $messages = CliColor::apply((string) $messages, $class);

            // Build the message to be logged, clean it and log
            // The log line format is DATE LEVEL PID GLOBALID/LOCALID MESSAGE EOL
            if ($clean) {
                $messages = Strings::cleanWhiteSpace($messages);
            }

            if (!$messages) {
                // Do not log empty messages
                return false;
            }

            // Build message string that should be written to log and (possibly) screen
            if (static::$use_prefix and $use_prefix) {
                if (is_bool($use_prefix)) {
                    // Display the default prefix
                    $messages = DateTime::new(null, 'server')->format('Y-m-d H:i:s.v') . ' ' . ($threshold === 10 ? 10 : ' ' . $threshold) . ' ' . getmypid() . ' ' . Core::getGlobalId() . ' / ' . Core::getLocalId() . ' ' . $messages . ($newline ? PHP_EOL : null);

                } else {
                    // Display the specified prefix instead of the default one
                    $messages = $use_prefix . $messages . ($newline ? PHP_EOL : null);
                }

            } else {
                // Display the log message without a prefix
                $messages = $messages . ($newline ? PHP_EOL : null);
            }

            // Write the message to the log file
            if (static::$file_enabled) {
                static::$streams[static::$file]->write($messages);
            }

            // In Command Line mode, if requested, always log to the screen too but not during PHPUnit test!
            if ($echo_screen and (PHP_SAPI === 'cli') and !Core::isPhpUnitTest() and !CliAutoComplete::isActive()) {
                echo $messages;
            }

            static::$lock = false;
            return true;

        } catch (Throwable $e) {
            return static::writeExceptionHandler($e, $messages, $threshold);
        }
    }


    /**
     * Handles log write exceptions
     *
     * @param Throwable $e
     * @param mixed|null $messages
     * @param int $threshold
     * @return bool
     */
    protected static function writeExceptionHandler(Throwable $e, mixed $messages = null, int $threshold = 10): bool
    {
        // Don't ever let the system crash because of a log issue, so we catch all possible exceptions
        static::$failed = true;

        try {
            $message = $threshold . ' ' . getmypid() . ' ' . Core::getGlobalId() . '/' . Core::getLocalId() . ' Failed to log message to internal log files because "' . $e->getMessage() . '"';
            static::errorLog($message);

            try {
                foreach (Arrays::force($messages, null) as $message) {
                    $message = CliColor::strip((string) $message);
                    $message = $threshold . ' ' . getmypid() . ' ' . Core::getGlobalId() . '/' . Core::getLocalId() . ' ' . $message;

                    static::errorLog($message);
                }

            } catch (Throwable $g) {
                // Okay this is messed up, we can't even log to system logs.
                static::errorLog('Failed to log message because: ' . $g->getMessage());
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
     * Write a debug header message in the log file
     *
     * @param string $keyword
     * @param int $trace
     * @param int $threshold
     * @param bool $echo_screen
     * @param string|bool $use_prefix
     * @return bool
     */
    protected static function logDebugHeader(string $keyword, int $trace = 4, int $threshold = 10, bool $echo_screen = true, string|bool $use_prefix = true): bool
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

        return static::write(tr('Showing debug data with :keyword :class:function() in :file@:line',
            [
                ':keyword'  => $keyword,
                ':class'    => $class,
                ':function' => $function,
                ':file'     => $file,
                ':line'     => $line
            ]), 'debug', $threshold, use_prefix: $use_prefix, echo_screen: $echo_screen);
    }


    /**
     * Dump the specified backtrace data
     *
     * @param array $backtrace The backtrace data
     * @param int $threshold The log level for this backtrace data
     * @param int|null $display How to display the backtrace. Must be one of Log::BACKTRACE_DISPLAY_FILE,
     *                          Log::BACKTRACE_DISPLAY_FUNCTION or Log::BACKTRACE_DISPLAY_BOTH.
     * @param string $class
     * @param bool $echo_screen
     * @param bool $from_script
     * @param string|bool $use_prefix
     * @return int The number of lines that were logged. -1 in case of an exception while trying to log the backtrace.
     */
    protected static function writeTrace(array $backtrace, int $threshold = 9, ?int $display = null, string $class = 'debug', bool $echo_screen = true, bool $from_script = true, string|bool $use_prefix = true): int
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
                static::write($line, $class, $threshold, false, use_prefix: $use_prefix, echo_screen: $echo_screen);
            }

            return count($lines);

        } catch (Throwable $e) {
            // Don't crash the process because of this, log it and return -1 to indicate an exception
            static::error(tr('Failed to write backtrace to log because of exception ":e"', [
                ':e' => $e->getMessage()
            ]));

            return -1;
        }
    }


    /**
     * Show a dot on the console each $each call if $each is false, "DONE" will be printed, with next line. Internal
     * counter will reset if a different $each is received.
     *
     * @note While log_console() will log towards the DIRECTORY_ROOT/data/log/ log files, cli_dot() will only log one
     *       single dot even though on the command line multiple dots may be shown
     *
     * @param int|true $each
     * @param string $color
     * @param string $dot
     * @param boolean $quiet
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
     * @see Log::write()
     */
    public static function dot(int|true $each = 10, string $color = 'green', string $dot = '.', bool $quiet = false): bool
    {
        static $count = 0,
        $l_each = 0;

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
            $count = 0;
            return true;
        }

        $count++;

        if ($l_each != $each) {
            $l_each = $each;
            $count = 0;
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
     * @return FileInterface
     */
    public static function rotate(): FileInterface
    {
        $current = static::$file;
        $file    = File::new(static::$file);
        $target  = $file->getPath() . '~' . DateTime::new()->format('Ymd');
        $target  = File::getAvailableVersion($target, '.gz');

        static::action(tr('Rotating to next syslog file'));

        $file = $file->rename($target)->gzip();
        static::setFile($current);

        Log::information(tr('Continuing syslog from file ":file"', [':file' => $file->getPath()]));
        return $file;
    }


    /**
     * Clean up old log files
     *
     * @param int|null $age_in_days
     * @return void
     */
    public static function clean(?int $age_in_days): void
    {
        if (!$age_in_days) {
            $age_in_days = Config::getInteger('log.clean.age', 30);
        }

        Log::action(tr('Cleaning log files older than ":age" days', [
            ':age' => $age_in_days
        ]));

        Find::new()
            ->setFindPath(DIRECTORY_DATA . 'log/')
            ->setMtime('+' . ($age_in_days * 1440))
            ->setExec('rf {} -rf')
            ->executeNoReturn();
    }
}
