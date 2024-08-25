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
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Utils\Numbers;


class FileValidator implements FileValidatorInterface
{
    /**
     * The file being validated
     *
     * @var FsFileInterface
     */
    protected FsFileInterface $source;


    /**
     * FileValidator constructor
     *
     * @param FsFileInterface $source
\     */
    public function __construct(FsFileInterface $source)
    {
        $this->source = &$source;
    }


    /**
     * Returns a new FileValidator object
     *
     * @param FsFileInterface $source
     *
     * @return static
     */
    public static function new(FsFileInterface $source): static
    {
        return static($source);
    }


    /**
     * Validates that the file has the specified mime type
     *
     * @param string      $primary
     * @param string|null $secondary
     *
     * @return static
     */
    public function isMimeType(string $primary, ?string $secondary = null): static
    {
        if ($secondary) {
            $primary .= '/' . $secondary;
        }

        if (!$this->source->hasMimetype($primary)) {
            $this->addFailure(tr());
        }

        return $this;
    }


    /**
     * Validates that the file is a compressed file
     *
     * @return static
     */
    public function isCompressed(): static
    {
        return $this;
    }


    /**
     * Validates that the file is a ZIP compressed file
     *
     * @return static
     */
    public function isZip(): static
    {
        return $this;
    }


    /**
     * Validates that the file is a plain text file
     *
     * @return static
     */
    public function isText(): static
    {
        return $this;
    }


    /**
     * Validates that the file is an image
     *
     * @param string|null $allowed_mimetypes
     *
     * @return static
     */
    public function isImage(?string $allowed_mimetypes = null): static
    {
        return $this;
    }


    /**
     * Validates that the file is a (text) document
     *
     * @return static
     */
    public function isDocument(): static
    {
        return $this;
    }


    /**
     * Validates that the file is a JPEG image
     *
     * @return static
     */
    public function isJpeg(): static
    {
        return $this;
    }


    /**
     * Validates that the file is a PNG image
     *
     * @return static
     */
    public function isPng(): static
    {
        return $this;
    }


    /**
     * Validates that the file is a PDF file
     *
     * @return static
     */
    public function isPdf(): static
    {
        return $this;
    }


    /**
     * Validates that the file is smaller than the specified number of bytes
     *
     * @param string|int $size
     *
     * @return static
     */
    public function isSmallerThan(string|int $size): static
    {
        return $this;
    }


    /**
     * Validates that the file is larger than the specified number of bytes
     *
     * @param string|int $size
     *
     * @return static
     */
    public function isLargerThan(string|int $size): static
    {
        $size = Numbers::fromBytes($size);

        return $this;
    }
}
