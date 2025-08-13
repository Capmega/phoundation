<?php

/**
 * Class ArraysTest
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils\Library\tests\Phoundation\Utils;

use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\Tests\TestDataEntry;
use Phoundation\Databases\Connectors\Connector;
use Phoundation\Databases\Redis\Redis;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use PHPUnit\Framework\TestCase;
use Throwable;

class ArraysTest extends TestCase
{

    /**
     * Tests Arrays::requiredKeys
     *
     * @return void
     */
    public function testRequiredKeys()
    {
        $array = [
            'test' => 'value'
        ];

        Arrays::requiredKeys($array, 'test');

        $array_2 = [
            'test' => 'value',
            'test2' => 'value2'
        ];

        Arrays::requiredKeys($array_2, ['test']);
        Arrays::requiredKeys($array_2, ['test', 'test2']);

        try {
            Arrays::requiredKeys(['id' => 1], ['id', 'missing']);
            $this->fail('Expected OutOfBoundsException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(OutOfBoundsException::class, $e);
        }
    }


    /**
     * Tests Arrays::isMultiDimensional
     *
     * @return void
     */
    public function testIsMultiDimensional()
    {
        $this->assertFalse(Arrays::isMultiDimensional([]));
        $this->assertFalse(Arrays::isMultiDimensional(['a', 'b', 'c']));
        $this->assertTrue(Arrays::isMultiDimensional([['a'], ['b']]));
        $this->assertTrue(Arrays::isMultiDimensional(['a', ['b'], 'c']));
    }


    /**
     * Tests Arrays::hasAllKeys()
     *
     * @return void
     */
    public function testHasAllKeys()
    {
        $array = [
            'name' => 'John',
            'age' => 30,
            'email' => 'john@example.com'
        ];

        $this->assertTrue(Arrays::hasAllKeys($array, ['name', 'email']));
        $this->assertTrue(Arrays::hasAllKeys($array, 'name'));
        $this->assertTrue(Arrays::hasAllKeys($array, ['name']));
        $this->assertFalse(Arrays::hasAllKeys($array, ['name', 'address']));
        $this->assertFalse(Arrays::hasAllKeys($array, 'missing_key'));
        $this->assertTrue(Arrays::hasAllKeys($array, []));
    }


    /**
     * Tests Arrays::force
     *
     * @return void
     */
    public function testForce()
    {
        $this->assertEquals(['a', 'b', 'c'], Arrays::force('a,b,c'));
        $this->assertEquals(['a', 'b', 'c'], Arrays::force('a|b|c', '|'));
        $this->assertEquals(['one', 'two'], Arrays::force(['one', 'two']));

        $this->assertEquals([], Arrays::force(''));
        $this->assertEquals([], Arrays::force(null));
        $this->assertEquals([123], Arrays::force(123));

        $object = TestDataEntry::new()->setName('test_name');
        $this->assertEquals($object->getSource(), Arrays::force($object));
    }


}
