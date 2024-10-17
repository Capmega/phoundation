<?php

/**
 * Tests bootstrap file
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Tests
 */


declare(strict_types=1);

namespace Phoundation\Tests;

use Phoundation\Core\Core;

require('./vendor/autoload.php');

Core::startup();
Core::setUnitTestMode(true);
