<?php

/**
 * Command image/convert
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
use Phoundation\Content\Images\Image;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\FsRestrictions;

CliDocumentation::setUsage('./pho image convert IMAGE');

CliDocumentation::setHelp('This command can apply various conversions to the specified image');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('method')->isName()
                     ->select('file')->isFile(true)
                     ->validate();


// Get image and crop it
$image = Image::new($argv['file'], FsRestrictions::new(DIRECTORY_DATA, true));
$image->convert()->crop();


// Display image information
Log::information(tr('Displaying image information for ":file"', [':file' => $argv['file']]));
