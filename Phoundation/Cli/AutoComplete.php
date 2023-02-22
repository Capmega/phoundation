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
use Phoundation\Processes\Commands\Command;
use Phoundation\Processes\Process;
use Throwable;


/**
 * Class AutoComplete
 *
 *
 * @note See
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Cli
 */
class AutoComplete
{
    /**
     * The word location for the auto complete
     *
     * @var int $position
     */
    protected int $position;


    /**
     * AutoComplete class constructor
     *
     * @param int $position
     */
    public function __construct(int $position)
    {
        $this->position = $position;
    }



    /**
     * Process the auto complete for command line methods
     *
     * @see https://iridakos.com/programming/2018/03/01/bash-programmable-completion-tutorial
     * @param array|null $cli_methods
     * @param array $data
     * @return void
     */
    #[NoReturn] public function processMethods(?array $cli_methods, array $data): void
    {
        // $data['position'] is the amount of found methods
        // $this->position is the word # where the cursor was when <TAB> was pressed
        if ($cli_methods === null) {
            if ($this->position) {
                // Invalid situation, supposedly there is an auto complete location, but no methods?
                die('Invalid auto complete arguments' . PHP_EOL);
            }

            foreach ($data['methods'] as $method) {
                echo $method . PHP_EOL;
            }

        } elseif ($this->position > count($cli_methods)) {
            // Invalid situation, supposedly the location was beyond, after the amount of arguments?
            die('Invalid auto complete arguments' . PHP_EOL);

        } elseif ($data['position'] > $this->position) {
            // The findScript() method already found this particular word, so we know it exists!
            echo $cli_methods[$this->position];

        } else {
            $starts   = [];
            $contains = [];

            $argument_method = isset_get($cli_methods[$this->position], '');

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
     * Checks if autocomplete has been correctly setup
     *
     * @return void
     */
    public function checkEnabled(): void
    {
        $output = Process::new('complete')
            ->setPipe(Process::new('grep')
                ->addArgument('-i')
                ->addArgument('phoundation'))
            ->executeReturnString();

        if (!$output or ($output !== 'complete -F _phoundation pho')) {
            // Phoundation command line auto complete has not yet been set up, do so now.
            File::new('~/.phoundation_autocomplete')->appendData('
#/usr/bin/env bash
_phoundation()
{
PHO=$(./pho --auto-complete "${COMP_CWORD} ${COMP_LINE}");
COMPREPLY+=($(compgen -W "$PHO"));
}

complete -F _phoundation pho
');
        }

        Log::warning('Auto complete setup has been added to your .bashrc file. Please run "source ~/.bashrc" to ensure it is applied');
    }
}