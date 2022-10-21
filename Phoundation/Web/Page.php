<?php

namespace Phoundation\Web;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Filesystem\File;
use Phoundation\Web\Exception\RouteException;



/**
 * Class Page
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Page
{
    /**
     * Process the routed target
     *
     * We have a target for the requested route. If the resource is a PHP page, then
     * execute it. Anything else, send it directly to the client
     *
     * @param string $target             The target file that should be executed or sent to the client
     * @param boolean $attachment        If specified as true, will send the file as a downloadable attachement, to be
     *                                   written to disk instead of displayed on the browser. If set to false, the file
     *                                   will be sent as a file to be displayed in the browser itself.
     * @param array|string $restrictions If specified, apply the specified file system restrictions, which may block the
     *                                   request if the requested file is outside these restrictions
     * @return void
     * @throws \Throwable
     * @package Web
     * @see route()
     * @note: This function will kill the process once it has finished executing / sending the target file to the client
     * @version 2.5.88: Added function and documentation
     */
    #[NoReturn] public static function execute(string $target, bool $attachment, array|string $restrictions): void
    {
        if (str_ends_with($target, 'php')) {
            if ($attachment) {
                throw new RouteException(tr('Found "A" flag for executable target ":target", but this flag can only be used for non PHP files', [
                    ':target' => $target
                ]));
            }

            Log::action(tr('Executing page ":target"', [':target' => $target]));

            include($target);

        } else {
            if ($attachment) {
                // Upload the file to the client as an attachment
                $target = File::absolutePath(Strings::unslash($target), ROOT.'www/');

                Log::action(tr('Sending file ":target" as attachment', [':target' => $target]));
                File::httpDownload([
                    'restrictions' => $restrictions,
                    'attachment'   => $attachment,
                    'file'         => $target,
                    'filename'     => basename($target)
                ]);

            } else {
                $mimetype = mime_content_type($target);
                $bytes    = filesize($target);

                Log::action(tr('Sending contents of file ":target" with mime-type ":type" directly to client', [
                    ':target' => $target,
                    ':type' => $mimetype
                ]));

                header('Content-Type: ' . $mimetype);
                header('Content-length: ' . $bytes);

                include($target);
            }
        }

        die();
    }



}