<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Os\Processes\Commands\Composer;
use Phoundation\Utils\Strings;


/**
 * Command developer/composer/update
 *
 * This command will execute "composer update"
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


CliDocumentation::setHelp('This command will execute "composer update" passing on the given arguments to the composer
command


ARGUMENTS

' . Strings::from(Composer::new()->addArguments([
                                                                              'help',
                                                                              'update',
                                                                          ])->executeReturnString(), 'Arguments:'));

CliDocumentation::setUsage('./pho composer update
./pho composer update vendor/package1
./pho composer update vendor/package:1.0.*
');


// Get all arguments, don't validate as that is up to composer to do
$argv = ArgvValidator::getArguments();


// ExecuteExecuteInterface composer update
Composer::new()->setArguments($argv)->update();