<?php

declare(strict_types=1);

/**
 * This is the init script for the project. Run this script to ensure that the
 * database is running with the same version as the code
 *
 * Command line options:
 *
 * force                : Force a core database dump, and init from 0. This
 *                        option does NOT work on production environments
 *
 * dump                 : Dump the core database (this DOES work in production)
 *
 * fromprojectversion   : Make init fake the current project version registered
 *                        in the databaes to be the version number that follows
 *                        this option
 *
 * fromframeworkversion : Make init fake the current project version registered
 *                        in the databaes to be the version number that follows
 *                        this option
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   template
 */

use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Utils\Config;


// Ensure no arguments
$argv = ArgvValidator::new()->validate();


// Drop the core database
sql()->drop();
Log::warning(tr('WARNING: Dropped core database ":db"', [':db' => Config::get('databases.sql.instances.system.name')]));
