<?php

/**
 * Command developer repositories grep
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will execute a grep on all revisions of all known phoundation repositories to find the specified word.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Versioning\Repositories\Repositories;


// Start documentation
CliDocumentation::setAutoComplete([
    'arguments' => [
        '-g,--grouped' => false
    ]
]);

CliDocumentation::setUsage('./pho development repositories grep WORD
./pho dv rp gp WORD');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will execute a grep on all revisions of all known phoundation repositories to find the specified word.  


ARGUMENTS


WORD                                    The word to search for'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('word')->hasMaxCharacters(1024)
                     ->select('-g,--grouped')->isOptional()->isBoolean()
                     ->validate();


// Execute git pull on all known repositories
Repositories::new()->load()->grep($argv['word'], $argv['grouped']);
