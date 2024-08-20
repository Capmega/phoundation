<?php

/**
 * Command meta stats
 *
 * This script can be used to display meta data statistics
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Meta\Meta;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Date\DateTime;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;


CliDocumentation::setUsage('./pho meta stats
');

CliDocumentation::setHelp('This command will display statistics for the meta system


ARGUMENTS


-');


// Get command line arguments
ArgvValidator::new()->validate();


// Display the meta statistics
Log::information(tr('Meta data statistics:'), echo_prefix: false);
Meta::getStatistics()->displayCliKeyValueTable();
