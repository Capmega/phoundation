<?php

/**
 * Command file-system/mount/mount
 *
 * FsMounts the specified mount
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\Mounts\FsMount;
use Phoundation\Filesystem\Mounts\FsMounts;

CliDocumentation::setAutoComplete([
    'positions' => [
        0 => [
            'word'   => function ($word) { return FsMounts::new()->load()->keepMatchingValuesStartingWith($word)->limitAutoComplete(); },
            'noword' => function ()      { return FsMounts::new()->load()->limitAutoComplete(); }
        ]
    ]
]);

CliDocumentation::setUsage('./pho filesystem mount MOUNTNAME');

CliDocumentation::setHelp('This command will attempt to mount the specified configured mount point');


// Get the arguments
$argv = ArgvValidator::new()
    ->select('mount')->isName()
    ->validate();


// FsMount the specified mount
FsMount::new($argv['mount'])->mount();
