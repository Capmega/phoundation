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

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Exception\NumbersException;
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
    }


    /**
     * Tests Numbers::intToBinary()
     *
     * @return void
     */
    public function testIntToBinary()
    {
        // Test normal operation
        $this->assertEquals(chr(0)     , Numbers::intToBinary(0));
        $this->assertEquals("A"        , Numbers::intToBinary(65));
        $this->assertEquals("~~~~~~~~" , Numbers::intToBinary(9114861777597660798));
    }


    /**
     * Tests Numbers::limitRange()
     *
     * @return void
     */
    public function testLimitRange()
    {
        // Limit to Max or Min
        $this->assertEquals(10, Numbers::limitRange(15, 10));
        $this->assertEquals(5, Numbers::limitRange(3, 10, 5));
        $this->assertEquals(10, Numbers::limitRange(12, 10, null), 'Should limit to max, no min');
        $this->assertEquals(0, Numbers::limitRange(-1, 10, 0), 'Should limit to min');

        // Return the same number
        $this->assertEquals(5, Numbers::limitRange(5, 10));
        $this->assertEquals(10, Numbers::limitRange(10, 10, 5));
        $this->assertEquals(7, Numbers::limitRange(7, 10, 5));

        // Test with string inputs
        $this->assertEquals(10, Numbers::limitRange("15", "10"));
        $this->assertEquals(5, Numbers::limitRange("5", "10", "5"));
        $this->assertEquals(0, Numbers::limitRange("-1", "10", "0"));

        // Test edge cases
        $this->assertEquals(PHP_INT_MAX, Numbers::limitRange(PHP_INT_MAX, PHP_INT_MAX));
        $this->assertEquals(PHP_INT_MIN, Numbers::limitRange(PHP_INT_MIN, PHP_INT_MAX, PHP_INT_MIN));
    }


    /**
     * Tests Numbers::getHumanReadableAndPreciseBytes()
     *
     * @return void
     */
    public function testGetHumanReadableAndPreciseBytes()
    {
        // Test normal operation
        $this->assertEquals('0b / 0 bytes', Numbers::getHumanReadableAndPreciseBytes(0));
        $this->assertEquals('1b / 1 bytes', Numbers::getHumanReadableAndPreciseBytes(1));
        $this->assertEquals('1.00KB / 1000 bytes', Numbers::getHumanReadableAndPreciseBytes(1000));
        $this->assertEquals('1.02KB / 1024 bytes', Numbers::getHumanReadableAndPreciseBytes(1024));
        $this->assertEquals('1.04MB / 1048576 bytes', Numbers::getHumanReadableAndPreciseBytes(1048576));

        // Test custom precision
        $this->assertEquals('1.024KB / 1024 bytes', Numbers::getHumanReadableAndPreciseBytes(1024, 'auto', 3));
        $this->assertEquals('1.0KB / 1024 bytes', Numbers::getHumanReadableAndPreciseBytes(1024, 'auto', 1));

        // Test unit overrides
        $this->assertEquals('1.00MiB / 1048576 bytes', Numbers::getHumanReadableAndPreciseBytes(1048576, 'MiB'));
        $this->assertEquals('1,024.00KiB / 1048576 bytes', Numbers::getHumanReadableAndPreciseBytes(1048576, 'KiB'));

        // Test without suffix
        $this->assertEquals('1.00 / 1024 bytes', Numbers::getHumanReadableAndPreciseBytes(1024, 'KiB', 2, false));

        // Test failures
        $this->expectException(OutOfBoundsException::class);
        Numbers::getHumanReadableAndPreciseBytes(-1);
    }


    /**
     * Tests Numbers::fromBytes()
     *
     * @return void
     */
    public function testFromBytes()
    {
        // Test valid inputs
        $this->assertEquals(0, Numbers::fromBytes('0'));
        $this->assertEquals(1024, Numbers::fromBytes('1KiB'));
        $this->assertEquals(1000, Numbers::fromBytes('1KB'));
        $this->assertEquals(1048576, Numbers::fromBytes('1MiB'));
        $this->assertEquals(1000000, Numbers::fromBytes('1MB'));

        // Test invalid format
        $this->expectException(NumbersException::class);
        Numbers::fromBytes('invalidFormat');
    }


    /**
     * Tests Numbers::getStep()
     *
     * @return void
     */
    public function testGetStep()
    {
        // Test normal operation
        $this->assertEquals('0.001', Numbers::getStep(1, 15, .1, 0.009));

        // Test invalid input
        $this->expectException(NumbersException::class);
        Numbers::getStep(1, 'not_numeric');
    }


    /**
     * Tests Numbers::getRandomFloat()
     *
     * @return void
     */
    public function testGetRandomFloat()
    {
        // Test normal operation
        $result = Numbers::getRandomFloat(0, 10);
        $this->assertGreaterThanOrEqual(0, $result);
        $this->assertLessThanOrEqual(10, $result);
    }


    /**
     * Tests Numbers::getRandomInt()
     *
     * @return void
     */
    public function testGetRandomInt()
    {
        // Test normal operation
        $result = Numbers::getRandomInt(0, 10);
        $this->assertGreaterThanOrEqual(0, $result);
        $this->assertLessThanOrEqual(10, $result);
    }


    /**
     * Tests Numbers::getRandomInt()
     *
     * @return void
     */
    public function testGetHighest()
    {
        // Test normal operation
        $this->assertEquals(10, Numbers::getHighest(1, 2, 10));
        $this->assertEquals(-1, Numbers::getHighest(-10, -1));
    }


    /**
     * Tests Numbers::getLowest()
     *
     * @return void
     */
    public function testGetLowest()
    {
        // Test normal operation
        $this->assertEquals(1, Numbers::getLowest(1, 2, 10));
        $this->assertEquals(-10, Numbers::getLowest(-10, -1));
    }


    /**
     * Tests Numbers::HumanReadable()
     *
     * @return void
     */
    public function testHumanReadable()
    {
        // Test normal operation
        $this->assertEquals('1.00K', Numbers::humanReadable(1000, 1000, 2));
        $this->assertEquals('1.00M', Numbers::humanReadable(1000000, 1000, 2));
        $this->assertEquals('1.00G', Numbers::humanReadable(1000000000, 1000, 2));

        // Test small number
        $this->assertEquals('1.00', Numbers::humanReadable(1, 1000, 2));
    }
}
