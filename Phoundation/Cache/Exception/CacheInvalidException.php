<?php

/**
 * Class CacheInvalidException
 *
 * This exception will be thrown when cache returned an invalid value for the specified key
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cache
 */


declare(strict_types=1);

namespace Phoundation\Cache\Exception;

use Phoundation\Exception\PhoException;

class CacheInvalidException extends PhoException
{
}
