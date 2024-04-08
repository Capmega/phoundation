<?php

declare(strict_types=1);

namespace Phoundation\Content\Images;

use Phoundation\Content\Images\Interfaces\ImageInterface;
use Phoundation\Filesystem\Traits\TraitDataRestrictions;

/**
 * Phoundation UsesImage trait
 *
 * This trait contains basic image architecture
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Content
 */
trait UsesImage
{
    use TraitDataRestrictions;

    /**
     * The image
     *
     * @var ImageInterface $image
     */
    protected Image $image;


    /**
     * Returns the image for this File object
     *
     * @return ImageInterface
     */
    public function getImage(): ImageInterface
    {
        return $this->image;
    }


    /**
     * Sets the image for this File object
     *
     * @param ImageInterface|string|null $image
     *
     * @return static
     */
    public function setImage(ImageInterface|string|null $image = null): static
    {
        if (!is_object($image)) {
            $image = new Image($image, $this->restrictions);
        }
        $this->image = $image;

        return $this;
    }
}
