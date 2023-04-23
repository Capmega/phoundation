<?php

namespace Phoundation\Content\Images;

use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Path;
use Phoundation\Processes\Commands\Command;

/**
 * Class Convert
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Content
 */
class Convert extends Command
{
    /**
     * The image source file that will be converted
     *
     * @var Image $source
     */
    protected Image $source;

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
     * @var string|null $method
     */
    protected ?string $method = null;


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
     * @param Image $source
     * @return Convert
     */
    public function setSource(Image $source): Convert
    {
        // TODO Validate the format
        $this->source = $source;
        return $this;
    }


    /**
     * Returns the source image on which the conversions will be applied
     *
     * @return Image
     */
    public function getSource(): Image
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
        return $this->source->getFile();
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
            $this->path(dirname($this->target))->clear();
        }

        // Ensure that a path for the target file exists
        $this->path(dirname($this->target))->ensure();
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
     * @param string|null $method
     * @return Convert
     */
    public function setMethod(?string $method): Convert
    {
        $this->method = $method;
        return $this;
    }


    /**
     * Returns the method with which the image should be converted
     *
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
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
     * Returns a new Path object with the restrictions for this image object
     *
     * @param string $path
     * @return Path
     */
    protected function path(string $path): Path
    {
        return new Path($path, $this->restrictions);
    }
}