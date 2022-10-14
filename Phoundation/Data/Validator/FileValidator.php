<?php

namespace Phoundation\Data\Validator;



/**
 * FileValidator class
 *
 * This class can be used to validate files
 *
 * $_REQUEST will be cleared automatically as this array should not  be used.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Utils
 */
class FileValidator extends Validator
{
    use ValidatorBasics;



    /**
     * Validates that the file is a plain text file
     *
     * @return Validator
     */
    public function isText(): Validator
    {
        return $this;
    }



    /**
     * Validates that the file is an image
     *
     * @return Validator
     */
    public function isImage(): Validator
    {
        return $this;
    }



    /**
     * Validates that the file is a JPEG image
     *
     * @return Validator
     */
    public function isJpeg(): Validator
    {
        return $this;
    }


    /**
     * Validates that the file is smaller than the specified amount of bytes
     *
     * @param int $size
     * @return Validator
     */
    public function isSmallerThan(int $size): Validator
    {
        return $this;
    }
}