<?php

/**
 * Command image info
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Content\Images\ImageFile;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho image info IMAGE_FILE_NAME');

CliDocumentation::setHelp('This command will display basic information about the specified image');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('file', true)->sanitizeFile(FsDirectory::getFilesystemRoot())
                     ->validate();


// Display image information
Log::information(tr('Displaying image information for ":file"', [':file' => $argv['file']]));
Cli::displayTable(ImageFile::new($argv['file'])->getInformation());
