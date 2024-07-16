<?php

/**
 * Command servers/accounts/modify
 *
 * This command will create a new account with the specified properties
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Limit;
use Phoundation\Filesystem\FsFile;
use Phoundation\Servers\SshAccount;

CliDocumentation::setAutoComplete(SshAccount::getAutoComplete([
                                                                  'positions' => [
                                                                      0 => [
                                                                          'word'   => 'SELECT `name` FROM `ssh_accounts` WHERE `name` LIKE :word AND `status` IS NULL LIMIT ' . Limit::shellAutoCompletion(),
                                                                          'noword' => 'SELECT `name` FROM `ssh_accounts` WHERE `status` IS NULL LIMIT ' . Limit::shellAutoCompletion(),
                                                                      ],
                                                                  ],
                                                              ]));

CliDocumentation::setUsage('./pho servers accounts modify IDENTIFIER [OPTIONS]
./pho system servers accounts modify NAME -u phoundation -k KEYFILE -d "This is a test account"');

CliDocumentation::setHelp(SshAccount::getHelpText('This script allows you to modify existing SSH accounts.


ARGUMENTS


IDENTIFIER                              The identifier (id or name) for the SSH account that needs to be modified'));


// Validate the specified hostname
$argv = ArgvValidator::new()
                     ->select('identifier')->hasMaxCharacters(128)->isDomain()
                     ->validate();


// Load account, ensure the new account name doesn't exist yet
$account = SshAccount::load($argv['identifier']);


// Add SSH key for this account either from file or from CLI input
if ($argv['ssh_key_file']) {
    FsFile::new($argv['ssh_key_file'], $argv['ssh_key_file'])->ensureReadable();
    $argv['ssh_key'] = file_get_contents($argv['ssh_key_file']);
} else {
    $argv['ssh_key'] = Cli::readPassword(tr('Please paste the private key here:'));
}


// Modify and save the SSH account
$account->apply()->save();


// Done!
Log::success(tr('Modified SSH account ":account"', [':account' => $account->getName()]));