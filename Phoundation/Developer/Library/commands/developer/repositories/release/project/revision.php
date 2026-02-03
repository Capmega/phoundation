<?php

/**
 * Command developer repositories release project revision
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will release an upgraded version with an increased revision number for each of your project repositories
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Phoundation\Enums\EnumPhoundationClass;
use Phoundation\Developer\Versioning\Repositories\Repositories;


// Start documentation
CliDocumentation::setAutoComplete([
    'positions' => [
        0 => true
    ]
]);

CliDocumentation::setUsage('./pho development repositories release project revision
./pho development rp rl pr rv');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will release an upgraded version with an increased revision number for each of your project repositories 


ARGUMENTS


-


OPTIONAL ARGUMENTS


-n, --number                            The amount to increase the revision number by
                                        [1]'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-n,--number', true)->isOptional()->isPositive()
                     ->validate();


// Synchronize all available repositories
$o_repositories = Repositories::new()->load();

Log::cli(ts('Releasing revision update, this might take a few seconds...'), 'action');

$o_repositories->releaseRevision(EnumPhoundationClass::project, $argv['number']);
