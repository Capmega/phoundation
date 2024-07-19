<?php

/**
 * \Phoundation\Utils\Arrays test class
 */

declare(strict_types=1);

namespace tests;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use PHPUnit\Framework\TestCase;

class ArraysTest extends TestCase
{
    public function testNextKey()
    {
        // Test normal operation
        $array = [
            'a' => 1,
            'b' => 2,
        ];

        $this->assertEquals('b', Arrays::nextKey($array, 'a'));

        // Get b from a, and delete both, leaving an empty array
        Arrays::nextKey($array, 'a', true);
        $this->assertEquals([], $array);

        // Test failures
        $this->expectException(OutOfBoundsException::class);
        $this->assertEquals(null, Arrays::nextKey($array, 'c'));

        $this->expectException(OutOfBoundsException::class);
        $this->assertEquals(null, Arrays::nextKey($array, 'b'));
    }
}