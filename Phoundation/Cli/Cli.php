<?php

namespace Phoundation\Cli;

use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;

/**
 * Cli\Cli class
 *
 * This class contains basic Command Line Interface management methods
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 <copyright@capmega.com>
 * @package Phoundation\Cli
 */
class Cli
{
    /**
     * Safe and simple way to get arguments from CLI
     *
     * This function will REMOVE and then return the argument when its found
     * If the argument is not found, $default will be returned
     *
     * @param $keys (NOTE: See $next for what will be returned) If set to a numeric value, the value from $argv[$key]
     *              will be selected. If set as a string value, the $argv key where the value is equal to $key will be
     *              selected. If set specified as an array, all entries in the specified array will be selected.
     * @param $next .When set to true, it REQUIRES that the specified key contains a next argument, and this will be
     *              returned. If set to "all", it will return all following arguments. If set to "optional",
     *              a next argument will be returned, if available.
     * @param string|null $default
     * @return mixed If $next is null, it will return a boolean value, true if the specified key exists, false if not.
     *              If $next is true or "optional", the next value will be returned as a string. However, if "optional"
     *              was used, and the next value was not specified, boolean FALSE will be returned instead. If $next
     *              is specified as all, all subsequent values will be returned in an array
     * @category Function reference
     * @package cli
     *
     * @author Sven Olaf Oostenbrink <sven@zonworks.com>
     */
    public static function argument(string $keys = null, bool $next = false, ?string $default = null)
    {
        global $argv;

        if (is_integer($keys)) {
            if ($next === 'all') {
                foreach ($argv as $argv_key => $argv_value) {
                    if ($argv_key < $keys) {
                        continue;
                    }

                    if ($argv_key == $keys) {
                        unset($argv[$keys]);
                        continue;
                    }

                    if (substr($argv_value, 0, 1) == '-') {
                        //Encountered a new option, stop!
                        break;
                    }

                    //Add this argument to the list
                    $value[] = $argv_value;
                    unset($argv[$argv_key]);
                }

                return isset_get($value);
            }

            if (isset($argv[$keys++])) {
                $argument = $argv[$keys - 1];
                unset($argv[$keys - 1]);
                return $argument;
            }

            //No arguments found (except perhaps for test or force)
            return $default;
        }

        if ($keys === null) {
            $value = array_shift($argv);
            $value = Strings::startsNot((string)$value, '-');
            return $value;
        }

        //Detect multiple key options for the same command, but ensure only one is specified
        if (is_array($keys) || (is_string($keys) && strstr($keys, ','))) {
            $keys = Arrays::force($keys);
            $results = array();

            foreach ($keys as $key) {
                if ($next === 'all') {
                    //We're requesting all values for all specified keys.
                    //It will return null in case the specified key does not exist
                    $value = static::argument($key, 'all', null);

                    if (is_array($value)) {
                        $found = true;
                        $results = array_merge($results, $value);
                    }
                } else {
                    $value = static::argument($key, $next, null);

                    if ($value) {
                        $results[$key] = $value;
                    }
                }
            }

            if (($next === 'all') && isset($found)) {
                return $results;
            }

            switch (count($results)) {
                case 0:
                    return $default;

                case 1:
                    return current($results);

                default:
                    //Multiple command line options were specified, this is not allowed!
                    throw new CliScriptException(
                        'Multiple command line arguments "' . Strings::log($results) . '" for the same option specified. Please specify only one'
                    );
            }
        }

        if (($key = array_search($keys, $argv)) === false) {
            //Specified argument not found
            return $default;
        }

        if ($next) {
            if ($next === 'all') {
                //Return all following arguments, if available, until the next option
                $value = array();

                foreach ($argv as $argv_key => $argv_value) {
                    if (empty($start)) {
                        if ($argv_value == $keys) {
                            $start = true;
                            unset($argv[$argv_key]);
                        }

                        continue;
                    }

                    if (substr($argv_value, 0, 1) == '-') {
                        //Encountered a new option, stop!
                        break;
                    }

                    //Add this argument to the list
                    $value[] = $argv_value;
                    unset($argv[$argv_key]);
                }

                return $value;
            }

            // Return next argument, if available
            $value = null;

            try {
                $value = Arrays::nextValue($argv, $keys, true);
            } catch (NotExistsException $e) {
                if ($e->getCode() == 'invalid') {
                    if ($next !== 'optional') {
                        //This argument requires another parameter
                        throw $e->setCode('missing-arguments');
                    }

                    $value = false;
                }
            }

            if (substr($value, 0, 1) == '-') {
                throw new OutOfBoundsException(
                    'Argument "' . Strings::log($keys) . '" has no assigned value. It is immediately followed by argument "' . $value . '"',
                    [
                        'keys' => Strings::log($keys)
                    ]
                );
            }

            return $value;
        }

        unset($argv[$key]);
        return true;
    }



