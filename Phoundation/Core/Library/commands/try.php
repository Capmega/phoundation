<?php

/**
 * Script try
 *
 * General quick try and test script. Scribble any test code that you want to execute here and execute it with
 * ./pho try
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Accounts\Users\User;
use Phoundation\Cli\Cli;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\Validator\ArrayValidator;
use Phoundation\Developer\Phoundation\Repositories\Repositories;
use Phoundation\Developer\Phoundation\Repositories\Repository;
use Phoundation\Filesystem\Commands\LsBlk;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Utils\Strings;




$restrictions = FsRestrictions::getWritable([DIRECTORY_DATA . 'sources/', DIRECTORY_TMP], tr('try'));
$word         = '';

show(FsDirectory::new(DIRECTORY_DATA . 'sources/', $restrictions)->scan($word . '*.sql'));




passthru('stty');