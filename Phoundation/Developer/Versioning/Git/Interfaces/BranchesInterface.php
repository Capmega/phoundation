<?php

namespace Phoundation\Developer\Versioning\Git\Interfaces;

/**
 * Class Branches
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */
interface BranchesInterface
{
    /**
     * Returns the directory for this ChangedFiles object
     *
     * @param string $directory
     *
     * @return static
     */
    public function setDirectory(string $directory): static;


    /**
     * Display the branches on the CLI
     *
     * @return void
     */
    public function CliDisplayTable(): void;
}
