<?php

namespace Phoundation\Cli;

use Phoundation\Core\Log\Log;


/**
 * Class Documentation
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Cli
 */
class Documentation
{
    /**
     * Show help text?
     *
     * @var bool $help
     */
    protected static bool $help = false;

    /**
     * Show usage text?
     *
     * @var bool $usage
     */
    protected static bool $usage = false;


    /**
     * Returns the help text
     *
     * @param string $help
     * @return void
     */
    public static function help(string $help): void
    {
        global $argv;

        if (isset_get($argv['help'])) {
            Log::information(tr('Command help:'), 8);
            Log::notice(trim($help) . PHP_EOL, 10, false);
            Script::die();
        }
    }



    /**
     * Sets the usage text
     *
     * @param string $usage
     * @return void
     */
    public static function usage(string $usage): void
    {
        global $argv;

        if (isset_get($argv['usage'])) {
            Log::information(tr('Command usage:'), 8);
            Log::notice(trim($usage) . PHP_EOL, 10, false);
            Script::die();
        }
    }



    /**
     * Process auto complete requests specific for this method
     *
     * @param array $definitions
     * @return void
     */
    public static function autoComplete(array $definitions): void
    {
        if (AutoComplete::isActive()) {
            AutoComplete::processScript($definitions);
        }
    }
}