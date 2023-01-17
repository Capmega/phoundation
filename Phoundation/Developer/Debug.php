<?php

namespace Phoundation\Developer;

use JetBrains\PhpStorm\NoReturn;
use PDOStatement;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\ConfigException;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Log;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Developer\Exception\DebugException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Notifications\Notification;
use Phoundation\Web\WebPage;



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
    protected static array $statistics = [];

    /**
     * A simple counter for debugging
     *
     * @var DebugCounter|null $counter
     */
    protected static ?DebugCounter $counter = null;



    /**
     * Sets or returns if the system is running in debug mode or not
     *
     * @param bool|null $enabled
     * @return bool
     */
    public static function enabled(?bool $enabled = null): bool
    {
        static $loop = false;

        if ($loop) {
            // We're in a loop!
            return false;
        }

        $loop = true;

        if (Core::initState()) {
            // System startup has not yet completed, disable debug!
            return false;
        }

        if ($enabled === null) {
            // Return the setting
            $return = strings::getBoolean(Config::get('debug.enabled', false));
            $loop   = false;

            return $return;
        }

        // Make the setting
        Config::set('debug.enabled', $enabled);
        $loop = false;
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
        static $loop = false;

        if ($loop) {
            // We're in a loop!
            return false;
        }

        $loop = true;

        try {
            if ($production === null) {
                if (!defined('ENVIRONMENT')) {
                    // Oops, we're so early in startup that we don't have an environment available yet!
                    // Assume production!
                    $loop = false;
                    return true;
                }

                // Return the setting
                $return = Config::getBoolean('debug.production', false);
                $loop   = false;
                return $return;
            }

            // Set the value
            Config::set('debug.production', $production);
            $loop = false;
            return $production;
        } catch (ConfigException) {
            // Failed to get (or write) config. Assume production
            $loop = false;
            return true;
        }
    }



    /**
     * Returns a backtrace
     *
     * @param int $start
     * @param array|string[] $remove_sections
     * @return array
     */
    public static function backtrace(int $start = 1, array|string $remove_sections = ['args', 'object']): array
    {
        $trace           = [];
        $remove_sections = Arrays::force($remove_sections);

        foreach (debug_backtrace() as $key => $value) {
            if ($start and ($key < $start)) {
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
     * @param string|null $default
     * @return string|null
     */
    public static function currentClass(int $trace = 0, ?string $default = '-'): ?string
    {
        $backtrace = debug_backtrace();
        return isset_get($backtrace[$trace + 1]['class'], $default);
    }


    /**
     * Returns the function name from where this call was made
     *
     * @param int $trace
     * @param string|null $default
     * @return string|null
     */
    public static function currentFunction(int $trace = 0, ?string $default = '-'): ?string
    {
        $backtrace = debug_backtrace();
        return isset_get($backtrace[$trace + 1]['function'], $default);
    }



    /**
     * Returns the filename from where this call was made
     *
     * @param int $trace
     * @param string|null $default
     * @return string|null
     */
    public static function currentFile(int $trace = 0, ?string $default = '-'): ?string
    {
        $backtrace = debug_backtrace();
        return isset_get($backtrace[$trace + 1]['file'], $default);
    }



    /**
     * Returns the line number from where this call was made
     *
     * @param int $trace
     * @param int|null $default
     * @return int|null
     */
    public static function currentLine(int $trace = 0, ?int $default = -1): ?int
    {
        $backtrace = debug_backtrace();
        return isset_get($backtrace[$trace + 1]['line'], $default);
    }



    /**
     * Returns a string indicating function or class method in file@line,
     *
     * @note To avoid any possible enless looping, this method does NOT return translatable texts
     * @param int $trace
     * @return string
     */
    public static function currentLocation(int $trace = 0): string
    {
        $class    = self::currentClass($trace + 1, null);
        $function = self::currentFunction($trace + 1, null);
        $return   = self::currentFile($trace) . '@' . self::currentLine($trace);

        switch ($function) {
            case null:
                // no-break
            case 'include':
                // no-break
            case 'require':
                // Just file@line
                return 'File ' . $return;

            default:
                if ($class) {
                    $function = $class . '::' . $function;
                }

                // Class method or function in file@line
                return $function . 'in' . $return;
        }
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
     * @param int $trace_offset
     * @param bool $quiet
     * @return mixed
     */
    public static function show(mixed $value = null, int $trace_offset = 0, bool $quiet = false): mixed
    {
        if (!self::enabled()) {
            return null;
        }

        Core::unregisterShutdown('route_postprocess');

        if (Debug::production()) {
            // This is not usually something you want to happen!
            Notification::new()
                ->setTitle('Debug mode enabled on production environment!')
                ->setMessage('Debug mode enabled on production environment, with this all internal debug information can be visible to everybody!')
                ->setGroups('developers')
                ->send();
        }


        // Filter secure data
        if (is_array($value)) {
            $value = Arrays::hide($value, 'GLOBALS,%pass,ssh_key');
        }

        if (Core::readyState() and PLATFORM_HTTP) {
            if (empty($core->register['debug_plain'])) {
                switch (Core::getCallType()) {
                    case 'api':
                        // no-break
                    case 'ajax':
                        if (!headers_sent()) {
                            WebPage::setContentType('text/html');
                            WebPage::sendHttpHeaders(WebPage::buildHttpHeaders($value));
                        }

                        $output = PHP_EOL . tr('DEBUG SHOW (:file@:line) [:size]', [
                            ':file' => self::currentFile($trace_offset - 1),
                            ':line' => self::currentLine($trace_offset - 1),
                            ':size' => ($value === null ? 'NULL' : (is_scalar($value) ? strlen((string) $value) : count((array) $value)))
                        ]) . PHP_EOL . print_r($value, true) . PHP_EOL;
                        break;

                    default:
                        // Force HTML content type, and show HTML data
                        $output = self::showHtml($value, tr('Unknown'), $trace_offset);
                }

                // Show output on web
                if (!headers_sent()) {
                    WebPage::setContentType('text/html');
                    WebPage::sendHttpHeaders(WebPage::buildHttpHeaders($output));
                }

                echo $output;
                ob_flush();
                flush();

            } else {
                echo PHP_EOL . tr('DEBUG SHOW (:file@:line) [:size]', [
                    ':file' => self::currentFile($trace_offset),
                    ':line' => self::currentLine($trace_offset),
                        ':size' => ($value === null ? 'NULL' : (is_scalar($value) ? strlen((string) $value) : count((array) $value)))
                ]) . PHP_EOL;;
                print_r($value) . PHP_EOL;;
                flush();
                ob_flush();
            }

        } else {
            $return = '';

            if (PLATFORM_HTTP) {
                // We're displaying plain text to a browser platform. Send "<pre>" to force readable display
                echo '<pre>';
            }

            // Show output on CLI console
            if (is_scalar($value)) {
                $return .= ($quiet ? '' : tr('DEBUG SHOW (:file@:line) [:size] ', [
                    ':file' => self::currentFile($trace_offset),
                    ':line' => self::currentLine($trace_offset),
                    ':size' => strlen((string) $value)
                    ])) . $value . PHP_EOL;

            } else {
                // Sort if is array for easier reading
                if (is_array($value)) {
                    ksort($value);
                }

                if (!$quiet) {
                    $return .= tr('DEBUG SHOW (:file@:line) [:size]', [
                        ':file' => self::currentFile($trace_offset),
                        ':line' => self::currentLine($trace_offset),
                        ':size' => ($value === null ? 'NULL' : count((array) $value))
                    ]) . PHP_EOL;
                }

                $return .= print_r($value, true);
                $return .= PHP_EOL;
            }

            echo $return;
        }

        return $value;
    }


    /**
     * Show the given value on screen, then die.
     *
     * In command line and API type modes, the value will be displayed using print_r(),
     * in web page mode, the value will be nicely displayed in a recursive table
     *
     * @param mixed $value
     * @param int $trace_offset
     * @param bool $quiet
     */
    #[NoReturn] public static function showDie(mixed $value = null, int $trace_offset = 1, bool $quiet = false): void
    {
        if (self::enabled()) {
            self::show($value, $trace_offset, $quiet);
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
        self::$statistics[] = $statistic;
        return $statistic;
    }



    /**
     * Returns an HTML table that contains the specified $value in a very nice and readable way for debugging purposes
     *
     * @param mixed $value
     * @param string|null $key
     * @param int $trace_offset
     * @return string
     */
    protected static function showHtml(mixed $value, string|null $key = null, int $trace_offset = 0): string
    {
        static $style;

        if ($key === null) {
            $key = tr('Unknown');
        }

        if (empty($style)) {
            $style  = true;

            $return = '<style type="text/css">
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
            $return = '';
        }

        return $return . '<table class="debug">
                    <thead class="debug-header"><td colspan="4">'.self::currentFile(1 + $trace_offset) . '@'.self::currentLine(1 + $trace_offset) . '</td></thead>
                    <thead class="debug-columns"><td>'.tr('Key') . '</td><td>'.tr('Type') . '</td><td>'.tr('Size') . '</td><td>'.tr('Value') . '</td></thead>'.self::showHtmlRow($value, $key) . '
                </table>';
    }



    /**
     * Generates and returns a single HTML line with debug information for the specified value
     *
     * @param mixed $value
     * @param string|null $key
     * @return string
     */
    protected static function showHtmlRow(mixed $value, ?string $key = null): string
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

                // no-break

            case 'integer':
                // no-break

            case 'double':
                return '<tr>
                    <td>'.htmlentities($key) . '</td>
                    <td>' . $type.'</td>
                    <td>'.strlen((string) $value) . '</td>
                    <td class="value">'.nl2br(htmlentities($value)) . '</td>
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
                    <td>'.$key . '</td>
                    <td>' . $type.'</td>
                    <td>0</td>
                    <td class="value">'.$value . '</td>
                </tr>';

            case 'resource':
                return '<tr><td>'.htmlentities($key) . '</td>
                    <td>' . $type.'</td>
                    <td>?</td>
                    <td class="value">' . $value.'</td>
                </tr>';

            case 'method':
                // no-break

            case 'property':
                return '<tr><td>'.htmlentities($key) . '</td>
                    <td>' . $type.'</td>
                    <td>'.strlen($value) . '</td>
                    <td class="value">' . $value.'</td>
                </tr>';

            case 'array':
                $return = '';

                ksort($value);

                foreach ($value as $subkey => $subvalue) {
                    $return .= self::showHtmlRow($subvalue, $subkey);
                }

                return '<tr>
                    <td>'.htmlentities($key) . '</td>
                    <td>' . $type.'</td>
                    <td>'.count($value) . '</td>
                    <td style="padding:0">
                        <table class="debug">
                            <thead><td>'.tr('Key') . '</td><td>'.tr('Type') . '</td><td>'.tr('Size') . '</td><td>'.tr('Value') . '</td></thead>' . $return.'
                        </table>
                    </td>
                </tr>';

            case 'object':
                // Clean contents!
                $value  = print_r($value, true);
                $value  = preg_replace('/-----BEGIN RSA PRIVATE KEY.+?END RSA PRIVATE KEY-----/imus', '*** HIDDEN ***', $value);
                $value  = preg_replace('/(\[.*?pass.*?\]\s+=>\s+).+/', '$1*** HIDDEN ***', $value);
                $return = '<pre>' . $value.'</pre>';

                return '<tr>
                    <td>' . $key . '</td>
                    <td>' . $type.'</td>
                    <td>?</td>
                    <td>' . $return.'</td>
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



    /**
     * Return semi random values to automatically fill in values in HTML forms (very useful for debugging and testing)
     *
     * In environments where debug is enabled, this function can pre-fill large HTML forms with test data
     *
     * @param string $format
     * @param int|null $size
     * @return string The value to be inserted.
     * @note This function will NOT return any values when not running in debug mode
     * @see Debug::enabled()
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
     */
    function value(string $format, ?int $size = null): string
    {
        if (!self::enabled()) return '';
        if (!Debug::enabled()) return '';


        // Generate debug value
        switch ($format) {
            case 'username':
                // no-break
            case 'word':
                return synonym_random(1, true);

            case 'name':
                return not_empty(Strings::force(synonym_random(not_empty($size, mt_rand(1, 4))), ' '), Strings::random(not_empty($size, 32), false, '0123456789abcdefghijklmnopqrstuvwxyz     '));

            case 'text':
                // no-break
            case 'words':
                return not_empty(Strings::force(synonym_random(not_empty($size, mt_rand(5, 15))), ' '), Strings::random(not_empty($size, 150), false, '0123456789abcdefghijklmnopqrstuvwxyz     '));

            case 'email':
                return str_replace('-', '', str_replace(' ', '', not_empty(Strings::force(synonym_random(mt_rand(1, 2), true), Strings::random(mt_rand(0, 1), false, '._-')), Strings::random())).'@'.str_replace(' ', '', not_empty(Strings::force(synonym_random(mt_rand(1, 2), true), Strings::random(mt_rand(0, 1), false, '_-')), Strings::random()).'.com'));

            case 'url':
                return str_replace(' ', '', 'http://'.not_empty(Strings::force(synonym_random(mt_rand(1, 2), true), Strings::random(mt_rand(0, 1), false, '._-')), Strings::random()).'.'.pick_random(1, 'com', 'co', 'mx', 'org', 'net', 'guru'));

            case 'random':
                return Strings::random(not_empty($size, 150), false, '0123456789abcdefghijklmnopqrstuvwxyz     ');

            case 'zip':
                // no-break
            case 'zipcode':
                return Strings::random(not_empty($size, 5), false, '0123456789');

            case 'number':
                return Strings::random(not_empty($size, 8), false, '0123456789');

            case 'address':
                return Strings::random().' '.Strings::random(not_empty($size, 8), false, '0123456789');

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
            // Reverse key sort to ensure that there are keys that contain at least parts of other keys will not be used incorrectly
            // example:
            // [
            //   category    => test,
            //   category_id => 5
            // ]
            //
            // Would cause the query to look like `category` = "test", `category_id` = "test"_id
            krsort($execute);

            if (is_object($query)) {
                // Query to be debugged is a PDO statement, extract the query
                if (!($query instanceof PDOStatement)) {
                    throw new CoreException(tr('Object of unknown class ":class" specified where PDOStatement was expected', [
                        ':class' => get_class($query)
                    ]));
                }

                $query = $query->queryString;
            }

            foreach ($execute as $key => $value) {
                if (is_string($value)) {
                    $value = addslashes($value);
                    $query = str_replace($key, '"'.(!is_scalar($value) ? ' ['.tr('NOT SCALAR').'] ' : '') . Strings::Log($value).'"', $query);

                } elseif (is_null($value)) {
                    $query = str_replace($key, ' '.tr('NULL').' ', $query);

                } elseif (is_bool($value)) {
                    $query = str_replace($key, Strings::boolean($value), $query);

                } else {
                    if (!is_scalar($value)) {
                        throw new CoreException(tr('Specified key ":key" has non-scalar value ":value"', [
                            ':key'   => $key,
                            ':value' => $value
                        ]));
                    }

                    $query = str_replace($key, $value, $query);
                }
            }
        }

        if ($return_only) {
            return $query;
        }

        if (empty(Core::readRegister('debug', 'clean'))) {
            $query = str_replace(PHP_EOL, ' ', $query);
            $query = Strings::noDouble($query, ' ', '\s');
        }

        // Debug::enabled() already logs the query, don't log it again
        if (!Debug::enabled()) {
            Log::printr(Strings::endsWith($query, ';'));
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
     * @return string|null The HTML that can be included at the end of the web page which will show the debug bar.
     */
    function debugBar(): ?string
    {
        if (!Debug::enabled()) return '';

        $enabled = Config::get('debug.bar.enabled', false);

        if ($enabled === false) {
            return null;
        }

        if ($enabled === 'limited') {
            if (empty($_SESSION['user']['id']) or !Session::user()->hasAllRights("debug")) {
                /*
                 * Only show debug bar to authenticated users with "debug" right
                 */
                return null;
            }
        } elseif ($enabled !== true) {
            throw new CoreException(tr('Unknown configuration option ":option" specified. Please specify true, false, or "limited"', [
                ':option' => Config::get('debug.bar', false)
            ]));
        }

        // Add debug bar javascript directly to the footer, as this debug bar is added AFTER html_generate_js() and so
        // won't be processed anymore
        Html::prependToFooter(html_script('$("#debug-bar").click(function(e) { $("#debug-bar").find(".list").toggleClass("hidden"); });'));

        // Setup required variables
        usort($core->register['debug_queries'], 'debug_bar_sort');
        $usage = getrusage();
        $files = get_included_files();


        // Build HTML
        $html = '<div class="debug" id="debug-bar">
                '.($_CONFIG['cache']['method'] ? '(CACHE=' . $_CONFIG['cache']['method'].') ' : '').count(Core::readRegister('debug_queries')).' / '.number_format(microtime(true) - STARTTIME, 6).'
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

        // Add query statistical data ordered by slowest queries first
        foreach ($core->register['debug_queries'] as $query) {
            $html .= '      <tr>
                            <td>'.number_format($query['time'], 6).'</td>
                            <td>' . $query['function'].'</td>
                            <td>' . $query['query'].'</td>
                        </tr>';
        }

        $html .= '          </tbody>
                    </table>';

        // Show some basic statistics
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

        // Show all included files
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
                            <td>' . $file.'</td>
                        </tr>';
        }

        $html .= '          </tbody>
                    </table>';

        $html .= '  </div>
             </div>';

        $html = str_replace(':query_count'   , count(Core::readRegister('debug_queries'))                    , $html);
        $html = str_replace(':execution_time', number_format(microtime(true) - STARTTIME, 6), $html);

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

        }

        if ($a['time'] < $b['time']) {
            return 1;
        }

        // They're the same, so ordering doesn't matter
        return 0;
    }



    /**
     * Die when this method has been called the $count specified amount of times, and display the optional message
     *
     * @param int $count
     * @param string|null $message
     * @return void
     */
    public static function dieIn(int $count, string $message = null): void
    {
        static $counter = 1;

        if (!$message) {
            $message = tr('Terminated process because die counter reached "%count%"');
        }

        if ($counter++ >= $count) {
            // Ensure that the shutdown function doesn't try to show the 404 page
            Core::unregisterShutdown('route_postprocess');

            die(Strings::endsWith(str_replace('%count%', $count, $message), PHP_EOL));
        }
    }



    /**
     * Returns the debug counter and selects the specified counter
     *
     * @param string $counter
     * @return DebugCounter
     */
    public static function counter(string $counter): DebugCounter
    {
        if (self::$counter === null) {
            self::$counter = new DebugCounter();
        }

        self::$counter->select($counter);
        return self::$counter;
    }



    /**
     * Get the class path from the specified .php file
     *
     * @param string $file
     * @return Object
     */
    public static function getClassPath(string $file): string
    {
        if (!File::new($file, [PATH_ROOT . 'Phoundation', PATH_ROOT . 'Plugins', PATH_ROOT . 'Templates'])->isPhp()) {
            throw new OutOfBoundsException(tr('The specified file ":file" is not a PHP file', [':file' => $file]));
        }

        // Scan for namespace and class lines
        $namespace = null;
        $class     = null;
        $results   = File::new($file, [PATH_ROOT . 'Phoundation', PATH_ROOT . 'Plugins', PATH_ROOT . 'Templates'])->grep(['namespace ', 'class '], 100);

        // Get the namespace
        foreach ($results['namespace '] as $line) {
            if (preg_match_all('/^namespace\s+(.+?);$/i', $line, $matches)) {
                $namespace = $matches[1][0];
            }
        }

        if (!$namespace) {
            throw new DebugException(tr('Failed to find a namespace for file ":file"', [':file' => $file]));
        }

        // Get the class name
        foreach ($results['class '] as $line) {
            if (preg_match_all('/^class\s+([a-z0-9_]+)(?:(?:\s+extends\s+.+?)?\s+\{)?/i', $line, $matches)) {
                $class = $matches[1][0];
            }
        }

        if (!$class) {
            throw new DebugException(tr('Failed to find a class for file ":file"', [':file' => $file]));
        }

        // Now we can return the class path
        return Strings::endsWith($namespace, '\\') . $class;
    }



    /**
     * Get the .php file for the specified class path
     *
     * @param string $class_path
     * @return string
     */
    public static function getClassFile(string $class_path): string
    {
        if (!$class_path) {
            throw new OutOfBoundsException(tr('No class path specified'));
        }

        $file = str_replace('\\', '/', $class_path);
        $file = Strings::startsNotWith($file, '/');
        $file = PATH_ROOT . $file . '.php';

        if (!File::new($file, [PATH_ROOT . 'Phoundation', PATH_ROOT . 'Plugins', PATH_ROOT . 'Templates', ])->isPhp()) {
            throw new OutOfBoundsException(tr('The specified file ":file" is not a PHP file', [':file' => $file]));
        }

        return $file;
    }



    /**
     * Get the .php file for the specified class path
     *
     * @param string $class_path
     * @return void
     */
    public static function loadClassFile(string $class_path): void
    {
        $file = self::getClassFile($class_path);
        Log::action(tr('Including class file ":file"', [':file' => $file]), 2);
        include_once($file);
    }
}