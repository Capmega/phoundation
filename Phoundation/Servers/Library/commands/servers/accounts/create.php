<?php

/**
 * Command servers accounts create
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
use Phoundation\Cli\CliCommand;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Servers\SshAccount;


CliDocumentation::setAutoComplete(SshAccount::getAutoComplete());

CliDocumentation::setUsage('./pho servers accounts create [OPTIONS]
./pho system servers accounts create -u phoundation -k KEYFILE -d "This is a test account"');

CliDocumentation::setHelp('This command allows you to create SSH accounts.
' . SshAccount::getHelpText());


$argv = ArgvValidator::new()
            ->select('-n,--name', true)->isName()
            ->select('-u,--username', true)->isVariable()
            ->select('-i,--ssh-key-file', true)->isOptional()->sanitizeFile(FsDirectory::getFilesystemRootObject())
            ->validate();


// Check if the account already exists
SshAccount::notExists(['name' => $argv['username']], null, true);


// Add password for this account
if ($argv['ssh_key_file']) {
    $argv['ssh_key'] = FsFile::new($argv['ssh_key_file'], FsRestrictions::getReadonly('/'))
                             ->ensureReadable()
                             ->getContentsAsString();

} elseif(CliCommand::hasStdInStream()) {
    // Get the SSH key from the STDIN stream
    $argv['ssh_key'] = CliCommand::getStdInStream();

} else {
    // Get the SSH key from the command line
    $argv['ssh_key'] = Cli::readPassword(tr('Please paste the private key here:'));
}

unset($argv['ssh_key_file']);


// Create account
$account = SshAccount::new()->apply(source: $argv)->save();


// Done!
Log::success(tr('Created new SSH account ":account"', [':account' => $account->getName()]));
