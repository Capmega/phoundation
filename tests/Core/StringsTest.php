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
    /**
     * Test Strings::from()
     *
     * @return void
     */
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
        $this->assertEquals(null, Strings::from('so.oostenbrink@gmail.com', ''));                           // Needle is obligatory
    }



    /**
     * Test Strings::until()
     *
     * @return void
     */
    public function testUntil()
    {
        // Test normal operation
        $this->assertEquals('so.oostenbrink', Strings::until('so.oostenbrink@gmail.com', '@'));             // Until single character
        $this->assertEquals('so.oostenbrink@', Strings::until('so.oostenbrink@gmail.com', 'gmail'));        // Until multiple characters
        $this->assertEquals('so.oostenbrink@gmail', Strings::until('so.oostenbrink@gmail.com', '.com'));    // Until last few characters
        $this->assertEquals('', Strings::until('so.oostenbrink@gmail.com', 's'));                           // Until first character
        $this->assertEquals('', Strings::until('', 'sven'));                                                // Until empty string
        $this->assertEquals('', Strings::until('so.oostenbrink@gmail.com', 'so.oostenbrink@gmail.com'));    // Until entire source string

        // Test failures
        $this->expectException(OutOfBoundsException::class);
        $this->assertEquals(null, Strings::until('so.oostenbrink@gmail.com', ''));                          // Needle is obligatory
    }
}