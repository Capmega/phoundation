<?php

/**
 * Command developer repositories enable
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will enable the specified repository so that it will no longer be used
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Versioning\Repositories\Repository;
use Phoundation\Filesystem\PhoDirectory;


// Start documentation
CliDocumentation::setAutoComplete([
    'positions' => [
        0 => function ($word) {
            return PhoDirectory::newFilesystemRootObject()->scan()->keepMatchingAutocompleteValues($word);
        }
    ]
]);

CliDocumentation::setUsage('./pho development repositories enable PATH');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will enable the specified repository so that it will no longer be used 


ARGUMENTS


NAME [... NAME, NAME]                   The unique name or names of the repositories that should be enabled'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->selectAll('name')->sanitizeForceArray()->forEachField()->isCode()->existsInDatabase(table: 'developer_repositories')
                     ->validate();


// Disable the repository, it will no longer be used
foreach ($argv['name'] as $name) {
    Repository::new(['name'=> $name])->enable();
}
