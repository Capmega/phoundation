<?php

/**
 * Command security non200urls process
 *
 * This command will process Non HTTP-200 URL's and check for any illicit activities
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Web\Non200Urls\Non200Urls;


CliDocumentation::setAutoComplete([
                                      'arguments' => [
                                          '-a,--amount' => true,
                                      ],
                                  ]);

CliDocumentation::setUsage('./pho security logs non200urls process');

CliDocumentation::setHelp('This command will process Non HTTP-200 URL\'s and check for any illicit activities');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('-a,--amount', true)->isNatural()->isMoreThan(1)
                     ->validate();


// Start processing
Non200Urls::process($argv['amount']);
