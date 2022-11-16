<?php

namespace Phoundation\Content;

use Phoundation\Content\Exception\ContentException;
use Phoundation\Core\Strings;
use Phoundation\Filesystem\File;
use Phoundation\Processes\Commands\Command;
use Phoundation\Processes\Process;



/**
 * Class View
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Content
 */
class Content extends File
{
    /**
     * View the object file
     *
     * @return array
     */
    public function view(): array
    {
        $file     = File::new($this->file)->checkReadable('image');
        $mimetype = $file->mimetype();
        $primary  = Strings::until($mimetype, '/');

        return match ($primary) {
            'image' => self::viewImage(),
            'video' => self::viewVideo(),
            'pdf'   => self::viewPdf(),
            default => throw new ContentException(tr('Unknown mimetype ":viewer" for file ":file"', [
                ':file' => $file->getFile(),
                ':mimetype' => $mimetype
            ])),
        };

        throw new ContentException(tr('No file specified'), 'invalid');
    }



    /**
     * Display the image file
     *
     * @return array
     */
    protected function viewImage(): array
    {
        return Process::new('feh', $this->server, 'feh')
            ->addArgument($file->getFile())
            ->executeReturnArray();
    }



    /**
     * Display the PDF file
     *
     * @return array
     */
    protected function viewPdf(): array
    {

    }



    /**
     * Display the video file
     *
     * @return array
     */
    protected function viewVideo(): array
    {

    }
}