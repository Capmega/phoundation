<?php

declare(strict_types=1);

namespace Phoundation\Data\Exception;

/**
 * Class IteratorKeyNotExistsException
 *
 * This is exception is thrown when a key is referenced in the Iterator class that does not exist
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
class IteratorKeyNotExistsException extends IteratorException
{
}
