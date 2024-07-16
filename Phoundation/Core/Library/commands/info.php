<?php

/**
 * Script info
 *
 * This command will display detailed information about the current framework, project, database ,etc.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Cli\CliColor;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Core;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Exception\SqlAccessDeniedException;
use Phoundation\Databases\Sql\Exception\SqlDatabaseDoesNotExistException;
use Phoundation\Developer\Debug;
use Phoundation\Utils\Config;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;

CliDocumentation::setUsage('./pho info');
CliDocumentation::setHelp('The info script will show detailed information about the current framework, project, database and more


ARGUMENTS


-');


$argv  = ArgvValidator::new()->validate();
$no_db = false;

try {
    Sql()->query('SELECT 1');

} catch (SqlDatabaseDoesNotExistException $e) {
    $no_db = tr('Database does not exist');

    define('FRAMEWORKDBVERSION', tr('Database ":db" does not exist', [':db' => Config::get('databases.sql.instances.system.name')]));
    define('PROJECTDBVERSION', tr('Database ":db" does not exist', [':db' => Config::get('databases.sql.instances.system.name')]));

} catch (SqlAccessDeniedException $e) {
    $no_db = tr('Access denied');

    define('FRAMEWORKDBVERSION', tr('Access denied to server or database ":db"', [':db' => Config::get('databases.sql.instances.system.name')]));
    define('PROJECTDBVERSION', tr('Access denied to server or database ":db"', [':db' => Config::get('databases.sql.instances.system.name')]));
}

$system_size    = 0;
$plugins_size   = 0;
$templates_size = 0;

//$framework_status = version_compare(Core::FRAMEWORKCODEVERSION, Core::getVersion('framework'));
//$project_status   = version_compare(Config::get('project.version')  , Core::getVersion('project'));

Log::cli(CliColor::apply(Strings::size(tr('Framework:'), 28), 'white') . ' ' . 'PHOUNDATION');
Log::cli(CliColor::apply(Strings::size(tr('Project name:'), 28), 'white') . ' ' . PROJECT);
Log::cli(CliColor::apply(Strings::size(tr('Current platform:'), 28), 'white') . ' ' . PLATFORM);
Log::cli(CliColor::apply(Strings::size(tr('Environment:'), 28), 'white') . ' ' . ENVIRONMENT);
Log::cli(CliColor::apply(Strings::size(tr('Production:'), 28), 'white') . ' ' . Strings::fromBoolean(Core::isProductionEnvironment()));
Log::cli(CliColor::apply(Strings::size(tr('Debug:'), 28), 'white') . ' ' . Strings::fromBoolean(Debug::isEnabled()));
Log::cli(CliColor::apply(Strings::size(tr('Core database:'), 28), 'white') . ' ' . Config::get('databases.sql.instances.system.name', 'unknown') . ($no_db ? ' (' . CliColor::apply(tr('NOT CONNECTED BECAUSE ":reason"', [':reason' => $no_db]), 'red') . ')' : ''));

Log::cli(' ');
Log::cli(CliColor::apply(Strings::size(tr('System libraries:'), 28) . ' ' . Strings::size(tr('Code version'), 14) . Strings::size(tr('Database version'), 18) . Strings::size(tr('Size'), 14), 'white'));

foreach (Libraries::listLibraries(true, false, false) as $library) {
    $system_size += $library->getSize();
    Log::cli(CliColor::apply(Strings::size($library->getName(), 28), 'white') . ' ' . Strings::size($library->getCodeVersion() ?? '-', 14) . Strings::size(($no_db ? '?' : $library->getDatabaseVersion() ?? '-'), 18) . Strings::size(Numbers::getHumanReadableBytes($library->getSize()) ?? '-', 14));
}

Log::cli(' ');
Log::cli(CliColor::apply(Strings::size(tr('Plugin libraries:'), 28) . ' ' . Strings::size(tr('Code version'), 14) . Strings::size(tr('Database version'), 18) . Strings::size(tr('Size'), 14), 'white'));

foreach (Libraries::listLibraries(false, true, false) as $library) {
    $plugins_size += $library->getSize();
    Log::cli(CliColor::apply(Strings::size($library->getVendor() . '/' . $library->getName(), 28) . ' ' . Strings::size($library->getCodeVersion() ?? '-', 14) . Strings::size(($no_db ? '?' : $library->getDatabaseVersion() ?? '-'), 18) . Strings::size(Numbers::getHumanReadableBytes($library->getSize()) ?? '-', 14), 'white'));
}

Log::cli(' ');
Log::cli(CliColor::apply(Strings::size(tr('Template libraries:'), 61) . Strings::size(tr('Size'), 14), 'white'));

foreach (Libraries::listLibraries(false, false, true) as $library) {
    $templates_size += $library->getSize();
    Log::cli(CliColor::apply(Strings::size($library->getName(), 28), 'white') . ' ' . Strings::size('-', 14) . Strings::size('-', 18) . Strings::size(Numbers::getHumanReadableBytes($library->getSize()) ?? '-', 14));
}

Log::cli(' ');
Log::cli(CliColor::apply(Strings::size(tr('Statistics:'), 28), 'white'));
Log::cli(CliColor::apply(Strings::size(tr('Total library size:'), 28), 'white') . ' ' . Numbers::getHumanReadableBytes($system_size));
Log::cli(CliColor::apply(Strings::size(tr('Plugins library size:'), 28), 'white') . ' ' . Numbers::getHumanReadableBytes($plugins_size));
Log::cli(CliColor::apply(Strings::size(tr('Templates library size:'), 28), 'white') . ' ' . Numbers::getHumanReadableBytes($templates_size));
Log::cli(CliColor::apply(Strings::size(tr('Total library size:'), 28), 'white') . ' ' . Numbers::getHumanReadableBytes($system_size + $plugins_size + $templates_size));

Log::cli();
