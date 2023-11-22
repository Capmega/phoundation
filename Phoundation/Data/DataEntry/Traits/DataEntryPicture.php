<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Content\Images\Image;
use Phoundation\Content\Images\Interfaces\ImageInterface;
use Phoundation\Utils\Strings;


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
        $picture = get_null($this->getSourceFieldValue('string', 'picture')) ?? 'img/profiles/default.png';

        return Image::new($picture)
            ->setDescription(tr('Profile picture for :customer', [':customer' => $this->getName()]));
    }


    /**
     * Sets the picture for this entry
     *
     * @param ImageInterface|string|null $picture
     * @return static
     */
    public function setPicture(ImageInterface|string|null $picture): static
    {
        if ($picture) {
            // Make sure we have an Image object
            $picture = Image::new($picture);
        }

        return $this->setSourceValue('picture', Strings::from(get_null($picture)?->getFile(), DIRECTORY_CDN));
    }
}