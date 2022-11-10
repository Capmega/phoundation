<?php

namespace Phoundation\Content\Images;



use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;

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
class Convert
{
    /**
     * The image source file that will be converted
     *
     * @var string
     */
    protected string $source;

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
     * Filesystem restrictions for this image object
     *
     * @var Restrictions|null $restrictions
     */
    protected ?Restrictions $restrictions = null;



    /**
     * Convert class constructor
     *
     * @param string $source
     */
    public function __construct(string $source, ?Restrictions $restrictions = null)
    {
        if (!$source) {
            throw new OutOfBoundsException(tr('No source file specified'));
        }

        if (!$restrictions) {
            $restrictions = new Restrictions(PATH_DATA . 'cdn/');
        }

        $this->source       = $source;
        $this->restrictions = $restrictions;
    }



    /**
     * The format to which the image should be converted
     *
     * @param string $format
     * @return Convert
     */
    public function setFormat(string $format): Convert
    {
        // TODO Validat the format
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
     * The format to which the image should be converted
     *
     * @param string $target
     * @return Convert
     */
    public function setFile(string $target): Convert
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
     * @return string
     */
    public function getFile(): ?string
    {
        return $this->target;
    }



    /**
     * The format to which the image should be converted
     *
     * @param string $method
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
     * @return string
     */
    public function getMethod(): ?string
    {
        return $this->method;
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