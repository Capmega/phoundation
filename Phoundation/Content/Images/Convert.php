<?php

/**
 * Class Convert
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Content
 */


declare(strict_types=1);

namespace Phoundation\Content\Images;

use Phoundation\Content\Images\Interfaces\ConvertInterface;
use Phoundation\Content\Images\Interfaces\ImageFileInterface;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Os\Processes\Commands\Command;


class Convert extends Command implements ConvertInterface
{
    /**
     * The image source file that will be converted
     *
     * @var ImageFileInterface $source
     */
    protected ImageFileInterface $source;

    /**
     * The target file that will contain the converted image
     *
     * @var string|null $target
     */
    protected ?string $target = null;

    /**
     * Sets the format to which the image should be converted
     *
     * @var string|null $format
     */
    protected ?string $format = null;

    /**
     * Sets the method with which the image should be converted
     *
     * @var string|null $convert_method
     */
    protected ?string $convert_method = null;


    /**
     * The format to which the image should be converted
     *
     * @param string $format
     * @return Convert
     */
    public function setFormat(string $format): Convert
    {
        // TODO Validate the format
        $this->format = $format;
        return $this;
    }


    /**
     * Returns the format to which the image should be converted
     *
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }


    /**
     * Sets the source image on which the conversions will be applied
     *
     * @param ImageFileInterface $source
     *
     * @return Convert
     */
    public function setSource(ImageFileInterface $source): Convert
    {
        // TODO Validate the format
        $this->source = $source;
        return $this;
    }


    /**
     * Returns the source image on which the conversions will be applied
     *
     * @return ImageFileInterface
     */
    public function getSource(): ImageFileInterface
    {
        return $this->source;
    }


    /**
     * Returns the source file
     *
     * @return string|null
     */
    public function getSourceFile(): ?string
    {
        return $this->source->getSource();
    }


    /**
     * The format to which the image should be converted
     *
     * @param string $target
     * @return Convert
     */
    public function setTargetFile(string $target): Convert
    {
        if ($this->target) {
            // Target already exists. See if we need to clean the directory for this target
            $this->getDirectory(dirname($this->target))->clear();
        }

        // Ensure that a path for the target file exists
        $this->getDirectory(dirname($this->target))->ensure();
        $this->target = $target;
        return $this;
    }


    /**
     * Returns the file to which the image should be converted
     *
     * @note If no target was set, this will return the source file
     * @return string|null
     */
    public function getTargetFile(): ?string
    {
        if (!$this->target) {
            // Apply the operations the source file
            return $this->getSourceFile();
        }

        return $this->target;
    }


    /**
     * The format to which the image should be converted
     *
     * @param string|null $convert_method
     * @return Convert
     */
    public function setMethod(?string $convert_method): Convert
    {
        $this->convert_method = $convert_method;
        return $this;
    }


    /**
     * Returns the method with which the image should be converted
     *
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->convert_method;
    }


    /**
     * Access to the image resize object
     *
     * @param bool $background
     * @return Resize
     */
    public function resize(bool $background = false): Resize
    {
        $resize = new Resize($this->source, $this->restrictions);
        $resize->setBackground($background);

        return $resize;
    }


    /**
     * Access to the image resize object
     *
     * @return Resize
     */
    public function crop(): Resize
    {
        throw new UnderConstructionException();
    }


    /**
     * Returns a new Directory object with the restrictions for this image object
     *
     * @param string $directory
     * @return FsDirectoryInterface
     */
    protected function getDirectory(string $directory): FsDirectoryInterface
    {
        return new FsDirectory($directory, $this->restrictions);
    }
}
