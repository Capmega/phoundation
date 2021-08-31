<?php

namespace Phoundation\Core\Log;

use Throwable;

/**
 * Log class
 *
 * This class is the main event logger class
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2021 <copyright@capmega.com>
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
     * Keeps track of the LOG FAILURE statuc
     */
    protected static bool $fail = false;



    /**
     * Singleton
     *
     * @param string|null $target
     * @return Log|null
     */
    public static function getInstance(string $target = null)
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
     * Write a success message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function success(string $message, int $level = 5): bool
    {

    }



    /**
     * Write a warning message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function warning(string $message, int $level = 3): bool
    {

    }



    /**
     * Write an error message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function error(string $message, int $level = 1): bool
    {

    }



    /**
     * Write a notice message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function notice(string $message, int $level = 7): bool
    {

    }



    /**
     * Write a information message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function information(string $message, int $level = 3): bool
    {

    }



    /**
     * Write a debug message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function debug(string $message, int $level = 1): bool
    {

    }



    /**
     * Write a deprecated message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function deprecated(string $message, int $level = 3): bool
    {

    }



    /**
     * Write a hex encoded message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function hex(string $message, int $level = 3): bool
    {

    }



    /**
     * Write a checkpoint message to the log file
     *
     * @param string|null $message
     * @param int $level
     * @return bool
     */
    public function checkpoint(?string $message, int $level = 1): bool
    {

    }



    /**
     * Write a debug message using print_r() to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function printr(string $message, int $level = 1): bool
    {

    }



    /**
     * Write a debug message using vardump() to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function vardump(string $message, int $level = 1): bool
    {

    }



    /**
     * Write a backtrace message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function backtrace(string $message, int $level = 1): bool
    {

    }



    /**
     * Write a debug statistics message to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function statistics(string $message, int $level = 1): bool
    {

    }



    /**
     * Log constructor.
     *
     * @param string|null $target
     */
    protected function __construct(string $target = null)
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
}