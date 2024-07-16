<?php

/**
 * Command file-system/mount/unmount
 *
 * Unmounts the specified mount
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\Mounts\Exception\NotMountedException;
use Phoundation\Filesystem\Mounts\Exception\UnmountBusyException;
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

CliDocumentation::setUsage('./pho filesystem unmount MOUNTNAME');

CliDocumentation::setHelp('This command will try to unmount the specified registered MOUNTNAME');


// Get the arguments
$argv = ArgvValidator::new()
    ->select('mount')->isName()
    ->validate();


// Unmount the specified mount
try {
    FsMount::new($argv['mount'])->unmount();

} catch (NotMountedException) {
    Log::warning(tr('Cannot unmount ":path", it is not mounted', [
        ':path' => $argv['mount']
    ]));

} catch (UnmountBusyException $e) {
    Log::warning(tr('Cannot unmount ":path", it is busy', [
        ':path' => $argv['mount']
    ]));

    Log::printr($e->getDataKey('processes'));
}
