<?php

declare(strict_types=1);

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliColor;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Os\Processes\Commands\Databases\MySql;


/**
 * Script databases/mysql/timezones/import
 *
 * This script will import the mysql timezones
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho databases mysql timezones import');

CliDocumentation::setHelp('This command will import the mysql timezones


ARGUMENTS


-');

ArgvValidator::new()->validate();

Log::cli(CliColor::apply(tr('Importing timezone data files in MySQL, this may take a couple of seconds'), 'white'));
Log::cli(tr('You may ignore any "Warning: Unable to load \'/usr/share/zoneinfo/........\' as time zone. Skipping it." messages'));
Log::cli(tr('Please fill in MySQL root password in the following "Enter password:" request'));

$password = Cli::readPassword('Please specify the MySQL root password');

if (!$password) {
    throw OutOfBoundsException::new(tr('No MySQL root password specified'))->makeWarning();
}

Mysql::new()->importTimezones($password);
