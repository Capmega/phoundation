<?php

/**
 * Command developer repositories status
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will list the status for all known phoundation repositories
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Versioning\Repositories\Repositories;


// Start documentation
CliDocumentation::setAutoComplete();

CliDocumentation::setUsage('./pho development repositories status
./pho development repositories status -h');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will status all known phoundation repositories 


ARGUMENTS


-


OPTIONAL ARGUMENTS


[-h, --human-readable]                  If specified, will display the information with human readable statuses'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-h,--human-readable')->isOptional()->isBoolean()
                     ->validate();


if ($argv['human_readable']) {
    $columns = [
        'file'            => tr('File'),
        'readable_status' => tr('Status'),
    ];

} else {
    $columns = [
        'file'   => tr('File'),
        'status' => tr('Status'),
    ];
}

// List status for all available repositories
Repositories::new()->load()->getStatusObject()->displayCliTable($columns);
