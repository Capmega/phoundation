<?php

namespace Phoundation\Cli;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Numbers;
use Phoundation\Core\Strings;
use Phoundation\Date\Time;
use Phoundation\Developer\Debug;
use Phoundation\Exception\Exceptions;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Processes\Commands;
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
     * The exit code for this process
     *
     * @var int $exit_code
     */
    protected static int $exit_code = 0;

    /**
     * The command line arguments
     *
     * @var array|null $argv
     */
    protected static ?array $argv = null;



    /**
     * Execute a command by the "cli" script
     *
     * @param array $argv The PHP $argv
     * @return void
     */
    public static function execute(array $argv): void
    {
Debug::enabled(true);
        // Backup the command line arguments
        self::$argv = $GLOBALS['argv'];

        // All scripts will execute the cli_done() call, register basic script information
        Core::startup();
        Core::registerShutdown('cli_done');

        if (count($argv) <= 1) {
            throw Exceptions::OutOfBoundsException('No method specified!')->makeWarning();
        }

        // Get the script file to execute
        $script = self::findScript($argv);
show($script);
showdie('AAAAAAAAAAAAAAAAAAAAAA');

        Core::writeRegister($script, 'real_script');
        Core::writeRegister(Strings::fromReverse($script, '/'), 'script');

        // Execute the script
        self::executeScript($script, $argv);
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

show($file);
showdie($arguments);

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
     * Script execution has finished
     *
     * @param int|null $exit_code
     * @param string|null $exit_message
     * @return void
     */
    #[NoReturn] public static function die(?int $exit_code = null, ?string $exit_message = null): void
    {
        if (!$exit_code) {
            Scripts::setExitCode($exit_code, true);
        }

        // Execute all shutdown functions
        Core::shutdown($exit_code);

        if (!QUIET) {
            if ($exit_code) {
                if ($exit_code > 200) {
                    if ($exit_message) {
                        Log::warning($exit_message);
                    }

                    // Script ended with warning
                    Log::warning(tr('Script ":script" ended with exit code ":exitcode" warning in ":time" with ":usage" peak memory usage', [':script' => Core::readRegister('system', 'script'), ':time' => Time::difference(STARTTIME, microtime(true), 'auto', 5), ':usage' => Numbers::bytes(memory_get_peak_usage()), ':exitcode' => $exit_code]));

                } else {
                    if ($exit_message) {
                        Log::error($exit_message);
                    }

                    // Script ended with error
                    Log::error(tr('Script ":script" failed with exit code ":exitcode" in ":time" with ":usage" peak memory usage', [':script' => Core::readRegister('system', 'script'), ':time' => Time::difference(STARTTIME, microtime(true), 'auto', 5), ':usage' => Numbers::bytes(memory_get_peak_usage()), ':exitcode' => $exit_code]));
                }

            } else {
                if ($exit_message) {
                    Log::success($exit_message);
                }

                // Script ended successfully
                Log::success(tr('Finished ":script" script in ":time" with ":usage" peak memory usage', [':script' => Core::readRegister('system', 'script'), ':time' => Time::difference(STARTTIME, microtime(true), 'auto', 5), ':usage' => Numbers::bytes(memory_get_peak_usage())]), 'green');
            }
        }

        die($exit_code);
    }



    /**
     * Returns the process exit code
     *
     * @return int
     */
    public static function getExitCode(): int
    {
        return self::$exit_code;
    }



    /**
     * Sets the process exit code
     *
     * @param int $code
     * @param bool $only_if_null
     * @return void
     */
    public static function setExitCode(int $code, bool $only_if_null = false): void
    {
        if (($code < 0) or ($code > 255)) {
            throw new OutOfBoundsException(tr('Invalid exit code ":code" specified, it should be a positive integer value between 0 and 255', [':code' => $code]));
        }

        if (!$only_if_null or !self::$exit_code) {
            self::$exit_code = $code;
        }
    }



    /**
     * Returns the UID for the current process
     */
    public static function getProcessUid(): int
    {
        if (function_exists('posix_getuid')) {
            return posix_getuid();
        }

        $results = Commands::id(array('-u'));
        $results = array_pop($results);

        return $results;
    }
}