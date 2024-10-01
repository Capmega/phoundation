<?php

/**
 * Command accounts rights info
 *
 * This script displays information about the specified user.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Accounts\Rights\Right;
use Phoundation\Cli\Cli;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Paging;


CliDocumentation::setAutoComplete([
    'positions' => [
        0 => [
            'word'   => 'SELECT `name` FROM `accounts_rights` WHERE `name` LIKE :word AND `status` IS NULL',
            'noword' => 'SELECT `name` FROM `accounts_rights` WHERE `status` IS NULL LIMIT ' . Paging::getLimit(),
        ],
    ],
]);

CliDocumentation::setUsage('./pho accounts rights info USER');

CliDocumentation::setHelp('This script displays information about the specified user.  


ARGUMENTS


USER                                    The right to display information about. Specify either by user id or email 
                                        address');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('user')->hasMinCharacters(2)->hasMaxCharacters(255)
                     ->validate();


try {
    // Display user data
    Cli::displayForm(Right::load($argv['user'])->getSource());

} catch (DataEntryNotExistsException $e) {
    throw $e->makeWarning();
}
