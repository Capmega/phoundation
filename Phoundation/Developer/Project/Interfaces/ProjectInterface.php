<?php

declare(strict_types=1);

namespace Phoundation\Developer\Project\Interfaces;


/**
 * Project class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\Developer
 */
interface ProjectInterface
{
    /**
     * Returns true if the specified filesystem location contains a valid Phoundation project installation
     *
     * @param string $directory
     * @return bool
     */
    public function isPhoundationProject(string $directory): bool;

    /**
     * Updates your Phoundation installation
     *
     * @param string|null $branch
     * @param string|null $message
     * @param bool $signed
     * @param string|null $phoundation_path
     * @return static
     */
    public function updateLocalProject(?string $branch, ?string $message = null, bool $signed = false, ?string $phoundation_path = null): static;
}
