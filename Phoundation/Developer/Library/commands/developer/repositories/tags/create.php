<?php

/**
 * Command developer git repositories tags create
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will create the specified tag for all repositories
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Development
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Versioning\Repositories\Repositories;


// Start documentation
CliDocumentation::setAutoComplete([
    'positions' => [
        0 => function ($word) {
            return Repositories::new()->load()->keepMatchingAutocompleteValues($word, 'name');
        },
        1 => function ($word) {
            return Repositories::new()->load()->keepMatchingAutocompleteValues($word, 'name');
        },
    ]
]);

CliDocumentation::setUsage('./pho development repositories tags create TAG_NAME
./pho dv rp tg ls TAG_NAME -sm "example message"');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will create a tag with the specified name for all repositories 


ARGUMENTS


TAG_NAME                                The name for the tag to create


OPTIONAL ARGUMENTS


[-m, --message]                         The optional message to add to this tag. This will make the tag an annotated 
                                        tag
                                        
[-s, --signed]                          If specified, this will sign the tag. This requires that tag signing has been 
                                        setup in git prior'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('name'        , true)->isOptional()->isCode()
                     ->select('-m,--message', true)->isOptional()->isDescription()
                     ->select('-s,--signed')->isOptional()->isBoolean()
                     ->validate();


// Create the tag!
Repositories::new($argv['repository'])->createTag($argv['name'], $argv['message'], $argv['signed']);

