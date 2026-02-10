<?php

/**
 * Class UrlTest
 *
 * This PHPUnit test class will test the \Phoundation\Web\Http\Url Object
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Library\Tests\Phoundation\Web\Http;

use Phoundation\Web\Http\Url;
use PHPUnit\Framework\TestCase;


class UrlTest extends TestCase
{
    /**
     * Tests Url::makeWww()
     *
     * @return void
     */
    public function testGetWww()
    {
        $url = Url::newCurrent();
        $this->assertEquals('http://phoundation.org/en/', $url->getSource(), 'The result array should equal the sample array');
    }

    /**
     * Tests Url::new()
     *
     * @return void
     */
    public function testNew()
    {
        $url_1 = Url::new();
        $this->assertNull($url_1->getSource());

        $url_2 = Url::new('test');
        $this->assertEquals('test', $url_2->getSource());

        $domain = 'http://phoundation.org';
        $url_3 = Url::new($domain);
        $this->assertEquals($domain, $url_3->getSource());

        $url_4 = Url::new($url_3);
        $this->assertEquals($domain, $url_4->getSource());
    }
}
