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
        foreach ($this->file as $file) {
            $file     = File::new($file)->checkReadable('image', true);
            $mimetype = $file->mimetype();
            $primary  = Strings::until($mimetype, '/');

            return match ($primary) {
                'image' => self::viewImage($file),
                'video' => self::viewVideo($file),
                'pdf'   => self::viewPdf($file),
                default => throw new ContentException(tr('Unknown mimetype ":viewer" for file ":file"', [
                    ':file' => $file->getFile(),
                    ':mimetype' => $mimetype
                ])),
            };
        }

        throw new ContentException(tr('No file specified'), 'invalid');
    }



    /**
     * Display the image file
     *
     * @param File $file
     * @return array
     */
    protected function viewImage(File $file): array
    {
        return Process::new('feh', $this->server, 'feh')
            ->addArgument($file->getFile())
            ->executeReturnArray();
    }



    /**
     * Display the PDF file
     *
     * @param File $file
     * @return array
     */
    protected function viewPdf(File $file): array
    {

    }



    /**
     * Display the video file
     *
     * @param File $file
     * @return array
     */
    protected function viewVideo(File $file): array
    {

    }
}