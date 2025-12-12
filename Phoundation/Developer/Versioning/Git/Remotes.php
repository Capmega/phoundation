<?php

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Data\IteratorCore;
use Phoundation\Data\Traits\TraitDataStringDefault;
use Phoundation\Data\Traits\TraitStaticMethodNewWithGit;
use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\RemotesInterface;
use Phoundation\Developer\Versioning\Git\Traits\TraitDataObjectGit;


class Remotes extends IteratorCore implements RemotesInterface
{
    use TraitDataStringDefault;
    use TraitDataObjectGit;
    use TraitStaticMethodNewWithGit;


    /**
     * Remote class constructor
     *
     * @param GitInterface $o_git
     */
    public function __construct(GitInterface $o_git)
    {
        parent::__construct();
        $this->default = config()->getString('developer.versioning.git.remotes.default', 'origin');
        $this->o_git   = $o_git;

        $this->load();
    }


    /**
     * Loads the remotes for the specified git process
     *
     * @return static
     */
    public function load(): static
    {
        $this->source = $this->o_git->getRemotes();
        return $this;
    }
}
