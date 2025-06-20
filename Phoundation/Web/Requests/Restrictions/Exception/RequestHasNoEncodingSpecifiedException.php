<?php

/**
 * Class RequestHasNoEncodingSpecifiedException
 *
 * This is the standard exception thrown when the client specified no encoding with its request whilst the encoding
 * configuration is set to strict
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Requests\Restrictions\Exception;

use Phoundation\Data\Validator\Exception\ValidationFailedException;


class RequestHasNoEncodingSpecifiedException extends ValidationFailedException
{
}
