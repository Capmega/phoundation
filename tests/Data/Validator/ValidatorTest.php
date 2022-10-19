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
    /**
     * Test Validator::isScalar()
     *
     * @return void
     */
    public function testIsScalar()
    {
        // Test normal operation
        $array = [
            'test' => ' 1 ',
        ];

        $result = [
            'test' => ' 1 ',
        ];

        Validator::array($array)
            ->select('test')->isScalar()
            ->validate();

        $this->assertEquals($result, $array);

        // Test failures
        $array = [
            'test' => [],
        ];

        $result = [
            'test' => '',
        ];

        $this->expectException(ValidatorException::class);

        Validator::array($array)
            ->select('test')->isScalar()
            ->validate();

        $this->assertEquals($result, $array);
    }



    /**
     * Test Validator::isString()
     *
     * @return void
     */
    public function testIsString()
    {
        // Test normal operation
        $array = [
            'test' => ' 1 ',
        ];

        $result = [
            'test' => ' 1 ',
        ];

        Validator::array($array)
            ->select('test')->isString()
            ->validate();

        $this->assertEquals($result, $array);

        // Test failures
        $array = [
            'test' => [],
        ];

        $result = [
            'test' => '',
        ];

        $this->expectException(ValidatorException::class);

        Validator::array($array)
            ->select('test')->isString()
            ->validate();

        $this->assertEquals($result, $array);
    }



    /**
     * Test Validator::isArray()
     *
     * @return void
     */
    public function testIsArray()
    {
        // Test normal operation
        $array = [
            'test' => [' 1 '],
        ];

        $result = [
            'test' => [' 1 '],
        ];

        Validator::array($array)
            ->select('test')->isArray()
            ->validate();

        $this->assertEquals($result, $array);

        // Test failures
        $array = [
            'test' => '',
        ];

        $result = [
            'test' => [],
        ];

        $this->expectException(ValidatorException::class);

        Validator::array($array)
            ->select('test')->isArray()
            ->validate();

        $this->assertEquals($result, $array);
    }



    /**
     * Test Validator::isNull()
     *
     * @return void
     */
    public function testIsNull()
    {
        // Test normal operation
        $array = [
            'test' => null,
        ];

        $result = [
            'test' => null,
        ];

        Validator::array($array)
            ->select('test')->isNull()
            ->validate();

        $this->assertEquals($result, $array);

        // Test failures
        $array = [
            'test' => '',
        ];

        $result = [
            'test' => null,
        ];

        $this->expectException(ValidatorException::class);

        Validator::array($array)
            ->select('test')->isNull()
            ->validate();

        $this->assertEquals($result, $array);
    }



    /**
     * Test Validator::isBoolean()
     *
     * @return void
     */
    public function testIsBoolean()
    {
        // Test normal operation
        $array = [
            'test' => false,
        ];

        $result = [
            'test' => false,
        ];

        Validator::array($array)
            ->select('test')->isBoolean()
            ->validate();

        $this->assertEquals($result, $array);

        // Test failures
        $array = [
            'test' => '',
        ];

        $result = [
            'test' => false,
        ];

        $this->expectException(ValidatorException::class);

        Validator::array($array)
            ->select('test')->isBoolean()
            ->validate();

        $this->assertEquals($result, $array);
    }



    /**
     * Test Validator::isNumeric()
     *
     * @return void
     */
    public function testIsNumeric()
    {
        // Test normal operation
        $array = [
            'test' => 0,
        ];

        $result = [
            'test' => 0,
        ];

        Validator::array($array)
            ->select('test')->isNumeric()
            ->validate();

        $this->assertEquals($result, $array);

        // Test failures
        $array = [
            'test' => '',
        ];

        $result = [
            'test' => 0,
        ];

        $this->expectException(ValidatorException::class);

        Validator::array($array)
            ->select('test')->isNumeric()
            ->validate();

        $this->assertEquals($result, $array);
    }



    /**
     * Test Validator::isInteger()
     *
     * @return void
     */
    public function testIsInteger()
    {
        // Test normal operation
        $array = [
            'test' => 0,
        ];

        $result = [
            'test' => 0,
        ];

        Validator::array($array)
            ->select('test')->isInteger()
            ->validate();

        $this->assertEquals($result, $array);

        // Test failures
        $array = [
            'test' => '',
        ];

        $result = [
            'test' => 0,
        ];

        $this->expectException(ValidatorException::class);

        Validator::array($array)
            ->select('test')->isInteger()
            ->validate();

        $this->assertEquals($result, $array);
    }



    /**
     * Test Validator::isFloat()
     *
     * @return void
     */
    public function testIsFloat()
    {
        // Test normal operation
        $array = [
            'test' => 0.0,
        ];

        $result = [
            'test' => 0.0,
        ];

        Validator::array($array)
            ->select('test')->isFloat()
            ->validate();

        $this->assertEquals($result, $array);

        // Test failures
        $array = [
            'test' => '',
        ];

        $result = [
            'test' => 0.0,
        ];

        $this->expectException(ValidatorException::class);

        Validator::array($array)
            ->select('test')->isFloat()
            ->validate();

        $this->assertEquals($result, $array);
    }



    /**
     * Test Validator::isLessThan()
     *
     * @return void
     */
    public function testIsLessThan()
    {
        // Test normal operation
        $array = [
            'test' => 0.0,
        ];

        $result = [
            'test' => 0.0,
        ];

        Validator::array($array)
            ->select('test')->isLessThan(1)
            ->validate();

        $this->assertEquals($result, $array);

        // Test failures
        $array = [
            'test' => '',
        ];

        $result = [
            'test' => 5.0,
        ];

        $this->expectException(ValidatorException::class);

        Validator::array($array)
            ->select('test')->isLessThan(1)
            ->validate();

        $this->assertEquals($result, $array);
    }



    /**
     * Test Validator::isMoreThan()
     *
     * @return void
     */
    public function testIsMoreThan()
    {
        // Test normal operation
        $array = [
            'test' => 1.0,
        ];

        $result = [
            'test' => 1.0,
        ];

        Validator::array($array)
            ->select('test')->isMoreThan(0)
            ->validate();

        $this->assertEquals($result, $array);

        // Test failures
        $array = [
            'test' => '',
        ];

        $result = [
            'test' => 0.0,
        ];

        $this->expectException(ValidatorException::class);

        Validator::array($array)
            ->select('test')->isMoreThan(1)
            ->validate();

        $this->assertEquals($result, $array);
    }



    /**
     * Test Validator::isBetween()
     *
     * @return void
     */
    public function testIsBetween()
    {
        // Test normal operation
        $array = [
            'test' => 1.0,
        ];

        $result = [
            'test' => 1.0,
        ];

        Validator::array($array)
            ->select('test')->isBetween(0, 2)
            ->validate();

        $this->assertEquals($result, $array);

        // Test failures
        $array = [
            'test' => '',
        ];

        $result = [
            'test' => 0.0,
        ];

        $this->expectException(ValidatorException::class);

        Validator::array($array)
            ->select('test')->isBetween(1, 2)
            ->validate();

        $this->assertEquals($result, $array);
    }

//    public function testAll()
//    {
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
//
//
//    }
}