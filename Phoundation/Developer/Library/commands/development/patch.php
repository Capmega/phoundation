<?php

/**
 * Command developer patch
 *
 * THIS SCRIPT IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This script will copy git change patches back to your phoundation core, phoundation plugins, and phoundation
 * templates installations where you can submit them.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Development
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Phoundation\Exception\PatchPartiallySuccessfulException;
use Phoundation\Developer\Phoundation\Phoundation;
use Phoundation\Developer\Phoundation\Repositories\Repositories;
use Phoundation\Developer\Versioning\Git\Exception\GitPatchFailedException;
use Phoundation\Os\Processes\Commands\PhoCommand;
use Phoundation\Utils\Arrays;

// TODO Improve autocomplete, --branch should show branch options
CliDocumentation::setAutoComplete([
                                      'arguments' => [
                                          '-b,--branch'      => [
                                              'word'   => function ($word) { return Phoundation::new()->getPhoundationBranches()->keepMatchingKeysStartingWith($word); },
                                              'noword' => function ()      { return Phoundation::new()->getPhoundationBranches()->getSourceKeys(); },
                                          ],
                                          '-m,--message'     => true,
                                          '-c,--no-checkout' => false,
                                          '--no-phoundation' => false,
                                          '-n,--no-plugins'  => false,
                                          '-u,--no-update'   => false,
                                          '-p,--phoundation' => true,
                                          '-s,--signed'      => false,
                                      ],
                                  ]);

CliDocumentation::setUsage('./pho project update [OPTIONS]
./pho system development phoundation patch -b BRANCH
./pho system development phoundation patch -b  BRANCH --no-checkout -s
./pho system development phoundation patch -l --branch BRANCH -p /home/USER/projects/phoundation
');

CliDocumentation::setHelp('This command will update your Phoundation libraries and list


ARGUMENTS


[-b,--branch BRANCH]                    The branch from which to update

[-m,--message MESSAGE]                  The git commit message for this update. If not specified, a default will be used

[-c,--no-checkout]                      If specified will not automatically checkout (and thus remove) the local changes

[--no-phoundation]                      If specified will not patch the Phoundation core libraries

[-n,--no-plugins]                       If specified will not patch the plugins

[-u,--no-update]                        If specified will not perform a system update before executing the patching
                                        process

[-p,--phoundation PATH]                 If specified should contain the path to your local Phoundation installation

[-s,--signed]                           If specified will make a signed commit. This requires your git setup to be
                                        configured correctly for this');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-b,--branch', true)->isOptional()->isPrintable()
                     ->select('-c,--no-checkout')->isOptional()->isBoolean()
                     ->select('-m,--message', true)->isOptional()->isPrintable()->hasMinCharacters(10)->hasMaxCharacters(1024)
                     ->select('-p,--phoundation', true)->isOptional()->isPrintable()
                     ->select('-s,--signed')->isOptional()->isBoolean()
                     ->select('-n,--no-plugins')->isOptional()->isBoolean()
                     ->select('-t,--no-templates')->isOptional()->isBoolean()
                     ->select('--no-phoundation')->isOptional()->isBoolean()
                     ->select('-u,--no-update')->isOptional()->isBoolean()
                     ->select('-f,--no-forced-copy')->isOptional()->isBoolean()
                     ->validate();


Log::information(tr('Copying local changes in project ":project" back to your Phoundation repositories', [
    ':project' => PROJECT,
]));


// First update Phoundation, if allowed
if (!$argv['no_checkout']) {
    PhoCommand::new('system update')
              ->addArguments([
                                 $argv['no_phoundation'] ? '--no-phoundation'              : null,
                                 $argv['no_plugins']     ? '--no-plugins'                  : null,
                                 $argv['no_templates']   ? '--no-templates'                : null,
                                 $argv['signed']         ? ['--signed' , $argv['signed']]  : null,
                                 $argv['message']        ? ['--message', $argv['message']] : null,
                                 $argv['branch']         ? ['--branch' , $argv['branch']]  : null,
                             ])
              ->executePassthru();
}


// Start the repository patching process
try {
    Repositories::new()
                ->scan()
                ->setBranch($argv['branch'])
                ->setPatchCore(!$argv['no_phoundation'])
                ->setPatchPlugins(!$argv['no_plugins'])
                ->setPatchTemplates(!$argv['no_templates'])
                ->setPatchForcedCopy(!$argv['no_forced_copy'])
                ->setPatchCheckout(!$argv['no_checkout'])
                ->patch();

} catch (GitPatchFailedException | PatchPartiallySuccessfulException $e) {
    $files = $e->getDataKey('files');

    if ($files) {
        Log::warning(tr('Failed to patch the following files:'));

        foreach (Arrays::force($files) as $file) {
            Log::write($file, 'debug');
        }
    }

    throw $e->makeWarning();
}
