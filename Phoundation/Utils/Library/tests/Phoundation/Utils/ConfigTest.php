<?php

/**
 * Class ConfigTest
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

use Phoundation\Utils\Config;
use PHPUnit\Framework\TestCase;


class ConfigTest extends TestCase
{
    public function testGet()
    {
        // Test normal operation

        // Read non-existing key and return default value
        $this->assertEquals('abcde', Config::get('+_)(*&^%$#@!~', 'abcde'));

        // Test failures
    }
}
