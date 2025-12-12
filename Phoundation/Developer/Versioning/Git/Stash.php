<?php

/**
 * Class Stash
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Developer\Versioning\Git\Interfaces\StashInterface;
use Phoundation\Developer\Versioning\Git\Traits\TraitGitProcess;
use Phoundation\Developer\Versioning\Versioning;


class Stash extends Versioning implements StashInterface
{
}
