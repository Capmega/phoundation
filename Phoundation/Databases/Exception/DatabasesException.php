<?php

/**
 * Class DatabaseException
 *
 * This is the standard exception for all Phoundation Database classes
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Exception;

use Phoundation\Databases\Exception\Interfaces\DatabasesExceptionInterface;
use Phoundation\Exception\Exception;


class DatabasesException extends Exception implements DatabasesExceptionInterface
{
}