    /**
     * Safe and simple way to get arguments from CLI
     *
     * This function will REMOVE and then return the argument when its found
     * If the argument is not found, $default will be returned
     *
     * @param $keys (NOTE: See $next for what will be returned) If set to a numeric value, the value from $argv[$key]
     *              will be selected. If set as a string value, the $argv key where the value is equal to $key will be
     *              selected. If set specified as an array, all entries in the specified array will be selected.
     * @param $next .When set to true, it REQUIRES that the specified key contains a next argument, and this will be
     *              returned. If set to "all", it will return all following arguments. If set to "optional",
     *              a next argument will be returned, if available.
     * @param string|null $default
     * @return mixed If $next is null, it will return a boolean value, true if the specified key exists, false if not.
     *              If $next is true or "optional", the next value will be returned as a string. However, if "optional"
     *              was used, and the next value was not specified, boolean FALSE will be returned instead. If $next
     *              is specified as all, all subsequent values will be returned in an array
     * @category Function reference
     * @package cli
     *
     * @author Sven Olaf Oostenbrink <sven@zonworks.com>
     */
    public static function argument(string $keys = null, bool $next = false, ?string $default = null)
    {
        global $argv;

        if (is_integer($keys)) {
            if ($next === 'all') {
                foreach ($argv as $argv_key => $argv_value) {
                    if ($argv_key < $keys) {
                        continue;
                    }

                    if ($argv_key == $keys) {
                        unset($argv[$keys]);
                        continue;
                    }

                    if (substr($argv_value, 0, 1) == '-') {
                        //Encountered a new option, stop!
                        break;
                    }

                    //Add this argument to the list
                    $value[] = $argv_value;
                    unset($argv[$argv_key]);
                }

                return isset_get($value);
            }

            if (isset($argv[$keys++])) {
                $argument = $argv[$keys - 1];
                unset($argv[$keys - 1]);
                return $argument;
            }

            //No arguments found (except perhaps for test or force)
            return $default;
        }

        if ($keys === null) {
            $value = array_shift($argv);
            $value = Strings::startsNot((string)$value, '-');
            return $value;
        }

        //Detect multiple key options for the same command, but ensure only one is specified
        if (is_array($keys) || (is_string($keys) && strstr($keys, ','))) {
            $keys = Arrays::force($keys);
            $results = array();

            foreach ($keys as $key) {
                if ($next === 'all') {
                    //We're requesting all values for all specified keys.
                    //It will return null in case the specified key does not exist
                    $value = static::argument($key, 'all', null);

                    if (is_array($value)) {
                        $found = true;
                        $results = array_merge($results, $value);
                    }
                } else {
                    $value = static::argument($key, $next, null);

                    if ($value) {
                        $results[$key] = $value;
                    }
                }
            }

            if (($next === 'all') && isset($found)) {
                return $results;
            }

            switch (count($results)) {
                case 0:
                    return $default;

                case 1:
                    return current($results);

                default:
                    //Multiple command line options were specified, this is not allowed!
                    throw new CliScriptException(
                        'Multiple command line arguments "' . Strings::log($results) . '" for the same option specified. Please specify only one'
                    );
            }
        }

        if (($key = array_search($keys, $argv)) === false) {
            //Specified argument not found
            return $default;
        }

        if ($next) {
            if ($next === 'all') {
                //Return all following arguments, if available, until the next option
                $value = array();

                foreach ($argv as $argv_key => $argv_value) {
                    if (empty($start)) {
                        if ($argv_value == $keys) {
                            $start = true;
                            unset($argv[$argv_key]);
                        }

                        continue;
                    }

                    if (substr($argv_value, 0, 1) == '-') {
                        //Encountered a new option, stop!
                        break;
                    }

                    //Add this argument to the list
                    $value[] = $argv_value;
                    unset($argv[$argv_key]);
                }

                return $value;
            }

            // Return next argument, if available
            $value = null;

            try {
                $value = Arrays::nextValue($argv, $keys, true);
            } catch (NotExistsException $e) {
                if ($e->getCode() == 'invalid') {
                    if ($next !== 'optional') {
                        //This argument requires another parameter
                        throw $e->setCode('missing-arguments');
                    }

                    $value = false;
                }
            }

            if (substr($value, 0, 1) == '-') {
                throw new OutOfBoundsException(
                    'Argument "' . Strings::log($keys) . '" has no assigned value. It is immediately followed by argument "' . $value . '"',
                    [
                        'keys' => Strings::log($keys)
                    ]
                );
            }

            return $value;
        }

        unset($argv[$key]);
        return true;
    }


