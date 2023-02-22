<?php

namespace Phoundation\Cli;

use Phoundation\Core\Log\Log;

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
     * The auto complete processor
     *
     * @var AutoComplete $auto_complete
     */
    protected static AutoComplete $auto_complete;


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
    public function autoComplete(array $definitions): void
    {
        if (!isset(self::$auto_complete)) {
            return;
        }

        // Process the auto complete definitions
        foreach ($definitions as $word => $data) {

        }
    }



    /**
     * Enable the auto complete mode
     *
     * @param AutoComplete $auto_complete
     * @return void
     */
    public static function enableAutoComplete(AutoComplete $auto_complete): void
    {
        self::$auto_complete = $auto_complete;
    }
}