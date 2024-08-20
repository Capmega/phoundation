<?php

/**
 * Command image convert
 *
 * This script can apply various conversions to the specified image
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Content\Images\ImageFile;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\FsDirectory;


CliDocumentation::setUsage('./pho image resize IMAGE_FILE_NAME -x 500 -y 500 -m scale');

CliDocumentation::setHelp('This command can apply various conversions to the specified image


ARGUMENTS


-x, --width WIDTH                       Width of the image
-y, --height HEIGHT                     Height of the image
-m, --method METHOD                     Resizing method (scale, sample, resample)
-t, --type                              Resizing type ');


// Validate command line arguments
$argv = ArgvValidator::new()
                     ->select('-m,--method', true)->isOptional('scale')->isName()
                     ->select('-x,--width', true)->isInteger()
                     ->select('-y,--height', true)->isInteger()
                     ->select('file')->sanitizeFile(FsDirectory::getFilesystemRootObject())
                     ->validate();


// Display image information
Log::information(tr('Resizing image ":file" to ":xx:y"', [
    ':x'    => $argv['width'],
    ':y'    => $argv['height'],
    ':file' => $argv['file'],
]));


// Get image object, make a backup of this image and show it
$image  = ImageFile::new($argv['file']);
$backup = $image->backup();
$backup->view();


// Do the resize and show the result too
$image->convert()
      ->resize()
      ->setMethod($argv['method'])
      ->getAbsoluteSize($argv['width'], $argv['height']);

$image->view();
