<?php

/**
 * Class StringsTest
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils\Library\Tests\Phoundation\Utils;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;
use PHPUnit\Framework\TestCase;
use Throwable;

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
        $this->assertEquals('gmail.com', Strings::from('so.oostenbrink@gmail.com', '@'));                               // From single character
        $this->assertEquals('.com', Strings::from('so.oostenbrink@gmail.com', 'gmail'));                                // From multiple characters
        $this->assertEquals('', Strings::from('so.oostenbrink@gmail.com', '.com'));                                     // From last few characters
        $this->assertEquals('o.oostenbrink@gmail.com', Strings::from('so.oostenbrink@gmail.com', 's'));                 // From first character
        $this->assertEquals('', Strings::from('', 'sven'));                                                             // From empty string
        $this->assertEquals('', Strings::from('so.oostenbrink@gmail.com', 'so.oostenbrink@gmail.com'));                 // From entire source string

        $this->assertEquals('so.oostenbrink@gmail.com', Strings::from('so.oostenbrink@gmail.com', 'test'));
        $this->assertEmpty(Strings::from('so.oostenbrink@gmail.com', 'test', needle_required: true));
    }


    /**
     * Test Strings::until()
     *
     * @return void
     */
    public function testUntil()
    {
        // Test normal operation
        $this->assertEquals('so.oostenbrink', Strings::until('so.oostenbrink@gmail.com', '@'));                         // Until single character
        $this->assertEquals('so.oostenbrink@', Strings::until('so.oostenbrink@gmail.com', 'gmail'));                    // Until multiple characters
        $this->assertEquals('so.oostenbrink@gmail', Strings::until('so.oostenbrink@gmail.com', '.com'));                // Until last few characters
        $this->assertEquals('', Strings::until('so.oostenbrink@gmail.com', 's'));                                       // Until first character
        $this->assertEquals('', Strings::until('', 'sven'));                                                            // Until empty string
        $this->assertEquals('', Strings::until('so.oostenbrink@gmail.com', 'so.oostenbrink@gmail.com'));                // Until entire source string

        $this->assertEquals('so.oostenbrink@gmail.com', Strings::until('so.oostenbrink@gmail.com', 'test'));
        $this->assertEmpty(Strings::until('so.oostenbrink@gmail.com', 'test', needle_required: true));
    }


    /**
     * Test Strings::ensureProtocol()
     *
     * @return void
     */
    public function testEnsureProtocol()
    {
        $this->assertEquals('https://example.com', Strings::ensureProtocol('example.com'));
        $this->assertEquals('https://example.com', Strings::ensureProtocol('https://example.com'));
        $this->assertEquals('http://example.com', Strings::ensureProtocol('example.com', 'http://'));
        $this->assertEquals('ftp://file', Strings::ensureProtocol('file', 'ftp://'));
        $this->assertEquals('file', Strings::ensureProtocol('file', ''));
    }


    /**
     * Test Strings::plural()
     *
     * @return void
     */
    public function testPlural()
    {
        $this->assertEquals('house', Strings::plural(1, 'house', 'houses'));
        $this->assertEquals('houses', Strings::plural(0, 'house', 'houses'));
        $this->assertEquals('houses', Strings::plural(2, 'house', 'houses'));
        $this->assertEquals('houses', Strings::plural(1.5, 'house', 'houses'));
    }


    /**
     * Test Strings::isSerialized()
     *
     * @return void
     */
    public function testIsSerialized()
    {
        $this->assertTrue(Strings::isSerialized(serialize(['a' => 'b'])));
        $this->assertTrue(Strings::isSerialized(serialize(123)));
        $this->assertFalse(Strings::isSerialized('not serialized'));
        $this->assertFalse(Strings::isSerialized(null));
        $this->assertFalse(Strings::isSerialized(''));
    }


    /**
     * Test Strings::ensureUtf8()
     *
     * @return void
     */
    public function testEnsureUtf8()
    {
        $utf8 = 'Café';
        $latin1 = mb_convert_encoding($utf8, 'ISO-8859-1', 'UTF-8');

        $this->assertEquals($utf8, Strings::ensureUtf8($utf8));       // Already UTF-8
        $this->assertEquals($utf8, Strings::ensureUtf8($latin1));     // Not UTF-8 initially
    }


    /**
     * Test Strings::isUtf8()
     *
     * @return void
     */
    public function testIsUtf8()
    {
        $this->assertTrue(Strings::isUtf8('test'));
        $this->assertTrue(Strings::isUtf8('こんにちは')); // Japanese
        $this->assertFalse(Strings::isUtf8(mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8'))); // Latin1 encoded string
    }


    /**
     * Test Strings::capitalize()
     *
     * @return void
     */
    public function testCapitalize()
    {
        $this->assertEquals('Hello world', Strings::capitalize('hello world'));
        $this->assertEquals('heLlo world', Strings::capitalize('hello world', 2));
        $this->assertEquals('Hello world', Strings::capitalize('HELLO WORLD'));
        $this->assertEquals('', Strings::capitalize(''));
    }


    /**
     * Test Strings::isAlpha()
     *
     * @return void
     */
    public function testIsAlpha()
    {
        $this->assertTrue(Strings::isAlpha('abcDEF'));
        $this->assertTrue(Strings::isAlpha('abc 123')); // allow space by default
        $this->assertFalse(Strings::isAlpha('abc!123'));
        $this->assertTrue(Strings::isAlpha('abc_123', '_')); // allow underscore
    }


    /**
     * Test Strings::escapeForJquery()
     *
     * @return void
     */
    public function testEscapeForJquery()
    {
        $this->assertEquals('abc', Strings::escapeForJquery('abc'));
        $this->assertEquals('\\$&abc', Strings::escapeForJquery('#abc'));
        $this->assertEquals('\\$&\\$&\\$&\\$&', Strings::escapeForJquery('#&+('));
        $this->assertEquals('custom#@@test', Strings::escapeForJquery('custom#@test', '#@'));
    }


    /**
     * Test Strings::isBase64()
     *
     * @return void
     */
    public function testIsBase64()
    {
        $this->assertTrue(Strings::isBase64(base64_encode('hello world')));
        $this->assertTrue(Strings::isBase64('aGVsbG8gd29ybGQ=')); // valid base64
        $this->assertTrue(Strings::isBase64('YWJj')); // no padding, still valid
        $this->assertFalse(Strings::isBase64('not-base64!!!'));
        $this->assertFalse(Strings::isBase64('YWJj==')); // invalid padding
        $this->assertFalse(Strings::isBase64('')); // empty
    }


    /**
     * Test Strings::fromBase64()
     *
     * @return void
     */
    public function testFromBase64()
    {
        $this->assertEquals('hello', Strings::fromBase64('aGVsbG8='));
        $this->assertEquals('hello', Strings::fromBase64('aGVsbG8')); // no padding
        $this->assertEquals('', Strings::fromBase64('')); // empty string decodes to empty
    }


    /**
     * Test Strings::toBase64()
     *
     * @return void
     */
    public function testToBase64()
    {
        $this->assertEquals('aGVsbG8gd29ybGQ=', Strings::toBase64('hello world'));
        $this->assertEquals('', Strings::toBase64(''));
    }


    /**
     * Test Strings::isBase58()
     *
     * @return void
     */
    public function testIsBase58()
    {
        $encoded = Strings::toBase58('hello');
        $this->assertTrue(Strings::isBase58($encoded));

        $this->assertFalse(Strings::isBase58('O0Il')); // contains invalid base58 characters (O, 0, I, l)
        $this->assertFalse(Strings::isBase58('')); // empty
    }


    /**
     * Test Strings::fromBase58()
     *
     * @return void
     */
    public function testFromBase58()
    {
        $original = 'test123';
        $encoded  = Strings::toBase58($original);

        $this->assertEquals($original, Strings::fromBase58($encoded));
    }


    /**
     * Test Strings::toBase58()
     *
     * @return void
     */
    public function testToBase58()
    {
        $encoded = Strings::toBase58('example');
        $this->assertNotEmpty($encoded);
        $this->assertTrue(Strings::isBase58($encoded));

        // Check it’s reversible
        $this->assertEquals('example', Strings::fromBase58($encoded));
    }


    /**
     * Test Strings::camelCase()
     *
     * @return void
     */
    public function testCamelCase()
    {
        $this->assertEquals('Hello World', Strings::camelCase('hello world'));
        $this->assertEquals('One_Two_Three', Strings::camelCase('one_two_three', '_'));
        $this->assertEquals('Already Camel', Strings::camelCase('Already Camel'));
        $this->assertEquals('', Strings::camelCase(''));
    }


}
