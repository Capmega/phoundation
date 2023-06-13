<?php

declare(strict_types=1);

namespace Core;

use Phoundation\Core\Config;
use PHPUnit\Framework\TestCase;


/**
 * \Phoundation\Core\Config test class
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