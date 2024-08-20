<?php

namespace Phoundation\Content\Images\Interfaces;

use Phoundation\Content\Images\Convert;
use Phoundation\Content\Images\Resize;


interface ConvertInterface
{
    /**
     * The format to which the image should be converted
     *
     * @param string $format
     *
     * @return Convert
     */
    public function setFormat(string $format): Convert;


    /**
     * Returns the format to which the image should be converted
     *
     * @return string
     */
    public function getFormat(): string;


    /**
     * Sets the source image on which the conversions will be applied
     *
     * @param ImageFileInterface $source
     *
     * @return Convert
     */
    public function setSource(ImageFileInterface $source): Convert;


    /**
     * Returns the source image on which the conversions will be applied
     *
     * @return ImageFileInterface
     */
    public function getSource(): ImageFileInterface;


    /**
     * Returns the source file
     *
     * @return string|null
     */
    public function getSourceFile(): ?string;


    /**
     * The format to which the image should be converted
     *
     * @param string $target
     *
     * @return Convert
     */
    public function setTargetFile(string $target): Convert;


    /**
     * Returns the file to which the image should be converted
     *
     * @note If no target was set, this will return the source file
     * @return string|null
     */
    public function getTargetFile(): ?string;


    /**
     * The format to which the image should be converted
     *
     * @param string|null $convert_method
     *
     * @return Convert
     */
    public function setMethod(?string $convert_method): Convert;


    /**
     * Returns the method with which the image should be converted
     *
     * @return string|null
     */
    public function getMethod(): ?string;


    /**
     * Access to the image resize object
     *
     * @param bool $background
     *
     * @return Resize
     */
    public function resize(bool $background = false): Resize;


    /**
     * Access to the image resize object
     *
     * @return Resize
     */
    public function crop(): Resize;
}
