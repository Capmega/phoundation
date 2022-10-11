<?php

namespace Phoundation\Cli;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Cli\Exception\CliInvalidArgumentsException;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Numbers;
use Phoundation\Core\Strings;
use Phoundation\Date\Time;
use Phoundation\Exception\Exceptions;
use Phoundation\Exception\OutOfBoundsException;
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
     * The arguments given to the executed script
     *
     * @var array|null $arguments
     */
    protected static ?array $arguments = null;

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
     * @throws Throwable
     */
    public static function execute(array $argv): void
    {
        // Backup the command line arguments
        self::$argv      = $GLOBALS['argv'];
        self::$arguments = $GLOBALS['argv'];

        // All scripts will execute the cli_done() call, register basic script information
        Core::startup();
        Core::registerShutdown('cli_done');

        if (count(self::$argv) <= 1) {
            throw Exceptions::OutOfBoundsException('No method specified!')->makeWarning();
        }

        // Get the script file to execute
        $file = self::findScript();

        Core::writeRegister($file, 'script_file');
        Core::writeRegister(Strings::fromReverse($file, '/'), 'script');

        // Execute the script
        execute_script($file, self::$arguments);
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

        $results = Commands::id(['-u']);
        $results = array_pop($results);

        return $results;
    }



    /**
     * Ensures that no other command line arguments are left.
     *
     * If arguments were still found, an appropriate exceptoin will be thrown
     *
     * @param array $arguments
     * @return void
     */
    public static function noArgumentsLeft(array $arguments): void
    {
        if (!$arguments) {
            return;
        }

        throw Exceptions::CliInvalidArgumentsException(tr('Invalid arguments ":arguments" encountered', [':arguments' => Strings::force($arguments, ', ')]))->makeWarning();
    }



    /**
     * Find the script to execute from the given arguments
     *
     * @return string
     */
    protected static function findScript(): string
    {
        $file = ROOT . 'scripts/';

        foreach (self::$arguments as $position => $argument) {
            if (!$position) {
                unset(self::$arguments[$position]);
                continue;
            }

            if (str_ends_with($argument, '/cli')) {
                // This is the cli command, ignore it
                unset(self::$arguments[$position]);
                continue;
            }

            if (!ctype_alnum($argument)) {
                // Methods can only have alphanumeric characters
                throw Exceptions::OutOfBoundsException(tr('The specified method ":method" contains non alphanumeric characters which is not allowed', [':method' => $argument]))->makeWarning();
            }

            // Start processing arguments as methods here
            $file .= $argument;
            unset(self::$arguments[$position]);

            if (!file_exists($file)) {
                // The specified path doesn't exist
                throw Exceptions::MethodNotFoundException(tr('The specified method file ":file" was not found', [':file' => $file]))->makeWarning();
            }

            if (!is_dir($file)) {
                // This is a file, should be PHP, found it! Update the arguments to remove all methods from them.
                return $file;
            }

            // THis is a directory, continue scanning
            $file .= '/';
        }

        throw Exceptions::MethodNotFoundException(tr('The specified method file ":file" was not found', [':file' => $file]))->makeWarning();
    }
}