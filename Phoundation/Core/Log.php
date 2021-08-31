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
    public function success(string $message, int $level): bool
    {

    }



    /**
     * Write a success warning to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function warning(string $message, int $level): bool
    {

    }



    /**
     * Write an error warning to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function error(string $message, int $level): bool
    {

    }



    /**
     * Write a success warning to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function warning(string $message, int $level): bool
    {

    }



    /**
     * Write a success warning to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function warning(string $message, int $level): bool
    {

    }



    /**
     * Write a success warning to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function warning(string $message, int $level): bool
    {

    }



    /**
     * Write a success warning to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function warning(string $message, int $level): bool
    {

    }



    /**
     * Write a success warning to the log file
     *
     * @param string $message
     * @param int $level
     * @return bool
     */
    public function warning(string $message, int $level): bool
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