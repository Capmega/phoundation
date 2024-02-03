<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator;


use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Exception\OutOfBoundsException;


/**
 * FileValidator class
 *
 * This class can be used to validate files
 *
 * $_REQUEST will be cleared automatically as this array should not  be used.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
class FileValidator
{
    use ValidatorBasics;


    /**
     * FileValidator constructor
     *
     * @param string $source
     * @param ValidatorInterface|null $parent
     */
    public function __construct(string $source, ?ValidatorInterface $parent = null)
    {
        if (!$source) {
            throw new OutOfBoundsException(tr('No source file specified'));
        }

        $this->source = &$source;
        $this->parent = $parent;
    }


    /**
     * Validates that the file is a file
     *
     * @return FileValidator
     */
    public function isFile(): FileValidator
    {
        if ($this->process_value_failed) {
            // Validation already failed, don't test anything more
            return $this;
        }

        if (!is_file($this->source)) {
            $this->addFailure(tr('must be a file'));
        }

        return $this;
    }


    /**
     * Validates that the file is a plain text file
     *
     * @return FileValidator
     */
    public function isText(): FileValidator
    {
        return $this;
    }


    /**
     * Validates that the file is an image
     *
     * @return FileValidator
     */
    public function isImage(): FileValidator
    {
        return $this;
    }


    /**
     * Validates that the file is a JPEG image
     *
     * @return FileValidator
     */
    public function isJpeg(): FileValidator
    {
        return $this;
    }


    /**
     * Validates that the file is smaller than the specified number of bytes
     *
     * @param int $size
     * @return FileValidator
     */
    public function isSmallerThan(int $size): FileValidator
    {
        return $this;
    }
}