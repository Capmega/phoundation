<?php

/**
 * Command databases fix non-standard-fk-keys
 *
 * This command will attempt to automatically fix non standard foreign key keys (FK that point toward table columns that have a non-unique index)
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Connectors\Connectors;
use Phoundation\Databases\Import;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Os\Processes\Commands\Pho;


CliDocumentation::setUsage('./pho databases fix non-standard-fk-keys');

CliDocumentation::setHelp('This command will attempt to automatically fix non standard foreign key keys (FK that point toward table columns that have a 
non-unique index)


ARGUMENTS


-');

CliDocumentation::setAutoComplete([
    'arguments' => [
        '-c,--connector' => function ($word) {
            return Connectors::new()
                             ->load(null, true, true)
                             ->keepMatchingAutocompleteValues($word, 'name');
        },
    ],
]);


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('-c,--connector', true)->isOptional('system')->sanitizeLowercase()->isInArray(Connectors::new()->load(null, false, true)->getAllRowsSingleColumn('name'))
                     ->validate();


// Execute the import for the specified driver
Log::information(ts('Attempting to automatically fix non-unique indices for foreign key targets'), 10);


sql($argv['connector'])->disableRestrictFkOnNonStandardKeys()
                       ->fixFkOnNonStandardKeys()
;
