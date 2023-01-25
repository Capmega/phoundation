<?php

namespace Phoundation\Developer\Phoundation;

use Phoundation\Core\Log;
use Phoundation\Developer\Phoundation\Exception\IsPhoundationException;
use Phoundation\Developer\Phoundation\Exception\NotPhoundationException;
use Phoundation\Developer\Phoundation\Exception\PhoundationNotFoundException;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Versioning\Exception\GitHasChangesException;
use Phoundation\Versioning\Exception\GitHasNoChangesException;
use Phoundation\Versioning\Git\ChangedFiles;
use Phoundation\Versioning\Git\Git;


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
        if ($location) {
            $path = realpath($location);

            if (!$path) {
                throw new FileNotExistException(tr('The specified Phoundation location ":file" does not exist', [
                    ':file' => $location
                ]));
            }

            if (!$this->isPhoundation($path)) {
                throw new NotPhoundationException(tr('The specified Phoundation location ":file" exists but is not a valid Phoundation installation', [
                    ':file' => $location
                ]));
            }

            Log::success(tr('Using Phoundation installation in specified path ":path"', [':path' => $path]));
            $this->path = $path;
            return $path;

        } else {
            // Scan for phoundation installation location.
            foreach (['~/projects', '~/PhpstormProjects', '..', '../..', '/var/www/html/'] as $path) {
                $path = Filesystem::absolute($path);

                // The main phoundation directory should be called either phoundation or Phoundation.
                foreach (['phoundation', 'Phoundation'] as $name) {
                    $path = $path . $name;

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

                    if ($this->path == PATH_ROOT) {
                        throw new IsPhoundationException(tr('This project IS your Phoundation core installation', [
                            ':file' => $location
                        ]));
                    }

                    Log::success(tr('Found Phoundation installation in ":path"', [':path' => $path]));
                    $this->path = $path;
                    return $path;
                }
            }
        }

        throw new PhoundationNotFoundException();
    }



    /**
     * Copies all phoundation updates from your current project back to Phoundation
     *
     * @return int The amount of patches that were copied to Phoundation
     */
    public function copyUpdates(): int
    {
        try {
            // Get git objects for this project and the phoundation project
            $count           = 0;
            $local_git       = Git::new(PATH_ROOT);
            $phoundation_git = Git::new($this->path);

            // Ensure Phoundation has no changes
            if ($phoundation_git->hasChanges()) {
                throw GitHasChangesException::new(tr('Cannot copy changes, your Phoundation installation ":path" has uncommitted changes', [
                    ':path' => $this->path
                ]))->makeWarning();
            }

            // Ensure phoundation is on the right branch
            $phoundation_branch = $phoundation_git->getBranch();
            $phoundation_git->checkout($this->path);

            // Find local changes
            $changed_files = $local_git->getChanges();

            if (!self::hasPhoundationChanges($changed_files)) {
                throw GitHasNoChangesException::new(tr('Cannot copy changes, your project has no Phoundation changes', [
                    ':path' => PATH_ROOT
                ]))->makeWarning();
            }

            // Apply changes on Phoundation
            foreach ($changed_files as $file => $changes) {
                Log::action(tr('Patching file ":file"', [':file' => $file]));

                $phoundation_file = $file;
                $changes->applyPatch($phoundation_file);
            }

            return $changed_files->getCount();

        } catch (GitHasChangesException $e) {
            // Since the operation failed, ensure that Phoundation is back on its original branch
            if (isset($phoundation_git)) {
                if (isset($phoundation_branch)) {
                    $phoundation_git->checkout($phoundation_branch);
                }
            }

            throw $e;
        }
    }



    /**
     * Returns true if the specified filesystem location contains a valid Phoundation installation
     *
     * @param string $location
     * @return bool
     */
    public function isPhoundation(string $location): bool
    {
        // TODO IMPLEMENT
        return true;
    }



    /**
     * Returns true if the specified filesystem location contains a valid Phoundation project installation
     *
     * @param string $location
     * @return bool
     */
    public function isPhoundationProject(string $location): bool
    {
        // TODO IMPLEMENT
        return true;
    }



    /**
     * Returns true if the specified filesystem location contains a valid Phoundation installation
     *
     * @param ChangedFiles $changed_files
     * @return bool
     */
    public function hasPhoundationChanges(ChangedFiles $changed_files): bool
    {
        if (!$changed_files->getCount()) {
            throw GitHasNoChangesException::new(tr('Cannot copy changes, your project has no changes', [
                ':path' => PATH_ROOT
            ]))->makeWarning();
        }

        foreach ($changed_files as $file => $change) {
            // Check the file if its a Phoundation file
        }

        // TODO IMPLEMENT
        return true;
    }
}