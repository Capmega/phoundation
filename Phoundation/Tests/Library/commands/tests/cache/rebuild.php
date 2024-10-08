<?php

declare(strict_types=1);

use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Tests\Tests;


/**
 * Script tests/cache/rebuild
 *
 * This script will rebuild the test cache
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


ArgvValidator::new()->validate();


Tests::rebuildCache();