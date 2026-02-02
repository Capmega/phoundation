<?php

/**
 * Class Repository
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Developer\Versioning\Git\Traits\TraitDataObjectGit;
use Phoundation\Developer\Versioning\Repositories\Repository;


class Remote extends Repository
{
    use TraitDataObjectGit;


    /**
     * Remote class constructor
     *
     * @param GitInterface $o_git
     */
    public function __construct(GitInterface $o_git)
    {
        parent::__construct();
        $this->o_git = $o_git;
    }
}
