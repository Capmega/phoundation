<?php

namespace Core;

use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use PHPUnit\Framework\TestCase;



/**
 * \Phoundation\Core\Strings test class
 */
class StringsTest extends TestCase
{
    public function testFrom()
    {
        // Test normal operation
        $this->assertEquals('gmail.com', Strings::from('so.oostenbrink@gmail.com', '@'));                   // From single character
        $this->assertEquals('.com', Strings::from('so.oostenbrink@gmail.com', 'gmail'));                    // From multiple characters
        $this->assertEquals('', Strings::from('so.oostenbrink@gmail.com', '.com'));                         // From last few characters
        $this->assertEquals('o.oostenbrink@gmail.com', Strings::from('so.oostenbrink@gmail.com', 's'));     // From first character
        $this->assertEquals('', Strings::from('', 'sven'));                                                 // From empty string
        $this->assertEquals('', Strings::from('so.oostenbrink@gmail.com', 'so.oostenbrink@gmail.com'));     // From entire source string

        // Test failures
        $this->expectException(OutOfBoundsException::class);
        $this->assertEquals(null, Strings::from('so.oostenbrink@gmail.com', ''));                          // Needle is obligatory
    }
}