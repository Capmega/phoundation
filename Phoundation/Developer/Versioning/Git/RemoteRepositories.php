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

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Cli\Cli;
use Phoundation\Data\IteratorCore;
use Phoundation\Developer\Versioning\Git\Interfaces\RemoteRepositoriesInterface;
use Phoundation\Developer\Versioning\Git\Traits\TraitGitProcess;
use Phoundation\Exception\NotExistsException;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Os\Processes\Process;
use ReturnTypeWillChange;
use Stringable;


class RemoteRepositories extends IteratorCore implements RemoteRepositoriesInterface
{
    use TraitGitProcess {
        __construct as construct;
    }

    /**
     * RemoteRepositories class constructor
     */
    public function __construct(PhoDirectoryInterface $directory)
    {
        $this->construct($directory);
        $this->source = Process::new('git')
                               ->setExecutionDirectory($this->directory)
                               ->addArgument('remote')
                               ->addArgument('show')
                               ->executeReturnArray();
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
    public function displayCliTable(array|string|null $columns = null, array $filters = [], ?string $id_column = 'repository'): static
    {
        $list = [];

        foreach ($this->getSource() as $repository) {
            $list[$repository] = [];
        }

        Cli::displayTable($list, ['repository' => tr('Repository')], 'repository');
        return $this;
    }


    /**
     * Returns the specified repository
     *
     * @param Stringable|string|float|int $key
     * @param mixed                       $default
     * @param bool|null                   $exception
     *
     * @return RemoteRepository|null
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): mixed
    {
        if (!array_key_exists($key, $this->source)) {
            if ($exception) {
                throw new NotExistsException(tr('The repository ":key" does not exist in this object', [
                    ':key' => $key,
                ]));
            }
        }

        return RemoteRepository::new($this->directory, $key, $default);
    }
}
