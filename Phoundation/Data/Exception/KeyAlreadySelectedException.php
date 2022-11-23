<?php

namespace Phoundation\Data\Exception;

use Phoundation\Data\Validator\Exception\ValidatorException;



/**
 * Class KeyAlreadySelectedException
 *
 * This exception is thrown when selecting a key that has already been selected before
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class KeyAlreadySelectedException extends ValidatorException
{
}
