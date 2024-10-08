<?php

declare(strict_types=1);

use Phoundation\Core\Libraries\Libraries;
use Phoundation\Data\Validator\ArgvValidator;


/**
 * Script cache/rebuild/system
 *
 * This command will rebuild all system caches
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


ArgvValidator::new()->validate();


Libraries::rebuildCommandCache();
Libraries::rebuildWebCache();
Libraries::rebuildTestsCache();
