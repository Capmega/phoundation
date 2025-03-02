<?php

/**
 * Command business companies create
 *
 * This command will create a new company with the specified properties
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Business\Companies\Company;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho business companies create NAME [OPTIONS]
./pho system business companies create test -d "This is a test company!"');

CliDocumentation::setHelp('This command allows you to create companies


ARGUMENTS


NAME                                    The name for the company

[-d / --description]                    The description for the company');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('name', true)->isName()
                     ->select('-d,--description', true)->isOptional(null)->isDescription()
                     ->select('-r,--rights,--right', true)->isOptional(null)->sanitizeForceArray()->eachField()->isName()
                     ->validate();


// Check if the company already exists
Company::notExists(['name' => $argv['name']], null, true);


// Ensure that specified companies exist
if ($argv['rights']) {
    foreach ($argv['rights'] as &$right) {
        $right = Company::new()->load($right);
    }

    unset($right);
}


// Create company and save it
$company = Company::new()->apply(true, $argv)->save();


// Done!
Log::success(ts('Created new company ":company"', [':company' => $company->getName()]));