    /*
     * Show a dot on the console each $each call if $each is false, "DONE" will be printed, with next line. Internal counter will reset if a different $each is received.
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @note While log_console() will log towards the ROOT/data/log/ log files, cli_dot() will only log one single dot even though on the command line multiple dots may be shown
     * @see log_console()
     * @example
     * code
     * for($i=0; $i < 100; $i++){
     *     cli_dot();
     * }
     * /code
     *
     * This will return something like
     *
     * code
     * ..........
     * /code
     *
     * @param natural $each
     * @param string $color
     * @param string $dot
     * @param boolean $quiet
     * @return boolean True if a dot was printed, false if not
     */
    public function dot($each = 10, $color = 'green', $dot = '.', $quiet = false)
    {
        static $count = 0,
        $l_each = 0;

        try {
            if (!PLATFORM_CLI) {
                return false;
            }

            if ($quiet and QUIET) {
                /*
                 * Don't show this in QUIET mode
                 */
                return false;
            }

            if ($each === false) {
                if ($count) {
                    /*
                     * Only show "Done" if we have shown any dot at all
                     */
                    log_console(tr('Done'), $color);

                } else {
                    log_console('');
                }

                $l_each = 0;
                $count = 0;
                return true;
            }

            $count++;

            if ($l_each != $each) {
                $l_each = $each;
                $count = 0;
            }

            if ($count >= $l_each) {
                $count = 0;
                log_console($dot, $color, false);
                return true;
            }

        } catch (Exception $e) {
            throw new OutOfBoundsException('cli_dot(): Failed', $e);
        }
    }



    /*
     * Return the specified string in the specified color
     */
    function cli_color($string, $fore_color = null, $back_color = null, $force = false, $reset = true)
    {
        try {
            static $color;

            if (!$color) {
                $color = new Colors();
            }

            return $color->getColoredString($string, $fore_color, $back_color, $force, $reset);

        } catch (Exception $e) {
            throw new OutOfBoundsException('cli_color(): Failed', $e);
        }
    }


    /*
     * Return or echo CLI code to reset all colors
     */
    function cli_reset_color($echo = false)
    {
        try {
            if (!$echo) {
                return "\033[0m";
            }

            echo "\033[0m";

        } catch (Exception $e) {
            throw new OutOfBoundsException('cli_reset_color(): Failed', $e);
        }
    }


}
