<?php

/**
 * Command tests/cache/rebuild
 *
 * This command will rebuild the test cache
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Tests\Tests;

ArgvValidator::new()->validate();


Tests::rebuildCache();