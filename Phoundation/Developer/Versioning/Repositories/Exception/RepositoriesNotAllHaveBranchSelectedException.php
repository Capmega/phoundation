<?php

/**
 * Class RepositoriesNotAllHaveBranchSelectedException
 *
 * Thrown when a repository action requires all repositories to have a branch selected, but not all do
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

class RepositoriesNotAllHaveBranchSelectedException extends VersioningException
{
    /**
     * RepositoriesNotAllHaveBranchSelectedException class constructor
     *
     * @param Throwable|array|string|null $messages
     * @param Throwable|null              $previous
     */
    public function __construct(Throwable|array|string|null $messages, ?Throwable $previous = null) {
        parent::__construct($messages, $previous);

        $this->setWarning(true);
    }
}
