<?php

/**
 * Command audio error
 *
 * This command plays the error audio file on the local machine
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Content\Media\Audio\Audio;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\PhoDirectory;

CliDocumentation::setAutoComplete([
    'positions' => [
        0 => [
            'word'   => function($word) { return PhoDirectory::newFilesystemRootObject(false)->scan($word, '/.*?\.mp3$/')->addEmpty(); },
            'noword' => function($word) { return PhoDirectory::newFilesystemRootObject(false)->scan($word, '/.*?\.mp3$/')->addEmpty(); },
        ]
    ]
]);

CliDocumentation::setUsage('./pho audio warning
./pho audio warning -b');

CliDocumentation::setHelp('This command plays the error audio file on the local machine.


ARGUMENTS


-


OPTIONAL ARGUMENTS


[-b / --background]                     If specified, will play the audio in a background process and immediately return
                                        control to the shell');


$argv = ArgvValidator::new()
                     ->select('-b,--background')->isOptional()->isBoolean()
                     ->validate();


Log::information(ts('Playing audio file "error"'), 10);
Audio::new('critical.mp3')->playLocal($argv['background']);
