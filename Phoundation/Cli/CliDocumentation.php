<?php

/**
 * Class Documentation
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cli
 */


declare(strict_types=1);

namespace Phoundation\Cli;

use Phoundation\Core\Log\Log;
use Throwable;


class CliDocumentation
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
     * @param bool   $exit
     *
     * @return void
     */
    public static function setHelp(string $help, bool $exit = true): void
    {
        global $argv;

        if (array_get_safe($argv, 'help')) {
            Log::information(ts('Command help:'), 9);
            Log::notice(trim($help), 10, false);

            if ($exit) {
                exit();
            }
        }
    }


    /**
     * Displays the usage text
     *
     * @param string $usage
     * @param bool   $exit
     *
     * @return void
     */
    public static function setUsage(string $usage, bool $exit = true): void
    {
        global $argv;

        if (array_get_safe($argv, 'usage')) {
            Log::information(ts('Command usage:'), 9, echo_prefix: false);
            Log::notice(trim($usage) . PHP_EOL, 10, false, echo_prefix: false);

            if ($exit) {
                exit();
            }
        }
    }


    /**
     * Process auto complete requests specific for this method
     *
     * @param array|null $_definitions
     *
     * @return void
     */
    public static function setAutoComplete(?array $_definitions = null): void
    {
        try {
            if (CliAutoComplete::isActive()) {
                CliAutoComplete::processCommandPositions(array_get_safe($_definitions, 'positions'));
                CliAutoComplete::processCommandArguments(array_get_safe($_definitions, 'arguments'));
                exit();
            }

        } catch (Throwable $e) {
            Log::error($e, echo_screen: false);
            echo 'autocomplete-failed-see-system-log';
            exit(1);
        }
    }
}
