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

use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Core\Log\Log;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\Mimetypes\FsMimetype;
use Phoundation\Os\Processes\Process;
use Phoundation\Web\Html\Enums\EnumElement;
