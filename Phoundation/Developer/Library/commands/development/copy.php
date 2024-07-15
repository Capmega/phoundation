<?php

/**
 * This script will copy the specified files back to the various phoundation repositories found on your system
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Development
 */

declare(strict_types=1);

use Phoundation\Cli\CliCommand;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Phoundation\Phoundation;
use Phoundation\Developer\Phoundation\Repositories\Repositories;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;

CliDocumentation::setAutoComplete([
                                      'arguments' => [
                                          '-a,--allow-changes' => false,
                                          '-b,--branch'        => [
                                              'word'   => function ($word) { return Phoundation::new()->getPhoundationBranches()->keepMatchingKeysStartingWith($word); },
                                              'noword' => function ()      { return Phoundation::new()->getPhoundationBranches()->getSourceKeys(); },
                                          ],
                                      ],
                                  ]);

CliDocumentation::setUsage('./pho project copy 
./pho project copy --allow-changes FILE FILE FILE FILE
./pho project copy --branch 4.9-test FILE FILE');

CliDocumentation::setHelp('This command will copy the specified library file directly to your Phoundation installation

If, for example, you specify Phoundation/Web/Page.php as the file, it will copy this file back to your Phoundation
installation in the exact same location


ARGUMENTS


[FILE ... FILE FILE]                    A space separated list of files to copy. If left out, will try to copy all 
                                        files that have changes and have counterparts in the found repositories

-a, --allow-changes                     If specified will allow copies to repositories that contain uncommitted git 
                                        changes, allowing for potential loss of work
                                        
-b, --branch BRANCH                     Change the Phoundation to the specified branch');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-a,--allow-changes')->isOptional(false)->isBoolean()
                     ->select('-b,--branch', true)->isOptional()->isVariableName()
                     ->selectAll('files')->isOptional()->each()->isPath(FsDirectory::getRoot(false))
                     ->validate();


// Copy the file to the correct remote repositories
Repositories::new()
            ->setBranch($argv['branch'])
            ->setAllowChanges($argv['allow_changes'])
            ->copy($argv['files']);
