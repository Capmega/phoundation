<?php

declare(strict_types=1);

namespace Phoundation\Cli;

use Phoundation\Core\Log\Log;
use Throwable;


/**
 * Class Documentation
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * Displays the help text
     *
     * @param string $help
     * @return void
     */
    public static function help(string $help): void
    {
        global $argv;

        if (isset_get($argv['help'])) {
            Log::information(tr('Command help:'), 9, use_prefix: false);
            Log::notice(trim($help), 10, false, use_prefix: false);
            exit();
        }
    }


    /**
     * Displays the usage text
     *
     * @param string $usage
     * @return void
     */
    public static function usage(string $usage): void
    {
        global $argv;

        if (isset_get($argv['usage'])) {
            Log::information(tr('Command usage:'), 9, use_prefix: false);
            Log::notice(trim($usage) . PHP_EOL, 10, false, use_prefix: false);
            exit();
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
        try {
            if (AutoComplete::isActive()) {
                AutoComplete::processScriptPositions(isset_get($definitions['positions']));
                AutoComplete::processScriptArguments(isset_get($definitions['arguments']));
                exit();
            }

        } catch (Throwable $e) {
            Log::error($e, echo_screen: false);
            exit('Autocomplete-failure-see-system-log');
        }
    }
}
