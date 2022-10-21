<?php

namespace Phoundation\Cli;

use Phoundation\Cli\Exception\CliException;
use Phoundation\Core\Arrays;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;



/**
 * Cli\Cli class
 *
 * This class contains basic Command Line Interface management methods
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Cli
 */
class Cli
{
    /**
     * Returns the terminal available for this process
     *
     * @return string
     */
    public static function getTerm(): string
    {
            $term = exec('echo $TERM');
            return $term;
    }



    /**
     * Returns the columns for this terminal
     *
     * @note Returns -1 in case the columns could not be determined
     * @return int
     */
    public static function getColumns(): int
    {
        $cols = exec('tput cols');

        if (is_numeric($cols)) {
            return (int) $cols;
        }

        return -1;
    }



    /**
     * Returns the rows for this terminal
     *
     * @note Returns -1 in case the columns could not be determined
     * @return int
     */
    public static function getLines(): int
    {
        $cols = exec('tput lines');

        if (is_numeric($cols)) {
            return (int) $cols;
        }

        return -1;
    }
}