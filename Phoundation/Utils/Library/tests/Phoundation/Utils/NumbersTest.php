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
    /**
     * Tests Numbers::getHumanReadableBytes()
     *
     * @return void
     */
    public function testGetHumanReadableBytes()
    {
        // Test normal operation
        $this->assertEquals('0b'     , Numbers::getHumanReadableBytes(0));
        $this->assertEquals('1b'     , Numbers::getHumanReadableBytes(1));
        $this->assertEquals('0.98KiB', Numbers::getHumanReadableBytes(1000));
        $this->assertEquals('1.00KiB', Numbers::getHumanReadableBytes(1024));

        // Test failures
    }


    /**
     * Tests Numbers::binaryToInt()
     *
     * @return void
     */
    public function testBinaryToInt()
    {
        // Test normal operation
        $this->assertEquals(0                   , Numbers::binaryToInt(chr(0)));
        $this->assertEquals(0                   , Numbers::binaryToInt(chr(0) . chr(0) . chr(0) . chr(0)));
        $this->assertEquals(65                  , Numbers::binaryToInt("A"));
        $this->assertEquals(9114861777597660798 , Numbers::binaryToInt("~~~~~~~~"));
        // Test failures
    }


    /**
     * Tests Numbers::intToBinary()
     *
     * @return void
     */
    public function testIntToBinary()
    {
        // Test normal operation
        $this->assertEquals(chr(0)                            , Numbers::intToBinary(0));
        $this->assertEquals(chr(0) . chr(0) . chr(0) . chr(0) , Numbers::intToBinary(0));
        $this->assertEquals("A"                               , Numbers::intToBinary(65));
        $this->assertEquals("~~~~~~~~"                        , Numbers::intToBinary(9114861777597660798));
        // Test failures
    }
}
