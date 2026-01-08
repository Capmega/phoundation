<?php

/**
 * Class RepositoriesHaveChangesException
 *
 * Thrown when a repository action requires that none of the repositories have changes
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Repositories\Exception;

use Phoundation\Developer\Versioning\Exception\VersioningException;
use Throwable;

class RepositoriesHaveChangesException extends VersioningException
{
    public function __construct(Throwable|array|string|null $messages, ?Throwable $previous = null) {
        parent::__construct($messages, $previous);

        $this->setWarning(true);
    }
}
