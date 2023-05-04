<?php

declare(strict_types=1);

namespace Phoundation\Cli;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Cli\Exception\AutoCompleteException;
use Phoundation\Core\Locale\Language\Languages;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Geo\Timezones\Timezones;
use Phoundation\Processes\Commands\Grep;

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
 * @see https://iridakos.com/programming/2018/03/01/bash-programmable-completion-tutorial
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
     * List of available system arguments
     *
     * @var array $system_arguments
     */
    protected static array $system_arguments;


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
        self::$position         = $position;
        self::$system_arguments = [
            '-A,--all'                 => false,
            '-C,--no-color'            => false,
            '-D,--debug'               => false,
            '-E,--environment'         => true,
            '-F,--force'               => false,
            '-H,--help'                => false,
            '-L,--log-level'           => true,
            '-O,--order-by'            => true,
            '-P,--page'                => true,
            '-Q,--quiet'               => false,
            '-S,--status'              => true,
            '-T,--test'                => false,
            '-U,--usage'               => false,
            '-V,--verbose'             => false,
            '-W,--no-warnings'         => false,
            '--system-language'        => [
                'word'   => function($word) { return Languages::new()->filteredList($word); },
                'noword' => function()      { return Languages::new()->list(); },
            ],
            '--deleted'                => false,
            '--version'                => false,
            '--limit'                  => true,
            '--timezone'               => [
                'word'   => function($word) { return Timezones::new()->filteredList($word); },
                'noword' => function()      { return Timezones::new()->list(); },
            ],
            '--show-passwords'         => false,
            '--no-validation'          => false,
            '--no-password-validation' => false,
        ];
    }


    /**
     * Get the word location for auto complete
     *
     * @return int|null
     */
    public static function getPosition(): ?int
    {
        return self::$position;
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

            $words = ArgvValidator::getArguments();
            $word  = array_shift($words);

            if (str_starts_with((string) $word, '-')) {
                self::processArguments(self::$system_arguments);

            } else {
                foreach ($data['methods'] as $method) {
                    echo $method . PHP_EOL;
                }
            }

        } elseif (self::$position > count($cli_methods)) {
            // Invalid situation, supposedly the location was beyond, after the amount of arguments?
            die('Invalid auto complete arguments' . PHP_EOL);

        } elseif ($data['position'] > self::$position) {
            // The findScript() method already found this particular word, so we know it exists!
            echo $cli_methods[self::$position];

        } else {
            $contains        = [];
            $argument_method = isset_get($cli_methods[self::$position], '');

            if (!$argument_method) {
                // There are no methods, are there modifier arguments, perhaps?
                $arguments = ArgvValidator::getArguments();

                if ($arguments) {
                    // Get the argument from the modifier arguments list
                    $argument_method = array_shift($arguments);
                }
            }

            if ($argument_method) {
                // We have an argument method specified, likely it doesn't exist
                if (str_starts_with($argument_method, '-')) {
                    // This is a system modifier argument, show the system modifier arguments instead.
                    $data['methods'] = [];

                    foreach (self::$system_arguments as $arguments => $definitions) {
                        $arguments = explode(',', $arguments);

                        foreach ($arguments as $argument) {
                            $data['methods'][] = $argument;
                        }
                    }
                }

                foreach ($data['methods'] as $method) {
                    if (str_contains($method, $argument_method)) {
                        // A method contains the word we try to auto complete
                        $contains[] = $method;
                    }
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
     * @param array|null $definitions
     * @return void
     */
    public static function processScriptPositions(?array $definitions) {
        if (!$definitions) {
            return;
        }

        // Get the word where we're <TAB>bing on
        $word = isset_get(ArgvValidator::getArguments()[self::$position]);
        $word = strtolower(trim((string) $word));

        // First check positions!
        if (array_key_exists(self::$position, isset_get($definitions))) {
            // Get position specific data
            $position_data = $definitions[self::$position];

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


    /**
     * Process script arguments
     *
     * @param array|null $definitions
     * @return void
     */
    public static function processScriptArguments(?array $definitions): void
    {
        if ($definitions) {
            self::processArguments(array_merge($definitions, self::$system_arguments));
        } else {
            self::processArguments(self::$system_arguments);
        }
    }


    /**
     * Returns true if the specified script has auto complete support available
     *
     * @todo Add caching for this
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
        $file = Filesystem::absolute('~/.bash_completion');

        if (file_exists($file)) {
            // Check if it contains the setup for Phoundation
            // TODO Check if this is an issue with huge bash_completion files, are there huge files out there?
            $results = Grep::new(Restrictions::new($file, true))
                ->setValue('complete -F _phoundation pho')
                ->setFile($file)
                ->execute();

            if ($results) {
                // bash_completion contains rule for phoundation
                return;
            }
        }

        // Phoundation command line auto complete has not yet been set up, do so now.
        File::new('~/.bash_completion')
            ->setRestrictions('~/.bash_completion', true)
            ->append('#/usr/bin/env bash
_phoundation()
{
PHO=$(./pho --auto-complete "${COMP_CWORD} ${COMP_LINE}");
COMPREPLY+=($(compgen -W "$PHO"));
}

complete -F _phoundation pho');

        Log::information('Setup auto complete for Phoundation in ~/.bash_completion');
    }


    /**
     * Process auto complete for this script from the definitions specified by the script
     *
     * @param array $argument_definitions
     * @return void
     */
    #[NoReturn] public static function processArguments(array $argument_definitions) {
        // Get the word where we're <TAB>bing on
        if (self::$position) {
            $previous_word = isset_get(ArgvValidator::getArguments()[self::$position - 1]);
            $word          = isset_get(ArgvValidator::getArguments()[self::$position]);
            $word          = strtolower(trim((string) $word));

            // Check if the previous key was a modifier argument that requires a value
            foreach ($argument_definitions as $key => $value) {
                // Values may contain multiple arguments!
                $keys = explode(',', Strings::until($key, ' '));

                foreach ($keys as $key) {
                    if ($key === $previous_word) {
                        $requires_value = $value;
                    }
                }
            }

        } else {
            $word = '';
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
            foreach ($argument_definitions as $key => $value) {
                // Values may contain multiple arguments!
                $keys = explode(',', Strings::until($key, ' '));

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
            $definition = trim($definition);

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

        throw new AutoCompleteException(tr('Failed to process auto complete definition ":definition" for script ":script"', [
            ':script'     => self::$script,
            ':definition' => $definition
        ]));
    }
}