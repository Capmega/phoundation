<?php

/**
 * Class ArraysTest
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
