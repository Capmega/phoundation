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

use Phoundation\Cli\CliCommand;
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


GLOBAL SYSTEM ARGUMENTS


The following arguments are available to ALL commands


') . CliCommand::getHelpGlobalArguments();
