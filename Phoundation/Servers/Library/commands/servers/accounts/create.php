<?php

declare(strict_types=1);

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Filesystem\File;
use Phoundation\Servers\SshAccount;


/**
 * Script servers/accounts/create
 *
 * This script will create a new account with the specified properties
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setAutoComplete(SshAccount::getAutoComplete());

CliDocumentation::setUsage('./pho servers accounts create [OPTIONS]
./pho system servers accounts create -u phoundation -k KEYFILE -d "This is a test account"');

CliDocumentation::setHelp('This command allows you to create SSH accounts.
' . SshAccount::getHelpText());


// Check if the account already exists
SshAccount::notExists($argv['name'], 'name', null, true);


// Add password for this account
if ($argv['ssh_key_file']) {
    File::new($argv['ssh_key_file'], $argv['ssh_key_file'])->ensureReadable();
    $argv['ssh_key'] = file_get_contents($argv['ssh_key_file']);
} else {
    $argv['ssh_key'] = Cli::readPassword(tr('Please paste the private key here:'));
}


// Create account
$account = SshAccount::new()->apply()->save();
$account->setSshKey($argv['ssh_key']);


// Done!
Log::success(tr('Created new SSH account ":account"', [':account' => $account->getName()]));
