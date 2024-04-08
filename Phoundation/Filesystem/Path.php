<?php

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Filesystem\Traits\TraitPathConstructor;
use Phoundation\Filesystem\Traits\TraitPathNew;

/**
 * Class Path
 *
 * This library contains the basic functionalities to manage filesystem paths
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Filesystem
 */
class Path extends PathCore
{
    use TraitPathConstructor;
    use TraitPathNew;
}
