<?php

declare(strict_types=1);

namespace Phoundation\Content\Images;

use Phoundation\Content\Content;
use Phoundation\Content\Images\Interfaces\ImageInterface;
use Phoundation\Core\Exception\ImagesException;
use Phoundation\Filesystem\File;
use Phoundation\Utils\Strings;
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
class Image extends Content implements ImageInterface
{
    /**
     * The name of the image file
     *
     * @var string|null
     */
    protected ?string $path = null;

    /**
     * Description for this image. Will be used as ALT text when converting this to an image HTML element
     *
     * @var string|null
     */
    protected ?string $description = null;


    /**
     * Returns a Convert class to convert the specified image
     *
     * @return Convert
     */
    public function convert(): Convert
    {
        $convert = new Convert($this->getRestrictions());
        $convert->setSource($this);

        return $convert;
    }


    /**
     * Returns the image description
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }


    /**
     * Sets the image description
     *
     * @param string|null $description
     *
     * @return ImageInterface
     */
    public function setDescription(?string $description): ImageInterface
    {
        $this->description = $description;

        return $this;
    }


    /**
     * Return basic information about this image
     *
     * @return array
     */
    public function getInformation(): array
    {
        $return = [
            'file'   => $this->path,
            'exists' => file_exists($this->path),
        ];
        if ($return['exists']) {
            $return['size'] = filesize($this->path);
        }
        if ($return['size']) {
            $return['mimetype'] = File::new($this->path, $this->restrictions)
                                      ->getMimetype();
        }
        if (Strings::until($return['mimetype'], '/') === 'image') {
            $return['is_image'] = true;
            $dimensions         = getimagesize($this->path);
            $return['bits']       = $dimensions['bits'];
            $return['dimensions'] = [
                'width'  => $dimensions[0],
                'height' => $dimensions[1],
            ];
            $return['exif'] = $this->getExifInformation();

        } else {
            $return['is_image'] = false;
        }

        return $return;
    }


    /**
     * Returns EXIF information for the current image
     *
     * @return array
     */
    protected function getExifInformation(): array
    {
        $exif = exif_read_data($this->path);
        if (!$exif) {
            throw new ImagesException(tr('Failed to read EXIF information from image file ":file"', [
                ':file' => $this->path,
            ]));
        }

        return $exif;
    }


    /**
     * Returns an HTML Img element for this image
     *
     * @return Img
     */
    public function getHtmlElement(): Img
    {
        return Img::new()
                  ->setSrc($this->path)
                  ->setAlt($this->description);
    }
}
