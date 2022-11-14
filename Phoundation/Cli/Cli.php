<?php

namespace Phoundation\Cli;

use Phoundation\Cli\Exception\CliException;
use Phoundation\Core\Arrays;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
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
     * @param array $source
     * @param string|null $key_header
     * @param string|null $value_header
     * @param int $offset If specified, the text will be set $offset amount of characters to the right
     * @return void
     */
    public static function displayArray(array $source, ?string $key_header = null, string $value_header = null, int $offset = 0): void
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
            $key = Strings::capitalize($key) . ':';

            if (!is_scalar($value)) {
                if (is_object($value)) {
                    // Yeah, how to display this? Try to cast to array, hope for the best.
                    $value = (array) $value;
                }

                if (is_array($value)) {
                    Log::cli(Color::apply(Strings::size(' ', $offset) . Strings::size($key , $key_size), 'white') );
                    self::displayArray($value, '', '', $key_size + 1);
                    continue;
                }

                // This is likely a resource or something
                $value = gettype($value);
            }

            Log::cli(Color::apply(Strings::size(' ', $offset) . Strings::size($key , $key_size), 'white') . ' ' . $value);
        }
    }
}