<?php

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Cli\Cli;
use Phoundation\Core\Strings;
use Phoundation\Data\Classes\Iterator;
use Phoundation\Developer\Versioning\Git\Traits\GitPath;
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
    use GitPath {
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
        $this->git
            ->clearArguments()
            ->addArgument('remote')
            ->addArgument('rename')
            ->addArgument($this->repository)
            ->addArgument($repository)
            ->executePassthru();

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
        $this->git
            ->clearArguments()
            ->addArgument('remote')
            ->addArgument('set-url')
            ->addArgument($this->repository)
            ->addArgument($url)
            ->executePassthru();

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

        $data = $this->git
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
            $parse   = trim(Strings::from($line, '*'));
            $keyword = Strings::until($parse, ' ');
            $value   = Strings::from($parse, ' ');
            $section = 'top';

            switch ($section) {
                case 'top':
                    switch (strtolower($keyword)) {
                        case 'remote':
                            $this->data['remote'] = trim(Strings::from($parse, ':'));
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
                    if (str_contains(strtolower($parse), 'local branches configured for')) {
                        $section = 'local_branches';
                    } else {
                        $this->data['remote_branches'][$keyword] = $value;
                    }

                    break;

                case 'local_branches';
                    if (str_contains(strtolower($parse), 'local refs configured for')) {
                        $section = 'local_refs';
                    } else {
                        $value = Strings::from($value, 'merges with remote');
                        $value = trim($value);

                        $this->data['local_branches'][$keyword] = $value;
                    }

                    break;

                case 'local_refs':
                    $target = Strings::until($value, '(');
                    $target = Strings::from($target, 'pushes to');
                    $target = trim($target);

                    $status = Strings::from($value  , '(');
                    $status = Strings::until($status, ')');
                    $status = trim($status);

                    $this->data['local_branches'][$keyword] = [
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

//* remote origin
//  Fetch URL: git@github.com:Capmega/phoundation.git
//  Push  URL: git@github.com:Capmega/phoundation.git
//  HEAD branch: master
//  Remote branches:
//    2.2        tracked
//    2.3        tracked
//    2.4        tracked
//    2.5        tracked
//    2.6        tracked
//    2.7        tracked
//    2.8        tracked
//    3.0        tracked
//    4.0        tracked
//    4.1        tracked
//    freeze     tracked
//    master     tracked
//    production tracked
//  Local branches configured for 'git pull':
//    2.7        merges with remote 2.7
//    2.8        merges with remote 2.8
//    4.0        merges with remote 4.0
//    4.1        merges with remote 4.1
//    master     merges with remote master
//    production merges with remote production
//  Local refs configured for 'git push':
//    2.7        pushes to 2.7        (up to date)
//    2.8        pushes to 2.8        (up to date)
//    3.0        pushes to 3.0        (up to date)
//    4.0        pushes to 4.0        (up to date)
//    4.1        pushes to 4.1        (fast-forwardable)
//    master     pushes to master     (up to date)
//    production pushes to production (up to date)
    }
}