<?php

/**
 * Reports page
 *
 * This is the main reports index page showing all available reports pages
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Url;


return Card::new()
         ->setTitle(tr('Reports pages'))
         ->setContent();
