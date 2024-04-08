<?php

declare(strict_types=1);

namespace Phoundation\Cli;

use Phoundation\Cli\Exception\CliNoTtyException;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;

/**
 * Cli\Cli class
 *
 * This class contains basic Command Line Interface management methods
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cli
 */
class Cli
{
    /**
     * If true, passwords will be visible on the CLI
     *
     * @var bool $show_passwords
     */
    protected static bool $show_passwords = false;


    /**
     * Sets & returns if passwords are shown or not.
     *
     * @param bool|null $show_passwords
     *
     * @return bool
     */
    public static function showPasswords(?bool $show_passwords = null): bool
    {
        if ($show_passwords !== null) {
            static::$show_passwords = $show_passwords;
        }

        return static::$show_passwords;
    }


    /**
     * Returns the terminal available for this process
     *
     * @return string
     */
    public static function getTerm(): string
    {
        return exec('echo $TERM');
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


    /**
     * Display the data in the specified source array in a neat looking table
     *
     * @note The required format for the source is as follows:
     *       $source[$id] = [$column1 => $value1, $column2 => $value2, ...];
     *
     * @param IteratorInterface|array $source
     * @param array|string|null       $headers
     * @param string|null             $id_column
     * @param int                     $column_spacing
     *
     * @return void
     */
    public static function displayTable(IteratorInterface|array $source, array|string|null $headers = null, ?string $id_column = 'id', int $column_spacing = 2): void
    {
        if (is_object($source)) {
            // This is an Iterator object, get the array source
            $source = $source->getSource();
        }
        if (!is_natural($column_spacing)) {
            throw new OutOfBoundsException(tr('Invalid column spacing ":spacing" specified, please ensure it is a natural number, 1 or higher', [
                ':spacing' => $column_spacing,
            ]));
        }
        if ($source) {
            // Determine the size of the keys to display them
            $column_sizes = Arrays::getLongestStringPerColumn($source, 2, $id_column);
            // Get headers from id_column and row columns and reformat them for displaying
            if ($headers === null) {
                $value   = str_replace([
                    '_',
                    '-',
                ], ' ', (string) $id_column);
                $value   = Strings::capitalize($value) . ':';
                $headers = ($id_column ? [$id_column => $value] : []);
                $row     = current($source);
                $exists  = false;
                foreach (Arrays::force($row, null) as $header => $value) {
                    $value = str_replace([
                        '_',
                        '-',
                    ], ' ', (string) $header);
                    $value = Strings::capitalize($value) . ':';
                    $headers[$header] = $value;
                    if ($header === $id_column) {
                        $exists = true;
                    }
                }
                if (!$exists) {
                    // The specified ID column doesn't exist in the rows, remove it
                    unset($headers[$id_column]);
                }

            } else {
                // Validate and clean headers
                $headers = static::cleanHeaders($headers);
            }
            // Display header?
            if (!VERY_QUIET) {
                foreach (Arrays::force($headers) as $column => $header) {
                    $column_sizes[$column] = Numbers::getHighest($column_sizes[$column], strlen($header));
                    Log::cli(CliColor::apply(Strings::size((string) $header, $column_sizes[$column]), 'white') . Strings::size(' ', $column_spacing), 10, false, false);
                }
                Log::cli(' ');
            }
            // Display source
            foreach ($source as $id => $row) {
                $row = Arrays::force($row, null);
                if ($id_column) {
                    array_unshift($row, $id);
                }
                // Display all row cells
                foreach ($headers as $column => $label) {
                    $value = isset_get($row[$column]);
                    if ($column === 'status') {
                        $value = DataEntry::getHumanReadableStatus($value);
                    }
                    if (is_numeric($column) or array_key_exists($column, $headers)) {
                        Log::cli(Strings::size((string) $value, $column_sizes[$column], ' ', is_numeric($value)) . Strings::size(' ', $column_spacing), 10, false, false);
                    }
                }
                Log::cli(' ');
            }
        } else {
            // Oops, empty source!
            Log::warning(tr('No results'));
        }
    }


    /**
     * Returns cleaned headers from the specified headers value
     *
     * @param array|string $headers
     *
     * @return array
     */
    protected static function cleanHeaders(array|string $headers): array
    {
        $headers = Arrays::force($headers);
        $return  = [];
        foreach (Arrays::force($headers) as $column => $header) {
            if (is_numeric($column)) {
                // Headers were assigned only a label, which will be the column name
                $column = $header;
                $header = str_replace([
                    '_',
                    '-',
                ], ' ', (string) $header);
                $header = Strings::capitalize($header) . ':';
            }
            $return[$column] = $header;
        }

        return $return;
    }


    /**
     * Display the data in the specified source array in a neat looking form
     *
     * @param array       $source
     * @param string|null $key_header
     * @param string|null $value_header
     * @param int         $offset If specified, the text will be set $offset number of characters to the right
     *
     * @return void
     */
    public static function displayForm(array $source, ?string $key_header = null, string $value_header = null, int $offset = 0): void
    {
        // Validate arguments
        if ($offset < 0) {
            throw new OutOfBoundsException(tr('Invalid offset ":offset" specified, it should be 0 or higher', [
                ':offset' => $offset,
            ]));
        }
        if ($key_header === null) {
            $key_header = tr('Keys:');
        }
        if ($value_header === null) {
            $value_header = tr('Values:');
        }
        // Determine the size of the keys to display them
        $key_size = Arrays::getLongestKeyLength($source) + 4;
        // Display header
        if ($key_header and $value_header) {
            Log::cli(CliColor::apply(Strings::size(' ', $offset) . Strings::size($key_header, $key_size), 'white') . ' ' . $value_header);
        }
        // Display source
        foreach ($source as $key => $value) {
            $key = str_replace([
                '_',
                '-',
            ], ' ', $key);
            $key = Strings::capitalize($key) . ':';
            if (!is_scalar($value)) {
                if (is_object($value)) {
                    // Yeah, how to display this? Try to cast to array, hope for the best.
                    $value = (array) $value;
                }
                if (is_array($value)) {
                    Log::cli(CliColor::apply(Strings::size(' ', $offset) . Strings::size($key, $key_size), 'white'));
                    static::displayForm($value, '', '', $key_size + 1);
                    continue;
                }
                // This is likely a resource or something
                $value = gettype($value);
            }
            if ($key === 'status') {
                $value = DataEntry::getHumanReadableStatus($value);
            }
            Log::cli(CliColor::apply(Strings::size(' ', $offset) . Strings::size($key, $key_size), 'white') . ' ' . $value);
        }
    }


    /**
     * Read a password from the command line prompt
     *
     * @param string $prompt
     *
     * @return string|null
     */
    public static function readPassword(string $prompt): ?string
    {
        static::checkTty(STDIN, 'stdin');
        if (static::$show_passwords) {
            // We show passwords!
            return static::readInput($prompt);
        }
        echo trim($prompt) . ' ';
        system('stty -echo');
        $return = trim(fgets(STDIN));
        system('stty echo');
        echo PHP_EOL;

        return $return;
    }


    /**
     * Checks if we have a TTY and throws exception if we don't
     *
     * @param mixed  $file_descriptor
     * @param string $tty_name
     *
     * @return void
     */
    public static function checkTty(mixed $file_descriptor, string $tty_name): void
    {
        if (!PLATFORM_CLI) {
            throw new CliNoTtyException(tr('Cannot access TTY ":tty", the platform ":platform" is not supported for this', [
                ':platform' => PLATFORM,
                ':tty'      => $tty_name,
            ]));
        }
        if (!stream_isatty($file_descriptor)) {
            throw new CliNoTtyException(tr('Cannot access stream ":tty", the file descriptor is not a TTY', [
                ':tty' => $tty_name,
            ]));
        }
    }


    /**
     * Read an input from the command line prompt
     *
     * @param string      $prompt
     * @param string|null $default
     *
     * @return string|null
     */
    public static function readInput(string $prompt, ?string $default = null): ?string
    {
        static::checkTty(STDIN, 'stdin');
        $prompt = Strings::endsWith($prompt, ' ');
        if ($default) {
            $prompt .= '[' . $default . '] ';
        }
        $return = readline($prompt);
        if (!$return) {
            $return = $default;
        }

        return $return;
    }


    /**
     * Validates if the given argument is a valid CLI argument
     *
     *  Will return "-LETTER" where the specified source is a single leter, like "A" > "-A"
     *  Will return "--WORD" where the specified source is a single word, like "all" > "--all"
     *
     * @param string $argument
     * @param bool   $require_dashes
     * @param int    $max_words_size
     *
     * @return string
     */
    public static function validateAndSanitizeArgument(string $argument, bool $require_dashes = true, int $max_words_size = 32): string
    {
        if ($require_dashes) {
            $test = $argument;
        } else {
            switch (strlen($argument)) {
                case 0:
                    throw new OutOfBoundsException(tr('Empty argument specified'));
                case 1:
                    $test = Strings::startsWith($argument, '-');
                    break;
                default:
                    $test = Strings::startsWith($argument, '--');
            }
        }
        if (preg_match('/^-[a-z0-9]$/i', $test)) {
            return $test;
        }
        if (preg_match('/^--[a-z][a-z0-9-]{0,' . --$max_words_size . '}$/i', $test)) {
            return $test;
        }
        throw new OutOfBoundsException(tr('The specified argument ":argument" is not a valid command line argument', [
            ':argument' => $argument,
        ]));
    }
}
