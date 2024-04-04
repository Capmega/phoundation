<?php

declare(strict_types=1);

namespace Phoundation\Content\Images\Interfaces;

use Phoundation\Content\Images\Convert;
use Phoundation\Content\Interfaces\ContentInterface;
use Phoundation\Web\Html\Components\Img;


/**
 * Class Image
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Content
 */
interface ImageInterface extends ContentInterface
{
    /**
     * Returns a Convert class to convert the specified image
     *
     * @return Convert
     */
    public function convert(): Convert;

    /**
     * Sets the image description
     *
     * @param string|null $description
     *
     * @return ImageInterface
     */
    public function setDescription(?string $description): ImageInterface;

    /**
     * Returns the image description
     *
     * @return string
     */
    public function getDescription(): string;

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
    public function getHtmlElement(): Img;
}
