<?php

/**
 * Phoundation UsesImage trait
 *
 * This trait contains basic image architecture
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Content
 */


declare(strict_types=1);

namespace Phoundation\Content\Images;

use Phoundation\Content\Images\Interfaces\ImageFileInterface;
use Phoundation\Data\Traits\TraitDataRestrictions;

trait UsesImage
{
    use TraitDataRestrictions;

    /**
     * The image
     *
     * @var ImageFileInterface $image
     */
    protected ImageFileInterface $image;


    /**
     * Returns the image for this object
     *
     * @return ImageFileInterface
     */
    public function getImage(): ImageFileInterface
    {
        return $this->image;
    }


    /**
     * Sets the image for this object
     *
     * @param ImageFileInterface|string|null $image
     *
     * @return static
     */
    public function setImage(ImageFileInterface|string|null $image = null): static
    {
        if (!is_object($image)) {
            $image = new ImageFile($image, $this->restrictions);
        }

        $this->image = $image;

        return $this;
    }
}
