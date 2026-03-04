<?php

/**
 * Class PdfPasswordProtectedException
 *
 * This exception is thrown when a PDF is opened that requires a password, but no password was provided
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Content\Documents\Exception;

use Phoundation\Filesystem\Exception\Interfaces\FilePasswordProtectedExceptionInterface;

class PdfPasswordProtectedException extends PdfException implements FilePasswordProtectedExceptionInterface
{
}
