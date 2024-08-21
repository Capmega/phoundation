<?php

/**
 * Trait TraitDataEntryPicture
 *
 * This trait contains methods for DataEntry objects that require a picture
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Content\Images\Image;
use Phoundation\Content\Images\Interfaces\ImageInterface;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Utils\Strings;


trait TraitDataEntryPicture
{
    /**
     * Returns the picture for this entry
     *
     * @return ImageInterface
     */
    public function getPicture(): ImageInterface
    {
        return get_null($this->getTypesafe('string', 'picture')) ?? new Image('img/profiles/default.png', FsRestrictions::getReadonly('img/profiles'));
    }


    /**
     * Sets the picture for this entry
     *
     * @param ImageInterface|string|null $picture
     *
     * @return static
     */
    public function setPicture(ImageInterface|string|null $picture): static
    {
        // Make sure we have an Image object or NULL
        $picture = get_null($picture) ?? Image::new($picture, FsRestrictions::getReadonly('img/profiles'));
        $picture->setDescription(tr('Profile picture for :customer', [
            ':customer' => $this->getName()
        ]));

        return $this->set($picture, 'picture');
    }
}
