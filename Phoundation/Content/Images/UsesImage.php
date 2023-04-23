<?php

namespace Phoundation\Content\Images;

use Phoundation\Servers\Traits\UsesRestrictions;

/**
 * Phoundation UsesImage trait
 *
 * This trait contains basic image architecture
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Content
 */
trait UsesImage
{
    use UsesRestrictions;


    /**
     * The image
     *
     * @var Image $image
     */
    protected Image $image;


    /**
     * Returns the image for this File object
     *
     * @return Image
     */
    public function getImage(): Image
    {
        return $this->image;
    }


    /**
     * Sets the image for this File object
     *
     * @param Image|string|null $image
     * @return static
     */
    public function setImage(Image|string|null $image = null): static
    {
        if (!is_object($image)) {
            $image = new Image($image, $this->restrictions);
        }

        $this->image = $image;
        return $this;
    }
}
