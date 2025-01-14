<?php

/**
 * Command web server install
 *
 * Installs the virtualhost file for this project and environment to the appropriate webserver
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Core;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Project\Project;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Http\Domains;
use Phoundation\Web\Server\Webserver;


CliDocumentation::setAutoComplete([
    'arguments' => [
        '-s,--server'      => [
            'word'   => function ($word) { return Arrays::valuesMatch(['apache', 'nginx', 'litespeed'], $word); },
            'noword' => function ($word) { return ['apache', 'nginx', 'litespeed']; },
        ],
        '-e,--environment' => [
            'word'   => function ($word) { return Project::getEnvironments()->getMatchingValues($word); },
            'noword' => function ($word) { return Project::getEnvironments(); },
        ],
        '-d,--domains'     => [
            'word'   => function ($word) { return Domains::new()->getMatchingValues($word); },
            'noword' => function ($word) { return Domains::new(); },
        ],
        '-t,--type'        => [
            'word'   => function ($word) { return Arrays::valuesMatch(['web', 'cdn'], $word); },
            'noword' => function ($word) { return ['web', 'cdn']; },
        ],
    ],
]);

CliDocumentation::setUsage('./pho web server install 
./pho web server install -e production -s nginx');

CliDocumentation::setHelp('This command helps with the installation of the webserver\'s virtualhost file

The virtualhost file will be installed by placing a symlink in the servers "sites-available" directory.

Currently only supported on Debian Linux style servers 


ARGUMENTS


-


OPTIONAL ARGUMENTS


[-e, --environment ENVIRONMENT]         The Phoundation environment for which to install this virtualhost file 

[-d, --domain DOMAIN]                   The domain for which to install this virtualhost file. Defaults to the domain 
                                        for the specified (or if not specified, this) environment  

[-s, --server SERVER]                   The webserver on which the virtualhost file should be installed. Defaults to 
                                        whatever is detected for the specified (or if not specified, this) environment

[-t, --type web|cdn]                    The type of domain to install. Currently supported are "web" (default) or "cdn" ');


// This command requires root
Core::ensureProcessIsRoot();


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('server', true)->isOptional('apache')->isInArray(['apache', 'nginx', 'litespeed'])
                     ->select('environment', true)->isOptional(ENVIRONMENT)->isVariable()
                     ->select('domain', true)->isOptional('primary')->matchesRegex('/^[a-z0-9-]+$/')
                     ->select('type', true)->isOptional('web')->isInArray(['web', 'cdn'])
                     ->validate();


Webserver::getServerObject($argv['server'])->getVirtualhostObject()
                                           ->setEnvironment($argv['environment'])
                                           ->setDomain($argv['domain'])
                                           ->setType($argv['type'])
                                           ->installFile();