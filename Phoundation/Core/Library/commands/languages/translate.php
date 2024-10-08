<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Locale\Language\Languages;
use Phoundation\Data\Validator\ArgvValidator;


/**
 * Script languages/translate
 *
 * This script will translate your project into the various configured translations
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho languages translate');

CliDocumentation::setHelp('This command will translate your project into the various configured translations



ARGUMENTS


-');


// Validate arguments
$argv = ArgvValidator::new()
                     ->validate();


Languages::translate();
