<?php

/**
 * Command cache clear
 *
 * This command clears the specified cache group
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cache
 */


declare(strict_types=1);

use Phoundation\Cache\Cache;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho cache clear [OPTIONS]
./pho cache clear GROUP
./pho cache clear html
./pho cache clear autosuggest
');

CliDocumentation::setHelp('This command will clear the specified cache group


ARGUMENTS


GROUP                                   The cache group to clear. Must be one of "html", "autosuggest", or "dataentries"');

CliDocumentation::setAutoComplete([
    'arguments' => [
        'group' => [
            'html',
            'dataentries',
            'autosuggest',
        ],
    ]
]);


// Get command arguments
$argv = ArgvValidator::new()
    ->select('group')->isInArray(['html', 'dataentries', 'autosuggest'])
    ->validate();


// Clear cache
Cache::new($argv['group'])->clear();

Log::success(ts('Cleared cache group ":group"', [
    ':group' => $argv['group']
]));