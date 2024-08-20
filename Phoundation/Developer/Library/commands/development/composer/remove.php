<?php

/**
 * Command developer composer remove
 *
 * This command will execute "composer remove"
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Os\Processes\Commands\Composer;
use Phoundation\Utils\Strings;


CliDocumentation::setHelp('This command will execute "composer remove" passing on the given arguments to the composer
command


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
