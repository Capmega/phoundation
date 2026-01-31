<?php

/**
 * Command developer repositories tags delete-auto
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will synchronize the tags for all known phoundation repositories
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
use Phoundation\Developer\Versioning\Repositories\Repositories;


// Start documentation
CliDocumentation::setAutoComplete([
    'positions' => [
        0 => true
    ],
    'arguments' => [
        '-r,--not-remote' => false
    ]
]);

CliDocumentation::setUsage('./pho development repositories tags delete-auto BRANCH_NAME
./pho development rp br da BRANCH_NAME -Fr');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will delete the tags with the specified suffix for all known phoundation repositories, ensuring all repositories are on the right tag


ARGUMENTS


SUFFIX                                  The tag suffix after the version, for the tag to delete. For example, if 
                                        the project has version 1.1 and the phoundation version is 4.18, and the 
                                        specified suffix is "HELLO", then all project tags named 1.1-HELLO, and all 
                                        Phoundation tags named 4.18-HELLO will be deleted


OPTIONAL ARGUMENTS


[-r, --not-remote]                      If specified will NOT remove the tags from the default remote repository

[-F, --force]                           If specified, will forcibly delete the tag, even when there are reasons not 
                                        to, like the tag containing changes that have not been merged anywhere yet'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('suffix')->matchesRegex('/^[a-z0-9-]+$/i')
                     ->select('-r,--not-remote')->isOptional()->isBoolean()
                     ->validate();


// Load all available repositories and delete the requested tag
$o_repositories = Repositories::new()->load();

Log::cli(ts('Deleting tags for ":count" repositories, this might take a few seconds...', [
    ':count' => $o_repositories->getCount()
]), 'action');

$o_repositories->deleteVersionTag($argv['suffix'], !$argv['not_remote']);
