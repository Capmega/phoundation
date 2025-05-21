<?php

/**
 * Command debug
 *
 * This command will return either 1 if debug is enabled, or nothing at all
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

use Phoundation\Core\Log\Log;
use Phoundation\Developer\Debug\Debug;


// Show if debug is enabled or not
Log::cli(Debug::isEnabled() ? 1 : 0);
