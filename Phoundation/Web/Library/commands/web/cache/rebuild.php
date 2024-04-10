<?php

declare(strict_types=1);

use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Web\Web;


/**
 * Script web pages web/cache/rebuild
 *
 * This command rebuilds the web cache
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


// This command accepts no arguments
ArgvValidator::new()->validate();


// Rebuild web cache
Web::rebuildCache();