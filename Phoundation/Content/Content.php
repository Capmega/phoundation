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
     * @return void
     */
    public function view(): void
    {
        $file     = File::new($this->file)->checkReadable('image');
        $mimetype = $file->mimetype();
        $primary  = Strings::until($mimetype, '/');

        match ($primary) {
            'image'     => static::viewImage(),
            'video'     => static::viewVideo(),
            'pdf'       => static::viewPdf(),
            'directory' => static::viewDirectory(),
            default     => throw new ContentException(tr('Unknown mimetype ":viewer" for file ":file"', [
                ':file'     => $file->getFile(),
                ':mimetype' => $mimetype
            ])),
        };
    }



    /**
     * Display the image file
     *
     * @return void
     */
    protected function viewImage(): void
    {
        Process::new('feh', $this->restrictions, 'feh')
            ->addArgument($this->file)
            ->executeBackground();
    }



    /**
     * Display the PDF file
     *
     * @return void
     */
    protected function viewPdf(): void
    {

    }



    /**
     * Display the video file
     *
     * @return void
     */
    protected function viewVideo(): void
    {

    }



    /**
     * Display the files in this directory
     *
     * @return void
     */
    protected function viewDirectory(): void
    {

    }
}