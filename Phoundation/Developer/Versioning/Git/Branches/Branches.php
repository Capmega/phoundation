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
     * Branches class constructor
     *
     * @param RepositoryInterface $o_repository
     */
    public function __construct(RepositoryInterface $o_repository) {
        parent::__construct();

        $this->o_repository = $o_repository;
    }


    /**
     * Loads the remotes for the specified git process
     *
     * @return static
     */
    public function load(): static
    {
        $this->source = $this->o_repository->getGitObject()->getBranches();

        // Branch names must match versions only
        foreach ($this->source as $branch => $selected) {
            // Filter version with suffix?
            if (Branches::isVersionOnly($branch) and $this->filter_versions) {
                continue;
            }

            // Filter version only?
            if (Branches::isVersionWithSuffix($branch) and $this->filter_suffixes) {
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
