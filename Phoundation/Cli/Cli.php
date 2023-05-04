<?php

declare(strict_types=1);

namespace Phoundation\Cli;

use Phoundation\Core\Arrays;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Classes\Iterator;
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
     * If true, passwords will be visible on the CLI
     *
     * @var bool $show_passwords
     */
    protected static bool $show_passwords = false;


    /**
     * Sets & returns if passwords are shown or not.
     *
     * @param bool|null $show_passwords
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
     * @param Iterator|array $source
     * @param array|string|null $headers
     * @param string|null $id_column
     * @param int $column_spacing
     * @return void
     */
    public static function displayTable(Iterator|array $source, array|string|null $headers = null, ?string $id_column = 'id', int $column_spacing = 2): void
    {
        if (is_object($source)) {
            // This is an Iterator object, get the array source
            $source = $source->getList();
        }

        if (!is_natural($column_spacing)) {
            throw new OutOfBoundsException(tr('Invalid column spacing ":spacing" specified, please ensure it is a natural number, 1 or higher', [
                ':spacing' => $column_spacing
            ]));
        }

        // Determine the size of the keys to display them
        $column_sizes = Arrays::getLongestStringPerColumn($source, 2, $id_column);

        if ($source) {
            // Get headers from columns
            if ($headers === null) {
                $value   = str_replace(['_', '-'], ' ', $id_column);
                $value   = Strings::capitalize($value) . ':';
                $headers = ($id_column ? [$id_column => $value] : []);
                $row     = current($source);

                foreach ($row as $header => $value) {
                    $value = str_replace(['_', '-'], ' ', $header);
                    $value = Strings::capitalize($value) . ':';

                    $headers[$header] = $value;
                }
            }

            // Display header
            foreach ($headers as $column => $header) {
                Log::cli(Color::apply(Strings::size($header , $column_sizes[$column]), 'white') . Strings::size(' ', $column_spacing), 10, false);
            }

            Log::cli();

            // Display source
            foreach ($source as $id => $row) {
                if (!is_array($row)) {
                    // Wrong! This is a row and as such should be an array
                    throw new OutOfBoundsException(tr('Invalid row ":row" specified for id ":id", it should be an array', [
                        ':id' => $id,
                        ':row' => $row,
                    ]));
                }

                array_unshift($row, $id);

                foreach ($row as $column => $value) {
                    if ($column === 0) {
                        // Due to the nature of array_unshift (we can't specify key name, so it always has key 0), rename!
                        $column = $id_column;
                    }

                    Log::cli(Strings::size($value , $column_sizes[$column], ' ', is_numeric($value)) . Strings::size(' ', $column_spacing), 10, false);
                }

                Log::cli();
            }
        } else {
            // Oops, there are no results!
            Log::warning(tr('No results'));
        }
    }


    /**
     * Display the data in the specified source array in a neat looking form
     *
     * @param array $source
     * @param string|null $key_header
     * @param string|null $value_header
     * @param int $offset If specified, the text will be set $offset amount of characters to the right
     * @return void
     */
    public static function displayForm(array $source, ?string $key_header = null, string $value_header = null, int $offset = 0): void
    {
        // Validate arguments
        if ($offset < 0) {
            throw new OutOfBoundsException(tr('Invalid offset ":offset" specified, it should be 0 or higher', [
                ':offset' => $offset
            ]));
        }

        if ($key_header === null) {
            $key_header = tr('Keys:');
        }

        if ($value_header === null) {
            $value_header = tr('Values:');
        }

        // Determine the size of the keys to display them
        $key_size = Arrays::getLongestKeyString($source) + 4;

        // Display header
        if ($key_header and $value_header) {
            Log::cli(Color::apply(Strings::size(' ', $offset) . Strings::size($key_header , $key_size), 'white') . ' ' . $value_header);
        }

        // Display source
        foreach ($source as $key => $value) {
            $key = str_replace(['_', '-'], ' ', $key);
            $key = Strings::capitalize($key) . ':';

            if (!is_scalar($value)) {
                if (is_object($value)) {
                    // Yeah, how to display this? Try to cast to array, hope for the best.
                    $value = (array) $value;
                }

                if (is_array($value)) {
                    Log::cli(Color::apply(Strings::size(' ', $offset) . Strings::size($key , $key_size), 'white') );
                    static::displayForm($value, '', '', $key_size + 1);
                    continue;
                }

                // This is likely a resource or something
                $value = gettype($value);
            }

            Log::cli(Color::apply(Strings::size(' ', $offset) . Strings::size($key , $key_size), 'white') . ' ' . $value);
        }
    }


    /**
     * Read a password from the command line prompt
     *
     * @param string $prompt
     * @return string|null
     */
    public static function readPassword(string $prompt): ?string
    {
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
     * Read an input from the command line prompt
     *
     * @param string $prompt
     * @param string|null $default
     * @return string|null
     */
    public static function readInput(string $prompt, ?string $default = null): ?string
    {
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
}