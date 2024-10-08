<?php

declare(strict_types=1);

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Security\Puks\Puks;

/**
 * Script puks/encrypt
 *
 * This script will encrypt the specified data using the PUKS system
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Puks
 */

CliDocumentation::setAutoComplete();

CliDocumentation::setUsage('./pho puks encrypt DATA');

CliDocumentation::setHelp(Puks::getHelp('This script will encrypt the specified data and print the result out on the command line



ARGUMENTS'));

$password = Cli::readPassword('Please type the puks key password');

Puks::new($password)->encrypt(implode(' ', $argv));