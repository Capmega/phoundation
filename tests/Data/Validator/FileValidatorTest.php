<?php

namespace Data\Validator;

use Phoundation\Data\Validator\Validator;
use Phoundation\Exception\OutOfBoundsException;
use PHPUnit\Framework\TestCase;



/**
 * \Phoundation\Data\Validator\FileValidator test class
 */
class FileValidatorTest extends TestCase
{
    public function testIsFile()
    {
        // Test normal operation

        // Test failures

        // Specified file should be a file, not a directory
        $this->expectException(OutOfBoundsException::class);
        Validator::file(ROOT);
    }
}