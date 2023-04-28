<?php

namespace Phoundation\Core\Log;

use JetBrains\PhpStorm\ExpectedValues;
use PDOStatement;
use Phoundation\Cli\Color;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Libraries\Library;
use Phoundation\Core\Log\Exception\LogException;
use Phoundation\Core\Strings;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Developer\Debug;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Servers\Server;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Utils\Json;
use Throwable;

/**
 * Log class
 *
 * This class is the main event logger class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * Keeps track of what log files we're logging to
     */
    protected static array $handles = [];

    /**
     * Keeps track of the LOG FAILURE status
     */
    protected static bool $fail = false;

    /**
     * The current threshold level of the log class. The higher this value, the less will be logged
     *
     * @var int $threshold
     */
    protected static int $threshold;

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
     * A unique local code for this log entry
     *
     * @var string
     */
    protected static string $local_id = '-';

    /**
     * A unique global code for this log entry that is the same code over multiple machines to be able to follow
     * multi-machine requests more easily
     *
     * @var string
     */
    protected static string $global_id = '-';

    /**
     * The last message that was logged.
     *
     * @var string|null
     */
    protected static ?string $last_message = null;

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
     *
     * @param string $global_id
     */
    protected function __construct(string $global_id = '')
    {
        // Ensure that the log class hasn't been initialized yet
        if (static::$init) {
            return;
        }

        static::$init = true;

        // Apply configuration
        try {
            // Determine log threshold
            if (!isset(self::$threshold)) {
                if (defined('QUIET') and QUIET) {
                    // Ssshhhhhhhh..
                    $threshold = 9;
                } elseif (defined('VERBOSE') and VERBOSE) {
                    // Be loud!
                    $threshold = 1;
                } else {
                    // Be... normal, I guess
                    if (Debug::enabled()) {
                        // Debug shows a bit more
                        $threshold = Config::get('log.threshold', Core::errorState() ? 10 : 5);
                    } else {
                        $threshold = Config::get('log.threshold', Core::errorState() ? 10 : 3);
                    }
                }

                static::setThreshold($threshold);
            }

            static::$restrictions = Restrictions::new(PATH_DATA . 'log/', true, 'Log');
            static::setFile(Config::get('log.file', PATH_ROOT . 'data/log/syslog'));
            static::setBacktraceDisplay(Config::get('log.backtrace-display', self::BACKTRACE_DISPLAY_BOTH));
            static::setLocalId(substr(uniqid(true), -8, 8));
            static::setGlobalId($global_id);
        } catch (\Throwable) {
            // Likely configuration read failed. Just set defaults
            static::$restrictions = Restrictions::new(PATH_DATA . 'log/', true, 'Log');
            static::setThreshold(10);
            static::setFile(PATH_ROOT . 'data/log/syslog');
            static::setBacktraceDisplay(self::BACKTRACE_DISPLAY_BOTH);
            static::setLocalId('-1');
            static::setGlobalId($global_id);
        }

        static::$init = false;
    }


    /**
     * Singleton, ensure to always return the same Log object.
     *
     * @param string $global_id
     * @return Log
     */
    public static function getInstance(string $global_id = ''): static
    {
        try {
            if (!isset(self::$instance)) {
                self::$instance = new static($global_id);

                // Log class startup message
                if (Debug::enabled()) {
                    static::information(tr('Logger started, threshold set to ":threshold"', [
                        ':threshold' => static::$threshold
                    ]));
                }
            }
        } catch (Throwable $e) {
            // Crap, we could not get a Log instance
            static::$fail = true;

            error_log('Log constructor failed with the following message. Until the following issue has been resolved, all log entries will be written to the PHP system log only');
            error_log($e);
        }

        return self::$instance;
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
        if (!is_natural($threshold, 1) or ($threshold > 10)) {
            throw OutOfBoundsException::new(tr('The specified log threshold level ":level" is invalid. Please ensure the level is between 0 and 10', [
                ':level' => $threshold
            ]))->makeWarning();
        }

        $return            = $threshold;
        static::$threshold = $threshold;

        return $return;
    }


    /**
     * Returns if double messages shoudl be filtered or not
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
     * been opened it will remain open until closed with the Log::closeFile() method
     *
     * @param string|null $file
     * @return string|null
     * @throws LogException if the specified threshold is invalid.
     */
    public static function setFile(string $file = null): ?string
    {
        try {
            $return = static::$file;

            if ($file === null) {
                // Default log file is always the syslog
                $file = PATH_ROOT . 'data/log/syslog';
            }

            // Open the specified log file
            if (empty(static::$handles[$file])) {
                File::new($file, static::$restrictions)->ensureWritable(0640);
                static::$handles[$file] = File::new($file, static::$restrictions)->open('a+');
            }

            // Set the class file to the specified file and return the old value and
            static::$file = $file;
            static::$fail = false;

        } catch (Throwable $e) {
            // Something went wrong trying to open the log file. Log the error but do continue
            static::$fail = true;
            static::error(tr('Failed to open log file ":file" because of exception ":e"', [':file' => $file, ':e' => $e->getMessage()]));
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
            $file = PATH_ROOT . 'data/log/syslog';
        }

        if (empty(static::$handles[$file])) {
            throw new FilesystemException(tr('Cannot close log file ":file", it was never opened', [':file' => $file]));
        }

        fclose(static::$handles[$file]);
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
     * @param string $local_id
     * @return void
     */
    public static function setLocalId(string $local_id): void
    {
        if (static::$local_id !== '-') {
            throw new LogException('Cannot set the local log id, it has already been set');
        }

        static::$local_id = $local_id;
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
     * @param string $global_id
     * @return void
     */
    public static function setGlobalId(string $global_id): void
    {
        if (!$global_id) {
            return;
        }

        if (static::$global_id !== '-') {
            throw new LogException('Cannot set the global log id, it has already been set');
        }

        static::$global_id = $global_id;
    }


    /**
     * Write a success message in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @return bool
     */
    public static function success(mixed $messages = null, int $threshold = 5, bool $clean = true, bool $newline = true): bool
    {
        return static::write($messages, 'success', $threshold, $clean, $newline);
    }


    /**
     * Write an error message in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @return bool
     */
    public static function error(mixed $messages = null, int $threshold = 10): bool
    {
        return static::write($messages, 'error', $threshold, false);
    }


    /**
     * Dump an exception object in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @return bool
     */
    public static function exception(Throwable $messages = null, int $threshold = 10): bool
    {
        return static::write($messages, 'error', $threshold, false);
    }


    /**
     * Write a warning message in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @return bool
     */
    public static function warning(mixed $messages = null, int $threshold = 9, bool $clean = true, bool $newline = true): bool
    {
        return static::write($messages, 'warning', $threshold, $clean, $newline);
    }


    /**
     * Write a notice message in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @return bool
     */
    public static function notice(mixed $messages = null, int $threshold = 3, bool $clean = true, bool $newline = true): bool
    {
        return static::write($messages, 'notice', $threshold, $clean, $newline);
    }


    /**
     * Write a action message in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @return bool
     */
    public static function action(mixed $messages = null, int $threshold = 5, bool $clean = true, bool $newline = true): bool
    {
        return static::write($messages, 'action', $threshold, $clean, $newline);
    }


    /**
     * Write a command line interface message in the log file and to the screen
     *
     * @param mixed $messages
     * @param int $threshold
     * @param bool $newline
     * @return bool
     */
    public static function cli(mixed $messages = null, int $threshold = 10, bool $newline = true): bool
    {
        return static::write($messages, 'cli', $threshold, false, $newline);
    }


    /**
     * Write a information message in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @return bool
     */
    public static function information(mixed $messages = null, int $threshold = 7, bool $clean = true, bool $newline = true): bool
    {
        return static::write($messages, 'information', $threshold, $clean, $newline);
    }


    /**
     * Write a debug message in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @return bool
     */
    public static function debug(mixed $messages = null, int $threshold = 10): bool
    {
        $type = gettype($messages);

        switch ($type) {
            case 'array':
                $size = count($messages);
                break;

            case 'boolean':
                $size = '-';
                $message = strtoupper(Strings::boolean($messages));
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
        $prefix = strtoupper($type) . ' [' . $size . '] ';
        $messages = $prefix . $messages;

        static::logDebugHeader('PRINTR', 1, $threshold);
        return static::write($messages, 'debug', $threshold);
    }


    /**
     * Write a "FUNCTION IS DEPRECATED" message in the log file
     *
     * @param int $threshold
     * @return bool
     */
    public static function deprecated(int $threshold = 8, bool $clean = true, bool $newline = true): bool
    {
        return static::logDebugHeader('DEPRECATED', 1, $threshold, $clean, $newline);
    }


    /**
     * Write a hex encoded message in the log file. All hex codes will be grouped in groups of 2 characters for
     * readability
     *
     * @param mixed $messages
     * @param int $threshold
     * @return bool
     */
    public static function hex(mixed $messages = null, int $threshold = 3, bool $clean = true, bool $newline = true): bool
    {
        static::logDebugHeader('HEX', 1, $threshold);
        return static::write(Strings::interleave(bin2hex(Strings::force($messages)), 10), 'debug', $threshold, $clean, $newline);
    }


    /**
     * Write a checkpoint message in the log file.
     *
     * A checkpoint log entry will show when the checkpoint was passed where (class::function in file@line)
     *
     * @param string|null $message
     * @param int $threshold
     * @return bool
     */
    public static function checkpoint(?string $message = null, int $threshold = 10, bool $clean = true, bool $newline = true): bool
    {
        return static::logDebugHeader('CHECKPOINT ' . $message, 1, $threshold, $clean, $newline);
    }


    /**
     * Write a debug message using print_r() in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @return bool
     */
    public static function printr(mixed $messages = null, int $threshold = 10): bool
    {
        static::logDebugHeader('PRINTR', 1, $threshold);
        return static::write(print_r($messages, true), 'debug', $threshold, false);
    }


    /**
     * Write a debug message using vardump() in the log file
     *
     * @param mixed $messages
     * @param int $threshold
     * @return bool
     */
    public static function vardump(mixed $messages = null, int $threshold = 10): bool
    {
        static::logDebugHeader('VARDUMP', 1, $threshold);
        return static::write(var_export($messages, true), 'debug', $threshold, false);
    }


    /**
     * Write a backtrace message in the log file
     *
     * @param ?int $display
     * @param int $threshold
     * @return bool
     */
    public static function backtrace(?int $display = null, int $threshold = 10, bool $clean = true, bool $newline = true): bool
    {
        $backtrace = Debug::backtrace(1);
        static::logDebugHeader('BACKTRACE', 1, $threshold);
        static::dumpTrace($backtrace, $threshold, $display);
        return static::debug(basename($_SERVER['SCRIPT_FILENAME']), $threshold);
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
     * @return bool
     */
    public static function sql(string|PDOStatement $query, ?array $execute = null, int $threshold = 10, bool $clean = true, bool $newline = true): bool
    {
        $query = Sql::buildQueryString($query, $execute, false);
        $query = Strings::endsWith($query, ';');
        return static::write('SQL QUERY: ' . $query, 'debug', $threshold, $clean, $newline);
    }


    /**
     * Write the specified log message to the current log file for this instance
     *
     * @param mixed $messages    The messages that are to be logged
     * @param string|null $class The class of message that will be logged. Different classes will show in different
     *                           colors
     * @param int $threshold     The threshold level for this message. If the level is lower than the threshold, the
     *                           message will be dropped and not appear in the log files to avoid clutter
     * @param bool $clean        If true, the data will be cleaned before written to log. This will avoid (for example)
     *                           binary data from corrupting the log file
     * @param bool $newline      If true, a newline will be appended at the end of the log line
     * @return bool              True if the line was written, false if it was dropped
     */
    public static function write(mixed $messages = null, ?string $class = null, int $threshold = 10, bool $clean = true, bool $newline = true): bool
    {
        if (static::$init) {
            // Do not log anything while locked, initialising, or while dealing with a Log internal failure
            error_log($messages);
            return false;
        }

// TODO Delete static::$lock as it looks like its not needed anymore
        static::$lock = true;
        static::getInstance();

        try {
            // Do we have a log file setup?
            if (empty(static::$file)) {
                throw new LogException(tr('Cannot log, no log file specified'));
            }

            // If we received an array then log each line separately
            if (is_array($messages)) {
                $success = true;

                foreach ($messages as $message) {
                    $success = ($success and static::write($message, $class, $threshold, $clean));
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
                if (Debug::enabled()) {
                    if ($real_threshold > 10) {
                        // Yeah, this is not okay
                        static::warning(tr('Invalid log level ":level" specified for the following log message. This level should be set to 1-10', [
                            ':level' => $threshold
                        ]), 10);
                    }
                }
            }

            // If the message to be logged is an exception then extract the log information from there
            if (is_object($messages) and $messages instanceof Throwable) {
                // This is an exception object, log the warning or error  message data. PHP exceptions have
                // $e->getMessage() and Phoundation exceptions can have multiple messages using $e->getMessages()

                // Redetermine the log class
                if ($messages instanceof Exception) {
                    if ($messages->isWarning()) {
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
                static::write('Encountered "' . get_class($messages) . '" class exception in "' . $messages->getFile() . '@' . $messages->getLine() . '" (Main script "' . basename(isset_get($_SERVER['SCRIPT_FILENAME'])) . '")', $class, $threshold);
                static::write('"' . get_class($messages) . '" Exception message: [' . ($messages->getCode() ?? 'N/A') . '] ' . $messages->getMessage(), $class, $threshold, false);

                // Log the exception data
                if ($messages instanceof Exception) {
                    if ($messages->isWarning()) {
                        // Log warning data as individual lines for easier read
                        $data = $messages->getData();

                        if ($data) {
                            foreach (Arrays::force($data, null) as $line) {
                                static::write(print_r($line, true), 'warning', $threshold, false);
                            }

                            return true;
                        }
                    } else {
                        // Dump the error data completely
                        Log::printr($messages->getData());
                    }
                }

                // Warning exceptions do not need to show the extra messages, trace, or data or previous exception
                if ($class == 'error') {
                    // Log the backtrace
                    $trace = $messages->getTrace();

                    if ($trace) {
                        static::write(tr('Backtrace:'), 'debug', $threshold);
                        static::dumpTrace($messages->getTrace());
                    }

                    // Log all previous exceptions as well
                    $previous = $messages->getPrevious();

                    while ($previous) {
                        static::write('Previous exception: ', $class, $threshold);
                        static::write($previous, $class, $threshold, $clean);

                        $previous = $previous->getPrevious();
                    }
                }

                static::$lock = false;
                return true;
            }

            // Make sure the log message is clean and readable. Don't truncate as we might have very large log mesages!
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

            // If we're initializing the log then write to the system log
            if (static::$fail) {
                error_log($messages);
                static::$lock = false;
                return true;
            }

            // Add coloring for easier reading
            $messages = Color::apply($messages, $class);

            // Build the message to be logged, clean it and log
            // The log line format is DATE LEVEL PID GLOBALID/LOCALID MESSAGE EOL
            if ($clean) {
                $messages = Strings::cleanWhiteSpace($messages);
            }

            $line = date('Y-m-d H:i:s.') . substr(microtime(FALSE), 2, 3) . ' ' . ($threshold === 10 ? 10 : ' ' . $threshold) . ' ' . getmypid() . ' ' . static::$global_id . ' / ' . static::$local_id . ' ' . $messages . ($newline ? PHP_EOL : null);
            fwrite(static::$handles[static::$file], $line);

            // In Command Line mode always log to the screen too, but not during PHPUnit test!
            if ((PHP_SAPI === 'cli')  and !Core::isPhpUnitTest()) {
                echo $messages . ($newline ? PHP_EOL : null);
            }

            static::$lock = false;
            return true;

        } catch (Throwable $e) {
            // Don't ever let the system crash because of a log issue so we catch all possible exceptions
            static::$fail = true;

            try {
                error_log(date('Y-m-d H:i:s') . ' ' . getmypid() . ' [' . static::$global_id . '/' . static::$local_id . '] Failed to log message to internal log files because "' . $e->getMessage() . '"');

                try {
                    error_log(date('Y-m-d H:i:s') . ' ' . $threshold . ' ' . getmypid() . ' ' . static::$global_id . '/' . static::$local_id . $messages);
                } catch (Throwable $g) {
                    // Okay this is messed up, we can't even log to system logs.
                    error_log('Failed to log message');
                }
            } catch (Throwable $f) {
                // Okay WT actual F is going on here? We can't log to our own files, we can't log to system files. THIS
                // we won't stand for!
                throw new LogException('Failed to write to ANY log (Failed to write to both local log files and system log files', data: ['original exception' => $e]);
            }

            // We did NOT log
            return false;
        }
    }


    /**
     * Write a debug header message in the log file
     *
     * @param string $keyword
     * @param int $trace
     * @param int $threshold
     * @return bool
     */
    protected static function logDebugHeader(string $keyword, int $trace = 4, int $threshold = 10): bool
    {
        // Get the class, method, file and line data.
        $class    = Debug::currentClass($trace);
        $function = Debug::currentFunction($trace);
        $file     = Strings::from(Debug::currentFile($trace), PATH_ROOT);
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
            ]), 'debug', $threshold);
    }


    /**
     * Dump the specified backtrace data
     *
     * @param array $backtrace The backtrace data
     * @param int $threshold The log level for this backtrace data
     * @param int|null $display How to display the backtrace. Must be one of Log::BACKTRACE_DISPLAY_FILE,
     *                          Log::BACKTRACE_DISPLAY_FUNCTION or Log::BACKTRACE_DISPLAY_BOTH.
     * @return int The amount of lines that were logged. -1 in case of an exception while trying to log the backtrace.
     */
    protected static function dumpTrace(array $backtrace, int $threshold = 9, ?int $display = null): int
    {
        try {
            $count = 0;
            $largest = 0;
            $lines = [];

            if ($display === null) {
                $display = static::$display;
            }

            // Parse backtrace data and build the log lines
            foreach ($backtrace as $id => $step) {
                // We usually don't want to see arguments as that clogs up BADLY
                unset($step['args']);

                // Remove unneeded information depending on the specified display
                switch ($display) {
                    case self::BACKTRACE_DISPLAY_FILE:
                        // Display only file@line information, but ONLY if file@line information is available
                        if (isset($step['file'])) {
                            unset($step['class']);
                            unset($step['function']);
                        }

                        break;

                    case self::BACKTRACE_DISPLAY_FUNCTION:
                        // Display only function / class information
                        unset($step['file']);
                        unset($step['line']);
                        break;

                    case self::BACKTRACE_DISPLAY_BOTH:
                        // Display both function / class and file@line information
                        break;

                    default:
                        // Wut? Just display both
                        static::warning(tr('Unknown $display ":display" specified. Please use one of Log::BACKTRACE_DISPLAY_FILE, Log::BACKTRACE_DISPLAY_FUNCTION, or BACKTRACE_DISPLAY_BOTH', [':display' => $display]));
                        $display = self::BACKTRACE_DISPLAY_BOTH;
                }

                // Build up log line from here. Start by getting the file information
                $line = [];

                if (isset($step['class'])) {
                    if (isset_get($step['class']) === 'Closure') {
                        // Log the closure call
                        $line['call'] = '{closure}';
                    } else {
                        // Log the class method call
                        $line['call'] = $step['class'] . $step['type'] . $step['function'] . '()';
                    }
                } elseif (isset($step['function'])) {
                    // Log the function call
                    $line['call'] = $step['function'] . '()';
                }

                // Log the file@line information
                if (isset($step['file'])) {
                    // Remove PATH_ROOT from the filenames for clarity
                    $line['location'] = Strings::from($step['file'], PATH_ROOT) . '@' . $step['line'];
                }

                if (!$line) {
                    // Failed to build backtrace line
                    static::write(tr('Invalid backtrace data encountered, do not know how to process and display the following entry'), 'warning', $threshold);
                    static::printr($step, 10);
                    static::write(tr('Original backtrace data entry format below'), 'warning', $threshold);
                    static::printr($step, 10);
                }

                // Determine the largest call line
                $size = strlen(isset_get($line['call'], ''));

                if ($size > $largest) {
                    $largest = $size;
                }

                $lines[] = $line;
            }

            // format and write the lines
            foreach ($lines as $line) {
                $count++;

                if (isset($line['call'])) {
                    // Resize the call lines to all have the same size for easier log reading
                    $line['call'] = Strings::size($line['call'], $largest);
                }

                static::write(trim(($line['call'] ?? null) . (isset($line['location']) ? (isset($line['call']) ? ' in ' : null) . $line['location'] : null)), 'debug', $threshold, false);
            }

            return $count;
        } catch (Throwable $e) {
            // Don't crash the process because of this, log it and return -1 to indicate an exception
            static::error(tr('Failed to log backtrace because of exception ":e"', [':e' => $e->getMessage()]));
            return -1;
        }
    }
}