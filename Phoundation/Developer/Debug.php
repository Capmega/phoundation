<?php

declare(strict_types=1);

namespace Phoundation\Developer;

use JetBrains\PhpStorm\NoReturn;
use PDOStatement;
use Phoundation\Audio\Audio;
use Phoundation\Core\Core;
use Phoundation\Core\Enums\EnumRequestTypes;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Exception\ConfigException;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Page;
use Throwable;


/**
 * Class Debug
 *
 * This class contains the basic debug methods for use in Phoundation
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Debug {
    /**
     * @var bool $enabled
     */
    protected static bool $enabled;

    /**
     * If true will return the opposite of $enabled
     *
     * @var bool $switched
     */
    protected static bool $switched = false;

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
     * Returns if the system is running in debug mode or not
     *
     * @return bool
     */
    public static function getEnabled(): bool
    {
        static $loop = false;

        if (Core::inStartupState()) {
            // Can't read config and as such neither the debug configuration
            return false;
        }

        if (!isset(static::$enabled)) {
            // Avoid endless loops
            if ($loop) {
                // We're in a loop!
                return false;
            }

            $loop = true;
            static::$enabled = Config::getBoolean('debug.enabled', false);
            $loop = false;
        }

        if (static::$switched) {
            // Return the opposite
            return !static::$enabled;
        }

        return static::$enabled;
    }


    /**
     * Returns true if each request should print execution statistics in the log
     *
     * @return bool
     */
    public static function printStatistics(): bool
    {
        return Config::getBoolean('debug.statistics', false);
    }


    /**
     * Sets or returns if the system is running in debug mode or not
     *
     * @param bool|null $enabled
     * @return void
     */
    public static function setEnabled(?bool $enabled = null): void
    {
        static::$enabled = $enabled;
    }


    /**
     * This will switch the current "enabled" setting to its opposite
     *
     * @return bool
     */
    public static function switch(): bool
    {
        return static::$switched = !static::$switched;
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
            static::$clean_data = $enable;
        }

        return static::$clean_data;
    }


    /**
     * Returns a backtrace
     *
     * @param string|int $start
     * @param array|string[] $remove_sections
     * @return array
     */
    public static function backtrace(string|int $start = 1, array|string $remove_sections = ['args', 'object']): array
    {
        $trace           = [];
        $remove_sections = Arrays::force($remove_sections);

        foreach (debug_backtrace() as $key => $value) {
            if ($start) {
                if (is_string($start)) {
                    if ($start === 'auto') {
                        if (str_contains($value['file'], 'functions.php') and str_contains($value['function'], 'include(')) {
                            break;
                        }

                    } else {
                        throw new OutOfBoundsException(tr('Invalid backtrace start ":start" specified. Must be a positive integer or "auto"', [
                            ':start' => $start
                        ]));
                    }
                } elseif ($key < $start) {
                    // Start building backtrace at specified entry
                    continue;
                }
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
        $class    = static::currentClass($trace + 1, null);
        $function = static::currentFunction($trace + 1, null);
        $return   = static::currentFile($trace) . '@' . static::currentLine($trace);

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
                return $function . '() in ' . $return;
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
     * @param bool $sort
     * @param int $trace_offset
     * @param bool $quiet
     * @param bool|null $full_backtrace If true will dump full backtraces. If false, will dump limited backtraces
     *                                  starting from the executed command. If NULL, will determine true or false from
     *                                  config path "debug.backtrace.full"
     * @return mixed
     */
    public static function show(mixed $value = null, bool $sort = true, int $trace_offset = 0, bool $quiet = false, ?bool $full_backtrace = null): mixed
    {
        if (!static::getEnabled()) {
            return null;
        }

        if ($full_backtrace === null) {
            // Show debug backtraces starting from commands or full?
            $full_backtrace = Config::getBoolean('debug.backtrace.full', false);
        }

        try {
            Core::removeShutdownCallback('route[postprocess]');

            if (Core::isProductionEnvironment()) {
                // This is not usually something you want to happen!
                Notification::new()
                    ->setUrl('developer/incidents.html')
                    ->setMode(EnumDisplayMode::exception)
                    ->setTitle('Debug mode enabled on production environment!')
                    ->setMessage('Debug mode enabled on production environment, with this all internal debug information can be visible to everybody!')
                    ->setRoles('developer')
                    ->send();
            }

            // Filter secure data
            if (is_array($value)) {
                $value = Arrays::hide($value, 'GLOBALS,%pass,ssh_key');
            }

            if (Core::readyState() and PLATFORM_WEB) {
                if (empty($core->register['debug_plain'])) {
                    switch (Core::getRequestType()) {
                        case EnumRequestTypes::api:
                            // no-break
                        case EnumRequestTypes::ajax:
                            $output = PHP_EOL . tr('DEBUG SHOW (:file@:line) [:type :size]', [
                                ':type' => gettype($value),
                                ':file' => static::currentFile($trace_offset - 1),
                                ':line' => static::currentLine($trace_offset - 1),
                                ':size' => ($value === null ? 'NULL' : (is_scalar($value) ? strlen((string) $value) : count((array) $value)))
                            ]) . PHP_EOL . print_r($value, true) . PHP_EOL;

                            if (!headers_sent()) {
                                Page::setContentType('text/html');
                                Page::sendHttpHeaders(Page::buildHttpHeaders($output));
                            }

                            break;

                        default:
                            // Force HTML content type, and show HTML data
                            $output = static::showHtml($value, tr('Unknown'), $sort, $trace_offset, $full_backtrace);
                    }

                    $output = get_null(ob_get_clean()) . $output;

                    // Show output on web
                    if (!headers_sent()) {
                        Page::setContentType('text/html');
                        Page::sendHttpHeaders(Page::buildHttpHeaders($output));
                    }

                    echo $output;

                } else {
                    echo PHP_EOL . tr('DEBUG SHOW (:file@:line) [:type :size]', [
                        ':type' => gettype($value),
                        ':file' => static::currentFile($trace_offset),
                        ':line' => static::currentLine($trace_offset),
                        ':size' => ($value === null ? 'NULL' : (is_scalar($value) ? strlen((string) $value) : count((array) $value)))
                    ]) . PHP_EOL;

                    $output = get_null(ob_get_clean());

                    // Show output on web
                    if (!headers_sent()) {
                        Page::setContentType('text/html');
                        Page::sendHttpHeaders(Page::buildHttpHeaders($output));
                    }

                    echo $output;
                }

            } else {
                $return = '';

                if (PLATFORM_WEB) {
                    // We're displaying plain text to a browser platform. Send "<pre>" to force readable display
                    echo '<pre>';
                }

                // Show output on CLI console
                if (is_scalar($value)) {
                    $return .= ($quiet ? '' : tr('DEBUG SHOW (:file@:line) [:type :size] ', [
                        ':type' => gettype($value),
                        ':file' => static::currentFile($trace_offset),
                        ':line' => static::currentLine($trace_offset),
                        ':size' => strlen((string) $value)
                        ])) . $value . PHP_EOL;

                } else {
                    // Sort if is array for easier reading
                    if ($sort) {
                        if (is_array($value)) {
                            ksort($value);
                        }
                    }

                    if (!$quiet) {
                        $return .= tr('DEBUG SHOW (:file@:line) [:type :size]', [
                            ':type' => gettype($value),
                            ':file' => static::currentFile($trace_offset),
                            ':line' => static::currentLine($trace_offset),
                            ':size' => ($value === null ? 'NULL' : count((array) $value))
                        ]) . PHP_EOL;
                    }

                    $return .= print_r($value, true);
                    $return .= PHP_EOL;
                }

                echo $return;
            }

        } catch (Throwable $e) {
            if (php_sapi_name() !== 'cli') {
                // Only add this on browsers
                echo '<pre>' . PHP_EOL . '"';
            }

            echo 'Debug::show() call failed with following exception';
            print_r($e);
        }

        return $value;
    }


    /**
     * Show the given value on screen, then die.
     *
     * In command line and API type modes, the value will be displayed using print_r(),
     * in web page mode, the value will be nicely displayed in a recursive table
     *
     * @note Does NOT return data type "never" because in production mode this call will be completely ignored!
     * @param mixed $value
     * @param bool $sort
     * @param int $trace_offset
     * @param bool $quiet
     * @return void
     */
    #[NoReturn] public static function showDie(mixed $value = null, bool $sort = true, int $trace_offset = 1, bool $quiet = false): void
    {
        if (static::getEnabled()) {
            try {
                static::show($value, $sort, $trace_offset, $quiet);

                // Don't log within Log::write() or tr() to avoid endless loops
                if (!function_called('Log::write()') and !function_called('tr()')) {
                    Log::warning(tr('Reached showdie() call at :location', [
                        ':location' => static::currentLocation($trace_offset)
                    ]));
                    Audio::new('showdie.mp3')->playLocal(true);
                }

            } catch (Throwable $e) {
                if (php_sapi_name() !== 'cli') {
                    // Only add this on browsers
                    echo '<pre>' . PHP_EOL . '"';
                }

                echo 'Debug::showDie() call failed with following exception';
                print_r($e);
            }

            Core::exit(sig_kill: true);
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
        static::$statistics[] = $statistic;
        return $statistic;
    }


    /**
     * Returns an HTML table that contains the specified $value in a very nice and readable way for debugging purposes
     *
     * @param mixed $value
     * @param string|null $key
     * @param bool $sort
     * @param int $trace_offset
     * @param bool $full_backtrace
     * @return string
     */
    protected static function showHtml(mixed $value, string|null $key = null, bool $sort = true, int $trace_offset = 0, bool $full_backtrace = false): string
    {
        static $style;

        if ($key === null) {
            $key = tr('Unknown');
        }

        if (empty($style)) {
            $style  = true;

            $return = '<style>
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
                pre {
                    white-space: break-spaces;
                }
               </style>';
        } else {
            $return = '';
        }

        return $return . '  <table class="debug">
                              <thead class="debug-header"><td colspan="4">'.static::currentFile(1 + $trace_offset) . '@'.static::currentLine(1 + $trace_offset) . '</td></thead>
                              <thead class="debug-columns"><td>'.tr('Key') . '</td><td>'.tr('Type') . '</td><td>'.tr('Size') . '</td><td>'.tr('Value') . '</td></thead>
                              '.static::showHtmlRow($value, $key, $sort, $full_backtrace) . '
                            </table>';
    }


    /**
     * Generates and returns a single HTML line with debug information for the specified value
     *
     * @param mixed $value
     * @param string|null $key
     * @param bool $sort
     * @param bool $full_backtrace
     * @return string
     */
    protected static function showHtmlRow(mixed $value, ?string $key = null, bool $sort = true, bool $full_backtrace = false): string
    {
        if ($key === null) {
            $key = tr('Unknown');
        }

        $type = gettype($value);

        switch ($type) {
            case 'string':
                if (is_numeric($value)) {
                    if (is_integer($value)) {
                        $type = tr('integer');

                    } elseif (is_float($value)) {
                        $type = tr('float');

                    } elseif (is_string($value)) {
                        $type = tr('string');

                    } else {
                        $type = tr('unknown');
                    }

                    $type .= ' ' . tr('(numeric)');

                } else {
                    $type = tr('string');
                }

                // no-break

            case 'integer':
                // no-break

            case 'double':
                return '<tr>
                    <td>'.htmlspecialchars((string) $key) . '</td>
                    <td>' . $type.'</td>
                    <td>'.strlen((string) $value) . '</td>
                    <td class="value">'.nl2br(htmlspecialchars((string) $value)) . '</td>
                </tr>';

            case 'boolean':
                return '<tr>
                    <td>'.htmlspecialchars((string) $key) . '</td>
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
                return '<tr><td>'.htmlspecialchars((string) $key) . '</td>
                    <td>' . $type.'</td>
                    <td>?</td>
                    <td class="value">' . $value.'</td>
                </tr>';

            case 'method':
                // no-break

            case 'property':
                return '<tr><td>'.htmlspecialchars((string) $key) . '</td>
                    <td>' . $type.'</td>
                    <td>'.strlen((string) $value) . '</td>
                    <td class="value">' . $value.'</td>
                </tr>';

            case 'object':
                if (!($value instanceof ArrayableInterface)) {
                    // Format exception nicely
                    if ($value instanceof Throwable) {
                        $value =  static::displayException($value, $full_backtrace, 0);

                    } else {
                        $value  = print_r($value, true);
                        $value  = preg_replace('/-----BEGIN RSA PRIVATE KEY.+?END RSA PRIVATE KEY-----/imus', '*** HIDDEN ***', $value);
                        $value  = preg_replace('/(\[.*?pass.*?\]\s+=>\s+).+/', '$1*** HIDDEN ***', $value);
                    }

                    $return = '<pre>' . $value . '</pre>';

                    return '<tr>
                            <td>' . $key . '</td>
                            <td>' . $type.'</td>
                            <td>(' . tr('Dump size') . ')<br> ' . strlen($return) . '</td>
                            <td>' . $return . '</td>
                        </tr>';
                }

                // This is an object that has a $value::__toArray() method, convert it to array and display it as such
                $value = [
                    ''         => 'Arreable object',
                    'class'    => get_class($value),
                    'contents' => $value->__toArray()
                ];
                // No break

            case 'array':
                $return = '';

                if ($sort) {
                    ksort($value);
                }

                foreach ($value as $subkey => $subvalue) {
                    $return .= static::showHtmlRow($subvalue, (string) $subkey, $sort, $full_backtrace);
                }

                return '<tr>
                    <td>'.htmlspecialchars($key) . '</td>
                    <td>' . $type.'</td>
                    <td>'.count($value) . '</td>
                    <td style="padding:0">
                        <table class="debug">
                            <thead><td>'.tr('Key') . '</td><td>'.tr('Type') . '</td><td>'.tr('Size') . '</td><td>'.tr('Value') . '</td></thead>' . $return.'
                        </table>
                    </td>
                </tr>';

            default:
                return '<tr>
                    <td>' . $key . '</td>
                    <td>' . tr('Unknown') . '</td>
                    <td>???</td>
                    <td class="value">' . htmlspecialchars((string) $value) . '</td>
                </tr>';
        }
    }


    /**
     * Returns displayable information for the specified exception
     *
     * @param Throwable $e
     * @param bool $full_backtrace
     * @param int $indent
     * @return string
     */
    protected static function displayException(Throwable $e, bool $full_backtrace, int $indent): string
    {
        $prefix  = str_repeat(' ', $indent);
        $return  = $prefix . tr('":type" Exception', [':type' => get_class($e)]) . '<br><br>';
        $return .= $prefix . tr('Message: :message', [':message' => $e->getMessage()]) . '<br>';
        $return .= $prefix . tr('Additional messages:') . '<br>';

        if ($e instanceof Exception) {
            $messages = $e->getMessages();

            if ($messages) {
                foreach ($messages as $message) {
                    $return .= $prefix . htmlspecialchars((string) $message) . '<br>';
                }
            } else {
                $return .= $prefix . '-<br>';
            }
        }else {
            $return .= $prefix . '-<br>';
        }

        $return .= '<br>' . $prefix . tr('Location: ') . htmlspecialchars($e->getFile()) . '@' . $e->getLine() . '<br><br>' . $prefix . tr('Backtrace: ') . '<br>';

        foreach (Debug::formatBacktrace($e->getTrace()) as $line) {
            if (!$full_backtrace) {
                if (str_contains($line, 'Phoundation/functions.php@') and str_contains($line, 'include()')) {
                    break;
                }
            }

            $return .= $prefix . htmlspecialchars((string) $line) . '<br>';
        }

        $return .= '<br><br>' . $prefix . tr('Data: ') . '<br>';

        if ($e instanceof Exception) {
            $return .= $prefix . htmlspecialchars((string) str_replace(PHP_EOL, PHP_EOL . $prefix, print_r(not_empty($e->getData(), '-'), true))) . '<br>';

        } else {
            $return .= $prefix . htmlspecialchars('-') . '<br>';
        }

        if ($e->getPrevious()) {
            $return .= tr('Previous exception: ') . '<br>';
            $return .= static::displayException($e->getPrevious(), $full_backtrace, $indent + 4);
        }

        return $return;
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
                    $query = str_replace((string) $key, '"'.(!is_scalar($value) ? ' ['.tr('NOT SCALAR').'] ' : '') . Strings::Log($value) . '"', $query);

                } elseif (is_null($value)) {
                    $query = str_replace((string) $key, ' '.tr('NULL').' ', $query);

                } elseif (is_bool($value)) {
                    $query = str_replace((string) $key, Strings::fromBoolean($value), $query);

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
        if (!Debug::getEnabled()) {
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
        if (!Debug::getEnabled()) {
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
        if (!Debug::getEnabled()) return '';

        $enabled = Config::get('debug.bar.enabled', false);

        if ($enabled === false) {
            return null;
        }

        if ($enabled === 'limited') {
            if (empty($_SESSION['user']['id']) or !Session::getUser()->hasAllRights("debug")) {
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
     * Die when this method has been called the $count specified number of times, and display the optional message
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
            Core::removeShutdownCallback('route[postprocess]');

            exit(Strings::endsWith(str_replace('%count%', $count, $message), PHP_EOL));
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
        if (static::$counter === null) {
            static::$counter = new DebugCounter();
        }

        static::$counter->select($counter);
        return static::$counter;
    }


    /**
     * Dump the specified backtrace data
     *
     * @param array $backtrace The backtrace data
     * @return array The backtrace lines
     */
    public static function formatBackTrace(array $backtrace): array
    {
        $lines   = static::buildBackTrace($backtrace);
        $longest = Arrays::getLongestValueLength($lines, 'call');
        $return  = [];

        // format and write the lines
        foreach ($lines as $line) {
            if (isset($line['call'])) {
                // Resize the call lines to all have the same size for easier log reading
                $line['call'] = Strings::size($line['call'], $longest);
            }

            $return[] = trim(($line['call'] ?? null) . (isset($line['location']) ? (isset($line['call']) ? ' in ' : null) . $line['location'] : null));
        }

        return $return;
    }


    /**
     * Dump the specified backtrace data
     *
     * @param array $backtrace The backtrace data
     * @return array The backtrace lines
     */
    protected static function buildBackTrace(array $backtrace, ?int $display = null): array
    {
        $lines = [];

        if ($display === null) {
            $display = Log::getBacktraceDisplay();
        }

        // Parse backtrace data and build the log lines
        foreach ($backtrace as $step) {
            // We usually don't want to see arguments as that clogs up BADLY
            unset($step['args']);

            // Remove unneeded information depending on the specified display
            switch ($display) {
                case Log::BACKTRACE_DISPLAY_FILE:
                    // Display only file@line information, but ONLY if file@line information is available
                    if (isset($step['file'])) {
                        unset($step['class']);
                        unset($step['function']);
                    }

                    break;

                case Log::BACKTRACE_DISPLAY_FUNCTION:
                    // Display only function / class information
                    unset($step['file']);
                    unset($step['line']);
                    break;

                case Log::BACKTRACE_DISPLAY_BOTH:
                    // Display both function / class and file@line information
                    break;

                default:
                    // Wut? Just display both
                    Log::warning(tr('Unknown $display ":display" specified. Please use one of Log::BACKTRACE_DISPLAY_FILE, Log::BACKTRACE_DISPLAY_FUNCTION, or BACKTRACE_DISPLAY_BOTH', [':display' => $display]));
                    $display = Log::BACKTRACE_DISPLAY_BOTH;
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
                // Remove DIRECTORY_ROOT from the filenames for clarity
                $line['location'] = Strings::from($step['file'], DIRECTORY_ROOT) . '@' . $step['line'];
            }

            if (!$line) {
                // Failed to build backtrace line
                Log::write(tr('Invalid backtrace data encountered, do not know how to process and display the following entry'), 'warning');
                Log::printr($step);
                Log::write(tr('Original backtrace data entry format below'), 'warning');
                Log::printr($step);
            }

            $lines[] = $line;
        }

        return $lines;
    }
}
