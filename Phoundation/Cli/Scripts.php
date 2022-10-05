<?php

namespace Phoundation\Cli;

use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Throwable;



/**
 * Class Mc
 *
 * This is the default Scripts object
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Cli
 */
class Scripts
{
    /**
     * Execute a command by the "cli" script
     *
     * @param array $argv The PHP $argv
     * @return void
     */
    public static function executeCommand(array $argv): void
    {
        try {
            // All scripts will execute the cli_done() call, register basic script information
            Core::registerShutdown('cli_done');

            if (count($argv) <= 1) {
                throw new OutOfBoundsException('No method specified!');
            }

            // Get the script file to execute
            $script = self::findScript($argv);

            Core::writeRegister($script, 'real_script');
            Core::writeRegister(Strings::fromReverse($script, '/'), 'script');

            // Execute the script
            self::executeScript($script, $argv);

        } catch (Throwable $e) {
            // Something, anything went wrong with the execution of this script.
            self::handleException($e);
        }
    }



    /**
     * Find the script to execute from the given arguments
     *
     * @param array $arguments
     * @return string
     */
    protected static function findScript(array $arguments): string
    {
        $file = ROOT . 'scripts/';

        foreach ($arguments as $argument) {
            if (str_ends_with($argument, 'php')) {
                // This is the PHP command, ignore it
                continue;
            }

            $file = $file . $argument;

            File::checkReadable($file);

            if (is_dir($file)) {
                // Subdirectory, lets recurse in there
                continue;
            }

            if (is_file($file)) {
                // Yay, this is a regular file we can execute!
                return $file;
            }

            // What is this?
        }
    }



    /**
     * Execute the specified script
     *
     * @param string $script
     * @param array $argv
     * @return void
     */
    protected static function executeScript(string $script, array $argv): void
    {
        include($script);
    }



    /**
     * Handle the script exception
     *
     * @param Throwable $e
     * @return void
     */
    protected static function handleException(Throwable $e): void
    {
        Cli::setExitCode($e->getCode());
    }



    /**
     * Script execution has finished
     *
     * @return void
     */
    protected static function done(): void
    {
        $exit_code = Cli::getExitCode();

        // Execute all shutdown functions
        Core::shutdown();

        if (!QUIET){
            if ($exit_code) {
                if ($exit_code > 200){
                    /*
                     * Script ended with warning
                     */
                    Log::warning(tr('Script ":script" ended with exit code ":exitcode" warning in :time with ":usage" peak memory usage', [':script' => Core::register('script'), ':time' => time_difference(STARTTIME, microtime(true), 'auto', 5), ':usage' => bytes(memory_get_peak_usage()), ':exitcode' => $exit_code]));

                } else {
                    Log::error(tr('Script ":script" failed with exit code ":exitcode" in :time with ":usage" peak memory usage', [':script' => Core::register('script'), ':time' => time_difference(STARTTIME, microtime(true), 'auto', 5), ':usage' => bytes(memory_get_peak_usage()), ':exitcode' => $exit_code]));
                }

            } else {
                Log::success(tr('Finished ":script" script in :time with ":usage" peak memory usage', [':script' => Core::register('script'), ':time' => time_difference(STARTTIME, microtime(true), 'auto', 5), ':usage' => bytes(memory_get_peak_usage())]), 'green');
            }
        }

        die($exit_code);
    }
}