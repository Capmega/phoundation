<?php

namespace Phoundation\Data\Validator\Interfaces;

use Phoundation\Data\Validator\FileValidator;

interface FileValidatorInterface
{
    /**
     * Validates that the file is a file
     *
     * @return FileValidator
     */
    public function isFile(): FileValidator;


    /**
     * Validates that the file is a plain text file
     *
     * @return FileValidator
     */
    public function isText(): FileValidator;


    /**
     * Validates that the file is an image
     *
     * @return FileValidator
     */
    public function isImage(): FileValidator;


    /**
     * Validates that the file is a JPEG image
     *
     * @return FileValidator
     */
    public function isJpeg(): FileValidator;


    /**
     * Validates that the file is smaller than the specified number of bytes
     *
     * @param int $size
     *
     * @return FileValidator
     */
    public function isSmallerThan(int $size): FileValidator;
}
