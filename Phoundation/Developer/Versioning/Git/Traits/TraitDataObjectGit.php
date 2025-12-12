<?php

/**
 * Trait TraitDataObjectGit
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Traits;

use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;


trait TraitDataObjectGit
{
    /**
     * The path that will be checked
     *
     * @var GitInterface $o_git
     */
    protected GitInterface $o_git;


    /**
     * Returns the path for this ChangedFiles object
     *
     * @return GitInterface
     */
    public function getGitObject(): GitInterface
    {
        return $this->o_git;
    }


    /**
     * Returns the path for this ChangedFiles object
     *
     * @param GitInterface $o_git
     *
     * @return static
     */
    public function setGitObject(GitInterface $o_git): static
    {
        $this->o_git = $o_git;
        return $this;
    }
}
