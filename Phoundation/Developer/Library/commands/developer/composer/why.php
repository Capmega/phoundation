<?php

/**
 * Command developer composer why
 *
 * This command will execute "composer why"
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


CliDocumentation::setHelp('This command will execute "composer why" passing on the given arguments to the composer
command


ARGUMENTS

' . Strings::from(Composer::new()->appendArguments([
                                                                              'help',
                                                                              'why',
                                                                          ])->executeReturnString(), 'Arguments:'));

CliDocumentation::setUsage('
./pho composer why vendor/package1
./pho composer why vendor/package:1.0.*
');


// Get all arguments, do not validate as that is up to composer to do
$argv = ArgvValidator::getArguments();


// Execute composer why
Composer::new()->setArguments($argv)->why();
