<?php

namespace Phoundation\Cli;

use CliColorException;

/**
 * Cli\Color class
 *
 * This class manages color usage on the Linux Command Line Interface
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2021 <copyright@capmega.com>
 * @package Phoundation\Cli
 */

class Color
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
     * @param string $string
     * @param string|null $foreground_color
     * @param string|null $background_color
     * @return string
     */
    public function apply(string $string, ?string $foreground_color, ?string $background_color = null): string
    {
        $retval = '';

        // Validate the specified foreground and background colors
        if (!array_key_exists($foreground_color, self::$available_foreground_colors)) {
            throw new CliColorException('The specified foreground color "' . $foreground_color . '" does not exist');
        }

        if (!array_key_exists($background_color, self::$available_background_colors)) {
            throw new CliColorException('The specified background color "' . $background_color . '" does not exist');
        }

        // Apply colors
        $retval .= "\033[" . self::$available_foreground_colors[$foreground_color] . "m";
        $retval .= "\033[" . self::$available_background_colors[$background_color] . "m";

        // Add the specified string that should be colored and the coloring end-tag
        $retval .= $string . "\033[0m";

        return $retval;
    }
}
