<?php

/**
 * Command info
 *
 * This command will display detailed information about the current framework, project, database ,etc.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

use Phoundation\Cli\CliColor;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Core;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Libraries\Version;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Exception\SqlAccessDeniedException;
use Phoundation\Databases\Sql\Exception\SqlUnknownDatabaseException;
use Phoundation\Developer\Debug\Debug;
use Phoundation\Developer\Project\Project;
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

} catch (SqlUnknownDatabaseException $e) {
    $no_db = tr('Database does not exist');

    define('FRAMEWORKDBVERSION', tr('Database ":db" does not exist', [':db' => config()->get('databases.connectors.system.database')]));
    define('PROJECTDBVERSION', tr('Database ":db" does not exist', [':db' => config()->get('databases.connectors.system.database')]));

} catch (SqlAccessDeniedException $e) {
    $no_db = tr('Access denied');

    define('FRAMEWORKDBVERSION', tr('Access denied to server or database ":db"', [':db' => config()->get('databases.connectors.system.database')]));
    define('PROJECTDBVERSION', tr('Access denied to server or database ":db"', [':db' => config()->get('databases.connectors.system.database')]));
}

$system_size    = 0;
$plugins_size   = 0;
$templates_size = 0;

// Gather library information
$system    = Libraries::listLibraries(true, false, false);
$plugins   = Libraries::listLibraries(false, true, false);
$templates = Libraries::listLibraries(false, false, true);

//$framework_status = version_compare(Core::FRAMEWORKCODEVERSION, Project::getVersion('framework'));
//$project_status   = version_compare(Project::getVersion()  , Project::getVersion('project'));

Log::cli(CliColor::apply(Strings::size(tr('Framework name:'), 30), 'white') . ' ' . 'PHOUNDATION');
Log::cli(CliColor::apply(Strings::size(tr('Framework version:'), 30), 'white') . ' ' . Core::PHOUNDATION_VERSION);
Log::cli(CliColor::apply(Strings::size(tr('Database version:'), 30), 'white') . ' ' . Version::getString(Libraries::getMaximumVersion()));
Log::cli(CliColor::apply(Strings::size(tr('Project name:'), 30), 'white') . ' ' . Project::getFullName());
Log::cli(CliColor::apply(Strings::size(tr('Project version:'), 30), 'white') . ' ' . Project::getVersion());
Log::cli(CliColor::apply(Strings::size(tr('PHP required minimum version:'), 30), 'white') . ' ' . Core::PHP_MINIMUM_VERSION);
Log::cli(CliColor::apply(Strings::size(tr('Current platform:'), 30), 'white') . ' ' . PLATFORM);
Log::cli(CliColor::apply(Strings::size(tr('Environment:'), 30), 'white') . ' ' . ENVIRONMENT);
Log::cli(CliColor::apply(Strings::size(tr('Production:'), 30), 'white') . ' ' . Strings::fromBoolean(Core::isProductionEnvironment()));
Log::cli(CliColor::apply(Strings::size(tr('Debug:'), 30), 'white') . ' ' . Strings::fromBoolean(Debug::isEnabled()));
Log::cli(CliColor::apply(Strings::size(tr('Core database:'), 30), 'white') . ' ' . config()->get('databases.connectors.system.database', 'unknown') . ($no_db ? ' (' . CliColor::apply(tr('NOT CONNECTED BECAUSE ":reason"', [':reason' => $no_db]), 'red') . ')' : ''));

Log::cli(' ');
Log::cli(CliColor::apply(Strings::size(tr('System libraries:'), 30) . ' ' . Strings::size(tr('Code version'), 14) . Strings::size(tr('Database version'), 18) . Strings::size(tr('Size'), 14), 'white'));

foreach ($system as $library) {
    $system_size += $library->getSize();
    Log::cli(CliColor::apply(Strings::size($library->getName(), 30), 'white') . ' ' . Strings::size($library->getCodeVersion() ?? '-', 14) . Strings::size(($no_db ? '?' : $library->getDatabaseVersion() ?? '-'), 18) . Strings::size(Numbers::getHumanReadableBytes($library->getSize()) ?? '-', 14));
}

Log::cli(' ');
Log::cli(CliColor::apply(Strings::size(tr('Plugin libraries:'), 30) . ' ' . Strings::size(tr('Code version'), 14) . Strings::size(tr('Database version'), 18) . Strings::size(tr('Size'), 14), 'white'));

foreach ($plugins as $library) {
    $plugins_size += $library->getSize();
    Log::cli(CliColor::apply(Strings::size($library->getVendor() . '/' . $library->getName(), 30) . ' ' . Strings::size($library->getCodeVersion() ?? '-', 14) . Strings::size(($no_db ? '?' : $library->getDatabaseVersion() ?? '-'), 18) . Strings::size(Numbers::getHumanReadableBytes($library->getSize()) ?? '-', 14), 'white'));
}

Log::cli(' ');
Log::cli(CliColor::apply(Strings::size(tr('Template libraries:'), 61) . Strings::size(tr('Size'), 14), 'white'));

foreach ($templates as $library) {
    $templates_size += $library->getSize();
    Log::cli(CliColor::apply(Strings::size($library->getName(), 30), 'white') . ' ' . Strings::size('-', 14) . Strings::size('-', 18) . Strings::size(Numbers::getHumanReadableBytes($library->getSize()) ?? '-', 14));
}

Log::cli(' ');
Log::cli(CliColor::apply(Strings::size(tr('Statistics:'), 30), 'white'));

Log::cli(CliColor::apply(Strings::size(tr('System libraries:'), 30), 'white') . ' ' . number_format(count($system)));
Log::cli(CliColor::apply(Strings::size(tr('Plugin libraries:'), 30), 'white') . ' ' . number_format(count($plugins)));
Log::cli(CliColor::apply(Strings::size(tr('Template libraries:'), 30), 'white') . ' ' . number_format(count($templates)));
Log::cli(CliColor::apply(Strings::size(tr('Total libraries:'), 30), 'white') . ' ' . number_format(count($system) + count($plugins) + count($templates)));
Log::cli(' ');
Log::cli(CliColor::apply(Strings::size(tr('Total library size:'), 30), 'white') . ' ' . Numbers::getHumanReadableBytes($system_size));
Log::cli(CliColor::apply(Strings::size(tr('Plugins library size:'), 30), 'white') . ' ' . Numbers::getHumanReadableBytes($plugins_size));
Log::cli(CliColor::apply(Strings::size(tr('Templates library size:'), 30), 'white') . ' ' . Numbers::getHumanReadableBytes($templates_size));
Log::cli(CliColor::apply(Strings::size(tr('Total library size:'), 30), 'white') . ' ' . Numbers::getHumanReadableBytes($system_size + $plugins_size + $templates_size));

Log::cli();
