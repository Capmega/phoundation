<?php

namespace Phoundation\Core;

use Phoundation\Core\Config;
use Phoundation\Core\CoreException;

/**
 * Class Debug
 *
 * This class contains the basic debug methods for use in Phoundation
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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

        if (!self::enabled()) {
            return null;
        }

        try{
            if ($trace_offset === null) {
                if (PLATFORM_HTTP) {
                    $trace_offset = 3;

                } else {
                    $trace_offset = 2;
                }

            } elseif (!is_numeric($trace_offset)) {
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

                } else {
                    echo "\n".tr('DEBUG SHOW (:file@:line) ', array(':file' => self::currentFile($trace_offset), ':line' => self::currentLine($trace_offset)))."\n";
                    print_r($value)."\n";
                    ob_flush();
                    flush();
                }

                echo $retval;
                ob_flush();
                flush();

            } else {
                if (is_scalar($value)) {
                    $retval .= ($quiet ? '' : tr('DEBUG SHOW (:file@:line) ', array(':file' => self::currentFile($trace_offset), ':line' => self::currentLine($trace_offset)))) . $value."\n";

                } else {
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

        } catch (Exception $e) {
            if (self::production() or self::enabled()) {
                /*
                 * Show the error message with a conventional die() call
                 */
                die(tr('Debug::show() command at ":file@:line" failed with ":e"', array(':file' => self::currentFile($trace_offset), ':line' => self::currentLine($trace_offset), ':e' => $e->getMessage())));
            }

            try{
                notify($e);

            } catch (Exception $e) {
                /*
                 * Sigh, if notify and error_log failed as well, then there is little to do but go on
                 */

            }

            return null;
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



    /**
     *
     */
    protected static function showHtml(mixed $value, int|float|bool|string|null $key = null, int $trace_offset = 0): string
    {
        static $style;

        if ($key === null) {
            $key = tr('Unknown');
        }

        if (empty($style)) {
            $style  = true;

            $retval = '<style type="text/css">
                table.debug{
                    font-family: sans-serif;
                    width:99%;
                    background:#AAAAAA;
                    border-collapse:collapse;
                    border-spacing:2px;
                    margin: 5px auto 5px auto;
                }

                table.debug thead{
                    background: #00A0CF;
                }

                table.debug td{
                    border: 1px solid black;
                    padding: 10px;
                }
                table.debug td.value{
                    word-break: break-all;
                }
               </style>';
        } else {
            $retval = '';
        }

        return $retval . '<table class="debug">
                    <thead class="debug-header"><td colspan="4">'.self::currentFile(1 + $trace_offset) . '@'.self::currentLine(1 + $trace_offset) . '</td></thead>
                    <thead class="debug-columns"><td>'.tr('Key') . '</td><td>'.tr('Type') . '</td><td>'.tr('Size') . '</td><td>'.tr('Value') . '</td></thead>'.self::showHtmlRow($value, $key) . '
                </table>';
    }



    /**
     * Generates and returns a single HTML line with debug information for the specified value
     *
     * @return string
     */
    protected static function showHtmlRow(mixed $value, int|float|bool|string|null $key = null): string
    {
        if ($key === null) {
            $key = tr('Unknown');
        }

        $type = gettype($value);

        switch ($type) {
            case 'string':
                if (is_numeric($value)) {
                    $type = tr('numeric');

                    if (is_integer($value)) {
                        $type .= tr(' (integer)');

                    } elseif (is_float($value)) {
                        $type .= tr(' (float)');

                    } elseif (is_string($value)) {
                        $type .= tr(' (string)');

                    } else {
                        $type .= tr(' (unknown)');
                    }

                } else {
                    $type = tr('string');
                }

                //FALLTHROUGH

            case 'integer':
                //FALLTHROUGH

            case 'double':
                return '<tr>
                    <td>'.htmlentities($key) . '</td>
                    <td>' . $type.'</td>
                    <td>'.strlen((string) $value) . '</td>
                    <td class="value">'.htmlentities($value) . '</td>
                </tr>';

            case 'boolean':
                return '<tr>
                    <td>'.htmlentities($key) . '</td>
                    <td>' . $type.'</td>
                    <td>1</td>
                    <td class="value">'.($value ? tr('true') : tr('false')) . '</td>
                </tr>';

            case 'NULL':
                return '<tr>
                    <td>'.htmlentities($key) . '</td>
                    <td>' . $type.'</td>
                    <td>0</td>
                    <td class="value">'.htmlentities($value) . '</td>
                </tr>';

            case 'resource':
                return '<tr><td>'.htmlentities($key) . '</td>
                    <td>' . $type.'</td>
                    <td>?</td>
                    <td class="value">' . $value.'</td>
                </tr>';

            case 'method':
                // FALLTHROUGH

            case 'property':
                return '<tr><td>'.htmlentities($key) . '</td>
                    <td>' . $type.'</td>
                    <td>'.strlen($value) . '</td>
                    <td class="value">' . $value.'</td>
                </tr>';

            case 'array':
                $retval = '';

                ksort($value);

                foreach ($value as $subkey => $subvalue) {
                    $retval .= self::showHtmlRow($subvalue, $subkey);
                }

                return '<tr>
                    <td>'.htmlentities($key) . '</td>
                    <td>' . $type.'</td>
                    <td>'.count($value) . '</td>
                    <td style="padding:0">
                        <table class="debug">
                            <thead><td>'.tr('Key') . '</td><td>'.tr('Type') . '</td><td>'.tr('Size') . '</td><td>'.tr('Value') . '</td></thead>' . $retval.'
                        </table>
                    </td>
                </tr>';

            case 'object':
                /*
                 * Clean contents!
                 */
                $value  = print_r($value, true);
                $value  = preg_replace('/-----BEGIN RSA PRIVATE KEY.+?END RSA PRIVATE KEY-----/imus', '*** HIDDEN ***', $value);
                $value  = preg_replace('/(\[.*?pass.*?\]\s+=>\s+).+/', '$1*** HIDDEN ***', $value);
                $retval = '<pre>' . $value.'</pre>';

                return '<tr>
                    <td>' . $key . '</td>
                    <td>' . $type.'</td>
                    <td>?</td>
                    <td>' . $retval.'</td>
                </tr>';

            default:
                return '<tr>
                    <td>' . $key . '</td>
                    <td>'.tr('Unknown') . '</td>
                    <td>???</td>
                    <td class="value">'.htmlentities($value) . '</td>
                </tr>';
        }
    }
}