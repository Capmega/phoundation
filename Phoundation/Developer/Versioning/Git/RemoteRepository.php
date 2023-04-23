<?php

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Cli\Cli;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Classes\Iterator;
use Phoundation\Developer\Versioning\Git\Traits\GitProcess;
use Phoundation\Exception\OutOfBoundsException;

/**
 * Class Repository
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class RemoteRepository extends Iterator
{
    use GitProcess {
        __construct as protected construct;
    }


    /**
     * The repository name
     *
     * @var string $repository
     */
    protected string $repository;

    /**
     * Contains all repository data
     *
     * @var array $data
     */
    protected array $data = [];


    /**
     * Repository class constructor
     *
     * @param string $path
     * @param string $repository
     */
    public function __construct(string $path, string $repository)
    {
        $this->repository = $repository;
        $this->construct($path);
        $this->loadData();
    }


    /**
     * Returns a new Repository object
     *
     * @param string $path
     * @param string $repository
     * @return static
     */
    public static function new(string $path, string $repository): static
    {
        return new static($path, $repository);
    }


    /**
     * Returns the repository name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->repository;
    }


    /**
     * Sets the repository name
     *
     * @param string $repository
     * @return static
     */
    public function setName(string $repository): static
    {
        $output = $this->git_process
            ->clearArguments()
            ->addArgument('remote')
            ->addArgument('rename')
            ->addArgument($this->repository)
            ->addArgument($repository)
            ->executeReturnArray();

        Log::notice($output, 4, false);

        $this->repository = $repository;
        return $this;
    }


    /**
     * Returns the fetch url for this remote repository
     *
     * @return string|null
     */
    public function getFetchUrl(): ?string
    {
        return isset_get($this->data['fetch_url']);
    }


    /**
     * Sets the fetch url for this remote repository
     *
     * @param string $url
     * @return static
     */
    public function setFetchUrl(string $url): static
    {
        $output = $this->git_process
            ->clearArguments()
            ->addArgument('remote')
            ->addArgument('set-url')
            ->addArgument($this->repository)
            ->addArgument($url)
            ->executeReturnArray();

        Log::notice($output, 4, false);

        $this->data['push_url']  = $url;
        $this->data['fetch_url'] = $url;

        return $this;
    }


    /**
     * Returns the local refs for this repository
     *
     * @return array
     */
    public function getLocalRefs(): array
    {
        return $this->data['local_refs'];
    }


    /**
     * Returns the local branches for this repository
     *
     * @return array
     */
    public function getLocalBranches(): array
    {
        return $this->data['local_branches'];
    }


    /**
     * Returns the remote branches for this repository
     *
     * @return array
     */
    public function getRemoteBranches(): array
    {
        return $this->data['remote_branches'];
    }


    /**
     * Displays the repository information
     *
     * @return $this
     */
    public function CliDisplayForm(): static
    {
        Cli::displayForm($this->data);
        return $this;
    }


    /**
     * Ensures that the repository data is loaded
     *
     * @return void
     */
    protected function loadData(): void
    {
        if ($this->data) {
            return;
        }

        $data = $this->git_process
            ->addArgument('remote')
            ->addArgument('show')
            ->addArgument($this->repository)
            ->executeReturnArray();

        $this->parseData($data);
    }


    /**
     * Parse the git output and store it in data
     *
     * @param array $data
     * @return void
     */
    protected function parseData(array $data): void
    {
        $section    = 'top';
        $this->data = [
            'remote'          => null,
            'fetch_url'       => null,
            'push_url'        => null,
            'head_branch'     => null,
            'remote_branches' => [],
            'local_branches'  => [],
            'local_refs'      => []
        ];

        foreach ($data as $line) {
            $parse   = trim(Strings::from ($line , '*'));
            $keyword = trim(Strings::until($parse, ' '));
            $value   = trim(Strings::from ($parse, ' '));

            if (str_ends_with($parse, ':')) {
                $parse = strtolower(trim($parse));

                // Detect section
                if (str_contains($parse, 'local refs configured for')) {
                    $section = 'local_refs';
                    continue;
                }

                if (str_contains($parse, 'local branches configured for')) {
                    $section = 'local_branches';
                    continue;
                }

                if (str_starts_with($parse, 'remote branches')) {
                    $section = 'remote_branches';
                    continue;
                }

                throw OutOfBoundsException::new(tr('Unknown git output ":line" encountered', [':line' => $line]));
            }

            switch ($section) {
                case 'top':
                    switch (strtolower($keyword)) {
                        case 'remote':
                            $this->data['remote'] = $value;
                            continue 3;

                        case 'fetch':
                            $this->data['fetch_url'] = trim(Strings::from($parse, ':'));
                            continue 3;

                        case 'push':
                            $this->data['push_url'] = trim(Strings::from($parse, ':'));
                            continue 3;

                        case 'head':
                            $this->data['head_branch'] = trim(Strings::from($parse, ':'));
                            $section = 'remote_branches';
                            continue 3;
                    }

                    break;

                case 'remote_branches':
                    $this->data['remote_branches'][$keyword] = $value;
                    break;

                case 'local_branches';
                    if (str_contains(strtolower($parse), ':')) {
                        $section = ' ' . $parse;
                        break;
                    }

                    $value = Strings::from($value, 'merges with remote');
                    $value = trim($value);

                    $this->data['local_branches'][$keyword] = $value;
                    break;

                case 'local_refs':
                    $target = Strings::until($value, '(');
                    $target = Strings::from($target, 'pushes to');
                    $target = trim($target);

                    $status = Strings::from($value  , '(');
                    $status = Strings::until($status, ')');
                    $status = trim($status);

                    $this->data['local_refs'][$keyword] = [
                        'target' => $target,
                        'status' => $status
                    ];

                    break;

                default:
                    throw new OutOfBoundsException(tr('Unknown section ":section" encountered', [
                        ':section' => $section
                    ]));

            }
        }
    }
}