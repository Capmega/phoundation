<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\Mounts\Mount;
use Phoundation\Filesystem\Mounts\Mounts;


/**
 * Script file-system/mount/ismounted
 *
 * Mounts the specified mount
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */


CliDocumentation::setAutoComplete([
    'positions' => [
        0 => [
            'word'   => function ($word) { return Mounts::new()->load()->keepMatchingValuesStartingWith($word)->limitAutoComplete(); },
            'noword' => function ()      { return Mounts::new()->load()->limitAutoComplete(); }
        ]
    ]
]);

CliDocumentation::setUsage('./pho filesystem ismounted MOUNTNAME');

CliDocumentation::setHelp('This command will echo 1 if the specified mount name is mounted, 0 if not');


// Get the arguments
$argv = ArgvValidator::new()
    ->select('mount')->isName()
    ->validate();


// Get the arguments
$argv = ArgvValidator::new()
    ->select('mount')->isName()
    ->validate();


// Mount the specified mount
Log::cli(Mount::new($argv['mount'])->isMounted() ? 1 : 0);
