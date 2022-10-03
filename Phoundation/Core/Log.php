<?php

namespace Phoundation\Core;

use JsonException;
use PDOStatement;
use Phoundation\Cli\Colors;
use Phoundation\Core\Exception\LogException;
use Phoundation\Databases\Sql;
use Phoundation\Developer\Debug;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\File;
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
     * @var int|null $display
     */
    protected static ?int $display = null;

    /**
     * Keeps track of if the static object has been initialized or not
     *
     * @var bool $init
     */
    protected static bool $init = false;

    /**
     * A unique local code for this log entry
     *
     * @var string|null
     */
    protected static ?string $local_id = null;

    /**
     * A unique global code for this log entry that is the same code over multiple machines to be able to follow
     * multi-machine requests more easily
     *
     * @var string|null
     */
    protected static ?string $global_id = null;

    /**
     * The last message that was logged.
     *
     * @var string|null
     */
    protected static ?string $last_message = null;



    /**
     * Log constructor
     */
    protected function __construct(?int $global_id = null)
    {
        // Ensure that the log class hasn't been initialized yet
        if (self::$init) {
            return;
        }

        self::$init = true;

        // Apply configuration
        self::setThreshold(Config::get('log.level', 7));
        self::setFile(Config::get('log.file', ROOT . 'data/log/syslog'));
        self::setBacktraceDisplay(Config::get('log.backtrace-display', self::BACKTRACE_DISPLAY_FILE));
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
     * @param string|null $target
     * @return Log
     */
    public static function getInstance(string $target = null): Log
    {
        try {
            if (!isset(self::$instance)) {
                self::$instance = new Log($target);
            }
        } catch (Throwable $e) {
            // Crap, we could not get a Log instance
            self::$fail = true;

            error_log('Log constructor failed with the following message. Until the following issue has been resolved, all log entries will be written to the PHP system log only');
            error_log($e->getMessage());
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
     * @param ?string $file
     * @return string
     * @throws LogException if the specified threshold is invalid.
     */
    public static function setFile(string $file = null): string
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
            self::error(tr('Failed to open log file ":file" because of exception ":e"', [':file' => $file, ':e' => $e]));
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
        if (self::$local_id) {
            throw new LogException('Cannot set the global log id, it has already been set');
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
        if (self::$global_id) {
            throw new LogException('Cannot set the global log id, it has already been set');
        }

        self::$global_id = $global_id;
    }



    /**
     * Write a success message in the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public static function success(string $message, int $level = 5): bool
    {
        return self::getInstance()->write('success', $message, $level);
    }



    /**
     * Write a warning message in the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public static function warning(string $message, int $level = 7): bool
    {
        return self::getInstance()->write('warning', $message, $level);
    }



    /**
     * Write an error message in the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public static function error(string $message, int $level = 10): bool
    {
        return self::getInstance()->write('error', $message, $level);
    }



    /**
     * Write a notice message in the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public static function notice(string $message, int $level = 3): bool
    {
        return self::getInstance()->write('notice', $message, $level);
    }



    /**
     * Write a information message in the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public static function information(string $message, int $level = 7): bool
    {
        return self::getInstance()->write('information', $message, $level);
    }



    /**
     * Write a debug message in the log file
     *
     * @param mixed $message
     * @param int $level
     * @return bool
     */
    public static function debug(mixed $message, int $level = 10): bool
    {
        $type = gettype($message);

        switch ($type) {
            case 'array':
                $size = count($message);
                break;

            case 'boolean':
                $size = '-';
                $message = strtoupper(Strings::boolean($message));
                break;

            case 'string':
                $size = strlen($message);
                break;

            default:
                // For all other types size does not matter
                $size = '-';
        }

        if (!is_scalar($message)) {
            // We cannot display non-scalar data, encode it with JSON
            try {
                $message = Json::encode($message,JSON_INVALID_UTF8_IGNORE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
            } catch (JsonException $e) {
                // Message failed to be JSON encoded
                $message = tr('JSON data could not be encoded for this log message');
            }
        }

        // Build the message
        $prefix = strtoupper($type) . ' [' . $size . '] ';
        $message = $prefix . $message;

        self::logDebugHeader('PRINTR', $level);
        return self::getInstance()->write('debug', $message, $level);
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
     * @param mixed $message
     * @param int $level
     * @return bool
     */
    public static function hex(mixed $message, int $level = 3): bool
    {
        self::logDebugHeader('HEX', $level);
        return self::write('hex', Strings::interleave(bin2hex(Strings::force($message)), 10), $level);
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
     * @param mixed $message
     * @param int $level
     * @return bool
     */
    public static function printr(mixed $message, int $level = 10): bool
    {
        self::logDebugHeader('PRINTR', $level);
        return self::write('debug', print_r($message, true), $level, false);
    }



    /**
     * Write a debug message using vardump() in the log file
     *
     * @param mixed $message
     * @param int $level
     * @return bool
     */
    public static function vardump(mixed $message, int $level = 10): bool
    {
        self::logDebugHeader('VARDUMP', $level);
        return self::write('debug', var_export($message, true), $level, false);
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
        $query = Sql::buildQueryString($query, $execute, false);
        $query = Strings::endsWith($query, ';');
        return Log::printr($query, $level);
    }



    /**
     * Write the specified log message to the current log file for this instance
     *
     * @param string $class
     * @param mixed $messages
     * @param int $level
     * @param bool $clean
     * @return bool
     */
    protected static function write(string $class, mixed $messages, int $level, bool $clean = true): bool
    {
        try {
            // Do we have a log file setup?
            if (empty(self::$file)) {
                throw new LogException(tr('Cannot log, no log file specified'));
            }

            // If we received an array then log each line separately
            if (is_array($messages)) {
                $success = true;

                foreach ($messages as $message) {
                    $success = ($success and self::write($class, $message, $level, $clean));
                }

                return $success;
            }

            // Get the real level and check if we passed the threshold. If $level was negative, the same message may be
            // logged multiple times
            $real_level = abs($level);

            if ($real_level < self::$threshold) {
                // This log message level did not meet the threshold, discard it
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
                    if ($messages->getWarning()){
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
                self::write($class, get_class($messages) . ' exception in "' . $messages->getFile() . '@' . $messages->getLine() . '" (Main script "' . basename(isset_get($_SERVER['SCRIPT_FILENAME'])) . '")', $level);
                self::write($class, 'Exception message : [' . ($messages->getCode() ?? 'N/A') . '] ' . $messages->getMessage(), $level);

                // Warning exceptions do not need to show the extra messages, trace, or data or previous exception
                if ($class == 'error') {
                    // Log the backtrace data
                    self::dumpTrace($messages->getTrace());

                    // Log the exception data
                    if ($messages instanceof Exception) {
                        self::printr($messages->getData());
                    } else {
                        self::write($class, 'Exception contains no data', $level);
                    }

                    // Log all previous exceptions as well
                    $previous = $messages->getPrevious();

                    while ($previous) {
                        self::write($class, 'Previous exception: ', $level);
                        self::write($class, $previous, $level, $clean);

                        $previous = $messages->getPrevious();
                    }
                }

                return true;
            }

            // Make sure the log message is clean and readable. Don't truncate as we might have very large log mesages!
            if ($clean) {
                $messages = Strings::log($messages, 0);
            }

            // Don't log the same message twice in a row
            if (($level > 0) and (self::$last_message === $messages)) {
                return false;
            }

            self::$last_message = $messages;

            // Add coloring for easier reading
            switch ($class) {
                case 'success':
                    // no-break
                case 'greeen':
                    $messages = Colors::apply($messages, 'green');
                    break;

                case 'red':
                    // no-break
                case 'error':
                    // no-break
                case 'exception':
                    $messages = Colors::apply($messages, 'red');
                    break;

                case 'yellow':
                    // no-break
                case 'warning':
                    $messages = Colors::apply($messages, 'yellow');
                    break;

                case 'notice':
                    // These messages don't get color
                    break;

                case 'information':
                    // no-break
                case 'white':
                    $messages = Colors::apply($messages, 'white');
                    break;

                case 'debug':
                    // no-break
                case 'blue':
                    $messages = Colors::apply($messages, 'light_blue');
                    break;

                default:
                    throw new LogException('Unknown log message class ":class" specified', [':class' => $class]);
            }

            // Build the message to be logged, clean it and log
            // The log line format is DATE LEVEL PID GLOBALID/LOCALID MESSAGE EOL
            if (Debug::cleanData()) {
                return Strings::cleanWhiteSpace($messages);
            }

            $messages = date('Y-m-d H:i:s') . ' ' . $level . ' ' . getmypid() . ' ' . self::$global_id . '/' . self::$local_id . $messages . PHP_EOL;
            fwrite(self::$handles[self::$file], $messages);

            // In Command Line mode always log to the screen too
            if (PHP_SAPI === 'cli') {
                echo $messages;
            }

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

        self::write('debug', tr(':keyword :class:function() in :file@:line',
            [
                ':keyword' => $keyword,
                ':class' => $class,
                ':function' => $function,
                ':file' => $file,
                ':line' => $line
            ]), $level);
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

            // Parse backtrace data and log the information
            foreach ($backtrace as $step) {
                // We usually don't want to see arguments as that clogs up BADLY
                unset($step['args']);

                // Remove unneeded information depending on the specified display
                switch ($display) {
                    case self::BACKTRACE_DISPLAY_FILE:
                        // Display only file@line information
                        break;

                    case self::BACKTRACE_DISPLAY_FUNCTION:
                        // Display only function / class information
                        unset($step['class']);
                        unset($step['function']);
                        break;

                    case self::BACKTRACE_DISPLAY_BOTH:
                        // Display both function / class and file@line information
                        unset($step['file']);
                        unset($step['line']);
                        break;

                    default:
                        // Wut? Just display both
                        self::warning(tr('Unknown $display ":display" specified. Please use one of Log::BACKTRACE_DISPLAY_FILE, Log::BACKTRACE_DISPLAY_FUNCTION, or BACKTRACE_DISPLAY_BOTH', [':display' => $display]));
                        $display = self::BACKTRACE_DISPLAY_BOTH;
                }

                // Build up log line from here. Start by getting the file information
                $file = '';

                if (isset($step['file'])) {
                    // Remove ROOT from the filenames for clarity
                    $file = ' in ' . Strings::from($step['file'], ROOT) . '@' . $step['line'];
                }

                $count++;

                if (isset($step['class'])) {
                    if (isset($step['function'])) {
                        self::write('debug', $step['function'] . '()' . $file, $level);
                    } else {
                        self::write('debug', $file, $level);
                    }
                } else {
                    if ($step['class'] === 'Closure') {
                        // Log the closure calls
                        self::write('debug', '{closure}' . $file, $level);
                    } else {
                        // Log the class method calls
                        self::write('debug', $step['class'] . '::' . $step['function'] . '()' . $file, $level);
                    }
                }
            }

            return $count;
        } catch (Throwable $e) {
            // Don't crash the process because of this, log it and return -1 to indicate an exception
            self::error(tr('Failed to log backtrace because of exception ":e"', [':e' => $e->getMessage()]));
            return -1;
        }
    }


























//
//
//
//    /*
//     * Parse flags from the specified log text color
//     */
//    function log_flags($color)
//    {
//        try {
//            switch (Strings::until($color, '/')) {
//                case 'VERBOSE':
//                    if (!VERBOSE) {
//                        /*
//                         * Only log this if we're in verbose mode
//                         */
//                        return false;
//                    }
//
//                    /*
//                     * Remove the VERBOSE
//                     */
//                    $color = Strings::from(Strings::from($color, 'VERBOSE', 0, true), '/');
//                    break;
//
//                case 'VERBOSEDOT':
//                    if (!VERBOSE) {
//                        /*
//                         * Only log this if we're in verbose mode
//                         */
//                        $color = Strings::from(Strings::from($color, 'VERBOSEDOT', 0, true), '/');
//                        cli_dot(10, $color);
//                        return false;
//                    }
//
//                    /*
//                     * Remove the VERBOSE
//                     */
//                    $color = Strings::from(Strings::from($color, 'VERBOSEDOT', 0, true), '/');
//                    break;
//
//                case 'VERYVERBOSE':
//                    if (!VERYVERBOSE) {
//                        /*
//                         * Only log this if we're in verbose mode
//                         */
//                        return false;
//                    }
//
//                    /*
//                     * Remove the VERYVERBOSE
//                     */
//                    $color = Strings::from(Strings::from($color, 'VERYVERBOSE', 0, true), '/');
//                    break;
//
//                case 'VERYVERBOSEDOT':
//                    if (!VERYVERBOSE) {
//                        /*
//                         * Only log this if we're in verbose mode
//                         */
//                        $color = Strings::from(Strings::from($color, 'VERYVERBOSEDOT', 0, true), '/');
//                        cli_dot(10, $color);
//                        return false;
//                    }
//
//                    /*
//                     * Remove the VERYVERBOSE
//                     */
//                    $color = Strings::from(Strings::from($color, 'VERYVERBOSEDOT', 0, true), '/');
//                    break;
//
//                case 'QUIET':
//                    if (QUIET) {
//                        /*
//                         * Only log this if we're in verbose mode
//                         */
//                        return false;
//                    }
//
//                    /*
//                     * Remove the QUIET
//                     */
//                    $color = Strings::from(Strings::from($color, 'QUIET', 0, true), '/');
//                    break;
//
//                case 'DEBUG':
//                    if (!debug()) {
//                        /*
//                         * Only log this if we're in debug mode
//                         */
//                        return false;
//                    }
//
//                    /*
//                     * Remove the QUIET
//                     */
//                    $color = Strings::from(Strings::from($color, 'DEBUG', 0, true), '/');
//            }
//
//            return $color;
//
//        } catch (Exception $e) {
//            throw new OutOfBoundsException(tr('log_flags(): Failed'), $e);
//        }
//    }
//
//
//    /*
//     * Sanitize the specified log message
//     *
//     * Also, if required, sets the log message color, filters double messages and can set the log_file() $class
//     *
//     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @package system
//     * @note: This function basically only needs to be executed by log_file() and log_console()
//     * @version 2.5.22: Added function and documentation
//     *
//     * @param mixed $messages
//     * @param string $color
//     * @param boolean $filter_double
//     * @return null string $class
//     */
//    function log_sanitize($messages, $color, $filter_double = true, &$class = null)
//    {
//        static $last;
//
//        try {
//            if ($filter_double and ($messages == $last)) {
//                /*
//                * We already displayed this message, skip!
//                */
//                return array();
//            }
//
//            if (is_scalar($messages)) {
//                $messages = array($messages);
//
//            } elseif (is_array($messages)) {
//                /*
//                 * Do nothing, we're good
//                 */
//
//            } elseif (is_object($messages)) {
//                if ($messages instanceof CoreException) {
//                    $data = $messages->getData();
//
//                    if ($messages->isWarning()) {
//                        $messages = array($messages->getMessage());
//                        $color = 'warning';
//
//                    } else {
//                        $messages = $messages->getMessages();
//                        $color = 'error';
//                    }
//
//                    if ($data) {
//                        /*
//                         * Add data to messages
//                         */
//                        $messages[] = cli_color('Exception data:', 'error', null, true);
//
//                        foreach (Arrays::force($data) as $line) {
//                            if ($line) {
//                                if (is_scalar($line)) {
//                                    $messages[] = cli_color($line, 'error', null, true);
//
//                                } elseif (is_array($line)) {
//                                    /*
//                                     * This is a multi dimensional array or object,
//                                     * we cannot cli_color() these, so just JSON it.
//                                     */
//                                    $messages[] = cli_color(json_encode_custom($line), 'error', null, true);
//                                }
//                            }
//                        }
//                    }
//
//                    if (!$class) {
//                        $class = 'exception';
//                    }
//
//                } elseif ($messages instanceof Exception) {
//                    $messages = array($messages->getMessage());
//
//                } elseif ($messages instanceof Error) {
//                    $messages = array($messages->getMessage());
//
//                } else {
//                    $messages = $messages->__toString();
//                }
//            }
//
//            $last = $messages;
//
//            return $messages;
//
//        } catch (Exception $e) {
//            throw new OutOfBoundsException('log_sanitize(): Failed', $e);
//        }
//    }
//
//
//    /*
//     * Log specified message to console, but only if we are in console mode!
//     *
//     * Messages can be specified as a string, array, or Error, Exception or CoreException objects
//     *
//     * The function will sanitize the log message using log_sanitize() before displaying it on the console, and by default also log to the system logs using log_file()
//     *
//     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @see log_sanitize()
//     * @see log_file()
//     * @package system
//     * @version 2.5.22: Added documentation, upgraded to use log_sanitize()
//     *
//     * @param mixed $messages
//     * @param string $color
//     * @param boolean $newline
//     * @param boolean $filter_double
//     * @param boolean $log_file
//     * @return array the sanitized log messages in array format
//     */
//    function log_console($messages = '', $color = null, $newline = true, $filter_double = false, $log_file = true)
//    {
//        global $core;
//        static $c;
//
//        try {
//            if ($color and !is_scalar($color)) {
//                log_console(tr('[ WARNING ] log_console(): Invalid color ":color" specified for the following message, color has been stripped', array(':color' => $color)), 'warning');
//                $color = null;
//            }
//
//            /*
//             * Process logging flags embedded in the log text color
//             */
//            $color = log_flags($color);
//
//            if ($color === false) {
//                /*
//                 * log_flags() returned false, do not log anything at all
//                 */
//                return false;
//            }
//
//            /*
//             * Always log to file log as well
//             */
//            if ($log_file) {
//                log_file($messages, $core->register['real_script'], $color);
//            }
//
//            if (!PLATFORM_CLI) {
//                /*
//                 * Only log to console on CLI platform
//                 */
//                return false;
//            }
//
//            $messages = log_sanitize($messages, $color, $filter_double);
//
//            if ($color) {
//                if (defined('NOCOLOR') and !NOCOLOR) {
//                    if (empty($c)) {
//                        if (!class_exists('Colors')) {
//                            /*
//                             * This log_console() was called before the "cli" library
//                             * was loaded. Show the line without color
//                             */
//                            $color = '';
//
//                        } else {
//                            $c = new Colors();
//                        }
//                    }
//                }
//
//                switch ($color) {
//                    case 'yellow':
//                        // FALLTHROUGH
//                    case 'warning':
//                        // FALLTHROUGH
//                    case 'red':
//                        // FALLTHROUGH
//                    case 'error':
//                        $error = true;
//                }
//            }
//
//            foreach ($messages as $message) {
//                if ($color and defined('NOCOLOR') and !NOCOLOR) {
//                    $message = $c->getColoredString($message, $color);
//                }
//
//                if (QUIET) {
//                    $message = trim($message);
//                }
//
//                $message = stripslashes(br2nl($message)) . ($newline ? "\n" : '');
//
//                if (empty($error)) {
//                    echo $message;
//
//                } else {
//                    /*
//                     * Log to STDERR instead of STDOUT
//                     */
//                    fwrite(STDERR, $message);
//                }
//            }
//
//            return $messages;
//
//        } catch (Exception $e) {
//            throw new OutOfBoundsException('log_console(): Failed', $e, array('message' => $messages));
//        }
//    }
//
//
//    /*
//     * Log specified message(s) to file.
//     *
//     * Messages can be specified as a string, array, or Error, Exception or CoreException objects
//     *
//     * The function will sanitize the log message using log_sanitize() before displaying it on the console, and by default also log to the system logs using log_file()
//
//     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @see log_sanitize()
//     * @see log_console()
//     * @package system
//     * @version 2.5.22: Added documentation, upgraded to use log_sanitize()
//     *
//     * @param mixed $messages
//     * @param string $class
//     * @param string $color
//     * @param string $color
//     * @return array the sanitized log messages in array format
//     */
//    function log_file($messages, $class = 'syslog', $color = null, $filter_double = true)
//    {
//        global $_CONFIG, $core;
//        static $h = array(),
//        $log = true;
//
//        try {
//            if (!$log) {
//                /*
//                 * Do not log!
//                 */
//                return false;
//            }
//
//            /*
//             * Process logging flags embedded in the log text color
//             */
//            $color = log_flags($color);
//
//            if ($color === false) {
//                /*
//                 * log_flags() returned false, do not log anything at all
//                 */
//                return false;
//            }
//
//            $messages = log_sanitize($messages, $color, $filter_double, $class);
//
//            if (!is_scalar($class)) {
//                if ($class) {
//                    throw new OutOfBoundsException(tr('log_file(): Specified class ":class" is not scalar', array(':class' => str_truncate(json_encode_custom($class), 20))), 'invalid');
//                }
//
//                $class = $core->register['script'];
//            }
//
//            /*
//             * Add session data
//             */
//            if (PLATFORM_HTTP) {
//                $session = '(' . substr(session_id(), -8, 8) . ' / ' . REQUEST . ') ';
//
//            } else {
//                $session = '(CLI-' . getmypid() . ' / ' . REQUEST . ') ';
//            }
//
//            /*
//             * Single log or multi log?
//             */
//            if (!$core or !$core->register('ready')) {
//                $file = 'syslog';
//                $class = $session . cli_color('[ ' . $class . ' ] ', 'white', null, true);
//
//            } elseif ($_CONFIG['log']['single']) {
//                $file = 'syslog';
//                $class = $session . cli_color('[ ' . $class . ' ] ', 'white', null, true);
//
//            } else {
//                $file = $class;
//                $class = $session;
//            }
//
//            /*
//             * Write log entries
//             */
//            if (empty($h[$file])) {
//                file_ensure_path(ROOT . 'data/log');
//
//                try {
//                    $h[$file] = @fopen(ROOT . 'data/log/' . $file, 'a+');
//
//                } catch (Exception $e) {
//                    throw new OutOfBoundsException(tr('log_file(): Failed to open logfile ":file" to store messages ":messages"', array(':file' => $file, ':messages' => $messages)), $e);
//                }
//
//                if (!$h[$file]) {
//                    throw new OutOfBoundsException(tr('log_file(): Failed to open logfile ":file" to store messages ":messages"', array(':file' => $file, ':messages' => $messages)), 'failed');
//                }
//            }
//
//            $date = new DateTime();
//            $date = $date->format('Y/m/d H:i:s');
//
//            foreach ($messages as $key => $message) {
//                if (!is_scalar($message)) {
//                    if (is_array($message) or is_object($message)) {
//                        $message = json_encode_custom($message);
//
//                    } else {
//                        $message = '* ' . gettype($message) . ' *';
//                    }
//                }
//
//                if (count($messages) > 1) {
//                    /*
//                     * There are multiple messages in this log_file() call. Display
//                     * them all using their keys
//                     */
//                    if (!is_scalar($message)) {
//                        $message = Strings::Log($message);
//                    }
//
//                    if (!empty($color)) {
//                        $message = cli_color($message, $color, null, true);
//                    }
//
//                    fwrite($h[$file], cli_color($date, 'cyan', null, true) . ' ' . $core->callType() . '/' . $core->register['real_script'] . ' ' . $class . $key . ' => ' . $message . "\n");
//
//                } else {
//                    /*
//                     * There is only one message in this log_file() call, even when
//                     * the log_file() was called with an array, it only contained
//                     * one entry
//                     */
//                    if (!empty($color)) {
//                        $message = cli_color($message, $color, null, true);
//                    }
//
//                    fwrite($h[$file], cli_color($date, 'cyan', null, true) . ' ' . $core->callType() . '/' . $core->register['real_script'] . ' ' . $class . $message . "\n");
//                }
//            }
//
//            return $messages;
//
//        } catch (Exception $e) {
//            /*
//             * We encountered an exception trying to log, don't log ever again
//             */
//            $log = false;
//
//            if (empty($file)) {
//                throw new OutOfBoundsException('log_file(): Failed before $file was determined', $e, array('message' => $messages));
//            }
//
//            if (!is_writable(Strings::slash(ROOT . 'data/log') . $file)) {
//                if (PLATFORM_HTTP) {
//                    error_log(tr('log_file() failed because log file ":file" is not writable', array(':file' => $file)));
//                }
//
//                throw new OutOfBoundsException(tr('log_file(): Failed because log file ":file" is not writable', array(':file' => $file)), $e);
//            }
//
//            /*
//             * If log_file() fails, assume we cannot log to data/log/, log to PHP error instead
//             */
//            error_log(tr('log_file() failed to log the following exception:'));
//
//            foreach ($e->getMessages() as $message) {
//                error_log($message);
//            }
//
//            $message = $e->getMessage();
//
//            if (strstr($message, 'data/log') and strstr($message, 'failed to open stream: Permission denied')) {
//                /*
//                 * We cannot write in the log file
//                 */
//                throw new OutOfBoundsException(tr('log_file(): Failed to write to log, permission denied to write to log file ":file". Please ensure the correct write permissions for this file and the ROOT/data/log directory in general', array(':file' => Strings::cut(($message, 'fopen(', ')'))), 'warning');
//            }
//
//            throw new OutOfBoundsException('log_file(): Failed', $e, array('message' => $messages));
//        }
//    }
//
//
}
