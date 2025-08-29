<?php

/**
 * Class JsonSyntaxErrorException
 *
 * This exception is thrown when trying to decode a JSON string that contains a syntax error
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils\Exception;

class JsonSyntaxErrorException extends JsonException
{
}
