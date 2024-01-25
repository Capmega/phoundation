<?php

declare(strict_types=1);

namespace Phoundation\Developer\Phoundation;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Iterator;
use Phoundation\Developer\Phoundation\Exception\IsPhoundationException;
use Phoundation\Developer\Phoundation\Exception\NotPhoundationException;
use Phoundation\Developer\Phoundation\Exception\PatchPartiallySuccessfulException;
use Phoundation\Developer\Phoundation\Exception\PhoundationNotFoundException;
use Phoundation\Developer\Project\Project;
use Phoundation\Developer\Versioning\Git\Exception\GitHasChangesException;
use Phoundation\Developer\Versioning\Git\Exception\GitPatchException;
use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Developer\Versioning\Git\StatusFiles;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Processes\Commands\Cp;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Phoundation\Utils\Utils;


/**
 * Class Plugins
 *
 * This is one specific project: The Phoundation core project itself.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Plugins extends Project
{
    /**
     * The branch on which the Phoundation project is
     *
     * @var string|null $branch
     */
    protected ?string $branch = null;


    /**
     * Plugins constructor
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
     * @return string
     */
    public function detectLocation(?string $location = null): string
    {
        // Paths (in order) which will be scanned for Phoundation plugins installations
        $directories = [
            '~/projects',
            '~/PhpstormProjects',
            '..',
            '../..',
            '/var/www/html/'
        ];

        if ($location) {
            $directory = realpath($location);
            $this->restrictions = Restrictions::new(dirname($directory));

            if (!$directory) {
                throw new FileNotExistException(tr('The specified Phoundation plugins location ":file" does not exist', [
                    ':file' => $location
                ]));
            }

            if (!$this->isPhoundationProject($directory)) {
                // This is not a Phoundation type project directory
                throw new NotPhoundationException(tr('The specified Phoundation plugins location ":file" exists but is not a Phoundation project', [
                    ':directory' => $directory
                ]));
            }

            if (!$this->isPhoundation($directory)) {
                throw new NotPhoundationException(tr('The specified Phoundation plugins location ":file" exists but is not a Phoundation core installation', [
                    ':file' => $location
                ]));
            }

            Log::success(tr('Using Phoundation plugins installation in specified directory ":directory"', [
                ':directory' => $directory
            ]));

            $this->directory = $directory;
            return $directory;

        }

        // Scan for phoundation installation location.
        foreach ($directories as $directory) {
            try {
                $directory = Filesystem::absolute($directory);

            } catch (FileNotExistException) {
                // Okay, that was easy, doesn't exist. NEXT!
                continue;
            }

            $names = [
                'phoundation-plugins',
                'Phoundation-plugins',
                'phoundation/phoundation-plugins',
                'Phoundation/phoundation-plugins',
                'Phoundation/Phoundation-plugins',
                'phoundation/Phoundation-plugins'
            ];

            // The main phoundation directory should be called either phoundation or Phoundation.
            foreach ($names as $name) {
                $test_path = $directory . $name . '/';
                $this->restrictions = Restrictions::new(dirname($test_path));

                if (!file_exists($test_path)) {
                    Log::warning(tr('Ignoring directory ":directory", it does not exist', [
                        ':directory' => $test_path,
                    ]), 2);

                    continue;
                }

                if (!$this->isPhoundationPlugins($test_path)) {
                    // This is not a Phoundation plugins directory
                    Log::warning(tr('Ignoring directory ":directory", it has the name ":name" but is not a Phoundation project', [
                        ':directory' => $test_path,
                        ':name' => $name
                    ]));

                    continue;
                }

                Log::success(tr('Found Phoundation plugins installation in ":directory"', [
                    ':directory' => $test_path
                ]));

                $this->directory = $test_path;
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
     * Returns either the specified branch or the current Phoundation branch as default
     *
     * @param string|null $branch
     * @return string
     */
    protected function defaultBranch(?string $branch): string
    {
        if (!$branch) {
            // Select the current branch
            $branch = $this->git->getBranch();

            Log::notice(tr('Trying to patch updates on Phoundation using current project branch ":branch"', [
                ':branch' => $branch
            ]));
        }

        return $branch;
    }


    /**
     * Copies only the specified file back to Phoundation
     *
     * @param string $file
     * @param string|null $branch
     * @param bool $require_no_changes
     * @return void
     */
    public function copy(string $file, ?string $branch, bool $require_no_changes = true): void
    {
        $this->selectPluginsBranch($this->defaultBranch($branch));
        $this->ensureNoChanges(!$require_no_changes);

        $source = Filesystem::absolute($file, DIRECTORY_ROOT);
        $file = Strings::from($source, DIRECTORY_ROOT);

        if (!file_exists($source)) {
            throw new FileNotExistException(tr('The specified file ":file" does not exist', [
                ':file' => $file
            ]));
        }

        Cp::new()->archive($source, Restrictions::new(DIRECTORY_ROOT), $this->getDirectory() . $file, Restrictions::new($this->getDirectory(), true));
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
        if ($sign === null) {
            $sign = Config::getBoolean('developer.phoundation.patch.sign', true);
        }

        $branch = $this->defaultBranch($branch);

        Log::action(tr('Patching branch ":branch" on your local Phoundation plugins repository from this project', [
            ':branch' => $branch
        ]));

        // Reset local project to HEAD and update
        Project::new()->resetHead();
        Project::new()->updateLocalProjectPlugins($branch, $message, $sign);

        // Detect Phoundation plugins installation and ensure its clean and on the right branch
        $this->selectPluginsBranch($branch)->ensureNoChanges();

        try {
            // Execute the patching, first stash all libraries that are not in the official Phoundation Plugins list
            $stash                 = new Iterator();
            $sections              = ['Plugins'];
            $non_phoundation_stash = $this->stashNonPhoundationPlugins();

            foreach ($sections as $section) {
                // Patch phoundation target section and remove the changes locally
                while (true) {
                    try {
                        StatusFiles::new()
                            ->setDirectory(DIRECTORY_ROOT . $section)
                            ->patch($this->getDirectory() . $section);

                        // All okay!
                        break;

                    } catch (ProcessFailedException $e) {
                        // Fork me, the patch failed! What file? Stash the little forker and retry without, then
                        // un-stash it after for manual review / copy
                        $output = $e->getDataKey('output');
                        $output = Arrays::getMatches($output, 'patch failed', Utils::MATCH_ALL|Utils::MATCH_ANYWHERE|Utils::MATCH_NO_CASE);
                        $git    = Git::new(DIRECTORY_ROOT);

                        if ($output) {
                            Log::warning(tr('Trying to fix by stashing ":count" problematic file(s) ":files"', [
                                ':count' => count($output),
                                ':files' => $output
                            ]));

                            foreach ($output as $file) {
                                $file = Strings::fromReverse($file, ' ');
                                $file = Strings::untilReverse($file, ':');

                                $stash->add($file);

                                Log::warning(tr('Stashing problematic file ":file"', [':file' => $file]));
                                // Deleted files cannot be stashed after being added!
                                if (File::new($file)->exists()) {
                                    $git->add($file)->getStash()->stash($file);

                                } else {
                                    $git->reset('HEAD', $file)->getStash()->stash($file);
                                }
                            }

                        } else {
                            // There are no problematic files found, look for other issues.
                            $output = $e->getDataKey('output');
                            $output = Arrays::getMatches($output, 'already exists in working directory', Utils::MATCH_ALL|Utils::MATCH_ANYWHERE|Utils::MATCH_NO_CASE);

                            if ($output) {
                                // Found already existing files that cannot be merged. Delete on this side
                                foreach ($output as $file) {
                                    $file = Strings::untilReverse($file, ':');
                                    $file = Strings::from($file, ':');
                                    $file = trim($file);
                                    $git  = Git::new(DIRECTORY_ROOT);

                                    Log::warning(tr('Stashing already existing and unmergeable file ":file"', [
                                        ':file' => $file
                                    ]));

                                    $git->add($file)->getStash()->stash($file);
                                }
                            } else {
                                // Other unknown error
                                throw new GitPatchException(tr('Encountered unknown patch exception'), $e);
                            }
                        }
                    }
                }
            }

            if ($checkout) {
                // Checkout files locally in the specified sections so that these changes are removed from the project
                // Clean files locally in the specified sections so that new files are removed from the project
                Git::new(DIRECTORY_ROOT)
                    ->checkout($sections)
                    ->clean($sections, true, true);
            }

            if ($stash->getCount()) {
                $bad_files = clone $stash;

                // Whoopsie, we have shirts in stash, meaning some file was naughty.
                foreach ($stash as $key => $file) {
                    Log::warning(tr('Returning problematic file ":file" from stash', [':file' => $file]));
                    Git::new(DIRECTORY_ROOT)->getStash()->pop();
                    $stash->delete($key);
                }

                throw PatchPartiallySuccessfulException::new(tr('Phoundation patch was partially successful, some files failed'))
                    ->addData(['files' => $bad_files]);
            }

            if ($non_phoundation_stash) {
                // We have non Phoundation plugins in stash, pop those too
                Log::warning(tr('Unstashing non phoundation plugins ":plugins"', [
                    ':plugins' => array_keys($non_phoundation_stash)
                ]));

                Git::new(DIRECTORY_ROOT)->getStash()->pop();
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
     * Returns an array with plugin > path
     *
     * @param array $plugins
     * @return array
     */
    protected function addPluginPaths(array $plugins): array
    {
        $return = [];

        foreach ($plugins as $plugin) {
            $return[$plugin] = Directory::new(DIRECTORY_ROOT . 'Plugins/' . $plugin)->getPath();
        }

        return $return;
    }


    /**
     * Stashes those libraries that are not in the official Phoundation repository so that they don't get copied
     *
     * @return array|null
     */
    protected function stashNonPhoundationPlugins(): ?array
    {
        $non_phoundation_plugins = $this->getNonPhoundationPlugins();
        $non_phoundation_plugins = $this->addPluginPaths($non_phoundation_plugins);

        if ($non_phoundation_plugins) {
            Log::warning(tr('Stashing non phoundation plugins ":plugins"', [
                ':plugins' => array_keys($non_phoundation_plugins)
            ]));

            $this->git->getStash()->stash($non_phoundation_plugins);
        }

        return $non_phoundation_plugins;
    }


    /**
     * Returns a list of Plugins that are not part of the Phoundation repository
     *
     * @return array
     */
    protected function getNonPhoundationPlugins(): array
    {
        $plugins = array_diff($this->getLocalPlugins(), $this->getPhoundationPlugins());

        foreach ($plugins as $id => &$plugin) {
            $plugin = Strings::endsNotWith($plugin, '/');
        }

        unset($plugin);

        // The "Phoundation" plugin should NEVER be copied to the official repository!
        if (!in_array('Phoundation', $plugins)) {
            $plugins[] = 'Phoundation';
        }

        return $plugins;
    }


    /**
     * Returns a list of all local Plugins
     *
     * @return array
     */
    protected function getLocalPlugins(): array
    {
        return Directory::new(DIRECTORY_ROOT . 'Plugins/', DIRECTORY_ROOT . 'Plugins/')->scan();
    }


    /**
     * Returns a list of all plugins that are part of the Phoundation repository
     *
     * @return array
     */
    protected function getPhoundationPlugins(): array
    {
        return Directory::new($this->directory . 'Plugins/', $this->directory)->scan();
    }


    /**
     * Returns true if the specified filesystem location contains a valid Phoundation plugins installation
     *
     * @todo TODO Update to use git remote show origin!
     * @param string $directory
     * @return bool
     */
    public function isPhoundationPlugins(string $directory): bool
    {
        return Directory::new($directory . 'Plugins', $this->restrictions)->isReadable();
    }


    /**
     * Ensures that the Phoundation plugins installation has no changes
     *
     * @param bool $force
     * @return static
     */
    protected function ensureNoChanges(bool $force = false): static
    {
        // Ensure Phoundation has no changes
        if ($this->git->hasChanges()) {
            if (!$force) {
                throw GitHasChangesException::new(tr('Cannot copy changes, your Phoundation plugins installation ":directory" has uncommitted changes', [
                    ':directory' => $this->directory
                ]))->makeWarning();
            }
        }

        return $this;
    }


    /**
     * Ensure that Phoundation is on the specified branch
     *
     * @param string|null $branch
     * @return static
     */
    protected function selectPluginsBranch(?string $branch): static
    {
        if (!$branch) {
            return $this;
        }

        // Ensure phoundation is on the right branch
        $this->phoundation_branch = $this->git->getBranch();

        if ($branch !== $this->phoundation_branch) {
            Log::warning(tr('Phoundation plugins is currently on different branch ":current"', [
                ':current'   => $this->phoundation_branch,
            ]), 4);
            Log::action(tr('Switching Phoundation plugins branch to requested branch ":requested"', [
                ':requested' => $branch
            ]), 5);

            $this->git->checkout($branch);
        }

        return $this;
    }


    /**
     * Apply patches from the local project to phoundation
     *
     * @return int
     */
    protected function updateTo(): int
    {
        $count = 0;

        foreach ($this->phoundation_files as $directory) {
            $directory = $this->git->getDirectory() . $directory;

            // Find local Phoundation changes and filter Phoundation changes only
            $changed_files = $this->git->getStatus($directory);

            if (!$changed_files->getCount()) {
                Log::notice(tr('Not patching directory ":directory", it has no changes', [
                    ':directory' => $directory
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
