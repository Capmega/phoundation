<?php

namespace Data\Validator;

use Phoundation\Data\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Validator;
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
        $this->expectException(ValidationFailedException::class);
        Validator::file(PATH_ROOT)
            ->isFile()
            ->validate();
    }
}