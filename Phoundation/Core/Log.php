<?php

namespace Phoundation\Core;

use PDOStatement;
use Phoundation\Cli\Color;
use Phoundation\Core\Exception\LogException;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Developer\Debug;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\File;
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
    protected static int $threshold = 10;

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
     * Log constructor
     *
     * @param string $global_id
     */
    protected function __construct(string $global_id = '')
    {
        // Ensure that the log class hasn't been initialized yet
        if (self::$init) {
            return;
        }

        self::$init = true;

        // Apply configuration
        self::setThreshold(Config::get('log.threshold', Core::errorState() ? 1 : 3));
        self::setFile(Config::get('log.file', ROOT . 'data/log/syslog'));
        self::setBacktraceDisplay(Config::get('log.backtrace-display', self::BACKTRACE_DISPLAY_BOTH));
        self::setLocalId(substr(uniqid(true), -8, 8));
        self::setGlobalId($global_id);

        self::$init = false;

        // Log class startup message
        if (Debug::enabled()) {
            self::information(tr('Logger started with debug enabled, log threshold set to ":threshold"', [':threshold' => self::$threshold]));
        }
    }



    /**
     * Singleton, ensure to always return the same Log object.
     *
     * @param string $global_id
     * @return Log
     */
    public static function getInstance(string $global_id = ''): Log
    {
        try {
            if (!isset(self::$instance)) {
                self::$instance = new Log($global_id);
            }
        } catch (Throwable $e) {
            // Crap, we could not get a Log instance
            self::$fail = true;

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
        return self::$init;
    }



    /**
     * Returns the last message that was logged
     *
     * @return ?string
     */
    public static function getLastMessage(): ?string
    {
        return self::$last_message;
    }



    /**
     * Returns the log threshold on which log messages will pass to log files
     *
     * @return int
     */
    public static function getThreshold(): int
    {
        return self::$threshold;
    }



    /**
     * Sets the log threshold level to the newly specified level and will return the previous level.
     *
     * @param int $threshold
     * @return int
     * @throws LogException if the specified threshold is invalid.
     */
    public static function setThreshold(int $threshold): int
    {
        if ($threshold < 1 or $threshold > 10) {
            throw new LogException(tr('The specified log threshold level ":level" is invalid. Please ensure the level is between 0 and 10', [':level' => $threshold]));
        }

        $return = $threshold;
        self::$threshold = $threshold;
        return $return;
    }


    /**
     * Returns the file to which log messages will be written
     *
     * @return string
     */
    public static function getFile(): string
    {
        return self::$file;
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
            $return = self::$file;

            if ($file === null) {
                // Default log file is always the syslog
                $file = ROOT . 'data/log/syslog';
            }

            // Open the specified log file
            if (empty(self::$handles[$file])) {
                File::ensureWritable($file, 0640);
                self::$handles[$file] = fopen($file, 'a+');
            }

            // Set the class file to the specified file and return the old value and
            self::$file = $file;
            self::$fail = false;

        } catch (Throwable $e) {
            // Something went wrong trying to open the log file. Log the error but do continue
            self::$fail = true;
            self::error(tr('Failed to open log file ":file" because of exception ":e"', [':file' => $file, ':e' => $e->getMessage()]));
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
            $file = ROOT . 'data/log/syslog';
        }

        if (empty(self::$handles[$file])) {
            throw new FilesystemException(tr('Cannot close log file ":file", it was never opened', [':file' => $file]));
        }

        fclose(self::$handles[$file]);
    }



    /**
     * Returns the local log id value
     *
     * The local log id is a unique ID for this process only to identify log messages generated by THIS process in a log
     * file that contains log messages from multiple processes at the same time
     *
     * @return string
     */
    public function getLocalId(): string
    {
        return self::$local_id;
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
    public function setLocalId(string $local_id): void
    {
        if (self::$local_id !== '-') {
            throw new LogException('Cannot set the local log id, it has already been set');
        }

        self::$local_id = $local_id;
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
    public function getBacktraceDisplay(): int
    {
        return self::$display;
    }



    /**
     * Set the local id parameter.
     *
     * 1 BACKTRACE_DISPLAY_FUNCTION
     * 2 BACKTRACE_DISPLAY_FILE
     * 3 BACKTRACE_DISPLAY_BOTH
     *
     * @param int $display The new display configuration
     * @return int The previous value
     */
    public function setBacktraceDisplay(int $display): int
    {
        switch ($display) {
            case self::BACKTRACE_DISPLAY_FUNCTION:
                // no-break
            case self::BACKTRACE_DISPLAY_FILE:
            // no-break
            case self::BACKTRACE_DISPLAY_BOTH:
                // All valid
                break;

            default:
                throw new OutOfBoundsException(tr('Invalid backtrace display value ":display" specified. Please ensure it is one of Log::BACKTRACE_DISPLAY_FUNCTION, Log::BACKTRACE_DISPLAY_FILE, or Log::BACKTRACE_DISPLAY_BOTH', [':display' => $display]));
        }

        $return = self::$display;
        self::$display = $display;
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
    public function getGlobalId(): string
    {
        return self::$global_id;
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
    public function setGlobalId(string $global_id): void
    {
        if (!$global_id) {
            return;
        }

        if (self::$global_id !== '-') {
            throw new LogException('Cannot set the global log id, it has already been set');
        }

        self::$global_id = $global_id;
    }



    /**
     * Write a success message in the log file
     *
     * @param mixed $messages
     * @param int $level
     * @return bool
     */
    public static function success(mixed $messages, int $level = 5): bool
    {
        return self::write($messages, 'success', $level);
    }



    /**
     * Write an error message in the log file
     *
     * @param mixed $messages
     * @param int $level
     * @return bool
     */
    public static function error(mixed $messages, int $level = 10): bool
    {
        return self::write($messages, 'error', $level);
    }



    /**
     * Write a warning message in the log file
     *
     * @param mixed $messages
     * @param int $level
     * @return bool
     */
    public static function warning(mixed $messages, int $level = 7): bool
    {
        return self::write($messages, 'warning', $level);
    }



    /**
     * Write a notice message in the log file
     *
     * @param mixed $messages
     * @param int $level
     * @return bool
     */
    public static function notice(mixed $messages, int $level = 3): bool
    {
        return self::write($messages, 'notice', $level);
    }



    /**
     * Write a command line interface message in the log file and to the screen
     *
     * @param mixed $messages
     * @param int $level
     * @param bool $newline
     * @return bool
     */
    public static function cli(mixed $messages, int $level = 10, bool $newline = true): bool
    {
        return self::write($messages, 'cli', $level, false, $newline);
    }



    /**
     * Write a information message in the log file
     *
     * @param mixed $messages
     * @param int $level
     * @return bool
     */
    public static function information(mixed $messages, int $level = 7): bool
    {
        return self::write($messages, 'information', $level);
    }



    /**
     * Write a debug message in the log file
     *
     * @param mixed $messages
     * @param int $level
     * @return bool
     */
    public static function debug(mixed $messages, int $level = 10): bool
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

        self::logDebugHeader('PRINTR', $level);
        return self::write($messages, 'debug', $level);
    }



    /**
     * Write a "FUNCTION IS DEPRECATED" message in the log file
     *
     * @param int $level
     * @return bool
     */
    public static function deprecated(int $level = 8): bool
    {
        return self::logDebugHeader('DEPRECATED', $level);
    }



    /**
     * Write a hex encoded message in the log file. All hex codes will be grouped in groups of 2 characters for
     * readability
     *
     * @param mixed $messages
     * @param int $level
     * @return bool
     */
    public static function hex(mixed $messages, int $level = 3): bool
    {
        self::logDebugHeader('HEX', $level);
        return self::write(Strings::interleave(bin2hex(Strings::force($messages)), 10), 'debug', $level);
    }



    /**
     * Write a checkpoint message in the log file.
     *
     * A checkpoint log entry will show when the checkpoint was passed where (class::function in file@line)
     *
     * @param int $level
     * @return bool
     */
    public static function checkpoint(int $level = 10): bool
    {
        return self::logDebugHeader('CHECKPOINT', $level);
    }



    /**
     * Write a debug message using print_r() in the log file
     *
     * @param mixed $messages
     * @param int $level
     * @return bool
     */
    public static function printr(mixed $messages, int $level = 10): bool
    {
        self::logDebugHeader('PRINTR', $level);
        return self::write(print_r($messages, true), 'debug', $level, false);
    }



    /**
     * Write a debug message using vardump() in the log file
     *
     * @param mixed $messages
     * @param int $level
     * @return bool
     */
    public static function vardump(mixed $messages, int $level = 10): bool
    {
        self::logDebugHeader('VARDUMP', $level);
        return self::write(var_export($messages, true), 'debug', $level, false);
    }



    /**
     * Write a backtrace message in the log file
     *
     * @param ?int $display
     * @param int $level
     * @return bool
     */
    public static function backtrace(?int $display = null, int $level = 10): bool
    {
        $backtrace = Debug::backtrace();
        self::logDebugHeader('BACKTRACE', $level);
        self::dumpTrace($backtrace, $level, $display);
        return self::debug(basename($_SERVER['SCRIPT_FILENAME']), $level);
    }



    /**
     * Write a debug statistics dump message in the log file
     *
     * @param int $level
     * @return bool
     */
    public static function statistics(int $level = 10): bool
    {
        return Log::printr(Debug::getJson(), $level);
    }



    /**
     * Write the specified SQL query as a message in the log file
     *
     * @param string|PDOStatement $query
     * @param ?array $execute
     * @param int $level
     * @return bool
     */
    public static function sql(string|PDOStatement $query, ?array $execute = null, int $level = 3): bool
    {
        $query = Sql::db()->buildQueryString($query, $execute, false);
        $query = Strings::endsWith($query, ';');
        return Log::printr($query, $level);
    }



    /**
     * Write the specified log message to the current log file for this instance
     *
     * @param mixed $messages The messages that are to be logged
     * @param string|null $class The class of message that will be logged. Different classes will show in different
     *                           colors
     * @param int $level The threshold level for this message. If the level is lower than the threshold, the message
     *                   will be dropped and not appear in the log files to avoid clutter
     * @param bool $clean If true, the data will be cleaned before written to log. This will avoid (for example) binary
     *                    data from corrupting the log file
     * @param bool $newline If true, a newline will be appended at the end of the log line
     * @return bool True if the line was written, false if it was dropped
     */
    public static function write(mixed $messages, ?string $class = null, int $level = 10, bool $clean = true, bool $newline = true): bool
    {
// TODO Delete the following code block, looks like we won't need it anymore
//        if (self::$lock) {
//            // Do not log anything while locked, initialising, or while dealing with a Log internal failure
//            error_log($messages);
//            return false;
//        }

// TODO Delete self::$lock as it looks like its not needed anymore
        self::$lock = true;
        self::getInstance();

        try {
            // Do we have a log file setup?
            if (empty(self::$file)) {
                throw new LogException(tr('Cannot log, no log file specified'));
            }

            // If we received an array then log each line separately
            if (is_array($messages)) {
                $success = true;

                foreach ($messages as $message) {
                    $success = ($success and self::write($message, $class, $level, $clean));
                }

                self::$lock = false;
                return $success;
            }

            // Get the real level and check if we passed the threshold. If $level was negative, the same message may be
            // logged multiple times
            $real_level = abs($level);

            if ($real_level < self::$threshold) {
                // This log message level did not meet the threshold, discard it
                self::$lock = false;
                return false;
            }

            // Validate the specified log level
            if ($real_level > 9) {
                // This is an "always log!" message, which only are displayed if we're running in debug mode
                if (Debug::enabled()) {
                    if ($real_level > 10) {
                        // Yeah, this is not okay
                        self::warning(tr('Invalid log level ":level" specified for the following log message. This level should be set to 1-10', [':level' => $level]), 10);
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
                self::write('Encountered "' . get_class($messages) . '" class exception in "' . $messages->getFile() . '@' . $messages->getLine() . '" (Main script "' . basename(isset_get($_SERVER['SCRIPT_FILENAME'])) . '")', $class, $level);
                self::write('"' . get_class($messages) . '" Exception message: [' . ($messages->getCode() ?? 'N/A') . '] ' . $messages->getMessage(), $class, $level);

                // Warning exceptions do not need to show the extra messages, trace, or data or previous exception
                if ($class == 'error') {
                    // Log the backtrace data
                    self::dumpTrace($messages->getTrace());

                    // Log the exception data
                    if ($messages instanceof Exception) {
                        self::printr($messages->getData());
                    } else {
                        self::write('Exception contains no data', $class, $level);
                    }

                    // Log all previous exceptions as well
                    $previous = $messages->getPrevious();

                    while ($previous) {
                        self::write('Previous exception: ', $class, $level);
                        self::write($previous, $class, $level, $clean);

                        $previous = $previous->getPrevious();
                    }
                }

                self::$lock = false;
                return true;
            }

            // Make sure the log message is clean and readable. Don't truncate as we might have very large log mesages!
            // If no or an empty class was specified, we do not clean
            if ($class and $clean) {
                $messages = Strings::log($messages, 0);
            }

            // Don't log the same message twice in a row
            if (($level > 0) and (self::$last_message === $messages)) {
                self::$lock = false;
                return false;
            }

            self::$last_message = $messages;

            // If we're initializing the log then write to the system log
            if (self::$init or self::$fail) {
                error_log($messages);
                self::$lock = false;
                return true;
            }

            // Add coloring for easier reading
            $messages = Color::apply($messages, $class);

            // Build the message to be logged, clean it and log
            // The log line format is DATE LEVEL PID GLOBALID/LOCALID MESSAGE EOL
            if ($clean) {
                $messages = Strings::cleanWhiteSpace($messages);
            }

            $line = date('Y-m-d H:i:s') . ' ' . ($level === 10 ? 10 : ' ' . $level) . ' ' . getmypid() . ' ' . self::$global_id . ' / ' . self::$local_id . ' ' . $messages . ($newline ? PHP_EOL : null);
            fwrite(self::$handles[self::$file], $line);

            // In Command Line mode always log to the screen too
            if (PHP_SAPI === 'cli') {
                echo $messages . ($newline ? PHP_EOL : null);
            }

            self::$lock = false;
            return true;

        } catch (Throwable $e) {
            // Don't ever let the system crash because of a log issue so we catch all possible exceptions
            self::$fail = true;

            try {
                error_log(date('Y-m-d H:i:s') . ' ' . getmypid() . ' [' . self::$global_id . '/' . self::$local_id . '] Failed to log message to internal log files because "' . $e->getMessage() . '"');

                try {
                    error_log(date('Y-m-d H:i:s') . ' ' . $level . ' ' . getmypid() . ' ' . self::$global_id . '/' . self::$local_id . $messages);
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
     * @param int $level
     * @return bool
     */
    protected static function logDebugHeader(string $keyword, int $level = 10): bool
    {
        // Get the class, method, file and line data.
        $class = Debug::currentClass(0);
        $function = Debug::currentFunction(0);
        $file = Strings::from(Debug::currentFile(1), ROOT);
        $line = Debug::currentLine(0);

        if ($class) {
            // Add class - method separator
            $class .= '::';
        }

        return self::write(tr(':keyword :class:function() in :file@:line',
            [
                ':keyword'  => $keyword,
                ':class'    => $class,
                ':function' => $function,
                ':file'     => $file,
                ':line'     => $line
            ]), 'debug', $level);
    }



    /**
     * Dump the specified backtrace data
     *
     * @param array $backtrace The backtrace data
     * @param int $level The log level for this backtrace data
     * @param int|null $display How to display the backtrace. Must be one of Log::BACKTRACE_DISPLAY_FILE,
     *                          Log::BACKTRACE_DISPLAY_FUNCTION or Log::BACKTRACE_DISPLAY_BOTH.
     * @return int The amount of lines that were logged. -1 in case of an exception while trying to log the backtrace.
     */
    protected static function dumpTrace(array $backtrace, int $level = 9, ?int $display = null): int
    {
        try {
            $count = 0;
            $largest = 0;
            $lines = [];

            if ($display === null) {
                $display = self::$display;
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
                        self::warning(tr('Unknown $display ":display" specified. Please use one of Log::BACKTRACE_DISPLAY_FILE, Log::BACKTRACE_DISPLAY_FUNCTION, or BACKTRACE_DISPLAY_BOTH', [':display' => $display]));
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
                    // Remove ROOT from the filenames for clarity
                    $line['location'] = ' in ' . Strings::from($step['file'], ROOT) . '@' . $step['line'];
                }

                if (!$line) {
                    // Failed to build backtrace line
                    self::write(tr('Invalid backtrace data encountered, do not know how to process and display the following entry'), 'warning', $level);
                    self::printr($step, 10);
                    self::write(tr('Original backtrace data entry format below'), 'warning', $level);
                    self::printr($backtrace[$id], 10);
                }

                // Determine the largest call line
                $size = strlen(isset_get($line['call']));

                if ($size > $largest) {
                    $largest = $size;
                }

                $lines[] = $line;
            }

            // format and write the lines
            foreach ($lines as $line){
                $count++;

                if (isset($line['call'])) {
                    // Resize the call lines to all have the same size for easier log reading
                    $line['call'] = Strings::size($line['call'], $largest);
                }

                self::write(trim(($line['call'] ?? null) . ($line['location'] ?? null)), 'debug', $level, false);
            }

            return $count;
        } catch (Throwable $e) {
            // Don't crash the process because of this, log it and return -1 to indicate an exception
            self::error(tr('Failed to log backtrace because of exception ":e"', [':e' => $e->getMessage()]));
            return -1;
        }
    }
}