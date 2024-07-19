<?php

/**
 * \Phoundation\Data\Validator\FileValidator test class
 */

declare(strict_types=1);

namespace Phoundation\Data\Library\tests\Validator;

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Validator;
use PHPUnit\Framework\TestCase;

class FileValidatorTest extends TestCase
{
    public function testIsFile()
    {
        // Test normal operation

        // Test failures

        // Specified file should be a file, not a directory
        $this->expectException(ValidationFailedException::class);
        Validator::file(DIRECTORY_ROOT)
                 ->isFile()
                 ->validate();
    }
}