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

use Phoundation\Core\Log\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;
use PHPUnit\Framework\TestCase;
use Throwable;
use ValueError;

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

        // TODO should these 2 should return false?
//        $this->assertFalse(Strings::isBase64('not-base64!!!'));
//        $this->assertFalse(Strings::isBase64('YWJj==')); // invalid padding
        // TODO Should empty string return true or false?
//        $this->assertFalse(Strings::isBase64('')); // empty
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
        // TODO Should empty string return true or false?
//        $this->assertFalse(Strings::isBase58('')); // empty
    }


    /**
     * Test Strings::fromBase58()
     *
     * @return void
     */
    public function testFromBase58()
    {
        $original = 'test';
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

    /**
     * Test Strings::explode()
     *
     * @return void
     */
    public function testExplode()
    {
        $this->assertEquals([], Strings::explode(',', ''));
        $this->assertEquals(['1','2','3'], Strings::explode(',', '1,2,3'));

        try {
            $this->assertEquals([], Strings::explode('', '1,2,3')); // Separator cannot be empty
            $this->fail('Expected exception ValueError was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(ValueError::class, $e);
        }
        $this->assertEquals([], Strings::explode('', '')); // If source is empty, separator does not matter
    }


    /**
     * Test Strings::interleave()
     *
     * @return void
     */
    public function testInterleave()
    {
        $this->assertEquals('a-b-c', Strings::interleave('abc', '-')); // Simple chunk of 1
        $this->assertEquals('ab-cd', Strings::interleave('abcd', '-', chunk_size: 2)); // Chunk of 2
        $this->assertEquals('ab-cd-efgh', Strings::interleave('abcdefgh', '-', end: 6, chunk_size: 2)); // Ending preserved
        $this->assertEquals('abc', Strings::interleave('abc', '-', chunk_size: 5)); // No interleaving when chunk_size exceeds string length
        $this->assertEquals('', Strings::interleave('', '-')); // Empty string input

        try {
            Strings::interleave('abc', '');
            $this->fail('Expected exception OutOfBoundsException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(OutOfBoundsException::class, $e);
        }

        try {
            Strings::interleave('abc', '-', 0, 0);
            $this->fail('Expected exception OutOfBoundsException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(OutOfBoundsException::class, $e);
        }
    }


    /**
     * Tests Strings::convertAccents()
     *
     * @return void
     */
    public function testConvertAccents()
    {
        $this->assertEquals('aeoeu', Strings::convertAccents('æœü'));
        $this->assertEquals('e', Strings::convertAccents('é'));
        $this->assertEquals('n', Strings::convertAccents('ñ'));
        $this->assertEquals('Cafe', Strings::convertAccents('Café'));
        $this->assertEquals('', Strings::convertAccents(''));
    }


    /**
     * Tests Strings::stripHtmlWhitespace()
     *
     * @return void
     */
    public function testStripHtmlWhitespace()
    {
        $this->assertEquals('<div><span>text</span></div>', Strings::stripHtmlWhitespace('<div>  <span>text</span> </div>'));
        $this->assertEquals('<a><b></b></a>', Strings::stripHtmlWhitespace('<a>   <b> </b>  </a>'));
        $this->assertEquals('<p>hello</p>', Strings::stripHtmlWhitespace('<p>hello</p>')); // no change
        $this->assertEquals('', Strings::stripHtmlWhitespace(''));
    }


    /**
     * Tests Strings::quote()
     *
     * @return void
     */
    public function testQuote()
    {
        $this->assertEquals('\'text\'', Strings::quote('text'));
        $this->assertEquals('"text"', Strings::quote('text', '"'));
        $this->assertEquals('123', Strings::quote(123));
        $this->assertEquals('\'123\'', Strings::quote(123, '\'', true));
        $this->assertEquals('1', Strings::quote(true));
        $this->assertEquals('\'1\'', Strings::quote(true, '\'', true));
        $this->assertEquals('', Strings::quote(null));
        $this->assertEquals('\'\'', Strings::quote(null, '\'', true));
    }


    /**
     * Tests Strings::isVersion()
     *
     * @return void
     */
    public function testIsVersion()
    {
        $this->assertTrue(Strings::isVersion('1.0.0'));
        $this->assertTrue(Strings::isVersion('999.9999.9999'));
        $this->assertFalse(Strings::isVersion('1.0'));
        $this->assertFalse(Strings::isVersion('1.0.0.0'));
        $this->assertFalse(Strings::isVersion('1.a.0'));
        $this->assertFalse(Strings::isVersion(''));

        $this->assertTrue(Strings::isVersion('post_once', true));
        $this->assertTrue(Strings::isVersion('post_always', true));
        $this->assertTrue(Strings::isVersion('-1.0.0', true));
        $this->assertFalse(Strings::isVersion('1.0.0.0', true));
    }


    /**
     * Tests Strings::containsHtml()
     *
     * @return void
     */
    public function testContainsHtml()
    {
        $this->assertTrue(true);
        // TODO fix this
//        $this->assertTrue(Strings::containsHtml('<div>hello</div>'));
//        $this->assertTrue(Strings::containsHtml('<span>Test</span>'));
//        $this->assertTrue(Strings::containsHtml('<p>This is a paragraph.</p>'));
//
//        $this->assertTrue(Strings::containsHtml('<br/>'));
//        $this->assertTrue(Strings::containsHtml('<img src="image.jpg" />'));
//        $this->assertTrue(Strings::containsHtml('<input type="text" />'));
//        $this->assertTrue(Strings::containsHtml('<table><tr><td>cell</td></tr></table>'));
//        $this->assertTrue(Strings::containsHtml('<form><input type="submit" /></form>'));
//        $this->assertTrue(Strings::containsHtml('<script>alert("test");</script>'));
//        $this->assertTrue(Strings::containsHtml('<style>body { background: red; }</style>'));
//        $this->assertTrue(Strings::containsHtml('<div><span><a href="#">link</a></span></div>'));
//
//         non-HTML content
//        $this->assertFalse(Strings::containsHtml('Plain text'));
//        $this->assertFalse(Strings::containsHtml('2 < 5 and 7 > 3')); // comparison operators
//        $this->assertFalse(Strings::containsHtml('some text with angle brackets <>'));
//        $this->assertFalse(Strings::containsHtml(''));
    }


    /**
     * Tests Strings::isJson()
     *
     * @return void
     */
    public function testIsJson()
    {
        $this->assertTrue(Strings::isJson('{"key": "value"}'));
        $this->assertTrue(Strings::isJson('[1, 2, 3]'));
        // TODO fix
//        $this->assertTrue(Strings::isJson('"simple string"'));
        $this->assertTrue(Strings::isJson('123'));

        $this->assertFalse(Strings::isJson('{key: value}'));
        $this->assertFalse(Strings::isJson('plain text'));
        $this->assertFalse(Strings::isJson(''));
    }


    /**
     * Tests Strings::hasKeyword()
     *
     * @return void
     */
    public function testHasKeyword()
    {
        $text = 'The quick brown fox jumps over the lazy dog.';

        $this->assertEquals('fox', Strings::hasKeyword($text, ['cat', 'fox', 'dog']));
        $this->assertEquals('lazy', Strings::hasKeyword($text, ['lazy']));
        $this->assertNull(Strings::hasKeyword($text, ['elephant', 'giraffe'])); // none match

        try {
            Strings::hasKeyword('test', [['not-scalar']]);
            $this->fail('Expected exception OutOfBoundsException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(OutOfBoundsException::class, $e);
        }
    }


    /**
     * Tests Strings::hasKeywordWithRegex()
     *
     * @return void
     */
    public function testHasKeywordWithRegex()
    {
        $text = 'The price is $100.';
        $this->assertEquals('\$\d+', Strings::hasKeyword($text, ['\$\d+'], regex: true));
    }


    /**
     * Tests Strings::hasAllKeywords()
     *
     * @return void
     */
    public function testHasAllKeywords()
    {
        $text = 'Fast cars, loud engines, and fast drivers.';
        $this->assertTrue(Strings::hasAllKeywords($text, ['cars', 'engines']));
        $this->assertFalse(Strings::hasAllKeywords($text, ['cars', 'boats']));

        $text_2 = 'abc123def456ghi';
        $this->assertTrue(Strings::hasAllKeywords($text_2, ['\d{3}', 'abc'], regex: true));
        // TODO fix
//        $this->assertFalse(Strings::hasAllKeywords($text_2, ['\d{3}', 'xyz'], regex: true));
    }


    /**
     * Tests Strings::caps()
     *
     * @return void
     */
    public function testCaps()
    {
        $text = 'hello world';

        $this->assertEquals('hello world', Strings::caps($text, 'lowercase'));
        $this->assertEquals('HELLO WORLD', Strings::caps($text, 'uppercase'));
        $this->assertEquals('Hello world', Strings::caps($text, 'capitalize'));
        $this->assertEquals('Hello worlD', Strings::caps($text, 'doublecapitalize'));

        // TODO fix
//        $this->assertEquals('hELLO wORLD', Strings::caps($text, 'invertcapitalize'));
//        $this->assertEquals('hELLoworlD', Strings::caps($text, 'invertdoublecapitalize'));

        try {
            Strings::caps('hello', 'not_a_style');
            $this->fail('Expected exception OutOfBoundsException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(OutOfBoundsException::class, $e);
        }
    }


    /**
     * Tests Strings::capsGuess()
     *
     * @return void
     */
    public function testCapsGuess()
    {
        // TODO: implement method
        $this->assertTrue(true);
    }


    /**
     * Tests Strings::similar()
     *
     * @return void
     */
    public function testSimilar()
    {
        $percent = 0.0;

        $count = Strings::similar('hello world', 'hello world', $percent);
        $this->assertEquals(11, $count);
        $this->assertEquals(100.0, $percent);

        $count = Strings::similar('hello world', 'hello mars', $percent);
        $this->assertGreaterThan(0, $count);
        $this->assertGreaterThan(0, $percent);
        $this->assertLessThan(100.0, $percent);

        $count = Strings::similar('', '', $percent);
        $this->assertEquals(0, $count);
        $this->assertEquals(0.0, $percent);
    }


    /**
     * Tests Strings::xor()
     *
     * @return void
     */
    public function testXor()
    {
        // TODO: implement method
        $this->assertTrue(true);
    }


    /**
     * Tests Strings::trimArray()
     *
     * @return void
     */
    public function testTrimArray()
    {
        $input    = [' one ', ' two', 'three ', 'four'];
        $expected = ['one', 'two', 'three', 'four'];

        $this->assertEquals($expected, Strings::trimArray($input));

        $input = [
            ' key '  => ' value ',
            'nested' => [
                ' item1 ' => ' value1 ',
                ' item2 ' => [' sub ' => ' val '],
            ]
        ];

        $expected = [
            ' key '  => 'value',
            'nested' => [
                ' item1 ' => 'value1',
                ' item2 ' => [' sub ' => 'val'],
            ]
        ];

        $this->assertEquals($expected, Strings::trimArray($input));

        $input = [
            ' key ' => ' value ',
            'nested' => [
                ' item1 ' => ' value1 ',
            ]
        ];

        $expected = [
            ' key ' => 'value',
            'nested' => [
                ' item1 ' => ' value1 ',
            ]
        ];

        $this->assertEquals($expected, Strings::trimArray($input, recurse: false));
    }


}
