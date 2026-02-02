<?php

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Data\IteratorCore;
use Phoundation\Data\Traits\TraitDataStringDefault;
use Phoundation\Data\Traits\TraitStaticMethodNewWithRepository;
use Phoundation\Developer\Versioning\Git\Interfaces\RemotesInterface;
use Phoundation\Developer\Versioning\Git\Traits\TraitDataObjectRepository;
use Phoundation\Developer\Versioning\Repositories\Interfaces\RepositoryInterface;


class Remotes extends IteratorCore implements RemotesInterface
{
    use TraitDataStringDefault;
    use TraitDataObjectRepository;
    use TraitStaticMethodNewWithRepository;

    /**
     * Remote class constructor
     *
     * @param RepositoryInterface $o_repository
     */
    public function __construct(RepositoryInterface $o_repository)
    {
        parent::__construct();
        $this->default      = config()->getString('developer.versioning.git.remotes.default', 'origin');
        $this->o_repository = $o_repository;

        $this->load();
    }


    /**
     * Loads the remotes for the specified git process
     *
     * @return static
     */
    public function load(): static
    {
        $this->source = $this->o_repository->getGitObject()->getRemotes();
        return $this;
    }
}
