<?php

/**
 * Trait TraitDataEntryPicture
 *
 * This trait contains methods for DataEntry objects that require a picture
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Content\Images\Image;
use Phoundation\Content\Images\Interfaces\ImageInterface;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Utils\Strings;


trait TraitDataEntryProfilePicture
{
    /**
     * Returns the profile picture for this entry
     *
     * @return ImageInterface
     */
    public function getProfilePicture(): ImageInterface
    {
        return get_null($this->getTypesafe('string', 'picture')) ?? new Image('img/profiles/default.png', PhoRestrictions::newReadonlyObject('img/profiles'));
    }


    /**
     * Sets the profile picture for this entry
     *
     * @param ImageInterface|string|null $picture
     *
     * @return static
     */
    public function setProfilePicture(ImageInterface|string|null $picture): static
    {
        // Make sure we have an Image object or NULL
        $picture = get_null($picture) ?? Image::new($picture, PhoRestrictions::newReadonlyObject('img/profiles'));
        $picture->setDescription(tr('Profile picture for :name', [
            ':name' => $this->getName()
        ]));

        return $this->set($picture, 'picture');
    }
}
