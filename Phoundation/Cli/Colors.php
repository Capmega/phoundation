<?php

namespace Phoundation\Cli;

use CliColorsException;

/**
 * Cli\Colors class
 *
 * This class manages color usage on the Linux Command Line Interface
 * Taken from http://www.if-not-true-then-false.com/2010/php-class-for-coloring-php-command-line-cli-scripts-output-php-output-colorizing-using-bash-shell-colors/
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Cli
 */

class Colors
{
    /**
     * The supported foreground colors
     *
     * @var array $available_foreground_colors
     */
    protected static array $available_foreground_colors = [
        'black' => '0;30',
        'dark_gray' => '1;30',
        'blue' => '0;34',
        'light_blue' => '1;34',
        'green' => '0;32',
        'light_green' => '1;32',
        'cyan' => '0;36',
        'light_cyan' => '1;36',
        'red' => '0;31',
        'light_red' => '1;31',
        'purple' => '0;35',
        'light_purple' => '1;35',
        'brown' => '0;33',
        'yellow' => '1;33',
        'light_gray' => '0;37',
        'white' => '1;37'
    ];



    /**
     * The supported background colors
     *
     * @var array $available_background_colors
     */
    protected static array $available_background_colors = [
        '' => '40',
        'black' => '40',
        'red' => '41',
        'green' => '42',
        'yellow' => '43',
        'blue' => '44',
        'magenta' => '45',
        'cyan' => '46',
        'light_gray' => '47'
    ];



    /**
     * Apply the specified foreground color and background color to the specified text string
     *
     * @param string $source
     * @param string|null $foreground_color
     * @param string|null $background_color
     * @param bool $reset
     * @return string
     */
    public static function apply(string $source, ?string $foreground_color, ?string $background_color = null, bool $reset = true): string
    {
        if (NOCOLOR) {
            /*
             * Do NOT apply color
             */
            return $source;
        }

        $retval = '';

        // Validate the specified foreground and background colors
        if (!array_key_exists($foreground_color, self::$available_foreground_colors)) {
            throw new CliColorsException('The specified foreground color "' . $foreground_color . '" does not exist');
        }

        if (!array_key_exists($background_color, self::$available_background_colors)) {
            throw new CliColorsException('The specified background color "' . $background_color . '" does not exist');
        }

        // Apply colors
        $retval .= "\033[" . self::$available_foreground_colors[$foreground_color] . "m";
        $retval .= "\033[" . self::$available_background_colors[$background_color] . "m";

        // Add the specified string that should be colored and the coloring end-tag
        $retval .= $source . self::getColorReset();

        return $retval;
    }



    /**
     * Returns all foreground color names
     *
     * @return array
     */
    public static function getForegroundColors(): array
    {
        return array_keys(self::$available_foreground_colors);
    }



    /**
     * Returns all background color names
     *
     * @return array
     */
    public static function getBackgroundColors(): array
    {
        return array_keys(self::$available_background_colors);
    }



    /**
     * Returns all background color names
     *
     * @return string
     */
    public static function getColorReset(): string
    {
        return '"\033[0m"';
    }
}


