<?php

declare(strict_types=1);

use Phoundation\Audio\Audio;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;


/**
 * Script image/convert
 *
 * This script can apply various conversions to the specified image
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho audio play FILENAME
./pho system audio play PATH/FILE');

CliDocumentation::setHelp('This command can apply various conversions to the specified image



ARGUMENTS



FILENAME                                The file to play, the system will search in the ROOT/data/audio path

PATH/FILE                               The file to play directly

[-b / --background]                     If specified, will play the audio in a background process and immediately return
                                        control to the shell');


$argv = ArgvValidator::new()
                     ->select('file')->isFile()
                     ->select('-b,--background')->isOptional()->isBoolean()
                     ->validate();


Log::information(tr('Playing audio file ":file"', [':file' => $argv['file']]));
Audio::new($argv['file'])->playLocal($argv['background']);
