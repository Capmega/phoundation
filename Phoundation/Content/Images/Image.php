<?php

namespace Phoundation\Content\Images;

use Phoundation\Content\Content;
use Phoundation\Core\Exception\ImagesException;
use Phoundation\Core\Strings;
use Phoundation\Filesystem\File;
use Phoundation\Web\Http\Html\Components\Img;


/**
 * Class Image
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Content
 */
class Image extends Content
{
    /**
     * The name of the image file
     *
     * @var string|null
     */
    protected ?string $file = null;

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
     * Sets the image description
     *
     * @param string|null $description
     * @return static
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }



    /**
     * Returns the image description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }



    /**
     * Return basic information about this image
     *
     * @return array
     */
    public function getInformation(): array
    {
        $return = [
            'file' => $this->file,
            'exists' => file_exists($this->file)
        ];

        if ($return['exists']) {
            $return['size'] = filesize($this->file);
        }

        if ($return['size']) {
            $return['mimetype'] = File::new($this->file, $this->restrictions)->mimetype();
        }

        if (Strings::until($return['mimetype'], '/') === 'image') {
            $return['is_image'] = true;
            $dimensions = getimagesize($this->file);

            $return['bits']       = $dimensions['bits'];
            $return['dimensions'] = [
                'width'  => $dimensions[0],
                'height' => $dimensions[1]
            ];

            $return['exif'] = $this->getExifInformation();

        } else {
            $return['is_image'] = false;
        }

        return $return;
    }



    /**
     * Returns an HTML Img element for this image
     *
     * @return Img
     */
    public function getHtmlElement(): Img
    {
        return Img::new()
            ->setSrc($this->file)
            ->setAlt($this->description);
    }



    /**
     * Returns EXIF information for the current image
     *
     * @return array
     */
    protected function getExifInformation(): array
    {
        $exif = exif_read_data($this->file);

        if (!$exif) {
            throw new ImagesException(tr('Failed to read EXIF information from image file ":file"', [
                ':file' => $this->file
            ]));
        }

        return $exif;
    }
}