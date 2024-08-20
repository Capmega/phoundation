<?php

/**
 * FileValidator class
 *
 * This class can be used to validate files
 *
 * $_REQUEST will be cleared automatically as this array should not  be used.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Validator;

use Phoundation\Data\Validator\Interfaces\FileValidatorInterface;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Exception\OutOfBoundsException;


class ImageValidator extends FileValidator
{
    /**
     * Validates that the image has a resolution smaller than
     *
     * @param int|null $x
     * @param int|null $y
     *
     * @return ImageValidator
     */
    public function resolutionIsSmallerThan(?int $x, ?int $y = null): ImageValidator
    {
        return $this;
    }


    /**
     * Validates that the image has a resolution larger than
     *
     * @param int|null $x
     * @param int|null $y
     *
     * @return ImageValidator
     */
    public function resolutionIsLargerThan(?int $x, ?int $y = null): ImageValidator
    {
        return $this;
    }
}
