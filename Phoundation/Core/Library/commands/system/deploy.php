<?php

/**
 * Command project check
 *
 * This command will check for - and report - (and optionally fix) the project and its systems
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Project\Project;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;$restrictions = FsRestrictions::getReadonly([DIRECTORY_ROOT . 'config/deploy/'], tr('Deploy'));


CliDocumentation::setAutoComplete([
                                      'positions' => [
                                          0 => [
                                              'word'   => function ($word) use ($restrictions) { return FsDirectory::new(DIRECTORY_ROOT . 'config/deploy/', $restrictions)->scan($word . '*.yaml'); },
                                              'noword' => function ()      use ($restrictions) { return FsDirectory::new(DIRECTORY_ROOT . 'config/deploy/', $restrictions)->scan('*.yaml'); },
                                          ],
                                      ],
                                      'arguments' => [
                                          '-c / --categories'   => false,
                                          '-t / --targets'      => false,
                                          '--do-ignore-changes' => false,
                                          '--no-ignore-changes' => false,
                                          '--do-content-check'  => false,
                                          '--no-content-check'  => false,
                                          '--do-execute-hooks'  => false,
                                          '--no-execute-hooks'  => false,
                                          '--do-init'           => false,
                                          '--no-init'           => false,
                                          '--no-notify'         => false,
                                          '--no-compress'       => false,
                                          '--no-push'           => false,
                                          '--no-parrallel'      => false,
                                          '--no-sitemap'        => false,
                                          '--no-translate'      => false,
                                          '--no-bom-check'      => false,
                                          '--no-backup'         => false,
                                          '--do-backup'         => false,
                                          '--update-sitemap'    => false,
                                          '-F / --force'        => false,
                                          '--test-syntax'       => false,
                                          '--test-unit'         => false,
                                      ],
                                  ]);

CliDocumentation::setUsage('./pho system deploy TARGET
./pho system deploy TARGET --no-init
');

CliDocumentation::setHelp('This command will deploy your project from this machine to the target environment

Most options are configured in a per-target basis and can be specified on the command line to override this
configuration in a do or do not way. For example, the "production" environment can have "execute-hooks" enabled with
true, but this can be overridden on the command line by using --no-execute-hooks. Please note that modifiers specified
on the command line apply on all specified targets. If you wish to use different modifiers for different targets, then
execute an individual deploy process for each target

The general deployment configuration is stored in config/deploy/deploy.yml. Every target environment must have its own
deployment configuration file named config/deploy/ENVIRONMENT.yml where ENVIRONMENT is the name of the target
environment. If the required target environment has no configuration file, you will not be able to deploy to it.


ARGUMENTS


TARGET                                  - The target name to which to deploy

[-c / --categories]                     - List all available deployment categories

[-t / --targets]                        - List all available deployment targets


[--do-ignore-changes]                   - Do not ignore git status, check for uncommitted changes

[--no-ignore-changes]                   - Ignore git status, don\'t check for uncommitted changes

[--do-content-check]                    - Do check content files

[--no-content-check]                    - Do not check content files

[--do-execute-hooks]                    - Do execute hooks

[--no-execute-hooks]                    - Do not execute hooks

[--do-init]                             - Do execute an init on the target server

[--no-init]                             - Do not execute an init on the target server

[--no-notify]                           - Do not send notifications

[--no-compress]                         - Do not compress the javascript / CSS files.

[--no-push]                             - Do not automatically git push all changes and tags

[--no-parrallel]                        - Do not use parallel rsync deploy, even if the website is configured to do so
                                          anyway

[--no-sitemap]                          - Do not automatically reupdate the sitemap files

[--no-translate]                        - Do not execute file translation

[--no-bom-check]                        - Do not test for BOM character in all PHP files. The ByteOrderMark character is
                                          a UTF8 character (added mostly by apple machines) that indicates how to read
                                          the UTF8 text, but is interpreted by the PHP as a character before the <?php
                                          tag, which can cause problems with headers(). The clearbom script will strip
                                          the BOM characters and is by default always executed on each deploy to be sure
                                          no BOM characters end up in production code. This option disables the BOM
                                          check.

[--no-backup]                           - Do NOT execute a site / database backup, even though per configuration, a
                                          backup should be done

[--do-backup]                           - Execute a site / database backup, even if per configuration, a backup should
                                          not be done

[--update-sitemap]                      - rsync the www/LANG/sitemap file as well (Normally always skipped)

[-F / --force]                          - Force a deploy, even when it should be stopped due to (for example) git changes

[--test-syntax]                         - Do PHP syntax tests

[--test-unit]                           - Do PHP unit tests ');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('target')->isOptional()->sanitizeForceArray(',')->each()->isVariable()
                     ->select('--targets')->isOptional()->isBoolean()
                     ->select('-c,--categories')->isOptional()->isBoolean()
                     ->select('--do-ignore-changes')->isOptional()->orColumn('--no-ignore-changes')->isBoolean()
                     ->select('--no-ignore-changes')->isOptional()->orColumn('--do-ignore-changes')->isBoolean()
                     ->select('--do-content-check')->isOptional()->orColumn('--no-content-check')->isBoolean()
                     ->select('--no-content-check')->isOptional()->orColumn('--do-content-check')->isBoolean()
                     ->select('--do-execute-hooks')->isOptional()->orColumn('no-execute-hooks')->isBoolean()
                     ->select('--no-execute-hooks')->isOptional()->orColumn('--do-execute-hooks')->isBoolean()
                     ->select('--do-init')->isOptional()->orColumn('--no-init')->isBoolean()
                     ->select('--no-init')->isOptional()->orColumn('--do-init')->isBoolean()
                     ->select('--do-notify')->isOptional()->orColumn('--no-notify')->isBoolean()
                     ->select('--no-notify')->isOptional()->orColumn('--do-notify')->isBoolean()
                     ->select('--do-compress')->isOptional()->orColumn('--no-compress')->isBoolean()
                     ->select('--no-compress')->isOptional()->orColumn('do-compress')->isBoolean()
                     ->select('--do-push')->isOptional()->orColumn('--no-push')->isBoolean()
                     ->select('--no-push')->isOptional()->orColumn('--do-push')->isBoolean()
                     ->select('--do-parallel')->isOptional()->orColumn('--no-parallel')->isBoolean()
                     ->select('--no-parallel')->isOptional()->orColumn('--do-parallel')->isBoolean()
                     ->select('--do-translate')->isOptional()->orColumn('--no-translate')->isBoolean()
                     ->select('--no-translate')->isOptional()->orColumn('--do-translate')->isBoolean()
                     ->select('--do-bom-check')->isOptional()->orColumn('--no-bom-check')->isBoolean()
                     ->select('--no-bom-check')->isOptional()->orColumn('--do-bom-check')->isBoolean()
                     ->select('--do-backup')->isOptional()->orColumn('--no-backup')->isBoolean()
                     ->select('--no-backup')->isOptional()->orColumn('--do-backup')->isBoolean()
                     ->select('--do-stash')->isOptional()->orColumn('--no-stash')->isBoolean()
                     ->select('--no-stash')->isOptional()->orColumn('--do-stash')->isBoolean()
                     ->select('--do-update-sitemap')->isOptional()->orColumn('--no-update-sitemap')->isBoolean()
                     ->select('--no-update-sitemap')->isOptional()->orColumn('--do-update-sitemap')->isBoolean()
                     ->select('--do-test-syntax')->isOptional()->orColumn('--no-test-syntax')->isBoolean()
                     ->select('--no-test-syntax')->isOptional()->orColumn('--do-test-syntax')->isBoolean()
                     ->select('--do-test-unit')->isOptional()->orColumn('--no-test-unit')->isBoolean()
                     ->select('--no-test-unit')->isOptional()->orColumn('--do-test-unit')->isBoolean()
                     ->validate();


// Start deploying
Log::information('Starting deployment process');


if ($argv['targets']) {
    // List all available targets
    Project::new()->getDeploy()->listTargets()->getCliTable();

} elseif ($argv['categories']) {
    // List all available deployment categories
    Project::new()->getDeploy()->listCategories()->getCliTable();

} else {
    // Execute deployment
    Project::new()->getDeploy($argv['target'])
           ->setIgnoreChanges($argv['do_ignore_changes'], $argv['no_ignore_changes'])
           ->setExecuteHooks($argv['do_execute_hooks'], $argv['no_execute_hooks'])
           ->setContentCheck($argv['do_content_check'], $argv['no_content_check'])
           ->setInit($argv['do_init'], $argv['no_init'])
           ->setNotify($argv['do_notify'], $argv['no_notify'])
           ->setCompress($argv['do_compress'], $argv['no_compress'])
           ->setPush($argv['do_push'], $argv['no_push'])
           ->setParallel($argv['do_parallel'], $argv['no_parallel'])
           ->setUpdateSitemap($argv['do_update_sitemap'], $argv['no_update_sitemap'])
           ->setTranslate($argv['do_translate'], $argv['no_translate'])
           ->setBomCheck($argv['do_bom_check'], $argv['no_bom_check'])
           ->setBackup($argv['do_backup'], $argv['no_backup'])
           ->setStash($argv['do_stash'], $argv['no_stash'])
           ->setTestSyntax($argv['do_test_syntax'], $argv['no_test_syntax'])
           ->setTestUnit($argv['no_test_unit'], $argv['no_test_unit'])
           ->execute();
}
