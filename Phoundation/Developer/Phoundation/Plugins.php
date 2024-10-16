<?php

/**
 * Class Plugins
 *
 * This represents the list of Plugins found in the ROOT/Plugins directory.
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
use Phoundation\Developer\Phoundation\Exception\NotPluginsException;
use Phoundation\Developer\Phoundation\Exception\PhoundationPluginsNotFoundException;
use Phoundation\Developer\Project\Project;
use Phoundation\Developer\Versioning\Git\Exception\GitHasChangesException;
use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\FsPath;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Os\Processes\Commands\Cp;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;


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
     *
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
            '/var/www/html/',
        ];

        if ($location) {
            $directory          = realpath($location);
            $this->restrictions = FsRestrictions::new(dirname($directory));

            if (!$directory) {
                throw new FileNotExistException(tr('The specified Phoundation plugins location ":file" does not exist', [
                    ':file' => $location,
                ]));
            }

            if (!$this->isPhoundationPlugins($directory)) {
                // This is not a Phoundation plugins directory
                throw new NotPluginsException(tr('The specified Phoundation plugins location ":file" exists but is not a Phoundation plugins project', [
                    ':directory' => $directory,
                ]));
            }

            Log::success(tr('Using Phoundation plugins installation in specified directory ":directory"', [
                ':directory' => $directory,
            ]));

            $this->directory = new FsDirectory($directory);

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
                'phoundation-plugins',
                'Phoundation-plugins',
                'phoundation/phoundation-plugins',
                'Phoundation/phoundation-plugins',
                'Phoundation/Phoundation-plugins',
                'phoundation/Phoundation-plugins',
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

                if (!$this->isPhoundationPlugins($test_path)) {
                    // This is not a Phoundation plugins directory
                    Log::warning(tr('Ignoring directory ":directory", it has the name ":name" but is not a Phoundation project', [
                        ':directory' => $test_path,
                        ':name'      => $name,
                    ]), 4);
                    continue;
                }

                Log::success(tr('Found Phoundation plugins installation in ":directory"', [
                    ':directory' => $test_path,
                ]));

                $this->directory = new FsDirectory($test_path);

                return $test_path;
            }
        }

        throw new PhoundationPluginsNotFoundException();
    }


    /**
     * Returns true if the specified filesystem location contains a valid Phoundation plugins installation
     *
     * @param string $directory
     *
     * @return bool
     * @todo TODO Update to use git remote show origin!
     */
    public function isPhoundationPlugins(string $directory): bool
    {
        return FsDirectory::new($directory . 'Plugins', $this->restrictions)->isReadable();
    }


    /**
     * Switch the Phoundation project to the specified branch
     *
     * @param string|null $branch
     *
     * @return static
     */
    public function switchBranch(?string $branch = null): static
    {
        if ($branch === null) {
            // This will cause a switch back to the previous branch, selected in this same process
            if (!$this->branch) {
                throw new OutOfBoundsException(tr('Cannot switch back to previous branch, no previous branch available'));
            }

            // Select the previous branch and reset it
            Log::action(tr('Switching Phoundation plugins back to branch ":branch"', [
                ':branch' => $branch,
            ]), 3);

            $this->git->setBranch($this->branch);
            $this->branch = null;

        } else {
            // Select the previous branch and reset it
            Log::action(tr('Switching Phoundation plugins to branch ":branch"', [
                ':branch' => $branch,
            ]), 4);

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
        $this->selectPluginsBranch($this->defaultBranch($branch));
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

            if (!in_array($test, $plugins)) {
                // Any non-plugin files should be copied to Phoundation!
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

            Cp::new()->archive($file, FsRestrictions::new(DIRECTORY_ROOT), $this->getDirectory() . $target, FsRestrictions::new($this->getDirectory(), true));
        }
    }


    /**
     * Ensure that Phoundation is on the specified branch
     *
     * @param string|null $branch
     *
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
                ':current' => $this->phoundation_branch,
            ]), 4);
            Log::action(tr('Switching Phoundation plugins branch to requested branch ":requested"', [
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
     * Ensures that the Phoundation plugins installation has no changes
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
                throw GitHasChangesException::new(tr('Cannot copy changes, your Phoundation plugins installation ":directory" has uncommitted changes', [
                    ':directory' => $this->directory,
                ]))->makeWarning();
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

        Log::action(tr('Patching branch ":branch" on your local Phoundation plugins repository from this project', [
            ':branch' => $branch,
        ]));

        // Reset the local project to HEAD and update
        $project->resetHead();

        if ($update) {
            $project->updateLocalProjectPlugins($branch, $message, $sign);
        }

        // Detect Phoundation plugins installation and ensure its clean and on the right branch
        $this->selectPluginsBranch($branch)
             ->ensureNoChanges();

        try {
            // Execute the patching, first stash all libraries that are not in the official Phoundation Plugins list
            $non_phoundation_stash = $this->stashNonPhoundationPlugins();
            $stash                 = new Iterator();
            $sections              = [
                'Plugins',
                'Templates',
            ];

            static::patchSections($sections, $stash, $checkout);

            if ($non_phoundation_stash) {
                // We have non Phoundation plugins in stash, pop those too
                Log::warning(tr('Un-stashing non phoundation plugins'));
                Git::new(DIRECTORY_ROOT)
                   ->getStashObject()
                   ->pop();
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
     * Stashes those libraries that are not in the official Phoundation repository so that they don't get copied
     *
     * @return bool
     */
    public function stashNonPhoundationPlugins(): bool
    {
        $pre_stash_count  = 0;
        $post_stash_count = 0;

        $non_phoundation_plugins = $this->getNonPhoundationPlugins();
        $non_phoundation_plugins = $this->filterNonGitPlugins($non_phoundation_plugins);
        $non_phoundation_plugins = $this->addPluginPaths($non_phoundation_plugins);

        if ($non_phoundation_plugins) {
            Log::warning(tr('Stashing non phoundation plugins ":plugins"', [
                ':plugins' => implode(', ', array_keys($non_phoundation_plugins)),
            ]));

            $pre_stash_count  = Git::new(DIRECTORY_ROOT)
                                   ->getStashObject()
                                   ->getList()
                                   ->getCount();

            $post_stash_count = Git::new(DIRECTORY_ROOT)
                                   ->getStashObject()
                                   ->stash($non_phoundation_plugins)
                                   ->getList()
                                   ->getCount();
        }

        return (bool) ($post_stash_count - $pre_stash_count);
    }


    /**
     * Returns a list of Plugins that are not part of the Phoundation repository
     *
     * @return array
     */
    public function getNonPhoundationPlugins(): array
    {
        $plugins = $this->getLocalPlugins()->diff($this->getPhoundationPlugins());
        $return  = [];
        $skip    = [
            'Phoundation',
            'disabled',
        ];

        // All the plugins must contain files, or git stash will fail
        foreach ($plugins as $plugin) {
            if (in_array($plugin, $skip)) {
                // These are DEFINITELY not non-phoundation plugins
                continue;
            }

            if (FsDirectory::new(DIRECTORY_ROOT . 'Plugins/' . $plugin)->containFiles()) {
                $return[] = $plugin;

            } else {
                Log::warning(tr('Ignoring plugin ":plugin" because it contains no files', [
                    ':plugin' => $plugin,
                ]));
            }
        }

        return $return;
    }


    /**
     * Returns a list of all local Plugins
     *
     * @return IteratorInterface
     */
    public function getLocalPlugins(): IteratorInterface
    {
        return FsDirectory::new(DIRECTORY_ROOT . 'Plugins/', FsRestrictions::newRoot(false, 'Plugins/'))
                          ->scan()
                          ->eachField(function (&$value, $key) {
                              $value = Strings::ensureEndsNotWith($value, '/');
                          });
    }


    /**
     * Returns a list of all plugins that are part of the Phoundation repository
     *
     * @return IteratorInterface
     */
    public function getPhoundationPlugins(): IteratorInterface
    {
        return FsDirectory::new($this->directory . 'Plugins/', $this->directory->getRestrictions())
                          ->scan()
                          ->eachField(function (&$value, $key) {
                              $value = Strings::ensureEndsNotWith($value, '/');
                          });
    }


    /**
     * Filters plugins from the specified list that are not tracked by git
     *
     * @param array $phoundation_plugins
     *
     * @return array
     */
    protected function filterNonGitPlugins(array $phoundation_plugins): array
    {
        $paths = $this->git
                      ->getStatusFilesObject(
                          FsDirectory::new(DIRECTORY_ROOT . 'Plugins/',
                          FsRestrictions::newRoot(false, 'Plugins/')
                      ));

        foreach ($paths as $path => $info) {
            $path = Strings::from($path, 'Plugins/');
            $file = Strings::from($path, '/');
            $path = Strings::until($path, '/');

            // If it's a file within a tracked plugin, that's file
            if (!$file) {
                if (!$info->getStatus()->isTracked()) {
                    // This Plugin isn't tracked yet, ensure its removed!
                    $phoundation_plugins = Arrays::removeValues($phoundation_plugins, $path);
                }
            }
        }

        return $phoundation_plugins;
    }


    /**
     * Returns an array with plugin > path
     *
     * @param array $plugins
     *
     * @return array
     */
    protected function addPluginPaths(array $plugins): array
    {
        $return = [];

        foreach ($plugins as $plugin) {
            $return[$plugin] = FsDirectory::new(DIRECTORY_ROOT . 'Plugins/' . $plugin)->getSource();
        }

        return $return;
    }


    /**
     * Apply patches from the local project to phoundation
     *
     * @return int
     */
    protected function updateTo(): int
    {
throw new UnderConstructionException(tr('Plugins::updateTo() is under construction, not sure what it is supposed to do'));
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
