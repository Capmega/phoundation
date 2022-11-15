<?php

namespace Phoundation\Content\Images;

use Phoundation\Core\Exception\ImagesException;
use Phoundation\Core\Strings;
use Phoundation\Filesystem\File;



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
class Image extends File
{
    /**
     * The name of the image file
     *
     * @var string|null
     */
    protected ?string $file = null;



    /**
     * Returns a Convert class to convert the specified image
     *
     * @return Convert
     */
    public function convert(): Convert
    {
        $convert = new Convert($this->getServer());
        $convert->setSource($this);

        return $convert;
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