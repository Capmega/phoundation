<?php

/**
 * Command intro
 *
 * This command will display detailed information about the current framework, project, database ,etc.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;


CliDocumentation::setUsage('./pho intro');

CliDocumentation::setHelp('This introduction script will print a help text to help you familiarize yourself with 
Phoundation


ARGUMENTS


-');


echo tr('This is the Phoundation CLI interface command "pho"

With this Command Line Interface command you can manage your Phoundation installation and perform various tasks. Almost
all web interface functionalities are also available on the command line and certain maintenance and development options
are ONLY available on the CLI

The "pho" command line has bash command line auto complete support so with the <TAB> key you can very easily see  what
commands are available to you. Auto complete support is also already enabled for some commands so (for example) user
modification with "./pho accounts user modify" can show all available options when pressing the <TAB> key.

The ./pho command also has single letter parameter expansion, so you can specify -s -m -a as  -sma

The system arguments are ALWAYS available no matter what command is being executed. Some arguments always apply, others
only apply for the commands that implement and or use them. If a system modifier argument was specified with a command
that does not support it, it will simply be ignored. See the --help output for each command for more information.

Almost every command has help, usage or auto complete support and as such, running .pho command -H will print a help
text with options for that command

Arguments in the help files contain information on if the argument requires a value or not (--no-value or --value VALUE)
where the required value is specified in UPPERCASE LETTERS. The help documentation also shows if the argument is
optional or required (optional arguments are enclosed in square brackets like [-o, --optional VALUE]. If the value for
an argument is optional then the value itself may also be enclosed in square brackets like
[--optional-parameter [OPTIONALVALUE]]. Arguments sometimes have a long form -a word preceded by two dashes- or a short
form -a letter, preceded by a single dash- which is documented as well. When both short and long forms are available, it
may be documented as follows: [-p, --parameter [VALUE]] This is an optional parameter "parameter" with an optional value
that may be indicated with --parameter, but also with -p

Phoundation typically outputs a lot of extra information to both screen and log files. You can modify the number of
information logged using --log-level. The higher the number, the higher the log threshold and the less information is
printed on screen. The --quiet option will also remove startup and shutdown messages and -G will remove prefix data,
printing only the output. Output typically is colored, but the --no-color option will print all output in plain text.
All these options can also be configured in the configuration files.

If command line modifier arguments that match system command line modifier arguments need to be passed on internally to 
the commands, then prefix them with an extra dash. So -W would become --W, and --warning would become ---warning. This 
is used (for example) in the command "development composer ..." commands, where -W may be required to force requiring 
packages.  


USEFUL COMMANDS:


./pho intro                             Prints an introduction text to Phoundation

./pho info                              Prints general information about your Phoundation installation

./pho libraries stats                   Prints statistics about the libraries in your Phoundation installation

./pho system init                       Will run the database initialization function. This will typically be run
                                        automatically whenever you install a new plugin or when you update your
                                        Phoundation code, but can be run manually when required

./pho system setup                      The first command you will want to run (or the first page that will show up
                                        after you installed Phoundation) which allows you to setup an initial
                                        configuration and initializes your database

./pho system maintenance disable        Disables maintenance mode manually. This may be needed if some command that
                                        placed the system in maintenance mode crashed, leaving the system unusable

./pho system maintenance enable         Enables maintenance mode manually.

./pho accounts users list               Lists all available users on the command line.


GLOBAL ARGUMENTS


The following arguments are available to ALL commands


[-A, --all]                             If set, the system will run in ALL mode, which typically will display normally
                                        hidden information like deleted entries. Only used by specific commands, check
                                        --help on commands to see if and how this flag is used.

[-C, --no-color]                        If set, your log and console output will no longer have color

[-D, --debug]                           If set will run your system in debug mode. Debug commands will now generate and
                                        display output

[-E, --environment ENVIRONMENT]         Sets or overrides the environment with which your pho command will be running.
                                        If no environment was set in the shell environment using the
                                        ":environment" variable, your pho command will refuse to
                                        run unless you specify the environment manually using these flags. The
                                        environment has to exist as a ROOT/config/ENVIRONMENT.yaml file

[-F, --force]                           If specified, will run the CLI command in FORCE mode, which will override certain
                                        restrictions. See --help for information on how specific commands deal with this
                                        flag

[-H, --help]                            If specified, will display the help page for the typed command

[-J, --json JSON]                       Allows argument to be specified in JSON format. The system will decode the 
                                        arguments and add them to the rest of the argument list without overwriting 
                                        arguments that were already specified on the command line

[-L, --log-level LEVEL]                 If specified, will set the minimum threshold level for log messages to appear.
                                        Any message with a threshold level below the indicated amount will not appear in
                                        the logs. Defaults to 5.

[-O, --order-by "COLUMN ASC|DESC"]      If specified, and used by the command (only commands that display tables) will
                                        order the table contents on the specified column in the specified direction.
                                        Defaults to nothing

[-P, --page PAGE]                       If specified, and used by the command (only commands that display tables) will
                                        show the table on the specified page. Defaults to 1

[-Q, --quiet]                           Will have the system run in quiet mode, suppressing log startup and shutdown
                                        messages. NOTE: This will override DEBUG output; QUIET will suppress all debug
                                        messages!

[-G, --no-prefix]                       Will suppress the DATETIME - LOGLEVEL - PROCESS ID - GLOBAL PROCESS ID prefix
                                        that normally begins each log line output

[-S, --status STATUS]                   If specified, will only display DataEntry entries with the specified status

[-T, --test]                            Will run the system in test mode. Different commands may change their behaviour
                                        depending on this flag, see their --help output for more information.

                                        NOTE: In this mode, temporary directories will NOT be removed upon shutdown so
                                        that their contents can be used for debugging and testing.

[-U, --usage]                           Prints various command usage examples for the typed command

[-V, --verbose]                         Will print more output during log startup and shutdown

[-W, --no-warnings]                     Will only use "error" type exceptions with backtrace and extra information,
                                        instead of displaying only the main exception message for warnings

[-Y, --clear-tmp]                       Will clear all temporary data in ROOT/data/tmp, and memcached

[-Z, --clear-caches]                    Will clear all caches in ROOT/data/cache, and memcached

[--system-language]                     Sets the system language for all output

[--deleted]                             Will show deleted DataEntry records

[--version]                             Will display the current version for your Phoundation installation

[--limit NUMBER]                        Will limit table output to the number of specified fields

[--timezone STRING]                     Sets the specified timezone for the command you are executing

[--show-passwords]                      Will display passwords visibly on the command line. Both typed passwords and
                                        data output will show passwords in the clear!

[--no-validation]                       Will not validate any of the data input.

                                        WARNING: This may result in invalid data in your database!

[--no-password-validation]              Will not validate passwords.

                                        WARNING: This may result in weak and or compromised passwords in your database
', [':environment' => 'PHOUNDATION_' . PROJECT . '_ENVIRONMENT']);
