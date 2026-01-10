<?php

/**
 * Class VersionCannotBeModifiedException
 *
 * This exception is thrown by the Utils\Version class when trying to modify a version that cannot be modified
 *
 * Typically, this would be "post_always", or "post_once"
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Utils\Exception;

class VersionCannotBeModifiedException extends VersionException
{
}
