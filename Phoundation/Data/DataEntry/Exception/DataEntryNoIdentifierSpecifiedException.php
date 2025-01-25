<?php

/**
 * Class DataEntryNoIdentifierSpecifiedException
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Exception;

use Phoundation\Data\DataEntry\Exception\Interfaces\DataEntryNotExistsExceptionInterface;

class DataEntryNoIdentifierSpecifiedException extends DataEntryException implements DataEntryNotExistsExceptionInterface
{
}
