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
     * Tests Url::makeWww()
     *
     * @return void
     */
    public function testGetWww()
    {
        $url = Url::newCurrent();
        $this->assertEquals('http://mediweb.medinet.ca.local/en/', $url->getSource(), 'The result array should equal the sample array');
    }
}
