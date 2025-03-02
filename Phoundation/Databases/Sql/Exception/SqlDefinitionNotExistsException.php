<?php

/**
 * Class SqlDefinitionNotExistsException
 *
 * This exception is thrown by TableAlter::getDefinition() if the requested table definition was not found
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Sql\Exception;

class SqlDefinitionNotExistsException extends SqlException
{
}
