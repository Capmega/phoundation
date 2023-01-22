<?php

namespace Phoundation\Cli;

use Phoundation\Core\Log;

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
            Log::information(tr('Command help:'));
            Log::notice($help);
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
            Log::information(tr('Command usage:'));
            Log::notice($usage);
            Script::die();
        }
    }
}