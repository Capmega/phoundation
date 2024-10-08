<?php

declare(strict_types=1);

namespace Phoundation\Cli;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Cli\Exception\CliAutoCompleteException;
use Phoundation\Core\Locale\Language\Languages;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Limit;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Geo\Timezones\Timezones;
use Phoundation\Os\Processes\Commands\Grep;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;

/**
 * Class AutoComplete
 *
 * This class executes all BaSH autocompletion including registering ./pho for autocompletion if it hasn't been yet.
 *
 * The class supports autocompletion of commands, global system arguments, command arguments and command values.
 * Commands must use Documentation::setAutoComplete() for this
 *
 * @example
 *
 * Documentation::setAutoComplete([
 *     'positions' => [
 *         0 => [
 *             'word'   => 'SELECT `name` FROM `ssh_accounts` WHERE `name` LIKE :word AND `status` IS NULL',
 *             'noword' => 'SELECT `name` FROM `ssh_accounts` WHERE `status` IS NULL LIMIT ' .
 *             Limit::shellAutoCompletion()
 *         ]
 *     ],
 *     'arguments' => [
 *         '--file' => [
 *             'word'   => function ($word) { return Directory::new(DIRECTORY_DATA . 'sources/', DIRECTORY_DATA .
 *             'sources/')->scandir($word . '*.csv'); },
 *             'noword' => function ()      { return Directory::new(DIRECTORY_DATA . 'sources/', DIRECTORY_DATA .
 *             'sources/')->scandir('*.csv'); },
 *         ],
 *         '--user' => [
 *             'word'   => function ($word) { return Arrays::match(Users::new()->load()->getSourceColumn('email'),
 *             $word); },
 *             'noword' => function ()      { return Users::new()->load()->getSourceColumn('email'); },
 *         ]
 *     ]
 * ]);
 *
 * @note      Bash autocompletion has no man page and complete --help is of little, well, help. Search engines were
 *            also
 *       uncharacteristically unhelpful, so see the following links for more information
 *
 * @see       https://serverfault.com/questions/506612/standard-place-for-user-defined-bash-completion-d-scripts#831184
 * @see       https://github.com/scop/bash-completion/blob/master/README.md
 * @see       https://www.thegeekstuff.com/2013/12/bash-completion-complete/
 * @see       https://dev.to/iridakos/adding-bash-completion-to-your-scripts-50da
 * @see       https://iridakos.com/programming/2018/03/01/bash-programmable-completion-tutorial
 * @see       https://serverfault.com/questions/506612/standard-place-for-user-defined-bash-completion-d-scripts
 * @see       https://stackoverflow.com/questions/1146098/properly-handling-spaces-and-quotes-in-bash-completion#11536437
 *
 * @todo      Fix known issues with "foo" and "foo-bar", the second item won't ever be shown
 * @todo      Fix known issues with result set having entries containing spaces, "foo bar" will be shown as "foo" and
 *            "bar"
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cli
 */
class CliAutoComplete
{
    /**
     * The word location for the auto complete. NULL if auto complete hasn't been enabled
     *
     * @var int|null $position
     */
    protected static ?int $position = null;

