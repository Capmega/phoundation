<?php

namespace Data\Validator;

use Phoundation\Data\Exception\ValidatorException;
use Phoundation\Data\Validator\Validator;
use PHPUnit\Framework\TestCase;



/**
 * \Phoundation\Core\Validator test class
 */
class ValidatorTest extends TestCase
{
    public function testIsScalar()
    {
        // Test normal operation
        $array = [
            'test-scalar' => ' 1 ',
        ];

        $result = [
            'test-scalar' => ' 1 ',
        ];

        Validator::array($array)
            ->select('test-scalar')->isScalar()
            ->validate();

        show($array);

        $this->assertEquals($result, $array);

        // Test failures
        $array = [
            'test-scalar' => [],
        ];

        $result = [
            'test-scalar' => '',
        ];

        $this->expectException(ValidatorException::class);

        Validator::array($array)
            ->select('test-scalar')->isScalar()
            ->validate();

        $this->assertEquals($result, $array);
    }

    public function testIsString()
    {

    }

    public function testIsArray()
    {

    }

    public function testIsNull()
    {

    }

    public function testIsBoolean()
    {

    }

    public function testIsNumeric()
    {

    }

    public function testIsInteger()
    {

    }

    public function testIsFloat()
    {

    }

    public function testIsLessThan()
    {

    }

    public function testIsMoreThan()
    {

    }

    public function testIsBetween()
    {

    }

    public function testAll()
    {
//        $array = [
//            'test-xora' => ' 1 ',
//            'test-xorb' => ' 1 ',
//            'test-numeric' => ' 1 ',
//            'test-string' => 'blergh',
//            'test-list-array' => ['sub-list-array' => ['a', 'b', 'c', 'd', 'e']],
//            'test-array' => [
//                'sub-integer' => 23974,
//                'sub-email' => 'so.oostenbrink@gmail.com',
//                'sub-sub-array' => [
//                    'sub-sub-email' => 'so.oostenbrink@gmail.com',
//                ]
//            ],
//            'test-not-array' => 'blergh!',
//            'test-not-validated' => 'This entry should be deleted!',
//            'test-list-array2' => ['sub-list-array' => ['a', 'b', 'c', 'd', 'e']],
//            'test-name' => 'so.oostenbrink@gmail.com',
//            'test-email' => 'so.oostenbrink@gmail.com',
//        ];
//
//        Validator::array($array)
//            ->select('test-xora')->xor('test-xorb')->isArray()
//            ->select('test-optional')->isOptional([])->isArray()
//            ->select('test-list-array')->recurse()
//            ->select('sub-list-array')->hasMinimumElements(3)->each()->hasMinCharacters(1)
//            ->validate()
//            ->select('test-array')->isArray()->recurse()
//            ->select('sub-integer')->isInteger()
//            ->select('sub-sub-array')->isArray()->recurse()
//            ->select('sub-sub-email')->isEmail()
//            ->validate()
//            ->select('sub-email')->isString()
//            ->validate()
//            ->validate();
//
//        show($array);
//
//
//        // Test normal operation
//        $this->assertEquals('gmail.com', Strings::from('so.oostenbrink@gmail.com', '@'));                   // From single character
//        $this->assertEquals('.com', Strings::from('so.oostenbrink@gmail.com', 'gmail'));                    // From multiple characters
//        $this->assertEquals('', Strings::from('so.oostenbrink@gmail.com', '.com'));                         // From last few characters
//        $this->assertEquals('o.oostenbrink@gmail.com', Strings::from('so.oostenbrink@gmail.com', 's'));     // From first character
//        $this->assertEquals('', Strings::from('', 'sven'));                                                 // From empty string
//        $this->assertEquals('', Strings::from('so.oostenbrink@gmail.com', 'so.oostenbrink@gmail.com'));     // From entire source string
//
//        // Test failures
//        $this->expectException(OutOfBoundsException::class);
//        $this->assertEquals(null, Strings::from('so.oostenbrink@gmail.com', ''));                           // Needle is obligatory


    }
}