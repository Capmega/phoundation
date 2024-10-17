<?php

/**
 * Class NumbersTest
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils\Library\tests\Phoundation\Utils;

use Phoundation\Utils\Numbers;
use PHPUnit\Framework\TestCase;


class NumbersTest extends TestCase
{
    public function testBytes()
    {
        // Test normal operation
        $this->assertEquals('0.00KB', Numbers::getHumanReadableBytes(1));
        $this->assertEquals('1.00KB', Numbers::getHumanReadableBytes(1000));
        $this->assertEquals('1.02KB', Numbers::getHumanReadableBytes(1024));

        // Test failures
    }
}
