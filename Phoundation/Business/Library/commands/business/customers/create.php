<?php

/**
 * Command business customers create
 *
 * This command will create a new customer with the specified properties
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Business\Customers\Customer;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho business customers create NAME [OPTIONS]
./pho business customers create test -d "This is a test customer!"');

CliDocumentation::setHelp('This command allows you to create customers


ARGUMENTS


NAME                                    The name for the customer

[-d / --description]                    The description for the customer');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('name', true)->isName()
                     ->select('-d,--description', true)->isOptional(null)->isDescription()
                     ->select('-r,--rights,--right', true)->isOptional(null)->sanitizeForceArray()->forEachField()->isName()
                     ->validate();


// Check if the customer already exists
Customer::notExists(['name' => $argv['name']], null, true);


// Ensure that specified customers exist
if ($argv['rights']) {
    foreach ($argv['rights'] as &$right) {
        $right = Customer::new()->load($right);
    }

    unset($right);
}


// Create customer and save it
$customer = Customer::new()->apply(true, $argv)->save();


// Done!
Log::success(ts('Created new customer ":customer"', [':customer' => $customer->getName()]), 10);
