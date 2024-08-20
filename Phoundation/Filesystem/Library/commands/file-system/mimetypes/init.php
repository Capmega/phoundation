<?php

/**
 * Command file-system mimetypes init
 *
 * FsMounts the specified mount
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\Mimetypes\FsMimetypesInit;


CliDocumentation::setAutoComplete([
    'arguments' => [
        '-c,--clear' => false,
    ],
]);

CliDocumentation::setUsage('./pho filesystem mimetypes init
./pho filesystem mimetypes init --clear
');

CliDocumentation::setHelp('This command will initialize the filesystem mimetypes table


ARGUMENTS


[-c,--clear]                            This will clear the table before initializing it');


// Get the arguments
$argv = ArgvValidator::new()
    ->select('-c,--clear')->isOptional()->isBoolean()
    ->validate();


// Clear the table?
if ($argv['clear']) {
    FsMimetypesInit::clear();
}


// Initialize the mime types
FsMimetypesInit::init();
