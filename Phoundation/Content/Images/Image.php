<?php

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

declare(strict_types=1);

namespace Phoundation\Content\Images;

use Phoundation\Content\Content;
use Phoundation\Content\Images\Interfaces\ImageInterface;
use Phoundation\Core\Exception\ImagesException;
use Phoundation\Filesystem\FsFile;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Img;

class Image extends Content implements ImageInterface
{
    /**
     * The name of the image file
     *
     * @var string|null
     */
    protected ?string $source = null;

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
            'file'   => $this->source,
            'exists' => file_exists($this->source),
        ];

        if ($return['exists']) {
            $return['size'] = filesize($this->source);
        }

        if ($return['size']) {
            $return['mimetype'] = FsFile::new($this->source, $this->restrictions)->getMimetype();
        }

        if (Strings::until($return['mimetype'], '/') === 'image') {
            $return['is_image']   = true;
            $dimensions           = getimagesize($this->source);

            $return['bits']       = $dimensions['bits'];
            $return['exif']       = $this->getExifInformation();
            $return['dimensions'] = [
                'width'  => $dimensions[0],
                'height' => $dimensions[1],
            ];

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
        $exif = exif_read_data($this->source);

        if (!$exif) {
            throw new ImagesException(tr('Failed to read EXIF information from image file ":file"', [
                ':file' => $this->source,
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
                  ->setSrc($this->source)
                  ->setAlt($this->description);
    }
}
