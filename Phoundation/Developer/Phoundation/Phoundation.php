<?php

/**
 * Class Phoundation
 *
 * This is one specific project: The Phoundation core project found on this computer.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */

declare(strict_types=1);

namespace Phoundation\Developer\Phoundation;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Developer\Phoundation\Exception\IsPhoundationException;
use Phoundation\Developer\Phoundation\Exception\NotPhoundationException;
use Phoundation\Developer\Phoundation\Exception\PatchPartiallySuccessfulException;
use Phoundation\Developer\Phoundation\Exception\PhoundationNotFoundException;
use Phoundation\Developer\Project\Project;
use Phoundation\Developer\Versioning\Git\Exception\GitHasChangesException;
use Phoundation\Developer\Versioning\Git\Exception\GitPatchFailedException;
use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Developer\Versioning\Git\StatusFiles;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsPath;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Os\Processes\Commands\Cp;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;

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
     * @param string|null $directory
     */
    public function __construct(?string $directory = null)
    {
        parent::__construct($this->detectLocation($directory));
    }


    /**
     * Detects and returns the location of your phoundation installation
     *
     * @param string|null $location
     *
     * @return string
     */
    public function detectLocation(?string $location = null): string
    {
        // Paths (in order) which will be scanned for Phoundation installations
        $directories = [
            '~/projects',
            '~/PhpstormProjects',
            '..',
            '../..',
            '/var/www/html/',
        ];
        if ($location) {
            $directory          = realpath($location);
            $this->restrictions = FsRestrictions::new(dirname($directory));
            if (!$directory) {
                throw new FileNotExistException(tr('The specified Phoundation location ":file" does not exist', [
                    ':file' => $location,
                ]));
            }
            if (!$this->isPhoundationProject($directory)) {
                // This is not a Phoundation type project directory
                throw new NotPhoundationException(tr('The specified Phoundation location ":file" exists but is not a Phoundation project', [
                    ':directory' => $directory,
                ]));
            }
            if (!$this->isPhoundation($directory)) {
                throw new NotPhoundationException(tr('The specified Phoundation location ":file" exists but is not a Phoundation core installation', [
                    ':file' => $location,
                ]));
            }

            Log::success(tr('Using Phoundation installation in specified directory ":directory"', [':directory' => $directory]));
            $this->directory = $directory;

            return $directory;

        }
        // Scan for phoundation installation location.
        foreach ($directories as $directory) {
            try {
                $directory = FsPath::absolutePath($directory);

            } catch (FileNotExistException) {
                // Okay, that was easy, doesn't exist. NEXT!
                continue;
            }

            $names = [
                'phoundation',
                'Phoundation',
                'phoundation/phoundation',
                'Phoundation/phoundation',
                'Phoundation/Phoundation',
                'phoundation/Phoundation',
            ];

            // The main phoundation directory should be called either phoundation or Phoundation.
            foreach ($names as $name) {
                $test_path          = $directory . $name . '/';
                $this->restrictions = FsRestrictions::new(dirname($test_path));

                if (!file_exists($test_path)) {
                    Log::warning(tr('Ignoring directory ":directory", it does not exist', [
                        ':directory' => $test_path,
                    ]), 2);
                    continue;
                }

                if (!$this->isPhoundationProject($test_path)) {
                    // This is not a Phoundation type project directory
                    Log::warning(tr('Ignoring directory ":directory", it has the name ":name" but is not a Phoundation project', [
                        ':directory' => $test_path,
                        ':name'      => $name,
                    ]), 4);
                    continue;
                }

                if (!$this->isPhoundation($test_path)) {
                    // This is not the Phoundation directory
                    Log::warning(tr('Ignoring directory ":directory", it has the name ":name" and is a Phoundation project but is not a Phoundation core project', [
                        ':directory' => $test_path,
                        ':name'      => $name,
                    ]), 4);
                    continue;
                }

                if ($test_path == DIRECTORY_ROOT) {
                    throw new IsPhoundationException(tr('This project IS your Phoundation core installation', [
                        ':file' => $location,
                    ]));
                }

                Log::success(tr('Found Phoundation installation in ":directory"', [':directory' => $test_path]));
                $this->directory = $test_path;

                return $test_path;
            }
        }

        throw new PhoundationNotFoundException();
    }


    /**
     * Returns true if the specified filesystem location contains a valid Phoundation installation
     *
     * @param string $directory
     *
     * @return bool
     * @todo TODO Update to use git remote show origin!
     */
    public function isPhoundation(string $directory): bool
    {
        try {
            $file    = FsFile::new($directory . 'config/project', $this->restrictions)
                             ->checkReadable()
                             ->getSource();
            $project = file_get_contents($file);

            // TODO Update to use git remote show origin!
            return trim($project) === 'phoundation';

        } catch (FilesystemException $e) {
            // Failed to read the project file for this project
            Log::warning(tr('Failed to read project file ":file" because ":e"', [
                ':file' => $directory . 'config/project',
                ':e'    => $e->getMessage()
            ]));
            return false;
        }
    }


    /**
     * Switch the Phoundation project to the specified branch
     *
     * @param string|null $branch
     *
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
     * Copies only the specified file back to Phoundation
     *
     * @param array|string $files
     * @param string|null  $branch
     * @param bool         $require_no_changes
     *
     * @return void
     */
    public function copy(array|string $files, ?string $branch, bool $require_no_changes = true): void
    {
        $this->selectPhoundationBranch($this->defaultBranch($branch));
        $this->ensureNoChanges(!$require_no_changes);

        $files = Arrays::force($files);

        // Ensure specified source files exist and make files absolute
        foreach ($files as $id => $file) {
            $source  = FsPath::absolutePath($file, DIRECTORY_ROOT);
            $test    = Strings::from($source, DIRECTORY_ROOT);
            $test    = Strings::until($test, '/');
            $plugins = [
                'Templates',
                'Plugins',
            ];

            if (in_array($test, $plugins)) {
                // Plugins should be copied to Phoundation plugins!
                continue;
            }

            if (!file_exists($source)) {
                throw new FileNotExistException(tr('The specified file ":file" does not exist', [
                    ':file' => $file,
                ]));
            }

            $files[$id] = $source;
        }

        // Copy files
        foreach (Arrays::force($files) as $file) {
            $target = Strings::from($file, DIRECTORY_ROOT);

            Cp::new()->archive(
                  $file,
                  FsRestrictions::new(DIRECTORY_ROOT),
                  $this->getDirectory() . $target,
                  FsRestrictions::new($this->getDirectory(), true)
            );
        }
    }


    /**
     * Ensure that Phoundation is on the specified branch
     *
     * @param string|null $branch
     *
     * @return void
     */
    protected function selectPhoundationBranch(?string $branch): static
    {
        if (!$branch) {
            return $this;
        }
        // Ensure phoundation is on the right branch
        $this->phoundation_branch = $this->git->getBranch();
        if ($branch !== $this->phoundation_branch) {
            Log::warning(tr('Phoundation is currently on different branch ":current"', [
                ':current' => $this->phoundation_branch,
            ]), 4);
            Log::action(tr('Switching Phoundation branch to requested branch ":requested"', [
                ':requested' => $branch,
            ]), 5);
            $this->git->checkout($branch);
        }

        return $this;
    }


    /**
     * Returns either the specified branch or the current Phoundation branch as default
     *
     * @param string|null $branch
     *
     * @return string
     */
    protected function defaultBranch(?string $branch): string
    {
        if (!$branch) {
            // Select the current branch
            $branch = $this->git->getBranch();
            Log::notice(tr('Trying to patch updates on Phoundation using current project branch ":branch"', [
                ':branch' => $branch,
            ]));
        }

        return $branch;
    }


    /**
     * Ensures that the Phoundation installation has no changes
     *
     * @param bool $force
     *
     * @return static
     */
    protected function ensureNoChanges(bool $force = false): static
    {
        // Ensure Phoundation has no changes
        if ($this->git->hasChanges()) {
            if (!$force) {
                throw GitHasChangesException::new(tr('Cannot copy changes, your Phoundation installation ":directory" has uncommitted changes', [
                    ':directory' => $this->directory,
                ]))
                                            ->makeWarning();
            }
        }

        return $this;
    }


    /**
     * Copies all phoundation updates from your current project back to Phoundation
     *
     * @param string|null $branch
     * @param string|null $message
     * @param bool|null   $sign
     * @param bool        $checkout
     * @param bool        $update
     *
     * @return void
     */
    public function patch(?string $branch, ?string $message, ?bool $sign = null, bool $checkout = true, bool $update = true): void
    {
        $project = new Project();
        $sign    = $sign ?? Config::getBoolean('developer.phoundation.patch.sign', true);
        $branch  = $project->getBranch($branch);

        Log::action(tr('Patching branch ":branch" on your local Phoundation repository from this project', [
            ':branch' => $branch,
        ]));

        // Reset the local project to HEAD and update
        $project->resetHead();

        if ($update) {
            $project->updateLocalProject($branch, $message, $sign);
        }
        // Detect Phoundation installation and ensure its clean and on the right branch
        $this->selectPhoundationBranch($branch)
             ->ensureNoChanges();

        try {
            // Execute the patching
            $stash    = new Iterator();
            $sections = [
                'Phoundation',
                'Plugins/Phoundation',
            ];

            static::patchSections($sections, $stash, $checkout);

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
     * Returns an Iterator object containing all the available Phoundation branches
     *
     * @return IteratorInterface
     */
    public function getPhoundationBranches(): IteratorInterface
    {
        // Ensure phoundation is on the right branch
        return $this->git->getBranchesObject();
    }


    /**
     * Apply patches from the local project to phoundation
     *
     * @return int
     */
    protected function updateTo(): int
    {
        throw new UnderConstructionException(tr('Phoundation::updateTo() is under construction, not sure what it is supposed to do'));
        $count = 0;
        foreach ($this->phoundation_files as $directory) {
            $directory = $this->git->getDirectory() . $directory;
            // Find local Phoundation changes and filter Phoundation changes only
            $changed_files = $this->git->getStatusFilesObject($directory);
            if (!$changed_files->getCount()) {
                Log::notice(tr('Not patching directory ":directory", it has no changes', [
                    ':directory' => $directory,
                ]));
                continue;
            }
            // Apply changes on Phoundation
            $changed_files->applyPatch($this->directory);
            $count += $changed_files->getCount();
        }

        return $count;
    }
}
