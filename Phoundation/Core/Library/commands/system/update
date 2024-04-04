<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Phoundation\Exception\PhoundationBranchNotExistException;
use Phoundation\Developer\Phoundation\Phoundation;
use Phoundation\Developer\Project\Project;
use Phoundation\Filesystem\Directory;


/**
 * Script system/update
 *
 * This script can be used to test the authentication for the specified user
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

CliDocumentation::setAutoComplete([
                                      'arguments' => [
                                          '-b,--branch'      => [
                                              'word'   => function ($word) { return Phoundation::new()->getPhoundationBranches()->getMatchingKeys($word); },
                                              'noword' => function () { return Phoundation::new()->getPhoundationBranches()->getKeys(); },
                                          ],
                                          '-p,--phoundation' => [
                                              'word'   => function ($word) { return Directory::new('/var/www/html', '/var/www/html')->scan($word . '*'); },
                                              'noword' => function () { return Directory::new('/var/www/html', '/var/www/html')->scan(); },
                                          ],
                                          '-c,--check'       => false,
                                          '-l,--local'       => false,
                                          '-m,--message'     => true,
                                          '--no-commit'      => false,
                                          '--no-phoundation' => false,
                                          '-n,--no-plugins'  => false,
                                          '-s,--signed'      => false,
                                          '--no-caching'     => false,
                                      ],
                                  ]);

CliDocumentation::setUsage('./pho system update [OPTIONS]
./pho system update -b BRANCH
./pho system update -b  BRANCH --check
./pho system update -l --branch BRANCH
');

CliDocumentation::setHelp('This command will update your Phoundation libraries and list


ARGUMENTS


-b / --branch                           The branch from which to update.

[-c / --check]                          If specified will only check for available updates.

[-l / --local]                          If specified update from a local Phoundation core repository.

[-m / --message]                        The git commit message for this update. If not specified, a default will be used

[--no-caching]                          If specified will skip caching all Phoundation libraries which is normally done
                                        to avoid loading incomplete library classes against each other during and after
                                        the update has been finished. This may want to be skipped if a current library
                                        has a bug, or a new functionality executing during the finishing phase of the
                                        update system actually is expected or required.

[--no-commit]                           If specified will not commit after updating.

[--no-phoundation]                      If specified will not update phoundation core files.

[-n / --no-plugins]                     If specified will not update the plugins.

[-p / --phoundation]                    If specified should contain the path to your local Phoundation installation.

[-s / --signed]                         If specified will make a signed commit. This requires your git setup to be
                                        configured correctly for this. See the section "--signoff" in "git help commit"
                                        for more information on this subject.');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-b,--branch', true)->isOptional()->isVariable()
                     ->select('-c,--check')->isOptional()->isBoolean()
                     ->select('-l,--local')->isOptional()->isBoolean()
                     ->select('-m,--message', true)->isOptional()->isPrintable()->hasMinCharacters(10)->hasMaxCharacters(1024)
                     ->select('--no-commit')->isOptional()->isBoolean()
                     ->select('-n,--no-plugins')->isOptional()->isBoolean()
                     ->select('--no-phoundation')->isOptional()->isBoolean()
                     ->select('-p,--phoundation', true)->isOptional()->isPrintable()
                     ->select('-s,--signed')->isOptional(false)->isBoolean()
                     ->select('--no-caching')->isOptional(false)->isBoolean()
                     ->validate();


// Load all the Phoundation classes into memory to avoid newer -and possibly incompatible- classes being loaded in the
// processes right after the update that could cause a crash
if (!$argv['no_caching']) {
    Libraries::loadAllPhoundationClassesIntoMemory();
}


// Start the update
if ($argv['local']) {
    // Perform an update from local repositories on this computer
    try {
        if ($argv['no_phoundation']) {
            // Don't update Phoundation libraries
            Log::warning('Not updating phoundation core files');
        } else {
            Log::action(tr('Pulling updates from local Phoundation installation...'));
            Project::new()->updateLocalProject($argv['branch'], $argv['message'], $argv['signed'], $argv['phoundation'], !$argv['no_commit']);
        }

        if ($argv['no_plugins']) {
            // Don't update the plugins nor the templates
            Log::warning('Not updating plugins nor templates');
        } else {
            Log::action(tr('Pulling updates from local Phoundation plugins installation...'));
            Project::new()->updateLocalProjectPlugins($argv['branch'], $argv['message'], $argv['signed'], $argv['phoundation'], !$argv['no_commit']);
        }

    } catch (PhoundationBranchNotExistException $e) {
        throw $e->makeWarning();
    }

} elseif ($argv['check']) {
    // Don't update, just check
    Log::information(tr('Checking for Phoundation updates...'));
    Project::checkUpdates();

} else {
    // Perform an update from the remote repositories. This should be the default unless you're a Phoundation developer
    Log::information(tr('Updating Phoundation...'));
    Project::update();
}
