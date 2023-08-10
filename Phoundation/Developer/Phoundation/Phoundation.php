<?php

declare(strict_types=1);

namespace Phoundation\Developer\Phoundation;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Iterator;
use Phoundation\Developer\Phoundation\Exception\IsPhoundationException;
use Phoundation\Developer\Phoundation\Exception\NotPhoundationException;
use Phoundation\Developer\Phoundation\Exception\PatchPartiallySuccessfulException;
use Phoundation\Developer\Phoundation\Exception\PhoundationNotFoundException;
use Phoundation\Developer\Project\Project;
use Phoundation\Developer\Versioning\Git\Exception\GitHasChangesException;
use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Developer\Versioning\Git\StatusFiles;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Exception\ProcessFailedException;


/**
 * Class Phoundation
 *
 * This is one specific project: The Phoundation core project itself.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
            $this->restrictions = Restrictions::new(dirname($path));

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
            try {
                $path = Filesystem::absolute($path);

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
                'phoundation/Phoundation'
            ];

            // The main phoundation directory should be called either phoundation or Phoundation.
            foreach ($names as $name) {
                $test_path = $path . $name . '/';
                $this->restrictions = Restrictions::new(dirname($test_path));

                if (!file_exists($test_path)) {
                    Log::warning(tr('Ignoring path ":path", it does not exist', [
                        ':path' => $test_path,
                    ]), 2);

                    continue;
                }

                if (!$this->isPhoundationProject($test_path)) {
                    // This is not a Phoundation type project directory
                    Log::warning(tr('Ignoring path ":path", it has the name ":name" but is not a Phoundation project', [
                        ':path' => $test_path,
                        ':name' => $name
                    ]));

                    continue;
                }

                if (!$this->isPhoundation($test_path)) {
                    // This is not the Phoundation directory
                    Log::warning(tr('Ignoring path ":path", it has the name ":name" and is a Phoundation project but is not a Phoundation core project', [
                        ':path' => $test_path,
                        ':name' => $name
                    ]));

                    continue;
                }

                if ($test_path == PATH_ROOT) {
                    throw new IsPhoundationException(tr('This project IS your Phoundation core installation', [
                        ':file' => $location
                    ]));
                }

                Log::success(tr('Found Phoundation installation in ":path"', [':path' => $test_path]));

                $this->path = $test_path;
                return $test_path;
            }
        }

        throw new PhoundationNotFoundException();
    }


    /**
     * Switch the Phoundation project to the specified branch
     *
     * @param string|null $branch
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
     * @param bool $checkout
     * @return void
     */
    public function patch(?string $branch, ?string $message, ?bool $sign = null, bool $checkout = true): void
    {
        if (!$branch) {
            // Select the current branch
            $branch = $this->git->getBranch();

            Log::notice(tr('Trying to patch updates on Phoudation using current project branch ":branch"', [
                ':branch' => $branch
            ]));
        }

        if ($sign === null) {
            $sign = Config::getBoolean('developer.phoundation.patch.sign', true);
        }

        Log::information(tr('Patching branch ":branch" on your local Phoundation repository from this project', [
            ':branch' => $branch
        ]));

        // Update the local project
        $stash    = new Iterator();
        $sections = ['Phoundation', 'scripts'];
        $project  = Project::new();
        $project->updateLocalProject($branch, $message, $sign);

        // Detect Phoundation installation and ensure its clean and on the right branch
        $this->selectPhoundationBranch($branch);

        try {
            // Execute the patching
            foreach ($sections as $section) {
                // Patch phoundation target section and remove the changes locally
                while(true) {
                    try {
                        StatusFiles::new()
                            ->setPath(PATH_ROOT . $section)
                            ->patch($this->getPath() . $section);

                        // All okay!
                        break;

                    } catch (ProcessFailedException $e) {
                        // Fork me, the patch failed! What file? Stash the little forker and retry without, then unstash it
                        // after for manual review / copy
                        Log::warning(tr('Trying to fix by stashing problematic file(s)'));

                        $output = $e->getDataKey('output');
                        $output = Arrays::match($output, 'patch failed', Arrays::MATCH_ALL | Arrays::MATCH_ANYWHERE| Arrays::MATCH_NO_CASE);

                        foreach ($output as $file) {
                            $file = Strings::fromReverse($file, ' ');
                            $file = Strings::untilReverse($file, ':');

                            $stash->add($file);

                            Log::warning(tr('Stashing problematic file ":file"', [':file' => $file]));
                            Git::new(PATH_ROOT)->add($file)->getStash()->stash($file);
                        }
                    }
                }
            }

            if ($checkout) {
                // Checkout files locally in the specified sections so that these changes are removed from the project
                // Clean files locally in the specified sections so that new files are removed from the project
                Git::new(PATH_ROOT)
                    ->checkout($sections)
                    ->clean($sections, true, true);
            }

            if ($stash->getCount()) {
                $bad_files = clone $stash;

                // Whoopsie, we have shirts in stash, meaning some file was naughty.
                foreach ($stash as $key => $file) {
                    Log::warning(tr('Returning problematic file ":file" from stash', [':file' => $file]));
                    Git::new(PATH_ROOT)->getStash()->pop();
                    $stash->delete($key);
                }

                throw PatchPartiallySuccessfulException::new(tr('Phoundating patch was partially successful, some files failed'))
                    ->setData(['files' => $bad_files]);
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
    protected function selectPhoundationBranch(?string $branch): void
    {
        if (!$branch) {
            return;
        }

        // Ensure phoundation is on the right branch
        $this->phoundation_branch = $this->git->getBranch();

        if ($branch !== $this->phoundation_branch) {
            Log::warning(tr('Phoundation is currently on different branch ":current"', [
                ':current'   => $this->phoundation_branch,
            ]), 4);
            Log::action(tr('Switching Phoundation branch to requested branch ":requested"', [
                ':requested' => $branch
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