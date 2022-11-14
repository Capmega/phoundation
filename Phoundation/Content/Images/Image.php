<?php

namespace Phoundation\Content\Images;

use Phoundation\Core\Strings;
use Phoundation\Filesystem\File;
use Phoundation\Processes\Commands\Command;



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
class Image extends Command
{
    /**
     * The name of the image file
     *
     * @var string|null
     */
    protected ?string $file = null;



    /**
     * Returns the file for this image object
     *
     * @return string|null
     */
    public function getFile(): ?string
    {
        return $this->file;
    }



    /**
     * Sets the file for this image object
     *
     * @param string|null $file
     * @return static
     */
    public function setFile(?string $file): static
    {
        File::new($file, $this->restrictions)->checkReadable();

        $this->file = $file;
        return $this;
    }



    /**
     * Returns a Convert class to convert the specified image
     *
     * @return Convert
     */
    public function convert(): Convert
    {
        return new Convert($this);
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
            $dimensions = getimagesize($this->file);

            $return['bits']       = $dimensions['bits'];
            $return['dimensions'] = [
                'width'  => $dimensions[0],
                'height' => $dimensions[1]
            ];
        }

        return $return;
    }
}