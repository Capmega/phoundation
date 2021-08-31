<?php

use Phoundation\Core\Config;

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

        foreach(debug_backtrace() as $key => $value){
            if($skip_own and ($key <= 1)){
                continue;
            }

            foreach($remove_sections as $section){
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

        if(!isset($backtrace[$trace + 1])){
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

        if(!isset($backtrace[$trace + 1])){
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

        if(!isset($backtrace[$trace + 1])){
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

        if(!isset($backtrace[$trace + 1])){
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
     */
    public static function show(mixed $value): void
    {

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
        self::show($value);
        die();
    }
}