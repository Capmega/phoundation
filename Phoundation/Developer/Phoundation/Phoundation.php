<?php

namespace Phoundation\Developer\Phoundation;

use Phoundation\Core\Config;
use Phoundation\Core\Log\Log;
use Phoundation\Developer\Phoundation\Exception\IsPhoundationException;
use Phoundation\Developer\Phoundation\Exception\NotPhoundationException;
use Phoundation\Developer\Phoundation\Exception\PhoundationNotFoundException;
use Phoundation\Developer\Project\Project;
use Phoundation\Developer\Versioning\Git\Exception\GitHasChangesException;
use Phoundation\Developer\Versioning\Git\StatusFiles;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Servers\Server;


/**
 * Class Phoundation
 *
 * This is one specific project: The Phoundation core project itself.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Phoundation extends Project
{
    /**
     * The branch on which the Phoundation project is
     *
     * @var string|null $branch
     */
    protected ?string $branch = null;


    /**
     * Phoundation constructor
     *
     * @param string|null $path
     */
    public function __construct(?string $path = null)
    {
        parent::__construct($this->detectLocation($path));
    }


    /**
     * Detects and returns the location of your phoundation installation
     *
     * @param string|null $location
     * @return string
     */
    public function detectLocation(?string $location = null): string
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
            $this->restrictions = Server::new(dirname($path));

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
                $this->restrictions = Server::new(dirname($path));

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
     * @param string $branch
     * @return $this
     */
    public function switchBranch(?string $branch = null): static
    {
        if ($branch === null) {
            // This will cause a switch back to the previous branch, selected in this same process
            if (!$this->branch) {
                throw new OutOfBoundsException(tr('Cannot switch back to previous branch, no previous branch available'));
            }

            // Select the previous branch and reset it
            $this->git->setBranch($this->branch);
            $this->branch = null;

        } else {
            // Select the new branch and store the previous
            $this->branch = $this->git->getBranch();
            $this->git->setBranch($branch);
        }

        return $this;
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

            Log::information(tr('Patching branch ":branch" on your local Phoundation repository from this project', [
                ':branch' => $branch
            ]));

            // Update the local project
            $project = Project::new();
            $project->updateLocal($message, $sign);

            // Detect Phoundation installation and ensure its clean and on the right branch
            $this->ensureNoChanges();
            $this->selectBranch($branch);

            // Execute the patching
            foreach (['Phoundation', 'scripts'] as $section) {
                StatusFiles::new(PATH_ROOT . 'Phoundation')->patch($this->getPath() . $section);
            }

            if ($this->phoundation_branch) {
                $this->selectBranch($this->phoundation_branch);
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
     * @todo TODO Update to use git remote show origin!
     * @param string $path
     * @return bool
     */
    public function isPhoundation(string $path): bool
    {
        $file    = File::new($path . 'config/project', $this->restrictions)->checkReadable()->getFile();
        $project = file_get_contents($file);

// TODO Update to use git remote show origin!
        return strtolower($project) === 'phoundation';
    }



    /**
     * Ensures that the Phoundation installation has no changes
     *
     * @return void
     */
    protected function ensureNoChanges(): void
    {
        // Ensure Phoundation has no changes
        if ($this->git->hasChanges()) {
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
    protected function selectBranch(?string $branch): void
    {
        if (!$branch) {
            return;
        }

        // Ensure phoundation is on the right branch
        $this->phoundation_branch = $this->git->getBranch();

        if ($branch !== $this->phoundation_branch) {
            Log::warning(tr('Phoundation is currently on different branch ":current"', [
                ':requested' => $branch,
                ':current'   => $this->phoundation_branch,
            ]), 4);
            Log::action(tr('Switching Phoundation branch to requested branch ":requested"', [
                ':requested' => $branch,
                ':current'   => $this->phoundation_branch,
            ]), 5);

            $this->git->checkout($branch);
        }
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
            $path = $this->git->getPath() . $directory;

            // Find local Phoundation changes and filter Phoundation changes only
            $changed_files = $this->git->getStatus($path);

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
}