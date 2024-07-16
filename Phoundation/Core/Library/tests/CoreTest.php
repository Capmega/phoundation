<?php



/**
 * \Phoundation\Core\Core test class
 */

declare(strict_types=1);

namespace Core;

use Phoundation\Core\Core;
use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase
{
    public function testRegister()
    {
        // Test normal operation

        // Write value to core register and read it back
        Core::writeRegister('abcde', '+_)(*&^%$#@!', '{}[]');
        $this->assertEquals('abcde', Core::readRegister('+_)(*&^%$#@!', '{}[]'));

        // Test failures
    }
}