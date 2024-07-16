<?php

/**
 * Command devops/kubernetes/create/secret
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Virtualization\Kubernetes\Secrets\Secret;

CliDocumentation::setUsage('./pho devops kubernetes create secret');

CliDocumentation::setHelp('This command creates a Kubernetes secret using a secret file');


// Validate arguments
$argv = ArgvValidator::new()
    ->select('-n,--name', true)->isOptional(strtolower(PROJECT) . '-web')->matchesRegex('/^[a-z0-9-]+$/')
    ->select('-t,--type', true)->isOptional('Opaque')->isInArray(['Opaque'])
    ->select('-k,--keys', true)->sanitizeForceArray(',')
    ->validate();


// Get values for the keys
$keys = [];

foreach ($argv['keys'] as $key) {
    $keys[$key] = Cli::readPassword(tr('Please type the secret for key ":key"', [':key' => $key]));
}


// Create new secret file and apply it
$secret = Secret::new()
    ->setName($argv['name'])
    ->setData($keys)
    ->save()
    ->apply();
