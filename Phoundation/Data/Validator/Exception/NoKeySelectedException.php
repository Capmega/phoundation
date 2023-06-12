<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator\Exception;


/**
 * Class NoKeySelectedException
 *
 * This exception is thrown when a validation is done without having a key selected first
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class NoKeySelectedException extends ValidatorException
{
}
