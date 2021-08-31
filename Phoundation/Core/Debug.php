<?php

use Phoundation\Core\Config;
use Phoundation\Core\CoreException;

/**
 * Class Debug
 *
 * This class contains the basic debug methods for use in Phoundation
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2021 <copyright@capmega.com>
 * @package Phoundation\Core
 */
class Debug {
    /**
     * Sets or returns if the system is running in debug mode or not
     *
     * @param bool|null $enabled
     * @return bool
     */
    public static function enabled(?bool $enabled = null): bool
    {
        if ($enabled === null) {
            // Return the setting
            return (bool) Config::get('debug.enabled', false);
        }

        // Make the setting
        Config::set('debug.enabled', $enabled);
        return $enabled;
    }



    /**
     * Sets or returns if the system is running in production mode or not
     *
     * @param bool|null $production
     * @return bool
     */
    public static function production(?bool $production = null): bool
    {
        if ($production === null) {
            // Return the setting
            return (bool) Config::get('debug.production', false);
        }

        // Make the setting
        Config::set('debug.production', $production);
        return $production;
    }



    /**
     * Returns a backtrace
     *
     * @param array|string[] $remove_sections
     * @param bool $skip_own
     * @return array
     */
    public static function backtrace(array $remove_sections = ['args'], bool $skip_own = true): array
    {
        $trace = array();

        foreach (debug_backtrace() as $key => $value) {
            if ($skip_own and ($key <= 1)) {
                continue;
            }

            foreach ($remove_sections as $section) {
                unset($value[$section]);
            }

            $trace[] = $value;
        }

        return $trace;
    }



    /**
     * Returns the class name from where this call was made
     *
     * @param int $trace
     * @return string
     */
    public static function currentClass(int $trace = 0): string
    {
        $backtrace = debug_backtrace();

        if (!isset($backtrace[$trace + 1])) {
            return -1;
        }

        return isset_get($backtrace[$trace + 1]['class'], '-');
    }



    /**
     * Returns the filename from where this call was made
     *
     * @param int $trace
     * @return string
     */
    public static function currentFile(int $trace = 0): string
    {
        $backtrace = debug_backtrace();

        if (!isset($backtrace[$trace + 1])) {
            return '-';
        }

        return isset_get($backtrace[$trace + 1]['file'], '-');
    }



    /**
     * Returns the function name from where this call was made
     *
     * @param int $trace
     * @return string
     */
    public static function currentFunction(int $trace = 0): string
    {
        $backtrace = debug_backtrace();

        if (!isset($backtrace[$trace + 1])) {
            return -1;
        }

        return isset_get($backtrace[$trace + 1]['function'], '-');
    }



    /**
     * Returns the line number from where this call was made
     *
     * @param int $trace
     * @return int
     */
    public static function currentLine(int $trace = 0): int
    {
        $backtrace = debug_backtrace();

        if (!isset($backtrace[$trace + 1])) {
            return -1;
        }

        return isset_get($backtrace[$trace + 1]['line'], -1);
    }


    /**
     * Show the given value on screen.
     *
     * In command line and API type modes, the value will be displayed using print_r(),
     * in web page mode, the value will be nicely displayed in a recursive table
     *
     * @param mixed $value
     * @param int|null $trace_offset
     * @param bool $quiet
     * @return mixed
     * @throws CoreException
     */
    public static function show(mixed $value, int $trace_offset = null, bool $quiet = false): mixed
    {
        global $_CONFIG, $core;

        if (self::enabled()) {
            try{
                if ($trace_offset === null) {
                    if (PLATFORM_HTTP) {
                        $trace_offset = 3;

                    }else{
                        $trace_offset = 2;
                    }

                }elseif (!is_numeric($trace_offset)) {
                    throw new CoreException(tr('debug_show(): Specified $trace_offset ":trace" is not numeric', array(':trace' => $trace_offset)), 'invalid');
                }

                if (!self::enabled()) {
                    return $value;
                }

                /*
                 * First cleanup data
                 */
                if (is_array($value)) {
                    $value = array_hide($value, 'GLOBALS,%pass,ssh_key');
                }

                $retval = '';

                if (PLATFORM_HTTP) {
                    Http::headers(null, 0);
                }

                if ($_CONFIG['production']) {
                    if (!debug()) {
                        return '';
                    }

// :TODO:SVEN:20130430: This should NEVER happen, send notification!
                }

                if (PLATFORM_HTTP) {
                    if (empty($core->register['debug_plain'])) {
                        switch ($core->callType()) {
                            case 'api':
                                // FALLTHROUGH
                            case 'ajax':
                                /*
                                 * If JSON, CORS requests require correct headers!
                                 * Also force plain text content type
                                 */
                            Http::headers(null, 0);

                                if (!headers_sent()) {
                                    header_remove('Content-Type');
                                    header('Content-Type: text/plain', true);
                                }

                                echo "\n".tr('DEBUG SHOW (:file@:line) ', array(':file' => self::currentFile($trace_offset - 1), ':line' => self::currentLine($trace_offset - 1)))."\n";
                                print_r($value)."\n";
                                break;

                            default:
                                /*
                                 * Force HTML content type, and show HTML data
                                 */
                                if (!headers_sent()) {
                                    header_remove('Content-Type');
                                    header('Content-Type: text/html', true);
                                }

                                echo debug_html($value, tr('Unknown'), $trace_offset);
                                ob_flush();
                                flush();
                        }

                    }else{
                        echo "\n".tr('DEBUG SHOW (:file@:line) ', array(':file' => self::currentFile($trace_offset), ':line' => self::currentLine($trace_offset)))."\n";
                        print_r($value)."\n";
                        ob_flush();
                        flush();
                    }

                    echo $retval;
                    ob_flush();
                    flush();

                }else{
                    if (is_scalar($value)) {
                        $retval .= ($quiet ? '' : tr('DEBUG SHOW (:file@:line) ', array(':file' => self::currentFile($trace_offset), ':line' => self::currentLine($trace_offset)))).$value."\n";

                    }else{
                        /*
                         * Sort if is array for easier reading
                         */
                        if (is_array($value)) {
                            ksort($value);
                        }

                        if (!$quiet) {
                            $retval .= tr('DEBUG SHOW (:file@:line) ', array(':file' => self::currentFile($trace_offset), ':line' => self::currentLine($trace_offset)))."\n";
                        }

                        $retval .= print_r(variable_zts_safe($value), true);
                        $retval .= "\n";
                    }

                    echo $retval;
                }

                return $value;

            }catch(Exception $e) {
                if (self::production() or self::enabled()) {
                    /*
                     * Show the error message with a conventional die() call
                     */
                    die(tr('Debug::show() command at ":file@:line" failed with ":e"', array(':file' => self::currentFile($trace_offset), ':line' => self::currentLine($trace_offset), ':e' => $e->getMessage())));
                }

                try{
                    notify($e);

                }catch(Exception $e) {
                    /*
                     * Sigh, if notify and error_log failed as well, then there is little to do but go on
                     */

                }

                return '';
            }
        }
    }



    /**
     * Show the given value on screen, then die.
     *
     * In command line and API type modes, the value will be displayed using print_r(),
     * in web page mode, the value will be nicely displayed in a recursive table
     *
     * @param mixed $value
     */
    public static function showDie(mixed $value): void
    {
        if (self::enabled()) {
            self::show($value);
            die();
        }
    }
}