<?php

/**
 * Command libraries info
 *
 * This command will display detailed information about the specified library
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliColor;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Libraries\Library;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;


CliDocumentation::setUsage('./pho libraries info LIBRARY_NAME');

CliDocumentation::setHelp('The libraries info script will show detailed information about the specified library');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('-t,--type,--types', true)->isOptional(null)->isName()
                     ->select('library')->isOptional(null)->hasMaxCharacters(64)->sanitizeForceArray()->each()->isName()
                     ->validate();


// Show information for a specific library
if ($argv['library']) {
    if ($argv['type']) {
        throw OutOfBoundsException::new(tr('Library nane AND -t / --type specified, these are mutually exclusive. Please specify either one or the other'))->makeWarning();
    }

    foreach ($argv['library'] as $library) {
        $library    = Library::get($library);
        $statistics = $library->getPhpStatistics();

        Log::cli(CliColor::apply(Strings::size(tr('Name:')       , 28), 'white') . ' ' . $library->getName());
        Log::cli(CliColor::apply(Strings::size(tr('Version:')    , 28), 'white') . ' ' . $library->getVersion());
        Log::cli(CliColor::apply(Strings::size(tr('Path:')       , 28), 'white') . ' ' . $library->getDirectory());
        Log::cli(CliColor::apply(Strings::size(tr('Description:'), 28), 'white') . ' ' . $library->getDescription());

        Log::cli();

        Log::cli(CliColor::apply(Strings::size(tr('Statistics:'), 28), 'white'));
        Cli::displayForm($statistics['total_statistics']);
    }

} elseif ($argv['type']) {
    // Show information for a type of libraries
    // Sanitize and validate
    $argv['type'] = strtolower(trim($argv['type']));

    switch ($argv['type']) {
        case 'system':
            break;

        case 'plugin':
            // no break
        case 'plugins':
            $argv['type'] = 'plugins';
            break;

        case 'template':
            // no break

        case 'templates':
            $argv['type'] = 'templates';
            break;

        default:
            throw OutOfBoundsException::new(tr('Unknown library type ":type" specified. Please specify one of "system", "plugins" or "templates"', [
                ':type' => $argv['type'],
            ]));
    }

    // Get statistics and display
    $statistics = Libraries::getPhpStatistics(($argv['type'] === 'system'), ($argv['type'] === 'plugins'), ($argv['type'] === 'templates'));

    Log::cli(CliColor::apply(tr('Statistics:'), 'white'));
    Log::cli(' ');
    Log::cli(CliColor::apply(tr('Statistics for ":type" type libraries:', [':type' => Strings::capitalize($argv['type'])]), 'white'));
    Cli::displayForm($statistics[$argv['type']]['total_statistics']);

} else {
    $statistics = Libraries::getPhpStatistics();
    $types      = [
        'system',
        'plugins',
        'templates',
        'totals',
    ];

    Log::cli(CliColor::apply(Strings::size(tr('Statistics:'), 28), 'white'));

    foreach ($types as $type) {
        $statistics[$type]['total_statistics']['size'] = Numbers::getHumanReadableBytes($statistics[$type]['total_statistics']['size']);

        Log::cli(' ');
        Log::cli(CliColor::apply(tr('Statistics for ":type" libraries:', [':type' => $type]), 'white'));
        Cli::displayForm($statistics[$type]['total_statistics']);
        Log::cli(' ');
    }
}
