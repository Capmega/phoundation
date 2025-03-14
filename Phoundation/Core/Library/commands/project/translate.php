<?php

/**
 * Command project translate
 *
 * This is the translation control script for the project.
 *
 * This command can manage your translated copies of your project, remove them, re-translate them, and much more.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Translator\Translations;


CliDocumentation::setUsage('./pho project translate [OPTIONS]
./pho project translate --all
./pho project translate --language LANGUAGES
./pho project translate --clear');

CliDocumentation::setHelp('This is the translation control script for the project.

This command can manage your translated copies of your project, remove them, re-translate them, and much more.


ARGUMENTS


[--all]                                 Translate to all available languages

[-l,--language LANGUAGES]               Translate to only the specified languages

[-c,--clear]                            Clears all currently existing translated copies');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('--clear')->isOptional(false)->isBoolean()
                     ->select('-l,--languages', true)->isOptional()->sanitizeForceArray(',')->eachField()->hasCharacters(2)
                     ->validate();


// Clear all translations
if ($argv['clear']) {
    if (ALL or $argv['languages']) {
        throw new OutOfBoundsException(tr('Cannot use --clear in conjunction with --all or -l / --languages'));
    }

    Log::action(ts('Clearing all translated copies of this project'));
    Translations::new()->clean();

} else {
    // Update ALL translations, get a list of all configured languages
    if (ALL) {
        $argv['languages'] = Translations::new()->getLanguages();
    }


    if ($argv['languages']) {
        Translations::new()->setLanguages($argv['languages'])->execute();
    }
}
