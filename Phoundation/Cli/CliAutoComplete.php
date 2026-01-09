<?php

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
 *             'word'   => function ($word) {
 *                             return Directory::new(DIRECTORY_DATA . 'sources/', DIRECTORY_DATA . 'sources/')
 *                                             ->scandir('/^' . $word . '.*?\.csv$/');
 *                         },
 *             'noword' => function ($word) {
 *                             return Directory::new(DIRECTORY_DATA . 'sources/', DIRECTORY_DATA . 'sources/')
 *                                             ->scandir('/^' . $word . '.*?\.csv$/);
 *                         },
 *         ],
 *         '--user' => [
 *             'word'   => function ($word) {
 *                             return Arrays::keepMatchingValues(Users::new()->load()->getSourceColumn('email'), $word);
 *                         },
 *             'noword' => function ($word) {
 *                             return Users::new()->load()->getSourceColumn('email');
 *                         },
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
 * @see       https://opensource.com/article/18/3/creating-bash-completion-script
 * @see       https://unix.stackexchange.com/questions/148497/how-to-customize-bash-command-completion
 * @see       https://www.gnu.org/software/bash/manual/html_node/Programmable-Completion-Builtins.html
 *
 * @todo      Fix known issues with "foo" and "foo-bar", the second item won't ever be shown
 * @todo      Fix known issues with result set having entries containing spaces, "foo bar" will be shown as "foo" and
 *            "bar"
 * @todo      Fix known issue that when only one result is returned, it should add a space to automatically go to the
 *            next argument but instead it sticks with that last item.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cli
 */


declare(strict_types=1);

namespace Phoundation\Cli;

use JetBrains\PhpStorm\NoReturn;
use PDOStatement;
use Phoundation\Accounts\Users\Locale\Language\Languages;
use Phoundation\Cli\Exception\CliAutoCompleteException;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Limit;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Geo\Timezones\Timezones;
use Phoundation\Os\Processes\Commands\Grep;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Process;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Stringable;


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
            '-A,--all'                       => false,
            '-C,--no-color'                  => false,
            '-D,--debug'                     => false,
            '-E,--environment'               => true,
            '-G,--prefix'                    => false,
            '-F,--force'                     => false,
            '-H,--help'                      => false,
            '-I,--json-input'                => false,
            '-J,--json-output'               => false,
            '-K,--reinitialize-autocomplete' => false,
            '-L,--log-level'                 => [
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
            '-M,--timeout'                   => true,
            '-N,--no-audio'                  => false,
            '-O,--order-by'                  => true,
            '-P,--page'                      => true,
            '-Q,--verbose'                   => false,
            '-R,--rebuild-commands'          => false,
            '-S,--service'                   => true,
            '-T,--test'                      => false,
            '-U,--usage'                     => false,
            '-V,--version'                   => false,
            '-W,--no-warnings'               => false,
            '-X,--ignore-readonly'           => false,
            '-Y,--clear-tmp'                 => false,
            '-Z,--clear-caches'              => false,
            '--deleted'                      => false,
            '--iec'                          => false,
            '--limit'                        => true,
            '--no-validation'                => false,
            '--no-password-validation'       => false,
            '--show-passwords'               => false,
            '--si'                           => false,
            '--status'                       => true,
            '--sudo'                         => false,
            '--timezone'                     => function ($word) {
                return Timezones::new()->keepMatchingKeys($word);
            },
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
            // Minimum position is always 0
            $position = 0;
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
                CliAutoComplete::processArguments(static::$system_arguments);

            } else {
                if (empty($data['commands'])) {
                    Log::warning(ts('Auto complete could not find any available cached root commands. try ./pho -Z to rebuild system caches'), echo_screen: false);
                    Log::error(ts('no_root_commands_available_see_syslog_for_more_information'), 10);
                    exit(1);
                }

                CliAutoComplete::showResults($data['commands']);
            }
        }

        if (static::$position > count($cli_commands)) {
            // Invalid situation, supposedly the location was beyond, after the number of arguments?
            Log::error(ts('Cannot process commands, command line cursor position ":position" is beyond the command line count ":count"', [
                ':position' => static::$position,
                ':count'    => count($cli_commands),
            ]), echo_screen: false);

            Log::error('invalid_auto_complete_arguments_see_syslog_for_more_information', 10);
            exit(1);

        }

        if ($data['position'] > static::$position) {
            // The findCommand() method already found this particular word, so we know it exists! However, there may be
            // other commands starting with this particular word, so we may have to display multiple options instead
            $matches = static::getCommandsStartingWith($data['previous_commands'], $cli_commands[static::$position]);

            switch (count($matches)) {
                case 0:
                    // This shouldn't happen at all, there is a match or we wouldn't be here!
                    throw new CliAutoCompleteException(tr('Found no match while there should be a match'));

                case 1:
                    echo $cli_commands[static::$position];
                    exit();

                default:
                    // Multiple options available, still, show all!
                    CliAutoComplete::showResults($matches);
            }
        }

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

                foreach (static::$system_arguments as $arguments => $o_definitions) {
                    $arguments = explode(',', $arguments);

                    foreach ($arguments as $argument) {
                        $data['commands'][] = $argument;
                    }
                }
            }

            CliAutoComplete::showResults(static::getCommandsStartingWith($data['commands'], $argument_command));
        }

        CliAutoComplete::showResults($data['commands']);
    }


    /**
     * Process auto complete for this command from the definitions specified by the command
     *
     * @param array $argument_definitions
     *
     * @return never
     */
    #[NoReturn] public static function processArguments(array $argument_definitions): never
    {
        // Get the word where we're <TAB>bing on
        if (static::$position >= 0) {
            $previous_word = isset_get(ArgvValidator::getArguments()[static::$position - 1]);
            $word          = isset_get(ArgvValidator::getArguments()[static::$position]);
            $word          = trim((string) $word);

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
                                $results = static::processDefinition('argument ' . isset_get($previous_word, 'unknown'), $requires_value['word'], $word);
                            }

                        } else {
                            if (array_key_exists('noword', $requires_value)) {
                                $results = static::processDefinition('argument ' . isset_get($previous_word, 'unknown'), $requires_value['noword'], null);
                            }
                        }

                    } else {
                        // The $requires_value contains a list of possible values
                        $results = $requires_value;
                    }

                } else {
                    // This processes the new auto suggest formats
                    if (is_string($requires_value)) {
                        $results = static::processDefinition('argument ' . isset_get($previous_word, 'unknown'), $requires_value, $word);

                    } elseif (is_callable($requires_value)) {
                        $results = static::processDefinition('argument ' . isset_get($previous_word, 'unknown'), $requires_value, $word);
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
            CliAutoComplete::showResults($results, $word);
        }

        // There are no results
        exit();
    }


    /**
     * Shows the auto complete results
     *
     * @param IteratorInterface|array|string $results
     * @param string|null $word
     * @return never
     */
    #[NoReturn] protected static function showResults(IteratorInterface|array|string $results, ?string $word = null): never
    {
        // Sort the results, either array or Iterator
        if (is_array($results)) {
            $single = (count($results) === 1);
            asort($results);

        } elseif ($results instanceof IteratorInterface) {
            $single = ($results->hasSingleEntry());
            $results->sort();

        } else {
            // The given result is neither array nor Iterator
            throw OutOfBoundsException::new(tr('Invalid ":word" auto completion results specified', [
                ':word' => $word ? 'word' : 'noword',
            ]))->addData([
                'results' => $results,
            ]);
        }

        foreach ($results as $result) {
            if ($result) {
                if (!is_scalar($result)) {
                    if ($result instanceof DataEntryInterface) {
                        $result = $result->getAutoCompleteValue();

                    } elseif ($result instanceof Stringable) {
                        $result = (string) $result;

                    } else {
                        throw OutOfBoundsException::new(tr('Invalid ":word" auto completion results ":result" specified (from results list ":results")', [
                            ':word'    => $word ? 'word' : 'noword',
                            ':result'  => $result,
                            ':results' => $results,
                        ]))->addData([
                            'results' => $results,
                        ])->makeWarning();
                    }
                }
            }

            if ($single) {
                echo ((string) $result) . ' ' . PHP_EOL;

            } else {
                echo ((string) $result) . PHP_EOL;
            }
        }

        // Die here as we've echoed the results!
        exit();
    }


    /**
     * Process the specified definition
     *
     * @param string      $name
     * @param mixed       $definition
     * @param string|null $word
     *
     * @return IteratorInterface|array|string|null
     */
    protected static function processDefinition(string $name, mixed $definition, ?string $word): IteratorInterface|array|string|null
    {
        // If no definitions were given, we're done
        if (is_null($definition)) {
            return null;
        }

        // If the given definition was a function, we can just return the result
        if (is_callable($definition)) {
            $results = $definition((string) $word, ArgvValidator::getArguments());

            if (is_array($results) or ($results instanceof IteratorInterface)) {
                // Limit the number of results
                return static::limit($results);
            }

            // The callback returned a string. Assume it is a query, so update the definition to be the query instead
            $definition = $results;
        }

        if (is_string($definition)) {
            if (str_starts_with(trim($definition), 'SELECT ')) {
                if ($word) {
                    // Execute the query filtering on the specified word and limit the results
                    return static::limit(sql()->listScalar($definition, [':word' => '%' . $word . '%']));
                }

                // Execute the query completely and limit the results
                return static::limit(sql()->listScalar($definition));
            }

            return $definition;
        }

        if ($definition instanceof PDOStatement) {
            // Convert the query result into an array
            $definition = sql()->listKeyValue($definition);

        } elseif ($definition instanceof IteratorInterface) {
            // Convert the Iterator to an array
            $definition = $definition->getSource();
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

        throw CliAutoCompleteException::new(tr('Failed to process auto complete definition ":definition" for command ":command", the definition returned no array or IteratorInterface object', [
            ':definition' => $name,
            ':command'    => static::$command,
        ]))->addData([
            'definition' => $name,
            'results'    => $definition
        ]);
    }


    /**
     * Automatically limit the specified result set to the configured auto complete limit
     *
     * @param IteratorInterface|array $source
     *
     * @return array
     */
    public static function limit(IteratorInterface|array $source): array
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
        return Limit::getShellAutoCompletion();
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
     * Process auto complete for this command from the definitions specified by the command
     *
     * @param IteratorInterface|array|null $o_definitions
     *
     * @return void
     */
    public static function processCommandPositions(IteratorInterface|array|null $o_definitions)
    {
        if (!$o_definitions) {
            return;
        }

        if ($o_definitions instanceof IteratorInterface) {
            // From here use array
            $o_definitions = $o_definitions->getSource();
        }

        // Get the word where we're <TAB>bing on
        $word = isset_get(ArgvValidator::getArguments()[static::$position]);
        $word = trim((string) $word);

        // First check position!
        static::processCommandPosition($o_definitions, $word, static::$position);

        // Do we have an "all other positions" entry?
        static::processCommandPosition($o_definitions, $word, null);
        static::processCommandPosition($o_definitions, $word, -1);
        static::processCommandPosition($o_definitions, $word, 'all');
    }


    /**
     * Process auto complete for this command from the definitions specified by the command
     *
     * @param array           $definitions
     * @param string          $word
     * @param string|int|null $position
     *
     * @return void
     */
    protected static function processCommandPosition(array $definitions, string $word, string|int|null $position): void
    {
        if (!is_array($definitions)) {
            throw CliAutoCompleteException::new(tr('Invalid auto complete definitions specified for command ":command", the definitions must be an array', [
                ':command' => static::$command,
            ]))->addData([
                'definitions' => $definitions,
            ]);
        }

        if (array_key_exists($position, $definitions)) {
            // Get position specific data
            $position_data = $definitions[$position];

            if ($position_data === true) {
                // Argument is required but we can't autocomplete it

            } else {
                if (is_array($position_data)) {
                    // This is the old auto complete formatting
                    if ($word) {
                        // We may have a word or not, check if position_data allows word (or not) and process
                        if (array_key_exists('word', $position_data)) {
                            $results = static::processDefinition('position ' . $position, $position_data['word'], $word);
                        }

                    } else {
                        if (array_key_exists('noword', $position_data)) {
                            $results = static::processDefinition('position ' . $position, $position_data['noword'], $word);
                        }
                    }

                } else {
                    // This is the new auto complete formatting
                    $results = static::processDefinition('position ' . $position, $position_data, $word);
                }
            }

            // Process results only if we have any
            if (isset($results)) {
                CliAutoComplete::showResults($results, $word);

                // Die here as we've echoed the results!
                exit();
            }
        }
    }


    /**
     * Process command arguments
     *
     * @param IteratorInterface|array|null $o_definitions
     *
     * @return never
     */
    #[NoReturn] public static function processCommandArguments(IteratorInterface|array|null $o_definitions): never
    {
        if ($o_definitions) {
            if ($o_definitions instanceof IteratorInterface) {
                // From here use array
                $o_definitions = $o_definitions->getSource();
            }

            CliAutoComplete::processArguments(array_merge($o_definitions, static::$system_arguments));
        }

        CliAutoComplete::processArguments(static::$system_arguments);
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

        return !empty(PhoFile::new(static::$command . '.php', PhoRestrictions::newFilesystemRootObject())
                             ->grep(['Documentation::setAutoComplete('], 500));
    }


    /**
     * Checks if autocomplete has been correctly setup
     *
     * @param bool $force
     *
     * @return void
     */
    public static function setup(bool $force = false): void
    {
        Log::action(ts('Ensuring autocomplete availability'), 2);

        $file = PhoFile::new('~/.bash_completion', PhoRestrictions::newWritableObject('~/.bash_completion'))
                       ->makeAbsolute(must_exist: false);

        if ($file->exists()) {
            if ($file->isReadable()) {
                // Check if it contains the setup for Phoundation
                // TODO Check if this is an issue with huge bash_completion files, are there huge files out there?
                $results = Grep::new($file->getParentDirectoryObject())
                               ->setValue('_phoundation pho')
                               ->setFileObject($file)
                               ->grep(EnumExecuteMethod::returnArray);

                if ($results) {
                    // bash_completion contains rule for Phoundation
                    if (!$force) {
                        // We're done
                        return;
                    }

                    // Phoundation rule exists, update it forcibly by replacing the old command with the current
                    $contents    = $file->getContentsAsString();
                    $phoundation = Strings::from($contents, '_phoundation()');
                    $phoundation = Strings::untilReverse($phoundation, '_phoundation pho');
                    $phoundation = '_phoundation()' . $phoundation . '_phoundation pho';
                    $contents    = str_replace($phoundation, CliAutoComplete::getBashCompleteCommand(), $contents);

                    $file->putContents($contents);

                    Log::success('Updated auto complete for Phoundation in ~/.bash_completion');
                    Log::success('You may need to logout and login again for auto complete to work correctly with the new update');
                    return;
                }

            } else {
                // File is not readable
                if (!$file->uidMatchesPuid()) {
                    // Owner mismatch of file itself
                    Log::warning(ts('Not initializing existing bash completion file ":file" as its owner UID ":fuid (:fname)" does not match this process UID ":puid (:pname)"', [
                        ':file'  => $file->getAbsolutePath(must_exist: false),
                        ':fuid'  => $file->getOwnerUid(),
                        ':fname' => $file->getOwnerName(),
                        ':puid'  => Core::getProcessUid(),
                        ':pname' => Core::getProcessUsername()
                    ]));

                    return;
                }

                // Different reason
                Log::warning(ts('Cannot access bash completion file ":file", not performing auto-complete initialization check', [
                    ':file'  => $file->getAbsolutePath(must_exist: false),
                    ':fuid'  => $file->getOwnerUid(),
                    ':fname' => $file->getOwnerName(),
                    ':puid'  => Core::getProcessUid(),
                    ':pname' => Core::getProcessUsername()
                ]));

                return;
            }

        } else {
            // File does not exist. Does the parent directory match?
            if (!$file->getParentDirectoryObject()->uidMatchesPuid()) {
                Log::warning(ts('Not trying to initialize bash completion file ":file" as the owner UID ":fuid (:fname)" of the parent directory ":directory" does not match this process UID ":puid (:pname)"', [
                    ':directory' => $file->getParentDirectoryObject()->getAbsolutePath(must_exist: false),
                    ':file'      => $file->getAbsolutePath(must_exist: false),
                    ':fuid'      => $file->getParentDirectoryObject()->getOwnerUid(),
                    ':fname'     => $file->getParentDirectoryObject()->getOwnerName(),
                    ':puid'      => Core::getProcessUid(),
                    ':pname'     => Core::getProcessUsername()
                ]));

                return;
            }

            // Initialize the bash_completion file
            $file->appendData('#/usr/bin/env bash' . PHP_EOL);
        }

        // Phoundation command line auto complete has not yet been set up, do so now.
        $file->appendData(PHP_EOL . CliAutoComplete::getBashCompleteCommand() . PHP_EOL);

        // Source the .bash_completion file
        Process::new('source', which_command: false)
               ->setExecuteBash(true)
               ->setArgument('~/.bash_completion', false)
               ->executePassthru();

        Log::success('Setup auto complete for Phoundation in ~/.bash_completion');
        Log::success('You may need to logout and login again for auto complete to work correctly');
    }


    /**
     * Returns the bash complete command
     *
     * @return string
     */
    public static function getBashCompleteCommand(): string
    {
        return '_phoundation()
{
PHO=$(./pho --auto-complete "${COMP_CWORD} ${COMP_LINE}");
COMPREPLY+=($(compgen -W "$PHO"));
}

complete -F _phoundation pho';
    }
}
