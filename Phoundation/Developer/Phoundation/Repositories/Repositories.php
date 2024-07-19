<?php

/**
 * Class Repositories
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

use PDOStatement;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\IteratorCore;
use Phoundation\Data\Traits\TraitNewSource;
use Phoundation\Developer\Enums\EnumRepositoryType;
use Phoundation\Developer\Interfaces\VendorInterface;
use Phoundation\Developer\Phoundation\Exception\RepositoryNotFoundException;
use Phoundation\Developer\Phoundation\Repositories\Interfaces\RepositoriesInterface;
use Phoundation\Developer\Phoundation\Repositories\Interfaces\RepositoryInterface;
use Phoundation\Developer\Phoundation\Repositories\Vendors\Interfaces\RepositoryVendorsInterface;
use Phoundation\Developer\Phoundation\Repositories\Vendors\RepositoryVendors;
use Phoundation\Developer\Project\Interfaces\ProjectInterface;
use Phoundation\Developer\Project\Project;
use Phoundation\Developer\Traits\TraitDataProject;
use Phoundation\Developer\Versioning\Git\Exception\BranchNotAvailableException;
use Phoundation\Developer\Versioning\Git\Traits\TraitDataBranch;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Exception\FileNotWritableException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Utils\Config;
use Stringable;

class Repositories extends IteratorCore implements RepositoriesInterface
{
    use TraitDataProject;
    use TraitNewSource {
        __construct as protected ___construct;
    }
    use TraitDataBranch {
        setBranch as protected __setBranch;
    }


    /**
     * Tracks if changes should be copied if patching failed
     *
     * @var bool $copy
     */
    protected bool $copy = true;

    /**
     * Tracks if core repository should be updated / patched
     *
     * @var bool $patch_core
     */
    protected bool $patch_core = true;

    /**
     * Tracks if core repository should be updated / patched
     *
     * @var bool $patch_plugins
     */
    protected bool $patch_plugins = true;

    /**
     * Tracks if core repository should be updated / patched
     *
     * @var bool $patch_templates
     */
    protected bool $patch_templates = true;

    /**
     * Tracks if patches should be forced with a simple copy if applying a git diff failed
     *
     * @var bool $patch_forced_copy
     */
    protected bool $patch_forced_copy = true;

    /**
     * Checkout all patched files after the patch was applied successfully
     *
     * @var bool $patch_checkout
     */
    protected bool $patch_checkout = false;

    /**
     * Tracks if patching or copying permits target repositories to have uncommitted changes before the action
     *
     * @var bool $allow_changes
     */
    protected bool $allow_changes = false;

    /**
     * Tracks the paths that have been scanned
     *
     * @var IteratorInterface $scanned_paths
     */
    protected IteratorInterface $scanned_paths;


    /**
     * Repositories class constructor
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null, ?ProjectInterface $project = null)
    {
        $this->___construct($source);
        $this->project = $project ?? new Project();
    }


    /**
     * Use the specified project
     *
     * @param ProjectInterface|null $project
     *
     * @return $this
     */
    public function setProject(?ProjectInterface $project): static
    {
        $this->project = $project;
        return $this;
    }


    /**
     * Returns an Iterator object with the scanned paths
     *
     * @return IteratorInterface
     */
    public function getScannedPaths(): IteratorInterface
    {
        if (empty($this->scanned_paths)) {
            $this->scanned_paths = new Iterator();
        }

        return $this->scanned_paths;
    }


    /**
     * Returns true if the core repository will be patched, false if not
     *
     * @return bool
     */
    public function getPatchCore(): bool
    {
        return $this->patch_core;
    }


    /**
     * Sets if the core repository is patched, or not
     *
     * @param bool $patch_core
     *
     * @return static
     */
    public function setPatchCore(bool $patch_core): static
    {
        $this->patch_core = $patch_core;
        return $this;
    }


    /**
     * Returns if patching or copying will permit target repositories to have uncommitted changes before the action
     *
     * @return bool
     */
    public function getAllowChanges(): bool
    {
        return $this->allow_changes;
    }


    /**
     * Sets if patching or copying will permit target repositories to have uncommitted changes before the action
     *
     * @param bool $allow_changes
     *
     * @return static
     */
    public function setAllowChanges(bool $allow_changes): static
    {
        $this->allow_changes = $allow_changes;
        return $this;
    }


    /**
     * Returns true if the plugins repository will be patched, false if not
     *
     * @return bool
     */
    public function getPatchPlugins(): bool
    {
        return $this->patch_plugins;
    }


    /**
     * Sets if the plugins repository will be patched, or not
     *
     * @param bool $patch_plugins
     *
     * @return static
     */
    public function setPatchPlugins(bool $patch_plugins): static
    {
        $this->patch_plugins = $patch_plugins;
        return $this;
    }


    /**
     * Returns true if the templates repository will be patched, false if not
     *
     * @return bool
     */
    public function getPatchTemplates(): bool
    {
        return $this->patch_templates;
    }


    /**
     * Sets if the templates repository will be patched, or not
     *
     * @param bool $patch_templates
     *
     * @return static
     */
    public function setPatchTemplates(bool $patch_templates): static
    {
        $this->patch_templates = $patch_templates;
        return $this;
    }


    /**
     * Checkout all patched files after the patch was applied successfully
     *
     * @return bool
     */
    public function getPatchCheckout(): bool
    {
        return $this->patch_checkout;
    }


    /**
     * Checkout all patched files after the patch was applied successfully
     *
     * @param bool $patch_checkout
     *
     * @return static
     */
    public function setPatchCheckout(bool $patch_checkout): static
    {
        $this->patch_checkout = $patch_checkout;
        return $this;
    }



    /**
     * Returns if patches should be forced with a simple copy if applying a git diff failed
     *
     * @return bool
     */
    public function getPatchForcedCopy(): bool
    {
        return $this->patch_forced_copy;
    }


    /**
     * Sets if patches should be forced with a simple copy if applying a git diff failed
     *
     * @param bool $patch_forced_copy
     *
     * @return static
     */
    public function setPatchCopy(bool $patch_forced_copy): static
    {
        $this->patch_forced_copy = $patch_forced_copy;
        return $this;
    }


    /**
     * Sets the branch for all the repositories in this list
     *
     * @param string|null $branch
     *
     * @return $this
     */
    public function setBranch(?string $branch): static
    {
        if (!$branch) {
            $branch = $this->project->getBranch();
        }

        // First check that all repositories have the requested branch available.
        foreach ($this->source as $repository) {
            if (!$repository->hasBranch($branch)) {
                throw new BranchNotAvailableException(tr('Cannot switch repository ":repository" to branch ":branch", that branch does not exist in that repository', [
                    ':repository' => $repository->getName(),
                    ':branch'     => $branch
                ]));
            }
        }

        // Switch all repositories to the requested branch
        foreach ($this->source as $repository) {
            $repository->setBranch($branch);
        }

        return $this->__setBranch($branch);
    }


    /**
     * Adds the specified repository to this repositories list
     *
     * @param mixed                            $repository
     * @param float|Stringable|int|string|null $path
     * @param bool                             $skip_null_values
     * @param bool                             $exception
     *
     * @return $this
     */
    public function add(mixed $repository, float|Stringable|int|string|null $path = null, bool $skip_null_values = true, bool $exception = true): static
    {
        if (!$repository instanceof RepositoryInterface) {
            throw new OutOfBoundsException(tr('Specified repository ":path" must be a RepositoriesInterface object', [
                ':path' => $repository->getPath()
            ]));
        }

        if (!$repository->exists()) {
            throw new OutOfBoundsException(tr('The path for the specified repository ":path" does not exist', [
                ':path' => $repository->getPath()
            ]));
        }

        if (!$repository->isRepository()) {
            throw new OutOfBoundsException(tr('The path for the specified repository ":path" does not exist', [
                ':path' => $repository->getPath()
            ]));
        }

        return parent::add($repository, $path, $skip_null_values, $exception);
    }


    /**
     * Scans for available phoundation and or phoundation plugin and or phoundation template repositories
     *
     * @return $this
     */
    public function scan(): static
    {
        // Scan for phoundation repositories
        Log::action(tr('Scanning for Phoundation core, plugin, and template repositories'), 6);

        $this->scanned_paths = new Iterator();

        // Allow for configured search paths
        $directories = Config::getArray('development.repositories.search.paths', [
            '~/projects/',
            '~/PhpstormProjects/',
            '~/PhpStormProjects/',
            '~/phpstormprojects/',
            '../',
            '../../',
            '../../../',
            '/var/www/html/',
        ]);

        return $this->scanDirectories($directories, true);
    }


    /**
     * Will resolve and return all specified directories to normalized, absolute, real, and unique paths
     *
     * @param IteratorInterface|array $directories
     *
     * @return IteratorInterface
     */
    protected function resolveDirectories(IteratorInterface|array $directories): IteratorInterface
    {
        // Get absolute and normalized directories
        $return      = [];
        $directories = new Iterator($directories);

        foreach ($directories as $path => $directory) {
            $directory = FsDirectory::normalizePath($directory);
            $directory = FsDirectory::absolutePath($directory, must_exist: false);
            $directory = FsDirectory::realPath($directory);

            $directories->set($directory, $path);
        }

        // Remove double and or empty paths
        $directories->removeEmptyValues()->unique(SORT_STRING);

        // Convert to directory objects
        foreach ($directories as $directory) {
            $return[$directory] = FsDirectory::new(
                $directory,
                FsRestrictions::getWritable($directory, 'Developer\Repositories')
            );
        }

        return new Iterator($return);
    }


    /**
     * Scans for available phoundation and or phoundation plugin and or phoundation template repositories
     *
     * @return $this
     */
    protected function scanDirectories(IteratorInterface|array $directories, bool $recurse = false): static
    {
        // Ensure we have absolute, normalized, real and unique directories
        $directories = $this->resolveDirectories($directories);

        foreach ($directories as $directory) {
            if ($this->scanned_paths->keyExists($directory->getPath())) {
                // This path was already scanned
                continue;
            }

            // Track scanned paths
            $this->scanned_paths->add($directory, $directory->getPath());

            Log::action(tr('Scanning directory ":directory"', [
                ':directory' => $directory->getPath()
            ]));

            if (!$directory->exists()) {
                // Nothing here
                Log::warning(tr('Ignoring directory ":directory", it does not exist', [
                    ':directory' => $directory->getPath(),
                ]), 2);

                continue;
            }

            // The main phoundation directory should be called either phoundation or Phoundation.
            foreach ($directory->scan() as $path) {
                try {
                    // Ensure that the path that we're working with is absolute, normalized, and real.
                    $path->makeNormalized()
                         ->makeAbsolute(must_exist: false)
                         ->makeRealPath(false);

                    if (!$path->exists()) {
                        // The resolved path doesn't exist, continue
                        continue;
                    }

                    if ($this->keyExists($path->getPath())) {
                        // This repository has already been added
                        continue;
                    }


                    Log::action(tr('Testing directory ":directory" for Phoundation repository', [
                        ':directory' => $path->getPath(),
                    ]), 1);

                    $repository = new Repository(
                        $path,
                        FsRestrictions::getWritable($path->getParentDirectory(), 'Repositories::scan() > ' . $path)
                    );

                    if (!$repository->isRepository()) {
                        Log::warning(tr('Ignoring directory ":directory", it is not a repository', [
                            ':directory' => $repository->getPath(),
                        ]), 2);

                        if ($repository->isDirectory() and $recurse) {
                            $this->scanDirectories([$repository->getPath()]);
                        }

                        continue;
                    }

                    Log::success(tr('Found Phoundation repository in ":path"', [
                        ':path' => $repository->getPath()
                    ]), 3);

                    $this->add($repository, $repository->getPath());

                } catch (FileNotWritableException $e) {
                    Log::warning(tr('Ignoring path ":path", the path cannot be written to', [
                        ':path' => $path,
                    ]));
                }
            }
        }

        return $this;
    }


    /**
     * Checks to make sure the repository object is not empty, which would be useless
     *
     * @return void
     */
    protected function checkNotEmpty(): void
    {
        if (empty($this->source)) {
            throw new OutOfBoundsException(tr('Cannot use repositories object, no repositories have been scanned or loaded yet'));
        }
    }


    /**
     * Returns the phoundation core repository
     *
     * @return RepositoryInterface|null
     */
    public function getCoreRepository(): ?RepositoryInterface
    {
        $this->checkNotEmpty();

        foreach ($this->source as $repository) {
            if (!$repository->isCore()) {
                return $repository;
            }
        }

        return null;
    }


    /**
     * Returns all plugin repositories that are available in this repositories object
     *
     * @return RepositoriesInterface
     */
    public function getPluginsRepositories(): RepositoriesInterface
    {
        $this->checkNotEmpty();

        $source = [];

        foreach ($this->source as $repository) {
            if (!$repository->isPlugins()) {
                $source[] = $repository;
            }
        }

        return Repositories::new($source);
    }


    /**
     * Returns all data repositories that are available in this repositories object
     *
     * @return RepositoriesInterface
     */
    public function getDataRepositories(): RepositoriesInterface
    {
        $this->checkNotEmpty();

        $source = [];

        foreach ($this->source as $repository) {
            if (!$repository->isData()) {
                $source[] = $repository;
            }
        }

        return Repositories::new($source);
    }


    /**
     * Returns all template repositories that are available in this repositories object
     *
     * @return RepositoriesInterface
     */
    public function getTemplatesRepositories(): RepositoriesInterface
    {
        $this->checkNotEmpty();

        $source = [];

        foreach ($this->source as $repository) {
            if (!$repository->isTemplates()) {
                $source[] = $repository;
            }
        }

        return Repositories::new($source);
    }


    /**
     * Returns true if this repositories object contains template repositories
     *
     * @return bool
     */
    public function hasCoreRepository(): bool
    {
        return (bool) $this->getCoreRepository();
    }


    /**
     * Returns true if this repositories object contains template repositories
     *
     * @return bool
     */
    public function hasDataRepositories(): bool
    {
        return $this->getPluginsRepositories()->isNotEmpty();
    }


    /**
     * Returns true if this repositories object contains template repositories
     *
     * @return bool
     */
    public function hasTemplatesRepositories(): bool
    {
        return $this->getTemplatesRepositories()->isNotEmpty();
    }


    /**
     * Try to patch all loaded repositories according to the configured rules
     *
     * @return $this
     */
    public function patch(bool $force = false): static
    {
        $o_stash = new Iterator();

        $this->checkCorePatching()
             ->checkDataPatching()
             ->checkPluginsPatching()
             ->checkTemplatesPatching();

        // Start patching all found changes by vendor
        foreach ($this->getProject()->getVendors() as $o_vendor) {
            // Patch each found repository
            foreach ($this->getVendorRepositories($o_vendor) as $repository) {
                $repository->patch($o_vendor, $o_stash, $force);
            }
        }

showdie('Woah, from here we start patching!!');

        if ($this->patch_core) {
            if (empty($this->source['core'])) {
                throw new RepositoryNotFoundException(tr('Cannot patch Phoundation core libraries, no Phoundation core repository found'));
            }
        }

        if ($this->patch_plugins) {
            $repositories = $this->getPluginsRepositories();

            if (!$repositories->getCount()) {
                throw new RepositoryNotFoundException(tr('Cannot patch Phoundation core libraries, no Phoundation core repository found'));
            }

            foreach ($repositories as $repository) {
            }
        }

        if ($this->patch_templates) {
            if (empty($this->source['core'])) {
                throw new RepositoryNotFoundException(tr('Cannot patch Phoundation core libraries, no Phoundation core installation found'));
            }
        }

        return $this;
    }


    /**
     * Will copy modified files directly to the repositories
     *
     * @return $this
     */
    public function copyModified(FsFileInterface|array|null $files = null): static
    {
throw new UnderConstructionException();
    }


    /**
     * Checks if this Repositories object can patch core changes
     *
     * @return static
     */
    protected function checkCorePatching(): static
    {
        Log::action(tr('Checking core patch ready'));

        if ($this->patch_core) {
            if ($this->project->hasCoreChanges()) {
                Log::notice(tr('Changes detected in core libraries'));

                if (!$this->hasCoreRepository()) {
                    throw new RepositoryNotFoundException(tr('Cannot patch Phoundation core libraries, no Phoundation core repository found'));
                }
            }
        }

        return $this;
    }


    /**
     * Checks if this Repositories object can patch plugins changes
     *
     * @return static
     */
    protected function checkDataPatching(): static
    {
        Log::action(tr('Checking data patch ready'));

        if ($this->patch_plugins) {
            if ($this->project->hasDataChanges()) {
                Log::notice(tr('Changes detected in data files'));

                if (!$this->hasDataRepositories()) {
                    throw new RepositoryNotFoundException(tr('Cannot patch Phoundation plugins, no Phoundation plugins repository found'));
                }

                // Ensure we have repositories for all vendors that have changes
                $vendors = $this->project->getChangedDataVendors()
                                         ->removeKeys($this->getDataVendors()->getSourceKeys());

                if ($vendors->isNotEmpty()) {
                    foreach ($vendors as $vendor) {
                        Log::warning(tr('Not patching data files for vendor ":vendor", no plugins repository was found for this vendor', [
                            ':vendor' => $vendor->getName()
                        ]));

                        foreach ($vendor as $file) {
                            Log::warning(tr('Not patching file ":file"', [
                                ':file' => $file
                            ]));
                        }
                    }
                }
            }
        }

        return $this;
    }


    /**
     * Checks if this Repositories object can patch plugins changes
     *
     * @return static
     */
    protected function checkPluginsPatching(): static
    {
        Log::action(tr('Checking plugins patch ready'));

        if ($this->patch_plugins) {
            if ($this->project->hasPluginsChanges()) {
                Log::notice(tr('Changes detected in plugin libraries'));

                if (!$this->hasDataRepositories()) {
                    throw new RepositoryNotFoundException(tr('Cannot patch Phoundation plugins, no Phoundation plugins repository found'));
                }

                // Ensure we have repositories for all vendors that have changes
                $vendors = $this->project->getVendors()
                                         ->removeKeys($this->getPluginsVendors()
                                                           ->getSourceKeys());

                if ($vendors->isNotEmpty()) {
                    foreach ($vendors as $vendor) {
                        Log::warning(tr('Not patching plugins files for vendor ":vendor", no plugins repository was found for this vendor', [
                            ':vendor' => $vendor->getName()
                        ]));

                        foreach ($vendor as $file) {
                            Log::warning(tr('Not patching file ":file"', [
                                ':file' => $file
                            ]));
                        }
                    }
                }
            }
        }

        return $this;
    }


    /**
     * Checks if this Repositories object can patch templates changes
     *
     * @return static
     */
    protected function checkTemplatesPatching(): static
    {
        Log::action(tr('Checking templates patch ready'));

        if ($this->patch_templates) {
            if ($this->project->hasTemplatesChanges()) {
                Log::notice(tr('Changes detected in template files'));

                if (!$this->hasTemplatesRepositories()) {
                    throw new RepositoryNotFoundException(tr('Cannot patch Phoundation core libraries, no Phoundation templates repository found'));
                }

                // Ensure we have repositories for all vendors that have changes
                // Ensure we have repositories for all vendors that have changes
                $vendors = $this->project->getChangedTemplatesVendors();
                $vendors->removeKeys($this->getTemplatesVendors());

                if ($vendors->isNotEmpty()) {
                    foreach ($vendors as $vendor) {
                        Log::warning(tr('Not patching templates files for vendor ":vendor", no templates repository was found for this vendor', [
                            ':vendor' => $vendor->getName()
                        ]));

                        foreach ($vendor as $file) {
                            Log::warning(tr('Not patching file ":file"', [
                                ':file' => $file
                            ]));
                        }
                    }
                }
            }
        }

        return $this;
    }


    /**
     * Return all vendors
     *
     * @return RepositoryVendorsInterface
     */
    public function getVendors(): RepositoryVendorsInterface
    {
        $return = new RepositoryVendors(null);

        foreach ($this->source as $repository) {
            if ($repository->isVendorsRepository()) {
                $return->addSources($repository->getVendors());
            }
        }

        return $return;
    }


    /**
     * Return the repositories that have the specified vendor
     *
     * @param VendorInterface $vendor
     *
     * @return RepositoriesInterface
     */
    public function getVendorRepositories(VendorInterface $vendor): RepositoriesInterface
    {
        $return = new Repositories();

        foreach ($this->source as $repository) {
            if ($repository->isVendorsRepository()) {
                if ($repository->hasVendor($vendor->getIdentifier())) {
                    $return->add($repository, $repository->getPath());
                }
            }
        }

        return $return;
    }


    /**
     * Return the specified vendor matching both repository type and identifier
     *
     * @param EnumRepositoryType $type
     * @param string             $identifier
     *
     * @return RepositoryVendorsInterface
     */
    public function getVendorByTypeAndIdentifier(EnumRepositoryType $type, string $identifier): RepositoryVendorsInterface
    {
        $return = new RepositoryVendors(null);

        foreach ($this->source as $key => $o_repository) {
            if ($o_repository->isVendorsRepository()) {
                if ($o_repository->isRepositoryType($type)) {
                    $return->add($o_repository->getVendor($identifier), $key);
                }
            }
        }

        return $return;
    }


    /**
     * Return all plugins vendors
     *
     * @return RepositoryVendorsInterface
     */
    public function getDataVendors(): RepositoryVendorsInterface
    {
        $return = new RepositoryVendors(null);

        foreach ($this->source as $repository) {
            if ($repository->isData()) {
                $return->addSources($repository->getVendors());
            }
        }

        return $return;
    }


    /**
     * Return all plugins vendors
     *
     * @return RepositoryVendorsInterface
     */
    public function getPluginsVendors(): RepositoryVendorsInterface
    {
        $return = new RepositoryVendors(null);

        foreach ($this->source as $repository) {
            if ($repository->isPlugins()) {
                $return->addSources($repository->getVendors());
            }
        }

        return $return;
    }


    /**
     * Return all templates vendors
     *
     * @return RepositoryVendorsInterface
     */
    public function getTemplatesVendors(): RepositoryVendorsInterface
    {
        $return = new RepositoryVendors(null);

        foreach ($this->source as $repository) {
            if ($repository->isTemplates()) {
                $return->addSources($repository->getVendors());
            }
        }

        return $return;
    }


    /**
     * Returns an Iterator that contains all possible repository types
     *
     * @return IteratorInterface
     */
    public static function getTypes(): IteratorInterface
    {
        return new Iterator([
            tr('Core')      => EnumRepositoryType::core,
            tr('Data')      => EnumRepositoryType::data,
            tr('Plugins')   => EnumRepositoryType::plugins,
            tr('Templates') => EnumRepositoryType::templates,
        ]);
    }


    /**
     * Returns a Repositories object with only the core repository
     *
     * @return IteratorInterface
     */
    public function getCore(): IteratorInterface
    {
        $return = new Repositories();

        foreach ($this->source as $repository) {
            if ($repository->isCore()) {
                $return->add($repository);
            }
        }

        return $return;
    }


    /**
     * Returns a Repositories object with only data repositories
     *
     * @return IteratorInterface
     */
    public function getData(): IteratorInterface
    {
        $return = new Repositories();

        foreach ($this->source as $repository) {
            if ($repository->isData()) {
                $return->add($repository);
            }
        }

        return $return;
    }


    /**
     * Returns a Repositories object with only plugins repositories
     *
     * @return IteratorInterface
     */
    public function getPlugins(): IteratorInterface
    {
        $return = new Repositories();

        foreach ($this->source as $repository) {
            if ($repository->isPlugins()) {
                $return->add($repository);
            }
        }

        return $return;
    }


    /**
     * Returns a Repositories object with only templates repositories
     *
     * @return IteratorInterface
     */
    public function getTemplates(): IteratorInterface
    {
        $return = new Repositories();

        foreach ($this->source as $repository) {
            if ($repository->isTemplates()) {
                $return->add($repository);
            }
        }

        return $return;
    }


    /**
     * Returns a Repositories object with only repositories of the specified type
     *
     * @param EnumRepositoryType $repository_type
     *
     * @return IteratorInterface
     */
    public function getRepositoryType(EnumRepositoryType $repository_type): IteratorInterface
    {
        $return = new Repositories();

        foreach ($this->source as $repository) {
            if ($repository->isRepositoryType($repository_type)) {
                $return->add($repository);
            }
        }

        return $return;
    }
}
