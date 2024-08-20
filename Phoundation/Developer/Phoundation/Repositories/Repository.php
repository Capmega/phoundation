<?php

/**
 * Class Repository
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Phoundation\Repositories;

use Phoundation\Core\Core;
use Phoundation\Core\Libraries\Interfaces\LibraryInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Developer\Enums\EnumRepositoryType;
use Phoundation\Developer\Interfaces\VendorInterface;
use Phoundation\Developer\Phoundation\Exception\NotARepositoryException;
use Phoundation\Developer\Phoundation\Repositories\Interfaces\RepositoryInterface;
use Phoundation\Developer\Phoundation\Repositories\Vendors\Interfaces\RepositoryVendorsInterface;
use Phoundation\Developer\Phoundation\Repositories\Vendors\RepositoryVendors;
use Phoundation\Developer\Versioning\Git\Exception\GitPatchFailedException;
use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Utils\Strings;
use Stringable;


class Repository extends FsDirectory implements RepositoryInterface
{
    /**
     * Git command object
     *
     * @var GitInterface $git
     */
    public GitInterface $git;

    /**
     * Tracks the type of this repository
     *
     * @var EnumRepositoryType|null
     */
    protected ?EnumRepositoryType $repository_type;


    /**
     * Repository class constructor
     *
     * @param mixed|null                   $source
     * @param FsRestrictionsInterface|null $restrictions
     * @param Stringable|string|bool|null  $absolute_prefix
     */
    public function __construct(FsDirectoryInterface|Stringable|string $source, ?FsRestrictionsInterface $restrictions = null, Stringable|string|bool|null $absolute_prefix = false)
    {
        parent::__construct($source, $restrictions, $absolute_prefix);
        $this->detectType();
    }


    /**
     * Returns true if this contains a git repository
     *
     * @return bool
     */
    public function hasGit(): bool
    {
        return $this->addDirectory('.git')->exists();
    }


    /**
     * Returns true if this repository is a Phoundation repository
     *
     * @return static
     */
    public function checkRepository(): static
    {
        if ($this->isRepository()) {
            return $this;
        }

        throw new NotARepositoryException(tr('The path ":path" is not a Phoundation repository', [
            ':path' => $this->getSource()
        ]));
    }


    /**
     * Returns true if this repository is a phoundation project
     *
     * @return bool
     */
    public function isCore(): bool
    {
        if (!$this->isReadable() or !$this->hasGit()) {
            return false;
        }

        // The path basename must be "phoundation"
        if ($this->getBasename() !== 'phoundation') {
            return false;
        }

        // All these files and directories must be available.
        $files = [
            'config',
            'data',
            'Phoundation',
            'Plugins',
            'tests',
            'pho',
        ];

        foreach ($files as $file) {
            if (!file_exists($this . $file)) {
                return false;
            }
        }

        // All these files and directories must NOT be available.
        $files = [
            'Templates',
            'config/version',
        ];

        foreach ($files as $file) {
            if (file_exists($this . $file)) {
                return false;
            }
        }

        // The project file must contain "phoundation"
        $project = FsFile::new($this . 'config/project', $this->getRestrictions())->getContentsAsString();
        $project = trim($project);

        if ($project === 'phoundation') {
            return true;
        }

        return false;
    }


    /**
     * Returns true if this repository is a Phoundation repository
     *
     * @return static
     */
    public function checkCoreRepository(): static
    {
        if ($this->isCore()) {
            return $this;
        }

        throw new NotARepositoryException(tr('The path ":path" is not a Phoundation core repository', [
            ':path' => $this->getSource()
        ]));
    }


    /**
     * Returns if this is a Phoundation project, so NOT a repository
     *
     * @return bool
     */
    public function isPhoundationProject(): bool
    {
        if (!$this->isReadable()) {
            return false;
        }

        // All these files and directories must be available.
        $path = $this->getAbsolutePath();
        $files = [
            'config',
            'data',
            'Phoundation',
            'Plugins',
            'tests',
            'pho',
            'Templates',
            'config/project',
            'config/version',
        ];

        foreach ($files as $file) {
            if (!file_exists($path . $file)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Get the value of is_plugin
     *
     * @return bool
     */
    public function isPlugins(): bool
    {
        if (!$this->isReadable() or !$this->hasGit() or $this->isPhoundationProject() or $this->isCore()) {
            return false;
        }

        $path = $this;

        // The path basename must be "phoundation-plugins"
        if ($path->getBasename() !== 'phoundation-plugins') {
            return false;
        }

        // All these files and directories must be available.
        $path  = $path->getAbsolutePath();
        $files = ['README.md'];

        foreach ($files as $file) {
            if (!file_exists($path . $file)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Returns true if this repository is a Phoundation Plugin repository
     *
     * @return static
     */
    public function checkPluginsRepository(): static
    {
        if ($this->isPlugins()) {
            return $this;
        }

        throw new NotARepositoryException(tr('The path ":path" is not a Phoundation plugins repository', [
            ':path' => $this->getSource()
        ]));
    }


    /**
     * Get the value of is_template
     *
     * @return bool
     */
    public function isTemplates(): bool
    {
        if (!$this->isReadable() or !$this->hasGit() or $this->isPhoundationProject() or $this->isCore()) {
            return false;
        }

        // The path basename must be "phoundation-templates"
        if ($this->getBasename() !== 'phoundation-templates') {
            return false;
        }

        return true;
    }


    /**
     * Get the value of is_template
     *
     * @return bool
     */
    public function isData(): bool
    {
        if (!$this->isReadable() or !$this->hasGit() or $this->isPhoundationProject() or $this->isCore()) {
            return false;
        }

        // The path basename must be "phoundation-templates"
        if ($this->getBasename() !== 'phoundation-data') {
            return false;
        }

        return true;
    }


    /**
     * Returns true if this repository is a Phoundation repository
     *
     * @return static
     */
    public function checkDataRepository(): static
    {
        if ($this->isData()) {
            return $this;
        }

        throw new NotARepositoryException(tr('The path ":path" is not a Phoundation data repository', [
            ':path' => $this->getSource()
        ]));
    }


    /**
     * Returns true if the repository should have vendors
     *
     * @return bool
     */
    public function isVendorsRepository(): bool
    {
        if (!$this->isReadable() or !$this->hasGit() or $this->isPhoundationProject() or $this->isCore()) {
            return false;
        }

        $path = $this;

        // The path basename must be "phoundation-templates"
        if (!$this->isTemplates()) {
            // Not a templates repository, maybe a plugins repository?
            if (!$this->isPlugins()) {
                // Not a templates repository, not a plugins repository, maybe a data repository?
                return $this->isData();
            }
        }

        return true;
    }


    /**
     * Returns true if this repository is a Phoundation repository
     *
     * @return static
     */
    public function checkVendorRepository(): static
    {
        if ($this->isVendorsRepository()) {
            return $this;
        }

        throw new NotARepositoryException(tr('The path ":path" is not a vendor type repository', [
            ':path' => $this->getSource()
        ]));
    }


    /**
     * Returns an automatically generated name of the repository
     *
     * @return string
     */
    public function getName(): string
    {
        $this->checkRepository();

        return basename(dirname($this->getSource()));
    }


    /**
     * Returns the type of Phoundation repository
     *
     * @return EnumRepositoryType|null
     */
    public function getRepositoryType(): ?EnumRepositoryType
    {
        return $this->repository_type;
    }


    /**
     * Returns true if this repository is of the specified type
     *
     * @param EnumRepositoryType $repository_type
     * @return bool
     */
    public function isRepositoryType(EnumRepositoryType $repository_type): bool
    {
        return $this->repository_type === $repository_type;
    }


    /**
     * Detects and returns the type of this repository
     *
     * @return EnumRepositoryType|null
     */
    protected function detectType(): ?EnumRepositoryType
    {
        if ($this->exists()) {
            $this->git = new Git($this);

            if ($this->isCore()) {
                $this->repository_type = EnumRepositoryType::core;

            } elseif ($this->isData()) {
                $this->repository_type = EnumRepositoryType::data;

            } elseif ($this->isPlugins()) {
                $this->repository_type = EnumRepositoryType::plugins;

            } elseif ($this->isTemplates()) {
                $this->repository_type = EnumRepositoryType::templates;

            } else {
                $this->repository_type = null;
            }

        } else {
            $this->repository_type = null;
        }

        return $this->getRepositoryType();
    }


    /**
     * Returns true if this repository is a Phoundation repository
     *
     * @return bool
     */
    public function isRepository(): bool
    {
        return $this->repository_type !== null;
    }


    /**
     * If this repository is a Plugins repository, this method will return the found vendors
     *
     * @return RepositoryVendorsInterface
     */
    public function getVendors(): RepositoryVendorsInterface
    {
        // Only Plugins or Templates repositories have vendors
        $this->checkVendorRepository();

        // Return Plugin vendors, can be either "Templates" or "Plugins" or "data"
        return new RepositoryVendors($this);
    }


    /**
     * Returns the vendor with the specified identifier
     *
     * @param string $identifier
     * @param bool   $exception
     *
     * @return VendorInterface
     */
    public function getVendor(string $identifier, bool $exception = true): VendorInterface
    {
        // Only Plugins or Templates repositories have vendors
        $this->checkVendorRepository();

        // Return the requested vendor
        return $this->getVendors()->get($identifier, $exception);
    }


    /**
     * Returns true if this repository has the specified vendor
     *
     * @param string $identifier
     *
     * @return bool
     */
    public function hasVendor(string $identifier): bool
    {
        // Only Plugins or Templates repositories have vendors
        $this->checkVendorRepository();

        // Return Plugin vendors, can be either "Templates" or "Plugins" or "data"
        foreach ($this->getVendors() as $o_vendor) {
            if ($o_vendor->getIdentifier() === $identifier) {
                return true;
            }
        }

        return false;
    }


    /**
     * If this repository is a Plugins repository, this method will return the found plugins / libraries
     *
     * @param IteratorInterface|array|null $vendors
     *
     * @return LibraryInterface
     */
    public function getLibraries(IteratorInterface|array|null $vendors = null): LibraryInterface
    {
        throw new UnderConstructionException();
    }


    /**
     * Returns true if this repository has the specified branch
     *
     * @param string $branch
     * @return bool
     */
    public function hasBranch(string $branch): bool
    {
        return $this->git->hasBranch($branch);
    }


    /**
     * Sets the branch for this repository to the specified branch name
     *
     * @param string $branch
     * @return static
     */
    public function setBranch(string $branch): static
    {
        $this->git->setBranch($branch);
        return $this;
    }


    /**
     * Tries to apply relevant repository patches to this repository
     *
     * @param VendorInterface   $o_vendor
     * @param IteratorInterface $stash
     *
     * @return static
     */
    public function patch(VendorInterface $o_vendor, IteratorInterface $stash): static
    {
        try {
            // Add all paths to index to ensure all will be patched correctly, then create the patch file, then apply
            // it, then delete it, then we're done!
            $o_git        = Git::new($this)->add();
            $o_patch_file = $o_vendor->getChangedFiles()->getPatchFile();

            $o_git->reset('HEAD')
                  ->apply($o_patch_file);

            $o_patch_file->delete();

            return $this;

        } catch (ProcessFailedException $e) {
            Log::warning(tr('Patch failed to apply for repository ":directory" with following exception', [
                ':directory' => $this,
            ]));

            Log::warning($e->getMessages());
            Log::warning($e->getDataKey('output'));

            if (isset($o_git) and isset($o_patch_file)) {
                // There is a patch file, so we have a git process
                // Delete the temporary patch file
                Core::ExecuteIfNotInTestMode(function () use ($o_patch_file) {
                    Log::action(tr('Removing patch file ":file"', [':file' => $o_patch_file]));
                    $o_patch_file->delete();
                }, tr('Removing git patch file'));

                foreach ($e->getDataKey('output') as $line) {
                    if (str_contains($line, 'patch does not apply')) {
                        $files[] = Strings::cut($line, 'error: ', ': patch does not apply');
                    }

                    if (str_ends_with($line, ': No such file or directory')) {
                        $files[] = Strings::cut($line, 'error: ', ': No such file or directory');
                    }
                }

                if (isset($files)) {
                    // Specific files failed to apply
                    Log::warning(tr('Trying to fix by stashing ":count" problematic file(s) ":files"', [
                        ':count' => count($files),
                        ':files' => $files,
                    ]));

                    // Add all files to index before stashing, except deleted files.
                    foreach ($files as $file) {
                        $stash->add($file);

                        // Deleted files cannot be stashed after being added, un-add, and then stash
                        if (FsFile::new($file)->exists()) {
                            $o_git->add($file);

                        } else {
                            // Ensure it's not added yet
                            $o_git->reset('HEAD', $file);
                        }
                    }

                    // Stash all problematic files (auto un-stash later)
                    $o_git->getStashObject()->stash($files);

                    throw GitPatchFailedException::new(tr('Failed to apply patch ":patch" to directory ":directory"', [
                        ':patch'     => isset_get($o_patch_file),
                        ':directory' => $this,
                    ]), $e)->addData([
                        'files' => $files,
                    ]);
                }
            }

            // We have a different git failure
            throw $e;
        }
    }
}
