<?php

namespace Phoundation\Cli;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Cli\Exception\CliException;
use Phoundation\Cli\Exception\MethodNotExistsException;
use Phoundation\Cli\Exception\MethodNotFoundException;
use Phoundation\Cli\Exception\NoMethodSpecifiedException;
use Phoundation\Core\Arrays;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\NoProjectException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Numbers;
use Phoundation\Core\Strings;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Date\Time;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\ScriptException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Commands\Command;
use Phoundation\Processes\Commands\Grep;
use Phoundation\Processes\Process;
use Throwable;
use function Symfony\Component\String\s;


/**
 * Class AutoComplete
 *
 *
 *
 * @note Bash autocompletion has no man page and complete --help is of little, well, help. Search engines were also
 *       uncharacteristically unhelpful, so see the following links for more information
 *
 * @see https://serverfault.com/questions/506612/standard-place-for-user-defined-bash-completion-d-scripts#831184
 * @see https://github.com/scop/bash-completion/blob/master/README.md
 * @see https://www.thegeekstuff.com/2013/12/bash-completion-complete/
 * @see https://dev.to/iridakos/adding-bash-completion-to-your-scripts-50da
 * @see https://serverfault.com/questions/506612/standard-place-for-user-defined-bash-completion-d-scripts
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Cli
 */
class AutoComplete
{
    /**
     * The word location for the auto complete. NULL if auto complete hasn't been enabled
     *
     * @var int|null $position
     */
    protected static ?int $position = null;

    /**
     * The script for which auto complete is running
     *
     * @var string $script
     */
    protected static string $script;


    /**
     * Returns true if auto complete mode is active
     *
     * @return bool
     */
    public static function isActive(): bool
    {
        return self::$position !== null;
    }



    /**
     * Set the word location for auto complete
     *
     * @param int $position
     */
    public static function setPosition(int $position): void
    {
        self::$position = $position;
    }



    /**
     * Process the auto complete for command line methods
     *
     * @see https://iridakos.com/programming/2018/03/01/bash-programmable-completion-tutorial
     * @param array|null $cli_methods
     * @param array $data
     * @return void
     */
    #[NoReturn] public static function processMethods(?array $cli_methods, array $data): void
    {
        // $data['position'] is the amount of found methods
        // self::$position is the word # where the cursor was when <TAB> was pressed
        if ($cli_methods === null) {
            if (self::$position) {
                // Invalid situation, supposedly there is an auto complete location, but no methods?
                die('Invalid auto complete arguments' . PHP_EOL);
            }

            foreach ($data['methods'] as $method) {
                echo $method . PHP_EOL;
            }

        } elseif (self::$position > count($cli_methods)) {
            // Invalid situation, supposedly the location was beyond, after the amount of arguments?
            die('Invalid auto complete arguments' . PHP_EOL);

        } elseif ($data['position'] > self::$position) {
            // The findScript() method already found this particular word, so we know it exists!
            echo $cli_methods[self::$position];

        } else {
            $starts   = [];
            $contains = [];

            $argument_method = isset_get($cli_methods[self::$position], '');

            if ($argument_method) {
                foreach ($data['methods'] as $method) {
                    if (str_starts_with($method, $argument_method)) {
                        // A method starts with the word we try to auto complete
                        $starts[] = $method;
                        continue;
                    }

                    if (str_contains($method, $argument_method)) {
                        // A method contains the word we try to auto complete
                        $contains[] = $method;
                    }
                }

                switch (count($starts)) {
                    case 0:
                        break;

                    case 1:
                        // We found a single method that starts with the word we have, we'll use that and ignore other
                        // methods that contain the word
                        echo array_shift($starts);
                        $contains = [];
                        break;

                    default:
                        $contains = array_merge($starts, $contains);
                }

                switch (count($contains)) {
                    case 0:
                        break;

                    case 1:
                        // We found a single method that contains the word we have, we'll use that
                        echo array_shift($contains);
                        break;

                    default:
                        foreach ($contains as $method) {
                            echo $method . PHP_EOL;
                        }
                }

            } else {
                foreach ($data['methods'] as $method) {
                    echo $method . PHP_EOL;
                }
            }

        }

        // We're done,
        Script::die();
    }



    /**
     * Process auto complete for this script from the definitions specified by the script
     *
     * @param array $definitions
     * @return void
     */
    #[NoReturn] public static function processScript(array $definitions) {
        // Get the word where we're <TAB>bing on
        $previous_word = isset_get(ArgvValidator::getArguments()[self::$position - 1]);
        $word          = isset_get(ArgvValidator::getArguments()[self::$position]);
        $word          = strtolower(trim((string) $word));

