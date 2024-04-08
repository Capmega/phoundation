<?php

declare(strict_types=1);

namespace Core;

use Phoundation\Utils\Numbers;
use PHPUnit\Framework\TestCase;

/**
 * \Phoundation\Utils\Numbers test class
 */
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