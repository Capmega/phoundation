<?php

namespace Phoundation\Core;

use Phoundation\Core\Exception\LogException;
use Throwable;

/**
 * Log class
 *
 * This class is the main event logger class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 <copyright@capmega.com>
 * @package Phoundation\Core
 */
Class Log {
    /**
     * Singleton variable
     *
     * @var Log|null $instance
     */
    protected static ?Log $instance = null;

    /**
     * Keeps track of what file we're logging to
     */
    protected static string $target = '';

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
    protected ?string $local_id = null;

    /**
     * A unique global code for this log entry that is the same code over multiple machines to be able to follow
     * multi-machine requests more easily
     *
     * @var string|null
     */
    protected ?string $global_id = null;

    /**
     * Log constructor
     */
    protected function __construct()
    {
        // Ensure that the log class hasn't been initialized yet
        if (self::$init) {
            return;
        }

        self::$init = true;

        // Set default configuration
        self::setLevel(Config::get('log.level', 10));
        self::setFile(Config::get('log.file', ROOT . 'data/log/syslog'));

    }



    /**
     * Singleton
     *
     * @param string|null $target
     * @return Log
     */
    public static function getInstance(string $target = null): Log
    {
        try{
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
     * Sets the log threshold level to the newly specified level and will return the previous level.
     *
     * @param int $threshold
     * @return int
     * @throws LogException if the specified threshold is invalid.
     */
    public static function setFile(string $file = null): string
    {

    }


    /**
     * Write a success message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public static function success(string $message, int $level = 5): bool
    {

    }



    /**
     * Write a warning message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public static function warning(string $message, int $level = 3): bool
    {

    }



    /**
     * Write an error message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public static function error(string $message, int $level = 1): bool
    {

    }



    /**
     * Write a notice message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public static function notice(string $message, int $level = 7): bool
    {

    }



    /**
     * Write a information message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public static function information(string $message, int $level = 3): bool
    {

    }



    /**
     * Write a debug message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public static function debug(string $message, int $level = 1): bool
    {

    }



    /**
     * Write a deprecated message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public static function deprecated(string $message, int $level = 3): bool
    {

    }



    /**
     * Write a hex encoded message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public static function hex(string $message, int $level = 3): bool
    {

    }



    /**
     * Write a checkpoint message to the log file
     *
     * @param string|null $message
     * @param int $level
     * @return bool
     */
    public static function checkpoint(?string $message, int $level = 1): bool
    {

    }



    /**
     * Write a debug message using print_r() to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public static function printr(string $message, int $level = 1): bool
    {

    }



    /**
     * Write a debug message using vardump() to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public static function vardump(string $message, int $level = 1): bool
    {

    }



    /**
     * Write a backtrace message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public static function backtrace(string $message, int $level = 1): bool
    {

    }



    /**
     * Write a debug statistics message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public static function statistics(string $message, int $level = 1): bool
    {

    }



    /**
     * Write the specified message to the log file for this instance
     *
     * @param string $class
     * @param string $message
     * @param int $level
     * @return bool
     */
    protected static function write(string $class, string $message, int $level): bool
    {

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
//                if ($messages instanceof BException) {
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
//                        foreach (array_force($data) as $line) {
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
//     * Messages can be specified as a string, array, or Error, Exception or BException objects
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
//     * Messages can be specified as a string, array, or Error, Exception or BException objects
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
//                        $message = str_log($message);
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
//            if (!is_writable(slash(ROOT . 'data/log') . $file)) {
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
//                 * We cannot write to the log file
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
