<?php

/**
 * Command security incidents clear
 *
 * This command will clear all incidents with the specified parameters
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @author    Harrison Macey <harrison@medinet.ca>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setAutoComplete([
    'arguments' => [

    ],
]);

CliDocumentation::setUsage('./pho security incidents clear');

CliDocumentation::setHelp('This command will clear all incidents with the specified parameters');


// Validate arguments
$argv = ArgvValidator::new()
                     ->validate();


show('test');
