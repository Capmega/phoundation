<?php

declare(strict_types=1);

use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Tests\Tests;


/**
 * Command tests/unit/start
 *
 * This command will start PHP unit testing
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


// Validate arguments, there should be none.
ArgvValidator::new()->validate();


// Execute the PHP unit tests
Tests::startPhpUnitTests();
