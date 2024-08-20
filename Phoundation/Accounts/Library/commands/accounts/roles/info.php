<?php

/**
 * Command accounts roles info
 *
 * This script displays information about the specified role.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Accounts\Roles\Role;
use Phoundation\Cli\Cli;
use Phoundation\Cli\CliColor;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Paging;
use Phoundation\Utils\Arrays;


CliDocumentation::setAutoComplete([
                                      'positions' => [
                                          0 => [
                                              'word'   => 'SELECT `name` FROM `accounts_roles` WHERE `name` LIKE :word AND `status` IS NULL',
                                              'noword' => 'SELECT `name` FROM `accounts_roles` WHERE `status` IS NULL LIMIT ' . Paging::getLimit(),
                                          ],
                                      ],
                                  ]);

CliDocumentation::setUsage('./pho accounts roles info USER');

CliDocumentation::setHelp('This script displays information about the specified role.  


ARGUMENTS


USER                                    The role to display information about. Specify either by role id or seo-name');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('role')->hasMinCharacters(2)->hasMaxCharacters(255)
                     ->validate();


try {
    $role = Role::load($argv['role']);

    // Display role data and rights
    Cli::displayForm($role->getSource());

    Log::cli();
    Log::cli(CliColor::apply('Rights available for this role:', 'white'));

    Cli::displayTable(Arrays::listKeepKeys($role->getRights(), 'id,name'), id_column: null);

} catch (DataEntryNotExistsException $e) {
    throw $e->makeWarning();
}
