<?php

/**
 * Class RequestHasWrongEncodingException
 *
 * This is the standard exception thrown when the client specified an encoding that differs from the required encoding
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Requests\Restrictions\Exception;

use Phoundation\Data\Validator\Exception\ValidationFailedException;


class RequestHasWrongEncodingException extends ValidationFailedException
{
}
