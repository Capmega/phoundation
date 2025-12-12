<?php

/**
 * Class Repositories
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Repositories;

use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\DataIteratorCore;
use Phoundation\Data\Traits\TraitDataResultsWithPermissionDenied;
use Phoundation\Developer\Phoundation\Interfaces\RepositoryInterface;
use Phoundation\Developer\Versioning\Git\Traits\TraitGitProcess;
use Phoundation\Developer\Versioning\Repositories\Interfaces\RepositoriesInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Os\Processes\Commands\Interfaces\FindInterface;
use Phoundation\Os\Processes\Process;
use ReturnTypeWillChange;
use Stringable;


class Repositories extends DataIteratorCore implements RepositoriesInterface
{
    use TraitDataResultsWithPermissionDenied {
        getResultsWithPermissionDenied as protected __getResultsWithPermissionDenied;
    }
    use TraitGitProcess {
        __construct as construct;
    }


    /**
     * Tracks the Find process
     *
     * @var FindInterface
     */
    protected FindInterface $o_find;

    /**
     * Tracks the number of new repositories found
     *
     * @var array $new
     */
    protected array $new;

    /**
     * Tracks the number of repositories deleted
     *
     * @var array $deleted
     */
    protected array $deleted;


    /**
     * RemoteRepositories class constructor
     *
     * @param PhoPathInterface|null $o_parent_path
     */
    public function __construct(?PhoPathInterface $o_parent_path = null)
    {
        parent::__construct();
        $this->construct($o_parent_path);
        $this->setKeysAreUniqueColumn(true);

        $this->query = 'SELECT `developer_repositories`.* FROM `developer_repositories` WHERE `status` IS NULL';
    }


    /**
     * Returns the unique column for this class
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'name';
    }


    /**
     * Returns the data types that are allowed and accepted for this data iterator
     *
     * @return string|null
     */
    public static function getDefaultContentDataType(): ?string
    {
        return RepositoryInterface::class;
    }


    /**
     * Returns the amount of 'permission denied' items in the result set
     *
     * @return array
     */
    public function getResultsWithPermissionDenied(): array
    {
        return $this->o_find?->getResultsWithPermissionDenied();
    }


    /**
     * Returns an array with the new repositories found after a scan
     *
     * @return array
     */
    public function getNew(): array
    {
        if (empty($this->new)) {
            return [];
        }

        return $this->new;
    }


    /**
     * Returns the number of new repositories found after a scan
     *
     * @return int|null
     */
    public function getNewCount(): ?int
    {
        return count($this->getNew());
    }


    /**
     * Returns an array with the repositories that were deleted after a scan
     *
     * @return array
     */
    public function getDeleted(): array
    {
        if (empty($this->new)) {
            return [];
        }

        return $this->new;
    }


    /**
     * Returns the number of repositories deleted after a scan
     *
     * @return int|null
     */
    public function getDeletedCount(): ?int
    {
        return count($this->getDeleted());
    }


    /**
     * Returns the entry with the specified identifier
     *
     * @param Stringable|string|float|int $key
     * @param mixed                       $default
     * @param bool|null                   $exception
     *
     * @return RepositoryInterface|null
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): ?RepositoryInterface
    {
        return parent::get($key, $default, $exception);
    }


    /**
     * Scans for repositories on the current machine and registers them in the database
     *
     * @param PhoPathInterface $path
     * @param bool             $delete_gone
     *
     * @return static
     */
    public function scan(PhoPathInterface $path, bool $delete_gone = true): static
    {
        $this->load();

        Log::action(ts('Scanning path ":path" for repositories, this may take a little while...', [
            ':path' => $path
        ]));

        $this->o_find = Find::new()
                            ->setIgnorePermissionDeniedInResults(true)
                            ->setPathObject($path)
                            ->setType('d')
                            ->setName('.git');

        $found = $this->o_find->executeReturnArray();

        foreach ($found as $repository_path) {
            $o_repository_path = PhoDirectory::new($repository_path, $path->getRestrictionsObject())->getParentDirectoryObject();

            if (Repository::isPhoundation($o_repository_path)) {
                if (!Repository::exists($o_repository_path->getBasename())) {
                    Repository::newFromPathObject($o_repository_path)->save();
                }
            }
        }


        // Remove repositories that weren't found from the list?
        if ($delete_gone) {

        }

        return $this;
    }
}
