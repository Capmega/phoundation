<?php

declare(strict_types=1);

namespace Core;

use Phoundation\Utils\Config;
use PHPUnit\Framework\TestCase;

/**
 * \Phoundation\Utils\Config test class
 */
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