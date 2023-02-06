<?php

namespace Phoundation\Developer\Phoundation;

use Phoundation\Core\Config;
use Phoundation\Core\Log\Log;
use Phoundation\Developer\Phoundation\Exception\IsPhoundationException;
use Phoundation\Developer\Phoundation\Exception\NotPhoundationException;
use Phoundation\Developer\Phoundation\Exception\PhoundationNotFoundException;
use Phoundation\Developer\Versioning\Git\Exception\GitHasChangesException;
use Phoundation\Developer\Versioning\Git\Exception\GitHasNoChangesException;
use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Developer\Versioning\Git\StatusFiles;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Filesystem\Path;
use Phoundation\Processes\Commands\Rsync;
use Phoundation\Servers\Server;


/**
 * Class Phoundation
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Phoundation
{
    /**
     * The path of a Phoundation installation
     *
     * @var string
     */
    protected string $path;

    /**
     * The git instance for this project
     *
     * @var Git $local_git
     */
    protected Git $local_git;

    /**
     * The git instance for phoundation
     *
     * @var Git $phoundation_git
     */
    protected Git $phoundation_git;

    /**
     * Server object where the image conversion commands will be executed
     *
     * @var Server $server_restrictions
     */
    protected Server $server_restrictions;

    /**
     * The branch the phoundation project currently is on
     *
     * @var string|null $phoundation_branch
     */
    protected ?string $phoundation_branch = null;

    protected array $phoundation_directories = [
        'Phoundation/',
        'scripts/system/'
    ];



    /**
     * Phoundation constructor
     *
     * @param string|null $path
     */
    public function __construct(?string $path = null)
    {
        $this->detectPhoundationLocation($path);
    }



    /**
     * Returns a new Phoundation object
     *
     * @param string|null $path
     * @return static
     */
    public static function new(?string $path = null): static
    {
        return new static($path);
    }



    /**
     * Detects and returns the location of your phoundation installation
     *
     * @param string|null $location
     * @return string
     */
    public function detectPhoundationLocation(?string $location = null): string
    {
        // Paths (in order) which will be scanned for Phoundation installations
        $paths = [
            '~/projects',
            '~/PhpstormProjects',
            '..',
            '../..',
            '/var/www/html/'
        ];

        if ($location) {
            $path = realpath($location);
            $this->server_restrictions = Server::new(dirname($path));

            if (!$path) {
                throw new FileNotExistException(tr('The specified Phoundation location ":file" does not exist', [
                    ':file' => $location
                ]));
            }

            if (!$this->isPhoundationProject($path)) {
                // This is not a Phoundation type project directory
                throw new NotPhoundationException(tr('The specified Phoundation location ":file" exists but is not a Phoundation project', [
                    ':path' => $path
                ]));
            }

            if (!$this->isPhoundation($path)) {
                throw new NotPhoundationException(tr('The specified Phoundation location ":file" exists but is not a Phoundation core installation', [
                    ':file' => $location
                ]));
            }

            Log::success(tr('Using Phoundation installation in specified path ":path"', [':path' => $path]));

            $this->path = $path;
            return $path;

        }

        // Scan for phoundation installation location.
        foreach ($paths as $path) {
            $path = Filesystem::absolute($path);

            // The main phoundation directory should be called either phoundation or Phoundation.
            foreach (['phoundation', 'Phoundation'] as $name) {
                $path = $path . $name . '/';
                $this->server_restrictions = Server::new(dirname($path));

                if (!file_exists($path)) {
                    continue;
                }

                if (!$this->isPhoundationProject($path)) {
                    // This is not a Phoundation type project directory
                    Log::warning(tr('Ignoring path ":path", it has the name ":name" but is not a Phoundation project', [
                        ':path' => $path,
                        ':name' => $name
                    ]));

                    continue;
                }

                if (!$this->isPhoundation($path)) {
                    // This is not the Phoundation directory
                    Log::warning(tr('Ignoring path ":path", it has the name ":name" and is a Phoundation project but is not a Phoundation core project', [
                        ':path' => $path,
                        ':name' => $name
                    ]));

                    continue;
                }

                if ($path == PATH_ROOT) {
                    throw new IsPhoundationException(tr('This project IS your Phoundation core installation', [
                        ':file' => $location
                    ]));
                }

                Log::success(tr('Found Phoundation installation in ":path"', [':path' => $path]));

                $this->path = $path;
                return $path;
            }
        }

        throw new PhoundationNotFoundException();
    }



    /**
     * Copies all phoundation updates from your current project back to Phoundation
     *
     * @param string|null $branch
     * @param string|null $message
     * @param bool|null $sign
     * @return void
     */
    public function patch(?string $branch, ?string $message, ?bool $sign = null): void
    {
        try {
            if ($sign === null) {
                $sign = Config::getBoolean('developer.phoundation.patch');
            }

            $this->initializeGit();
            $this->ensurePhoudationNoChanges();
            $this->selectPhoundationBranch($branch);
            $this->updateFromLocalRepository($message, $sign);
            $this->resetHeadLocalProject();
            $this->updateTo();

            if ($this->phoundation_branch) {
                $this->selectPhoundationBranch($this->phoundation_branch);
            }

        } catch (GitHasChangesException $e) {
            // Since the operation failed, ensure that Phoundation is back on its original branch
            if (isset($this->phoundation_git)) {
                if (isset($phoundation_branch)) {
                    $this->phoundation_git->checkout($phoundation_branch);
                }
            }

            throw $e;
        }
    }



    /**
     * Returns true if the specified filesystem location contains a valid Phoundation installation
     *
     * @param string $path
     * @return bool
     */
    public function isPhoundation(string $path): bool
    {
        $file    = File::new($path . 'config/project', $this->server_restrictions)->checkReadable()->getFile();
        $project = file_get_contents($file);

        return strtolower($project) === 'phoundation';
    }



    /**
     * Returns true if the specified filesystem location contains a valid Phoundation project installation
     *
     * @param string $path
     * @return bool
     */
    public function isPhoundationProject(string $path): bool
    {
        // Is the path readable?
        $path = Path::new($path, $this->server_restrictions)->checkReadable()->getFile();

        // All these files and directories must be available.
        $files = [
            'config',
            'data',
            'Phoundation',
            'Plugins',
            'scripts',
            'Templates',
            'tests',
            'vendor',
            'www',
            'cli',
        ];

        foreach ($files as $file) {
            if (!file_exists($path . $file)) {
                return false;
            }
        }

        return true;
    }



    /**
     * Updates the Phoundation core files for this project
     *
     * Stashes current changes, updates the project with Phoundation updates, commits those, and unstashes local changes
     *
     * @return $this
     */
    public function updateFromLocalRepository(string $message, bool $signed = false): static
    {
        // Add all files to index to ensure everything will be stashed
        $this->local_git->add(PATH_ROOT);
        $this->local_git->stash();

        // Copy Phoundation core files
        $this->copyPhoundationFilesLocal();

        // If there are changes then add and commit
        if ($this->local_git->getStatus()->getCount()) {
            $this->local_git->add([PATH_ROOT . 'Phoundation/', PATH_ROOT . 'scripts/']);
            $this->local_git->commit($message, $signed);
        }

        // Stash pop the previous changes and reset HEAD to ensure index is empty
        $this->local_git->stash()->pop();
        $this->local_git->reset('HEAD');

        return $this;
    }



    /**
     * Initialize the git instances
     *
     * @return void
     */
    protected function initializeGit(): void
    {
        // Get git objects for this project and the phoundation project
        $this->local_git       = Git::new(PATH_ROOT);
        $this->phoundation_git = Git::new($this->path);
    }



    /**
     * Ensures that the Phoundation installation has no changes
     *
     * @return void
     */
    protected function ensurePhoudationNoChanges(): void
    {
        // Ensure Phoundation has no changes
        if ($this->phoundation_git->hasChanges()) {
            throw GitHasChangesException::new(tr('Cannot copy changes, your Phoundation installation ":path" has uncommitted changes', [
                ':path' => $this->path
            ]))->makeWarning();
        }
    }


    /**
     * Ensure that Phoundation is on the specified branch
     *
     * @param string|null $branch
     * @return void
     */
    protected function selectPhoundationBranch(?string $branch): void
    {
        if (!$branch) {
            return;
        }

        // Ensure phoundation is on the right branch
        $this->phoundation_branch = $this->phoundation_git->getBranch();

        if ($branch !== $this->phoundation_branch) {
            Log::warning(tr('Phoundation is currently on different branch ":current"', [
                ':requested' => $branch,
                ':current'   => $this->phoundation_branch,
            ]), 4);
            Log::action(tr('witching Phoundation branch to requested branch ":requested"', [
                ':requested' => $branch,
                ':current'   => $this->phoundation_branch,
            ]), 5);

            $this->phoundation_git->checkout($branch);
        }
    }



    /**
     * Reset the local project to HEAD branch to make sure nothing is indexed
     *
     * @return void
     */
    protected function resetHeadLocalProject(): void
    {
        $this->local_git->reset('HEAD');
    }



    /**
     * Apply patches from the local project to phoundation
     *
     * @return int
     */
    protected function updateTo(): int
    {
        $count = 0;

        foreach ($this->phoundation_directories as $directory) {
            $path = $this->local_git->getPath() . $directory;

            // Find local Phoundation changes and filter Phoundation changes only
            $changed_files = $this->local_git->getStatus($path);

            if (!$changed_files->getCount()) {
                Log::notice(tr('Not patching directory ":directory", it has no changes', [
                    ':directory' => $directory
                ]));

                continue;
            }

            // Apply changes on Phoundation
            $changed_files->applyPatch($this->path);
            $count += $changed_files->getCount();
        }

        return $count;
    }



    /**
     * @return void
     */
    protected function updateFrom(): void
    {

    }



    /**
     * Returns true if the specified file is a Phoundation core file
     *
     * @param string $file
     * @return bool
     */
    protected function isPhoundationFile(string $file): bool
    {
        foreach ($this->phoundation_directories as $directory) {
            if (str_starts_with($file, $directory)) {
                return true;
            }
        }

        return false;
    }



    /**
     * Copy all files from the local phoundation installation.
     *
     * @return void
     */
    protected function copyPhoundationFilesLocal(): void
    {
        $rsync = Rsync::new();
        $local = static::detectPhoundationLocation();

        // Move /Phoundation and /scripts out of the way
        $phoundation = File::new(PATH_ROOT . 'Phoundation/')->move(PATH_ROOT . 'data/garbage');
        $scripts     = File::new(PATH_ROOT . 'scripts/')->move(PATH_ROOT . 'data/garbage');

        // Copy new versions
        $rsync
            ->setSource($local . 'Phoundation/')
            ->setTarget(PATH_ROOT . 'Phoundation/')
            ->execute();

        // Copy new versions
        $rsync
            ->setSource($local . 'scripts/')
            ->setTarget(PATH_ROOT . 'scripts/')
            ->execute();

        // All is well? Get rid of the garbage
        $phoundation->delete();
        $scripts->delete();
    }
}