        // First check positions!
        if (array_key_exists('positions', $definitions)) {
            if (array_key_exists(self::$position, isset_get($definitions['positions']))) {
                // Get position specific data
                $position_data = $definitions['positions'][self::$position];

                // We may have a word or not, check if position_data allows word (or not) and process
                if ($word) {
                    if (array_key_exists('word', $position_data)) {
                        $results = self::processDefinition($position_data['word'], $word);
                    }
                } else {
                    if (array_key_exists('noword', $position_data)) {
                        $results = self::processDefinition($position_data['noword'], null);
                    }
                }

                // Process results only if we have any
                if (isset($results)) {
                    foreach ($results as $result) {
                        echo $result . PHP_EOL;
                    }

                    // Die here as we have echoed results!
                    Script::die();
                }
            }
        }

        // Check for modifier arguments
        if (array_key_exists('arguments', $definitions)) {
            // Check if the previous key was a modifier argument that requires a value
            foreach ($definitions['arguments'] as $key => $value) {
                // Values may contain multiple arguments!
                $keys = explode(',', $key);

                foreach ($keys as $key) {
                    if ($key === $previous_word) {
                        $requires_value = $value;
                    }
                }
            }

            if (isset($requires_value)) {
                if ($requires_value === true) {
                    // non-suggestible value, the user will have to type this themselves...
                } else {
                    if ($word) {
                        if (array_key_exists('word', $requires_value)) {
                            $results = self::processDefinition($requires_value['word'], $word);
                        }
                    } else {
                        if (array_key_exists('noword', $requires_value)) {
                            $results = self::processDefinition($requires_value['noword'], null);
                        }
                    }
                }
            } else {
                // Check if we can suggest modifier arguments
                foreach ($definitions['arguments'] as $key => $value) {
                    // Values may contain multiple arguments!
                    $keys = explode(',', $key);

                    foreach ($keys as $key) {
                        if (!$word or str_contains(strtolower(trim($key)), $word)) {
                            $results[] = $key;
                        }
                    }
                }
            }

            // Process results only if we have any
            if (isset($results)) {
                foreach ($results as $result) {
                    echo $result . PHP_EOL;
                }

                // Die here as we have echoed results!
                Script::die();
            }
        }

        // Die here so that the script doesn't get executed
        Script::die();
    }



    /**
     * Returns true if the specified script has auto complete support available
     *
     * @param string $script
     * @return bool
     */
    public static function hasSupport(string $script): bool
    {
        self::$script = $script;

        // Update the location to the first argument (argument 0) after the script
        $script = Strings::from(self::$script, PATH_ROOT . 'scripts/');
        $script = explode('/', $script);

        self::$position = self::$position - count($script);

        return !empty(File::new(self::$script, PATH_ROOT . 'scripts/')->grep(['Documentation::autoComplete('], 100));
    }



    /**
     * Checks if autocomplete has been correctly setup
     *
     * @return void
     */
    public static function ensureAvailable(): void
    {
        if (file_exists('~/.bash_completion')) {
            // Check if it contains the setup for Phoundation
            // TODO Check if this is an issue with huge bash_completion files, are there huge files out there?
            $results = Grep::new(Restrictions::new('~/.bash_completion'))
                ->setValue('complete -F _phoundation pho')
                ->setPath('~/.bash_completion')
                ->execute();

            if ($results) {
                // bash_completion contains rule for phoundation
                return;
            }
        }

        // Phoundation command line auto complete has not yet been set up, do so now.
        File::new(PATH_ROOT . 'bash_completion')->appendData('
#/usr/bin/env bash
_phoundation()
{
PHO=$(./pho --auto-complete "${COMP_CWORD} ${COMP_LINE}");
COMPREPLY+=($(compgen -W "$PHO"));
}

complete -F _phoundation pho
');

        Log::information('Setup auto complete for Phoundation in ~/.bash_completion');
    }



    /**
     * Process the specified definition
     *
     * @param string|null $word
     * @param mixed $definition
     * @return array|string|null
     */
    protected static function processDefinition(mixed $definition, ?string $word): array|string|null
    {
        if (is_null($definition)) {
            return null;
        }

        if (is_callable($definition)) {
            return $definition($word);
        }

        if (is_string($definition)) {
            if (str_starts_with($definition, 'SELECT ')) {
                if ($word) {
                    return sql()->list($definition, [':word' => '%' . $word . '%']);
                }

                return sql()->list($definition);
            }

            return $definition;
        }

        // Process an array, return all entries that have partial match
        if (is_array($definition)) {
            $results = [];

            foreach ($definition as $value) {
                if (!$word or str_contains(strtolower(trim($value)), $word)) {
                    $results[] = $value;
                }
            }

            return $results;
        }
    }
}