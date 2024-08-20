<?php

/**
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will copy changed files back to your phoundation installation. The script will assume your phoundation
 * installation is in ~/projects/phoundation
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Development
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Phoundation\Exception\PatchPartiallySuccessfulException;
use Phoundation\Developer\Phoundation\Phoundation;
use Phoundation\Developer\Phoundation\Plugins;
use Phoundation\Developer\Versioning\Git\Exception\GitPatchFailedException;
use Phoundation\Utils\Arrays;


// TODO Improve autocomplete, --branch should show branch options
CliDocumentation::setAutoComplete([
                                      'arguments' => [
                                          '-b,--branch'      => true,
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

[-c,--no-checkout]                      If specified, will not automatically checkout (and thus remove) the local changes

[--no-phoundation]                      If specified, will not patch the Phoundation core libraries

[-n,--no-plugins]                       If specified, will not patch the plugins

[-u,--no-update]                        If specified, will not perform a system update before executing the patching
                                        process

[-p,--phoundation PATH]                 If specified, should contain the path to your local Phoundation installation

[-s,--signed]                           If specified, will make a signed commit. This requires your git setup to be
                                        configured correctly for this');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-b,--branch', true)->isOptional()->isPrintable()
                     ->select('-c,--no-checkout')->isOptional()->isBoolean()
                     ->select('-m,--message', true)->isOptional()->isPrintable()->hasMinCharacters(10)->hasMaxCharacters(1024)
                     ->select('-p,--phoundation', true)->isOptional()->isPrintable()
                     ->select('-s,--signed')->isOptional()->isBoolean()
                     ->select('-n,--no-plugins')->isOptional()->isBoolean()
                     ->select('--no-phoundation')->isOptional()->isBoolean()
                     ->select('-u,--no-update')->isOptional()->isBoolean()
                     ->validate();


Log::information(tr('Copying local changes in ":project" project back to your Phoundation installation', [
    ':project' => PROJECT,
]));

try {
    if ($argv['no_phoundation']) {
        Log::warning('Not patching Phoundation core libraries');

    } else {
        Log::action('Patching Phoundation core libraries');

        if ($argv['no_update']) {
            Log::warning('Not executing phoundation update');
        }

        Phoundation::new($argv['phoundation'])->patch($argv['branch'], $argv['message'], $argv['signed'], !$argv['no_checkout'], !$argv['no_update']);
    }

    if ($argv['no_plugins']) {
        Log::warning('Not patching plugins');

    } else {
        Log::action('Patching plugins');

        if ($argv['no_update']) {
            Log::warning('Not executing plugins update');
        }

        Plugins::new($argv['phoundation'])->patch($argv['branch'], $argv['message'], $argv['signed'], !$argv['no_checkout'], !$argv['no_update']);
    }

} catch (GitPatchFailedException|PatchPartiallySuccessfulException $e) {
    $files = $e->getDataKey('files');

    if ($files) {
        Log::warning(tr('Failed to merge the following files:'));

        foreach (Arrays::force($files) as $file) {
            Log::write($file, 'debug');
        }
    }

    throw $e->makeWarning();
}
