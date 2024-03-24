<?php

/**
 * Class Repositories
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Cli\Cli;
use Phoundation\Data\IteratorCore;
use Phoundation\Developer\Versioning\Git\Interfaces\RemoteRepositoriesInterface;
use Phoundation\Developer\Versioning\Git\Traits\TraitGitProcess;
use Phoundation\Exception\NotExistsException;
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
    public function __construct(string $directory)
    {
        $this->construct($directory);
        $this->source = Process::new('git')
            ->setExecutionDirectory($this->directory)
            ->addArgument('remote')
            ->addArgument('show')
            ->executeReturnArray();
    }


    /**
     * Display the repositories on the CLI
     *
     * @return void
     */
    public function CliDisplayTable(): void
    {
        $list = [];

        foreach ($this->getSource() as $repository) {
            $list[$repository] = [];
        }

        Cli::displayTable($list, ['repository' => tr('Repository')], 'repository');
    }


    /**
     * Returns the specified repository
     *
     * @param Stringable|string|float|int
     * @param bool $exception
     * @return RemoteRepository|null
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, bool $exception = true): mixed
    {
        if (!array_key_exists($key, $this->source)) {
            if ($exception) {
                throw new NotExistsException(tr('The repository ":key" does not exist in this object', [
                    ':key' => $key
                ]));
            }
        }

        return RemoteRepository::new($this->directory, $key);
    }
}
