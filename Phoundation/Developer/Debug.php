<?php

namespace Phoundation\Developer;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\CoreException;
use Phoundation\Core\Strings;

/**
 * Class Debug
 *
 * This class contains the basic debug methods for use in Phoundation
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Debug {
    /**
     * If true, will clean up debug data string before returning them.
     *
     * @var bool $clean_data
     */
    protected static bool $clean_data = true;

    /**
     * Track system statistics
     *
     * @var array $statistics
     */
    protected array $statistics = [];



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
     * If true, methods supporting it will clean up debug data string before returning them.
     *
     * @param bool|null $enable
     * @return bool
     */
    public static function cleanData(?bool $enable = null): bool
    {
        // Set only if specified
        if (is_bool($enable)) {
            self::$clean_data = $enable;
        }

        return self::$clean_data;
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
     * Returns an array with debug information
     *
     * @return array
     */
    public static function getJson(): array
    {
        return [];
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

        try {
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

            try {
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
     * Returns a new statistics class
     *
     * @return Statistic
     */
    public static function addStatistic(): Statistic
    {
        $statistic = new Statistic();
        self::statistics[] = $statistic;
        return $statistic;
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


    /*
     * Auto fill in values in HTML forms (very useful for debugging and testing)
     *
     * In environments where debug is enabled, this function can pre-fill large HTML forms with test data
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @note This function will NOT return any values when not running in debug mode
     * @see debug()
     * @example
     * code
     * echo '<input type="text" name="username" value="'.value('username').'">';
     * /code
     *
     * This will show something like

     * code
     * <input type="text" name="username" value="YtiukrtyeG">
     * /code
     *
     * @param mixed $format
     * @param natural $size
     * @return string The value to be inserted.
     */
    function value($format, $size = null)
    {
        if (!self::enabled()) return '';

        /*
         * Generate debug value
         */
        load_libs('synonyms');

        if (!Debug::enabled()) {
            return '';
        }

        switch ($format) {
            case 'username':
                // no-break
            case 'word':
                return synonym_random(1, true);

            case 'name':
                return not_empty(str_force(synonym_random(not_empty($size, mt_rand(1, 4))), ' '), str_random(not_empty($size, 32), false, '0123456789abcdefghijklmnopqrstuvwxyz     '));

            case 'text':
                // no-break
            case 'words':
                return not_empty(str_force(synonym_random(not_empty($size, mt_rand(5, 15))), ' '), str_random(not_empty($size, 150), false, '0123456789abcdefghijklmnopqrstuvwxyz     '));

            case 'email':
                return str_replace('-', '', str_replace(' ', '', not_empty(str_force(synonym_random(mt_rand(1, 2), true), str_random(mt_rand(0, 1), false, '._-')), str_random())).'@'.str_replace(' ', '', not_empty(str_force(synonym_random(mt_rand(1, 2), true), str_random(mt_rand(0, 1), false, '_-')), str_random()).'.com'));

            case 'url':
                return str_replace(' ', '', 'http://'.not_empty(str_force(synonym_random(mt_rand(1, 2), true), str_random(mt_rand(0, 1), false, '._-')), str_random()).'.'.pick_random(1, 'com', 'co', 'mx', 'org', 'net', 'guru'));

            case 'random':
                return str_random(not_empty($size, 150), false, '0123456789abcdefghijklmnopqrstuvwxyz     ');

            case 'zip':
                // no-break
            case 'zipcode':
                return str_random(not_empty($size, 5), false, '0123456789');

            case 'number':
                return str_random(not_empty($size, 8), false, '0123456789');

            case 'address':
                return str_random().' '.str_random(not_empty($size, 8), false, '0123456789');

            case 'password':
                return 'aaaaaaaa';

            case 'money':
                if (!$size) {
                    $size = 5000;
                }

                return mt_rand(1, $size) / 100;

            case 'checked':
                if ($size) {
                    return ' checked ';
                }

                return '';

            default:
                return $format;
        }
    }


    /**
     * Show nice HTML table with all debug data
     *
     * @param mixed $value
     * @param string|null $key
     * @param integer $trace_offset
     * @return string
     * @see show()
     */
    function debugHtml(string $value, ?string $key = null, int $trace_offset = 0): string
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

        return $retval.'<table class="debug">
                    <thead class="debug-header"><td colspan="4">'.current_file(1 + $trace_offset).'@'.current_line(1 + $trace_offset).'</td></thead>
                    <thead class="debug-columns"><td>'.tr('Key').'</td><td>'.tr('Type').'</td><td>'.tr('Size').'</td><td>'.tr('Value').'</td></thead>'.self::htmlRow($value, $key).'
                </table>';
    }


    /**
     * Show HTML <tr> for the specified debug data
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @see show()
     *
     * @param mixed $value
     * @param string|null $key
     * @param string|null $type
     * @return
     */
    protected function debugHtmlRow(mixed $value, ?string $key = null, ?string $type = null): string
    {
        if ($type === null) {
            $type = gettype($value);
        }

        if ($key === null) {
            $key = tr('Unknown');
        }

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
                        <td>'.htmlentities($key).'</td>
                        <td>'.$type.'</td>
                        <td>'.strlen((string) $value).'</td>
                        <td class="value">'.htmlentities($value).'</td>
                    </tr>';

            case 'boolean':
                return '<tr>
                        <td>'.htmlentities($key).'</td>
                        <td>'.$type.'</td>
                        <td>1</td>
                        <td class="value">'.($value ? tr('true') : tr('false')).'</td>
                    </tr>';

            case 'NULL':
                return '<tr>
                        <td>'.htmlentities($key).'</td>
                        <td>'.$type.'</td>
                        <td>0</td>
                        <td class="value">'.htmlentities($value).'</td>
                    </tr>';

            case 'resource':
                return '<tr><td>'.htmlentities($key).'</td>
                        <td>'.$type.'</td>
                        <td>?</td>
                        <td class="value">'.$value.'</td>
                    </tr>';

            case 'method':
                // no-break

            case 'property':
                return '<tr><td>'.htmlentities($key).'</td>
                        <td>'.$type.'</td>
                        <td>'.strlen($value).'</td>
                        <td class="value">'.$value.'</td>
                    </tr>';

            case 'array':
                $retval = '';

                ksort($value);

                foreach ($value as $subkey => $subvalue) {
                    $retval .= debug_html_row($subvalue, $subkey);
                }

                return '<tr>
                        <td>'.htmlentities($key).'</td>
                        <td>'.$type.'</td>
                        <td>'.count($value).'</td>
                        <td style="padding:0">
                            <table class="debug">
                                <thead><td>'.tr('Key').'</td><td>'.tr('Type').'</td><td>'.tr('Size').'</td><td>'.tr('Value').'</td></thead>'.$retval.'
                            </table>
                        </td>
                    </tr>';

            case 'object':
                // Clean contents!
                $value  = print_r($value, true);
                $value  = preg_replace('/-----BEGIN RSA PRIVATE KEY.+?END RSA PRIVATE KEY-----/imus', '*** HIDDEN ***', $value);
                $value  = preg_replace('/(\[.*?pass.*?\]\s+=>\s+).+/', '$1*** HIDDEN ***', $value);
                $retval = '<pre>'.$value.'</pre>';

                return '<tr>
                        <td>'.$key.'</td>
                        <td>'.$type.'</td>
                        <td>?</td>
                        <td>'.$retval.'</td>
                    </tr>';

            default:
                return '<tr>
                        <td>'.$key.'</td>
                        <td>'.tr('Unknown').'</td>
                        <td>???</td>
                        <td class="value">'.htmlentities($value).'</td>
                    </tr>';
        }
    }



    /**
     * Displays the specified query in a show() output
     *
     * @param string|\PDOStatement $query
     * @param array|null $execute
     * @param boolean $return_only
     * @return mixed
     * @see show()
     */
    function debugSql(string|\PDOStatement $query, ?array $execute = null, bool $return_only = false)
    {
        if (is_array($execute)) {
            /*
             * Reverse key sort to ensure that there are keys that contain at least parts of other keys will not be used incorrectly
             *
             * example:
             *
             * array(category    => test,
             *       category_id => 5)
             *
             * Would cause the query to look like `category` = "test", `category_id` = "test"_id
             */
            krsort($execute);

            if (is_object($query)) {
                /*
                 * Query to be debugged is a PDO statement, extract the query
                 */
                if (!($query instanceof PDOStatement)) {
                    throw new CoreException(tr('debug_sql(): Object of unknown class ":class" specified where PDOStatement was expected', array(':class' => get_class($query))), 'invalid');
                }

                $query = $query->queryString;
            }

            foreach ($execute as $key => $value) {
                if (is_string($value)) {
                    $value = addslashes($value);
                    $query = str_replace($key, '"'.(!is_scalar($value) ? ' ['.tr('NOT SCALAR').'] ' : '').Strings::Log($value).'"', $query);

                } elseif (is_null($value)) {
                    $query = str_replace($key, ' '.tr('NULL').' ', $query);

                } elseif (is_bool($value)) {
                    $query = str_replace($key, Strings::boolean($value), $query);

                } else {
                    if (!is_scalar($value)) {
                        throw new CoreException(tr('debug_sql(): Specified key ":key" has non-scalar value ":value"', array(':key' => $key, ':value' => $value)), 'invalid');
                    }

                    $query = str_replace($key, $value, $query);
                }
            }
        }

        if ($return_only) {
            return $query;
        }

        if (empty($core->register['clean_debug'])) {
            $query = str_replace("\n", ' ', $query);
            $query = Strings::noDouble($query, ' ', '\s');
        }

        /*
         * VERYVERBOSE already logs the query, don't log it again
         */
        if (!VERYVERBOSE) {
            log_file(Strings::endsWith($query, ';'), 'debug-sql');
        }

        return show(Strings::endsWith($query, ';'), 6);
    }



    /**
     * Returns a filtered debug_backtrace()
     *
     * debug_backtrace() contains all function arguments and can get very clutered. debug_trace() will by default filter
     * the function arguments and return a much cleaner back trace for displaying in debug traces. The function allows
     * other keys to be filtered out if specified
     *
     * @param mixed $filters A list of keys that should be filtered from the debug_backtrace() output
     * @param boolean $skip_own If specified as true, will skip the debug_trace() call and its handler inclusion from
     *                          the trace
     * @return array The debug_backtrace() output with the specified keys filtered out
     */
    function debugTrace(array|string|null $filters = 'args', bool $skip_own = true): array
    {
        if (!Debug::enabled()) {
            return [];
        }

        $filters = Arrays::force($filters);
        $trace   = array();

        foreach (debug_backtrace() as $key => $value) {
            if ($skip_own and ($key <= 1)) {
                continue;
            }

            foreach ($filters as $filter) {
                unset($value[$filter]);
            }

            $trace[] = $value;
        }

        return $trace;
    }



    /**
     * Return an HTML bar with debug information that can be used to monitor site and fix issues
     *
     * @return string The HTML that can be included at the end of the web page which will show the debug bar.
     */
    function debugBar()
    {
        load_libs('numbers');

        if (!Debug::enabled()) return '';

        if ($_CONFIG['debug']['bar'] === false) {
            return '';

        } elseif ($_CONFIG['debug']['bar'] === 'limited') {
            if (empty($_SESSION['user']['id']) or !has_rights("debug")) {
                /*
                 * Only show debug bar to authenticated users with "debug" right
                 */
                return false;
            }

        } elseif ($_CONFIG['debug']['bar'] === true) {
            /*
             * Show debug bar!
             */

        } else {
            throw new CoreException(tr('debug_bar(): Unknown configuration option ":option" specified. Please specify true, false, or "limited"', array(':option' => $_CONFIG['debug']['bar'])), 'unknown');
        }

        /*
         * Add debug bar javascript directly to the footer, as this debug bar is
         * added AFTER html_generate_js() and so won't be processed anymore
         */
        $core->register['footer'] .= html_script('$("#debug-bar").click(function(e) { $("#debug-bar").find(".list").toggleClass("hidden"); });');

        /*
         * Setup required variables
         */
        usort($core->register['debug_queries'], 'debug_bar_sort');
        $usage = getrusage();
        $files = get_included_files();


        /*
         * Build HTML
         */
        $html = '<div class="debug" id="debug-bar">
                '.($_CONFIG['cache']['method'] ? '(CACHE='.$_CONFIG['cache']['method'].') ' : '').count($core->register('debug_queries')).' / '.number_format(microtime(true) - STARTTIME, 6).'
                <div class="hidden list">
                    <div style="width:100%; background: #2d3945; text-align: center; font-weight: bold; padding: 3px 0 3px;">
                        '.tr('Debug report').'
                    </div>
                    <table style="width:100%">
                        <thead>
                            <tr>
                                <th colspan="3">'.tr('Query information (Ordered by slowest first, fastest last)').'</th>
                            </tr>
                            <tr>
                                <th>'.tr('Time').'</th>
                                <th>'.tr('Function').'</th>
                                <th>'.tr('Query').'</th>
                            </tr>
                        </thead>
                        <tbody>';

        /*
         * Add query statistical data ordered by slowest queries first
         */
        foreach ($core->register['debug_queries'] as $query) {
            $html .= '      <tr>
                            <td>'.number_format($query['time'], 6).'</td>
                            <td>'.$query['function'].'</td>
                            <td>'.$query['query'].'</td>
                        </tr>';
        }

        $html .= '          </tbody>
                    </table>';

        /*
         * Show some basic statistics
         */
        $html .= '      <table style="width:100%">
                        <thead>
                            <tr>
                                <th colspan="2">'.tr('General information').'</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>'.tr('Peak memory usage').'</td>
                                <td>'.human_readable(memory_get_peak_usage()).'</td>
                            </tr>
                            <tr>
                                <td>'.tr('Execution time').'</td>
                                <td>'.tr(':time milliseconds', array(':time' => number_format((microtime(true) - STARTTIME) * 1000, 2))).'</td>
                            </tr>
                            <tr>
                                <td>'.tr('CPU usage system').'</td>
                                <td>'.tr(':time microseconds', array(':time' => number_format($usage['ru_stime.tv_usec'], 0, '.', ','))).'</td>
                            </tr>
                            <tr>
                                <td>'.tr('Included files count').'</td>
                                <td>'.count($files).'</td>
                            </tr>
                        </tbody>
                    </table>';

        /*
         * Show all included files
         */
        $html .= '      <table style="width:100%">
                        <thead>
                            <tr>
                                <th colspan="2">'.tr('Included files (In loaded order)').'</th>
                            </tr>
                        </thead>
                        <thead>
                            <tr>
                                <th>'.tr('Number').'</th>
                                <th>'.tr('File').'</th>
                            </tr>
                        </thead>
                        <tbody>';

        foreach ($files as $id => $file) {
            $html .= '      <tr>
                            <td>'.($id + 1).'</td>
                            <td>'.$file.'</td>
                        </tr>';
        }

        $html .= '          </tbody>
                    </table>';

        $html .= '  </div>
             </div>';

        $html  = str_replace(':query_count'   , count($core->register('debug_queries'))      , $html);
        $html  = str_replace(':execution_time', number_format(microtime(true) - STARTTIME, 6), $html);

        return $html;
    }



    /**
     * Used for ordering entries on the debug bar
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected static function barSort(array $a, array $b): int
    {
        if ($a['time'] > $b['time']) {
            return -1;

        } elseif ($a['time'] < $b['time']) {
            return 1;

        } else {
            /*
             * They're the same, so ordering doesn't matter
             */
            return 0;
        }
    }



    /**
     * Die when this method has been called the $count specified amount of times, and display the optional message
     *
     * @param int $count
     * @param string|null $message
     * @return void
     */
    function dieIn(int $count, string $message = null): void
    {
        static $counter = 1;

        if (!$message) {
            $message = tr('Terminated process because die counter reached "%count%"');
        }

        if ($counter++ >= $count) {
            // Ensure that the shutdown function doesn't try to show the 404 page
            Core::unregisterShutdown('route_404');

            die(Strings::endsWith(str_replace('%count%', $count, $message), "\n"));
        }
    }
}