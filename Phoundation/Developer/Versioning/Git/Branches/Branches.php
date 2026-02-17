<?php

/**
 * Class Branches
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Branches;

use Phoundation\Cli\Cli;
use Phoundation\Data\IteratorCore;
use Phoundation\Data\Traits\TraitStaticMethodNewWithRepository;
use Phoundation\Developer\Versioning\Git\Branches\Interfaces\BranchesInterface;
use Phoundation\Developer\Versioning\Git\Traits\TraitDataObjectRepository;
use Phoundation\Developer\Versioning\Repositories\Interfaces\RepositoryInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;

class Branches extends IteratorCore implements BranchesInterface
{
    use TraitDataObjectRepository;
    use TraitStaticMethodNewWithRepository;

    /**
     * Tracks if only version branches should be loaded
     *
     * @var bool $filter_versions
     */
    public bool $filter_versions = false;

    /**
     * Tracks if only suffix branches should be loaded
     *
     * @var bool $filter_suffixes
     */
    public bool $filter_suffixes = false;

    /**
     * Tracks if branch loading should filter on branches that contain the specified revision
     *
     * @var string|null $filter_revisions
     */
    public ?string $filter_revisions = null;


    /**
     * Branches class constructor
     *
     * @param RepositoryInterface $_repository
     */
    public function __construct(RepositoryInterface $_repository) {
        parent::__construct();
        $this->_repository = $_repository;
    }


    /**
     * Loads the remotes for the specified git process
     *
     * @return static
     */
    public function load(): static
    {
        $this->source = $this->_repository->getGitObject()->getBranches(contains: $this->filter_revisions);

        // Branch names must match versions only
        foreach ($this->source as $branch => $selected) {
            // Filter version with suffix?
            if ($this->filter_versions and Branches::isVersionOnly($branch)) {
                continue;
            }

            // Filter version only?
            if ($this->filter_suffixes and Branches::isVersionWithSuffix($branch)) {
                continue;
            }

            // Filter nothing?
            if (!$this->filter_versions and !$this->filter_suffixes) {
                continue;
            }

            // Did not match, filter out
            unset($this->source[$branch]);
        }

        return $this;
    }


    /**
     * Returns whether only branches should be loaded that contain the specified revision, or part of it
     *
     * @return string|null
     */
    public function getFilterRevision(): ?string
    {
        return $this->filter_revisions;
    }


    /**
     * Sets whether only branches should be loaded that contain the specified revision, or part of it
     *
     * @param string|null $filter If specified, branches will be filtered on having the4 specified revision
     *
     * @return static
     */
    public function setFilterRevision(?string $filter): static
    {
        $this->filter_revisions = $filter;
        return $this;
    }


    /**
     * Returns whether only version branches should be loaded
     *
     * @return bool
     */
    public function getFilterVersions(): bool
    {
        return $this->filter_versions;
    }


    /**
     * Sets whether only version branches should be loaded
     *
     * @param bool $filter If true, this Branches object will only load version branches
     *
     * @return static
     */
    public function setFilterVersions(bool $filter): static
    {
        $this->filter_versions = $filter;
        return $this;
    }


    /**
     * Returns whether only suffix branches should be loaded
     *
     * @return bool
     */
    public function getFilterSuffixes(): bool
    {
        return $this->filter_suffixes;
    }


    /**
     * Sets whether only suffix branches should be loaded
     *
     * @param bool $filter If true, this Branches object will only load suffix branches
     *
     * @return static
     */
    public function setFilterSuffixes(bool $filter): static
    {
        $this->filter_suffixes = $filter;
        return $this;
    }


    /**
     * Returns true if the specified branch is a version branch with suffix
     *
     * @param string $branch               The branch to test
     * @param bool   $short_version [true] If true, will require a short version (MAJOR.MINOR) instead of a full version (MAJOR.MINOR.REVISION)
     *
     * @return bool
     */
    public static function isVersionOnly(string $branch, bool $short_version = true): bool
    {
        return Strings::isVersion($branch, short_version: $short_version);
    }


    /**
     * Returns true if the specified branch is a version branch with suffix
     *
     * @param string $branch The branch to test
     * @param bool   $short_version [true] If true, will require a short version (MAJOR.MINOR) instead of a full version (MAJOR.MINOR.REVISION)
     *
     * @return bool
     */
    public static function isVersionWithSuffix(string $branch, bool $short_version = true): bool
    {
        return Strings::isVersion(Strings::until($branch, '-'), short_version: $short_version) and Strings::from($branch, '-', needle_required: true);
    }


    /**
     * Creates and returns a CLI table for the data in this list
     *
     * @param array|string|null $columns
     * @param array             $filters
     * @param string|null       $id_column
     *
     * @return static
     */
    public function displayCliTable(array|string|null $columns = null, array $filters = [], ?string $id_column = 'branch'): static
    {
        $list = [];

        foreach ($this->getSource() as $branch => $selected) {
            $list[$branch] = [
                'branch'   => $branch,
                'selected' => $selected ? '*' : ''
            ];
        }

        $filters = array_replace(
            $filters,
            [
                'branch'   => tr('Branch'),
                'selected' => tr('Selected'),
            ]
        );

        Cli::displayTable(
            $list,
            $filters,
            $id_column
        );

        return $this;
    }
}
