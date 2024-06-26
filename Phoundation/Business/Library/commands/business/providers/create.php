<?php

declare(strict_types=1);

use Phoundation\Business\Providers\Provider;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;


/**
 * Command business/providers/create
 *
 * This script will create a new provider with the specified properties
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho business providers create NAME [OPTIONS]
./pho system business providers create test -d "This is a test provider!"');

CliDocumentation::setHelp('This command allows you to create providers


ARGUMENTS


NAME                                    The name for the provider

[-d / --description]                    The description for the provider');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('name', true)->isName()
                     ->select('-d,--description', true)->isOptional(null)->isDescription()
                     ->select('-r,--rights,--right', true)->isOptional(null)->sanitizeForceArray()->each()->isName()
                     ->validate();


// Check if the provider already exists
Provider::notExists($argv['name'], 'name', null, true);


// Ensure that specified providers exist
if ($argv['rights']) {
    foreach ($argv['rights'] as &$right) {
        $right = Provider::load($right);
    }

    unset($right);
}


// Create provider and save it
$provider = Provider::new()->apply(true, $argv)->save();


// Done!
Log::success(tr('Created new provider ":provider"', [':provider' => $provider->getName()]));
