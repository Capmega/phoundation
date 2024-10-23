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

use Phoundation\Databases\Connectors\Connector;
use Phoundation\Databases\Redis\Redis;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use PHPUnit\Framework\TestCase;


class ArraysTest extends TestCase
{

    /**
     * Test requiredKeys method
     *
     * @return void
     */
    public function testRequiredKeys()
    {
        $array = new Arrays();
        $this->expectException(OutOfBoundsException::class);
        $array->requiredKeys((array) $array, 'test');
    }



}
