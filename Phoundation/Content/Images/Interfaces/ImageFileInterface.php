<?php

declare(strict_types=1);

namespace Phoundation\Content\Images\Interfaces;

use Phoundation\Content\Images\Convert;
use Phoundation\Content\Interfaces\ContentFileInterface;
use Phoundation\Web\Html\Components\Img;

interface ImageFileInterface extends ContentFileInterface
{
    /**
     * Returns a Convert class to convert the specified image
     *
     * @return ConvertInterface
     */
    public function convert(): ConvertInterface;


    /**
     * Sets the image description
     *
     * @param string|null $description
     *
     * @return ImageFileInterface
     */
    public function setDescription(?string $description): ImageFileInterface;


    /**
     * Returns the image description
     *
     * @return string|null
     */
    public function getDescription(): ?string;


    /**
     * Return basic information about this image
     *
     * @return array
     */
    public function getInformation(): array;


    /**
     * Returns an HTML Img element for this image
     *
     * @return Img
     */
    public function getImgObject(): Img;
}
