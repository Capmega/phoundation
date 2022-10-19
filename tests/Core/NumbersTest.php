<?php

namespace Core;

use Phoundation\Core\Numbers;
use PHPUnit\Framework\TestCase;



/**
 * \Phoundation\Core\Numbers test class
 */
class NumbersTest extends TestCase
{
    public function testBytes()
    {
        // Test normal operation
        $this->assertEquals('0.00KB', Numbers::bytes(1));
        $this->assertEquals('1.00KB', Numbers::bytes(1000));
        $this->assertEquals('1.02KB', Numbers::bytes(1024));

        // Test failures
    }
}