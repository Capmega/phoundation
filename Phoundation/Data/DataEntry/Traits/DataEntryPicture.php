<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Content\Images\Image;
use Phoundation\Content\Images\Interfaces\ImageInterface;
use Phoundation\Core\Strings;


/**
 * Trait DataEntryPicture
 *
 * This trait contains methods for DataEntry objects that require a picture
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryPicture
{
    /**
     * Returns the picture for this entry
     *
     * @return ImageInterface
     */
    public function getPicture(): ImageInterface
    {
        if (!$this->getDataValue('string', 'picture')) {
            $this->setDataValue('picture', 'img/profiles/default.png', true);
        }

        return Image::new($this->getDataValue('string', 'picture'))
            ->setDescription(tr('Profile image for :customer', [':customer' => $this->getName()]));
    }


    /**
     * Sets the picture for this entry
     *
     * @param ImageInterface|string|null $picture
     * @return static
     */
    public function setPicture(ImageInterface|string|null $picture): static
    {
        return $this->setDataValue('picture', Strings::from($picture?->getFile(), PATH_CDN));
    }
}