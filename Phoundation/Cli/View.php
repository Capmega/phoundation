<?php

namespace Phoundation\Cli;

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
class View extends Command
{
    /**
     * View the object file
     *
     * @return array
     */
    public function view(): array
    {
        // Validate argument
        if (!$file) {
            // A directory was specified instead of a file.
            throw new ContentException(tr('No file specified'), 'invalid');
        }

        if (!file_exists($file)) {
            // A directory was specified instead of a file.
            throw new ContentException(tr('The specified file ":file" does not exist', [
                ':file' => $file
            ]));
        }

        if (!is_file($file)) {
            if (is_dir($file)) {
                // A directory was specified instead of a file.
                throw new ContentException(tr('The specified file ":file" is not a normal file but a directory', [
                    ':file' => $file
                ]));
            }

            throw new ContentException(tr('The specified file ":file" is not a normal viewable file', [
                ':file' => $file
            ]));
        }

        $mimetype = File::new($file)->mimetype();
        $mimetype = Strings::until($mimetype, '/');

        switch ($mimetype) {
            case 'image':
                return self::viewImage();

            case 'video':
                return self::viewVideo();

            case 'pdf':
                return self::viewPdf();

            default:
                throw new ContentException(tr('view_image(): Unknown default image viewer ":viewer" specified', array(':viewer' => $_CONFIG['view']['images']['default'])), 'unknown');
        }
    }



    /**
     * Display the image file
     *
     * @return array
     */
    protected function viewImage(): array
    {
        return Process::new('feh')
            ->setAutoInstall('feh')
            ->addArgument($this->files)
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