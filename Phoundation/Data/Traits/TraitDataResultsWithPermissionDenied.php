<?php

/**
 * Trait TraitDataResultsWithPermissionDenied
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataResultsWithPermissionDenied
{
    /**
     * Tracks the "permission denied" items in the result set
     *
     * @var array $results_with_permission_denied
     */
    protected array $results_with_permission_denied;


    /**
     * Returns the "permission denied" items in the result set
     *
     * @return int
     */
    public function getNumberOfResultsWithPermissionDenied(): int
    {
        return count($this->getResultsWithPermissionDenied());
    }


    /**
     * Returns the "permission denied" items in the result set
     *
     * @return array
     */
    public function getResultsWithPermissionDenied(): array
    {
        if (empty($this->results_with_permission_denied)) {
            return [];
        }

        return $this->results_with_permission_denied;
    }


    /**
     * Sets the "permission denied" items in the result set
     *
     * @param array $results_with_permission_denied
     *
     * @return static
     */
    protected function setResultsWithPermissionDenied(array $results_with_permission_denied): static
    {
        $this->results_with_permission_denied = $results_with_permission_denied;
        return $this;
    }
}
