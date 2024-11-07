<?php

/**
 * Class UrlTest
 *
 * This PHPUnit test class will test the \Phoundation\Web\Http\Url Object
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Library\tests\Phoundation\Web\Http;

use Phoundation\Web\Http\Url;
use PHPUnit\Framework\TestCase;


class UrlTest extends TestCase
{
    /**
     * Tests Url::getWww()
     *
     * @return void
     */
    public function testGetWww()
    {
        $url = Url::getWww();
        $this->assertEquals($url, '', 'The result array should equal the sample array');
    }
}
