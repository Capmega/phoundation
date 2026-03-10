<?php

/**
 * Trait TraitDataEntryProfilePictureFile
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

use Phoundation\Content\Images\ImageFile;
use Phoundation\Content\Images\Interfaces\ImageFileInterface;
use Phoundation\Filesystem\PhoRestrictions;


trait TraitDataEntryProfilePictureFile
{
    /**
     * Returns the picture for this entry
     *
     * @return ImageFileInterface
     */
    public function getProfilePictureFileObject(): ImageFileInterface
    {
        return get_null($this->getTypesafe('string', 'picture')) ?? new ImageFile('img/profiles/default.png', PhoRestrictions::newReadonly('img/profiles'));
    }


    /**
     * Sets the picture for this entry
     *
     * @param ImageFileInterface|string|null $picture
     *
     * @return static
     */
    public function setProfilePictureFileObject(ImageFileInterface|string|null $picture): static
    {
        // Make sure we have an Image object or NULL
        $picture = get_null($picture) ?? ImageFile::new($picture, PhoRestrictions::newReadonly('img/profiles'));
        $picture->setDescription(tr('Profile picture for :customer', [
            ':customer' => $this->getName()
        ]));

        return $this->set($picture, 'picture');
    }
}
