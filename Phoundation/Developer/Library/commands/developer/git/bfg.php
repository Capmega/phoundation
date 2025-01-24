<?php

/**
 * Command developer git bfg
 *
 * This command will execute "./data/bin/bfg"
 *
 * @see https://rtyley.github.io/bfg-repo-cleaner/
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Os\Processes\Commands\Composer;
use Phoundation\Utils\Strings;


CliDocumentation::setHelp('This command will execute "the bfg" command on this repository

Using this command requires the repository to be "clean", so changes are not allowed


ARGUMENTS

' . Strings::from(Composer::new()->addArguments([
          'help',
          'remove',
      ])->executeReturnString(), 'Arguments:'));

CliDocumentation::setUsage('
./pho composer remove vendor/package1
./pho composer remove vendor/package:1.0.*
');


// Get all arguments, don't validate as that is up to composer to do
$argv = ArgvValidator::getArguments();


// Execute composer remove
Composer::new()->setArguments($argv)->remove();
