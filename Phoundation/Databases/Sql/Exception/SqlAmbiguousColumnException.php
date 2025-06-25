<?php

/**
 * Class SqlAmbiguousColumnException
 *
 * This exception is thrown when a query is referring to a column without table and the column is available in the
 * multiple tables accessed by the query
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Sql\Exception;

class SqlAmbiguousColumnException extends SqlException
{
}