    /**
     * The command for which auto complete is running
     *
     * @var string $command
     */
    protected static string $command;

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
        return static::$position !== null;
    }


    /**
     * Initializes system arguments
     */
    public static function initSystemArguments(): void
    {
        static::$system_arguments = [
            '-A,--all'                 => false,
            '-C,--no-color'            => false,
            '-D,--debug'               => false,
            '-E,--environment'         => true,
            '-F,--force'               => false,
            '-H,--help'                => false,
            '-L,--log-level'           => [
                0,
                1,
                2,
                3,
                4,
                5,
                6,
                7,
                8,
                9,
            ],
            '-O,--order-by'            => true,
            '-P,--page'                => true,
            '-Q,--quiet'               => false,
            '-S,--status'              => true,
            '-T,--test'                => false,
            '-U,--usage'               => false,
            '-V,--verbose'             => false,
            '-W,--no-warnings'         => false,
            '--system-language'        => [
                'word'   => function ($word) {
                    return Languages::new()->keepMatchingKeys($word);
                },
                'noword' => function () {
                    return Languages::new()->getSource();
                },
            ],
            '--deleted'                => false,
            '--version'                => false,
            '--limit'                  => true,
            '--timezone'               => [
                'word'   => function ($word) {
                    return Timezones::new()->keepMatchingKeys($word);
                },
                'noword' => function () {
                    return Timezones::new()->getSource();
                },
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
        return static::$position;
    }


    /**
     * Set the word location for auto complete
     *
     * @param int $position
     */
    public static function setPosition(int $position): void
    {
        if ($position < 0) {
            throw new CliAutoCompleteException(tr('Invalid position ":position" specified, must be 0 or higher', [
                ':position' => $position
            ]));
        }

        static::$position = $position;
    }


    /**
     * Process the auto complete for command line commands
     *
     * @see https://iridakos.com/programming/2018/03/01/bash-programmable-completion-tutorial
     *
     * @param array|null $cli_commands
     * @param array      $data
     *
     * @return never
     */
    #[NoReturn] public static function processCommands(?array $cli_commands, array $data): never
    {
        // $data['position'] is the number of found commands
        // static::$position is the word # where the cursor was when <TAB> was pressed
        if (empty($cli_commands)) {
            // No CLI commands have been specified yet, so all arguments are assumed system arguments
            $words = ArgvValidator::getArguments();
            $word  = array_shift($words);

            if (str_starts_with((string) $word, '-')) {
                static::processArguments(static::$system_arguments);

            } else {
                if (empty($data['commands'])) {
                    Log::warning(tr('Auto complete could not find any available cached root commands. try ./pho -Z to rebuild system caches'));
                    echo tr('no-commands-available-see-logs') . PHP_EOL;

                } else {
                    foreach ($data['commands'] as $command) {
                        echo $command . PHP_EOL;
                    }
                }
            }

        } elseif (static::$position > count($cli_commands)) {
            // Invalid situation, supposedly the location was beyond, after the number of arguments?
            Log::error(tr('Cannot process commands, command line cursor position ":position" is beyond the command line count ":count"', [
                ':position' => static::$position,
                ':count'    => count($cli_commands),
            ]), echo_screen: false);

            echo 'Invalid-auto-complete-arguments' . PHP_EOL;
            exit(1);

        } elseif ($data['position'] > static::$position) {
            // The findCommand() method already found this particular word, so we know it exists! However, there may be
            // other commands starting with this particular word, so we may have to display multiple options instead
            $matches = static::getCommandsStartingWith($data['previous_commands'], $cli_commands[static::$position]);

            switch (count($matches)) {
                case 0:
                    // This shouldn't happen at all, there is a match or we wouldn't be here!
                    throw new CliAutoCompleteException(tr('Found no match while there should be a match'));

                case 1:
                    echo $cli_commands[static::$position];
                    break;

                default:
                    // Multiple options available, still, show all!
                    static::displayMultipleMatches($matches);
            }

        } else {
            $argument_command = isset_get($cli_commands[static::$position], '');

            if (!$argument_command) {
                // There are no commands; Are there modifier arguments, perhaps?
                $arguments = ArgvValidator::getArguments();

                if ($arguments) {
                    // Get the argument from the modifier arguments list
                    $argument_command = array_shift($arguments);
                }
            }

            if ($argument_command) {
                // We have an argument command specified, likely it doesn't exist
                if (str_starts_with($argument_command, '-')) {
                    // This is a system modifier argument, show the system modifier arguments instead.
                    $data['commands'] = [];

                    foreach (static::$system_arguments as $arguments => $definitions) {
                        $arguments = explode(',', $arguments);

                        foreach ($arguments as $argument) {
                            $data['commands'][] = $argument;
                        }
                    }
                }

                $matches = static::getCommandsStartingWith($data['commands'], $argument_command);

                switch (count($matches)) {
                    case 0:
                        break;

                    case 1:
                        // We found a single command that contains the word we have, we'll use that
                        echo array_shift($matches);
                        break;

                    default:
                        static::displayMultipleMatches($matches);
                }

            } else {
                foreach ($data['commands'] as $command) {
                    if (empty($command)) {
                        continue;
                    }

                    echo $command . PHP_EOL;
                }
            }
        }

        // We're done,
        exit();
    }


    /**
     * Process auto complete for this command from the definitions specified by the command
     *
     * @param array $argument_definitions
     *
     * @return void
     */
    #[NoReturn] public static function processArguments(array $argument_definitions): void
    {
        // Get the word where we're <TAB>bing on
        if (static::$position >= 0) {
            $previous_word = isset_get(ArgvValidator::getArguments()[static::$position - 1]);
            $word          = isset_get(ArgvValidator::getArguments()[static::$position]);
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

        if (isset_get($requires_value)) {
            if ($requires_value === true) {
                // non-suggestible value, the user will have to type this themselves...

            } else {
                if (is_array($requires_value)) {
                    if (array_keys($requires_value) === ['word', 'noword']) {
                        // The $requires_value contains queries for if there is a partial word, or when there is no
                        // partial word
                        if ($word) {
                            if (array_key_exists('word', $requires_value)) {
                                $results = static::processDefinition($requires_value['word'], $word);
                            }

                        } else {
                            if (array_key_exists('noword', $requires_value)) {
                                $results = static::processDefinition($requires_value['noword'], null);
                            }
                        }

                    } else {
                        // The $requires_value contains a list of possible values
                        $results = $requires_value;
                    }
                }
            }

        } else {
            // The previous argument (if any?) requires no value. Check if we can suggest more modifier arguments
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
            exit();
        }
    }


    /**
     * Process the specified definition
     *
     * @param mixed       $definition
     * @param string|null $word
     *
     * @return IteratorInterface|array|string|null
     */
    protected static function processDefinition(mixed $definition, ?string $word): IteratorInterface|array|string|null
    {
        // If no definitions were given, we're done
        if (is_null($definition)) {
            return null;
        }

        // If the given definition was a function, we can just return the result
        if (is_callable($definition)) {
            $results = $definition($word, ArgvValidator::getArguments());

            if (is_array($results)) {
                // Limit the number of results
                $results = static::limit($results);
            }

            return $results;
        }

        if (is_string($definition)) {
            $definition = trim($definition);

            if (str_starts_with($definition, 'SELECT ')) {
                if ($word) {
                    // Execute the query filtering on the specified word and limit the results
                    return static::limit(sql()->listScalar($definition, [':word' => '%' . $word . '%']));
                }

                // Execute the query completely and limit the results
                return static::limit(sql()->listScalar($definition));
            }

            return $definition;
        }

        // Process an array, return all entries that have partial match
        if (is_array($definition)) {
            $definition = static::limit($definition);
            $results    = [];

            foreach ($definition as $value) {
                if (!$word or str_contains(strtolower(trim($value)), $word)) {
                    $results[] = $value;
                }
            }

            return $results;
        }

        throw new CliAutoCompleteException(tr('Failed to process auto complete definition ":definition" for command ":command"', [
            ':command'    => static::$command,
            ':definition' => $definition,
        ]));
    }


    /**
     * Automatically limit the specified result set to the configured auto complete limit
     *
     * @param array $source
     *
     * @return array
     */
    public static function limit(array $source): array
    {
        return Arrays::limit($source, static::getLimit());
    }


    /**
     * Returns the limit for auto complete
     *
     * @return int
     */
    public static function getLimit(): int
    {
        return Limit::shellAutoCompletion();
    }


    /**
     * Returns the commands starting with the specified word
     *
     * @param array  $commands
     * @param string $word
     *
     * @return array
     */
    protected static function getCommandsStartingWith(array $commands, string $word): array
    {
        $return = [];

        foreach ($commands as $command) {
            if (str_starts_with($command, $word)) {
                // A command contains the word we try to auto complete
                $return[] = $command;
            }
        }

        return $return;
    }


    /**
     * Displays multiple auto complete matches on screen
     *
     * @param array $matches
     *
     * @return void
     */
    protected static function displayMultipleMatches(array $matches): void
    {
        // We have multiple matches. Check if any of the matches
        foreach ($matches as $command) {
            echo $command . PHP_EOL;
        }
    }


    /**
     * Process auto complete for this command from the definitions specified by the command
     *
     * @param IteratorInterface|array|null $definitions
     *
     * @return void
     */
    public static function processCommandPositions(IteratorInterface|array|null $definitions)
    {
        if (!$definitions) {
            return;
        }

        if ($definitions instanceof IteratorInterface) {
            // From here use array
            $definitions = $definitions->getSource();
        }

        // Get the word where we're <TAB>bing on
        $word = isset_get(ArgvValidator::getArguments()[static::$position]);
        $word = strtolower(trim((string) $word));

        // First check position!
        static::processCommandPosition($definitions, $word, static::$position);

        // Do we have an "all other positions" entry?
        static::processCommandPosition($definitions, $word, -1);
    }


    /**
     * Process auto complete for this command from the definitions specified by the command
     *
     * @param array  $definitions
     * @param string $word
     * @param int    $position
     *
     * @return void
     */
    protected static function processCommandPosition(array $definitions, string $word, int $position): void
    {
        if (array_key_exists($position, isset_get($definitions))) {
            // Get position specific data
            $position_data = $definitions[$position];

            // We may have a word or not, check if position_data allows word (or not) and process
            if ($word) {
                if (array_key_exists('word', $position_data)) {
                    $results = static::processDefinition($position_data['word'], $word);
                }

            } else {
                if (array_key_exists('noword', $position_data)) {
                    $results = static::processDefinition($position_data['noword'], null);
                }
            }

            // Process results only if we have any
            if (isset($results)) {
                // Sort the results, either array or Iterator
                if (is_array($results)) {
                    asort($results);

                } elseif ($results instanceof IteratorInterface) {
                    $results->sort();

                } else {
                    // The given result is neither array nor Iterator
                    throw new OutOfBoundsException(tr('Invalid ":word" auto completion results specified', [
                        ':word' => $word ? 'word' : 'noword',
                    ]));
                }

                foreach ($results as $result) {
                    if (!$result) {
                        continue;
                    }

                    if (!is_scalar($result)) {
                        if (!$result instanceof DataEntryInterface) {
                            throw OutOfBoundsException::new(tr('Invalid ":word" auto completion results ":result" specified (from results list ":results")', [
                                ':word'    => $word ? 'word' : 'noword',
                                ':result'  => $result,
                                ':results' => $results,
                            ]))
                            ->addData([
                                'results' => $results,
                            ])
                            ->makeWarning();
                        }

                        $result = $result->getAutoCompleteValue();
                    }

                    echo ((string) $result) . PHP_EOL;
                }

                // Die here as we have echoed the results!
                exit();
            }
        }
    }


    /**
     * Process command arguments
     *
     * @param IteratorInterface|array|null $definitions
     *
     * @return void
     */
    #[NoReturn] public static function processCommandArguments(IteratorInterface|array|null $definitions): void
    {
        if ($definitions) {
            if ($definitions instanceof IteratorInterface) {
                // From here use array
                $definitions = $definitions->getSource();
            }

            static::processArguments(array_merge($definitions, static::$system_arguments));

        } else {
            static::processArguments(static::$system_arguments);
        }
    }


    /**
     * Returns true if the specified command has auto complete support available
     *
     * @param string $command
     *
     * @return bool
     * @todo Add caching for this
     */
    public static function hasSupport(string $command): bool
    {
        // Update the location to the first argument (argument 0) after the command
        static::$command  = $command;
        $command          = Strings::from(static::$command, DIRECTORY_COMMANDS);
        $command          = explode('/', $command);
        static::$position = static::$position - count($command);

        return !empty(File::new(static::$command . '.php', DIRECTORY_COMMANDS)
                          ->grep(['Documentation::setAutoComplete('], 500));
    }


    /**
     * Checks if autocomplete has been correctly setup
     *
     * @return void
     */
    public static function ensureAvailable(): void
    {
        $file = Path::absolutePath('~/.bash_completion', must_exist: false);

        if (file_exists($file)) {
            // Check if it contains the setup for Phoundation
            // TODO Check if this is an issue with huge bash_completion files, are there huge files out there?
            $results = Grep::new(Restrictions::new($file, true))
                           ->setValue('complete -F _phoundation pho')
                           ->setFile($file)
                           ->grep(EnumExecuteMethod::returnArray);

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
        Log::information('You may need to logout and login again for auto complete to work correctly');
    }
}
