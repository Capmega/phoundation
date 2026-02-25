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
     * @param RepositoryInterface $_repository
     */
    public function __construct(RepositoryInterface $_repository)
    {
        parent::__construct();
        $this->default      = config()->getString('developer.versioning.git.remotes.default', 'origin');
        $this->_repository = $_repository;

        $this->load();
    }


    /**
     * Loads the remotes for the specified git process
     *
     * @return static
     */
    public function load(): static
    {
        $this->source = $this->_repository->getGitObject()->getRemotes();
        return $this;
    }
}
