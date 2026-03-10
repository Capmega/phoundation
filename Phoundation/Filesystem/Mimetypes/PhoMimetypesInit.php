<?php

/**
 * Class FsMimetypesInit
 *
 * This class initializes the mimetypes table
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Mimetypes;

use Phoundation\Core\Log\Log;
use Phoundation\Exception\PhoException;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;


class PhoMimetypesInit
{
    /**
     * Clears the filesystem_mimetypes table
     *
     * @return void
     */
    public static function clear(): void
    {
        // Do NOT use TRUNCATE as that might cause issues with foreign keys
        Log::action(ts('Clearing mimetypes'));
        sql()->query('DELETE FROM `filesystem_mimetypes`');
    }


    /**
     * Initializes the filesystem_mimetypes table
     *
     * @return void
     */
    public static function init(): void
    {
        // Populate with types
        // See https://docs.w3cub.com/http/basics_of_http/mime_types/complete_list_of_mime_types.html
        // See https://stackoverflow.com/questions/4212861/what-is-a-correct-mime-type-for-docx-pptx-etc#4212908
        // See https://www.sitepoint.com/mime-types-complete-list/
        // See https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types

        $types = PhoFile::new(
            DIRECTORY_ROOT . 'Phoundation/Filesystem/Library/data/sources/filesystem/mimetypes.txt',
            PhoRestrictions::newRoot(false, 'Phoundation/Filesystem/Library/data/sources/filesystem')
        )->getContentsAsString();

        $types = Arrays::force($types, PHP_EOL);
        $count = 0;

        PhoMimetypesInit::clear();

        Log::action(ts('Registering ":count" mimetypes', [
            ':count' => count($types)
        ]), echo_newline: false);

        foreach ($types as $type) {
            $type = trim($type);

            if (empty($type)) {
                continue;
            }

            try {
                $type      = Arrays::force($type, "\t");
                $extension = Strings::ensureBeginsNotWith(trim($type[0]), '.');
                $mimetype  = trim($type[1]);
                $priority  = (int) trim((string) isset_get($type[2], 0));

                if (PhoMimetype::notExists(['extension' => $extension, 'mimetype' => $mimetype])) {
                    PhoMimetype::new()
                               ->setExtension($extension)
                               ->setName($mimetype)
                               ->setMimetype($mimetype)
                               ->setPriority($priority)
                               ->save();

                    $count++;
                    Log::dot();
                }

            } catch (PhoException $e) {
                // Register the failed mimetype and continue
                Incident::new()
                        ->setException($e)
                        ->save();

                Log::dot(1, 'yellow');
            }
        }

        Log::success(ts('Done!'));
        Log::success(ts('Registered ":count" mimetypes and extensions', [
            ':count' => $count,
        ]));
    }
}
