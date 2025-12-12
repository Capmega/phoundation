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

use Phoundation\Data\Entry;
use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Developer\Versioning\Git\Traits\TraitDataObjectGit;

class Remote extends Entry
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


    /**
     *
     *
     * @param string $name
     *
     * @return $this
     */
    protected function load(string $name): static
    {
        $output = $this->o_git->get()
                            ->addArgument('remote show')
                            ->addArgument($files ? '-f' : null)
                            ->addArgument($directories ? '-d' : null)
                            ->addArguments($branches_or_directories)
                            ->executeReturnArray();

    }
}
