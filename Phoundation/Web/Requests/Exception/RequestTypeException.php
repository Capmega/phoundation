<?php

/**
 * Class RequestTypeException
 *
 * This exception is thrown when issues were encountered with the request type. Possible issue are:
 *
 * 1. The request type is incompatible with the current platform (IE, we're on PLATFORM_CLI and somehow the web request
 *    class is used)
 *
 * 2. The request type was initially set and a second target execution tries to execute a file from a different request
 *    type
 *
 * 3. An unknown / unsupported request type has been encountered
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Requests\Exception;

class RequestTypeException extends RequestException
{
}
