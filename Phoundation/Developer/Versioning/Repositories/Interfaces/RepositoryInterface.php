<?php

namespace Phoundation\Developer\Versioning\Repositories\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\RemotesInterface;

interface RepositoryInterface extends DataEntryInterface
{
    /**
     * Returns the "required" property for this object
     *
     * @return string|null
     */
    public function getRequired(): ?string;


    /**
     * Sets the 'required' property for this object
     *
     * @param int|bool $required
     *
     * @return static
     */
    public function setRequired(int|bool $required): static;


    /**
     * Sets the path for this object
     *
     * @param string|null $path
     *
     * @return static
     */
    public function setPath(string|null $path): static;


    /**
     * Returns the Remotes class object for this Repository
     *
     * @return RemotesInterface
     */
    public function getRemotesObject(): RemotesInterface;
}
