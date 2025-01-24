<?php

/**
 * Class ServicesException
 *
 * This is the standard exception for all Phoundation Services classes
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Services\Exception;

use Phoundation\Os\Processes\Exception\ProcessesException;


class ServicesException extends ProcessesException
{
}
