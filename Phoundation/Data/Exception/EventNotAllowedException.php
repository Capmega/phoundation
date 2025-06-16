<?php

/**
 * Class EventNotAllowedException
 *
 * This exception is thrown when an exception key is defined that is not on the events allow list
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Exception;


class EventNotAllowedException extends DataException
{
}
