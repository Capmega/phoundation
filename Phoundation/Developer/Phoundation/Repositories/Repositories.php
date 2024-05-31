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

use Phoundation\Core\Log\Log;
use Phoundation\Data\Iterator;
use Phoundation\Developer\Phoundation\Exception\RepositoryNotFoundException;
use Phoundation\Developer\Phoundation\Repositories\Interfaces\RepositoriesInterface;
use Phoundation\Developer\Phoundation\Repositories\Interfaces\RepositoryInterface;
use Phoundation\Developer\Versioning\Git\Exception\BranchNotAvailableException;
use Phoundation\Developer\Versioning\Git\Traits\TraitDataBranch;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\Restrictions;
use Stringable;

class Repositories extends Iterator implements RepositoriesInterface
{
    use TraitDataBranch {
        setBranch as protected __setBranch;
    }


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
     * Returns true if the core repository will be patched, false if not
     *
     * @return bool
     */
    public function getPatchCore(): bool
    {
        return $this->patch_core;
    }


    /**
     * Sets if the core repository will be patched, or not
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
    public function setPatchForcedCopy(bool $patch_forced_copy): static
    {
        $this->patch_forced_copy = $patch_forced_copy;
        return $this;
    }



    /**
     * Sets the branch for all the repositories in this list
     *
     * @param string $branch
     * @return $this
     */
    public function setBranch(string $branch): static
    {
        // First check that all repositories have the requested branch available.
        foreach ($this->source as $value) {
            if (!$value->hasBranch($branch)) {
                throw new BranchNotAvailableException(tr('Cannot switch repository "" to branch "", that branch does not exist in that repository', [
                    ':branch' => $branch
                ]));
            }
        }

        // Switch all repositories to the requested branch
        foreach ($this->source as $value) {
            $value->setBranch($branch);
        }

        return $this->__setBranch($branch);
    }


    /**
     * Adds the specified repository to this repositories list
     *
     * @param mixed                            $repository
     * @param float|Stringable|int|string|null $name
     * @param bool                             $skip_null
     * @param bool                             $exception
     *
     * @return $this
     */
    public function add(mixed $repository, float|Stringable|int|string|null $name = null, bool $skip_null = true, bool $exception = true): static
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

        return parent::add($repository, $name, $skip_null, $exception);
    }


    /**
     * Scans for available phoundation and or phoundation plugin and or phoundation template repositories
     *
     * @return $this
     */
    public function scan(): static
    {
        // Paths (in order) which will be scanned for Phoundation repositories
        $directories = [
            '~/projects/',
            '~/PhpstormProjects/',
            '~/PhpStormProjects/',
            '~/phpstormprojects/',
            '../',
            '../../',
            '../../../',
            '/var/www/html/',
        ];

        // Normalize directories
        foreach ($directories as &$directory) {
            $directory = Directory::normalizePath($directory);
        }

        unset($directory);
        $directories = array_unique($directories);

        // Scan for phoundation repositories
        Log::action(tr('Scanning for Phoundation core, plugin, and template repositories'));

        foreach ($directories as $directory) {
            $directory = Directory::new($directory, Restrictions::readonly(dirname($directory), 'Repositories::scan()'));

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
            foreach ($directory->scan() as $name) {
                $repository = $directory . $name;
                $repository = new Repository($repository, Restrictions::writable(dirname($repository), 'Repositories::scan() > ' . $name));

                Log::action(tr('Testing directory ":directory" for Phoundation repository', [
                    ':directory' => $repository->getPath(),
                ]), 1);

                if (!$repository->isRepository()) {
                    Log::warning(tr('Ignoring directory ":directory", it is not a repository', [
                        ':directory' => $repository->getPath(),
                    ]), 2);
                    continue;
                }

                Log::success(tr('Found Phoundation repository in ":path"', [
                    ':path' => $repository->getPath()
                ]), 3);

                $this->add($repository, $repository->getName());
            }
        }

        return $this;
    }


    /**
     * Returns all plugin repositories that are available in this repositories object
     *
     * @return RepositoriesInterface
     */
    public function getPluginRepositories(): RepositoriesInterface
    {
        $source = [];

        foreach ($this->source as $repository) {
            if (!$repository->isPluginRepository()) {
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
    public function getTemplateRepositories(): RepositoriesInterface
    {
        $source = [];

        foreach ($this->source as $repository) {
            if (!$repository->isTemplateRepository()) {
                $source[] = $repository;
            }
        }

        return Repositories::new($source);
    }


    /**
     * Try to patch all loaded repositories according to the configured rules
     *
     * @return $this
     */
    public function patch(): static
    {
        // Ensure all repositories were found
        if ($this->patch_core) {
            if (empty($this->source['core'])) {
                throw new RepositoryNotFoundException(tr('Cannot patch Phoundation core libraries, no Phoundation core repository found'));
            }
        }

        if ($this->patch_plugins) {
            $plugin_repositories = $this->getPluginRepositories();

            if (!$plugin_repositories->getCount()) {
                throw new RepositoryNotFoundException(tr('Cannot patch Phoundation plugins, no Phoundation plugins repository found'));
            }

            // Ensure we have repositories for all vendors that have changes
            foreach ($this->getVendors() as $vendor) {

            }
        }

        if ($this->patch_templates) {
            $template_repositories = $this->getTemplateRepositories();

            if (!$template_repositories->getCount()) {
                throw new RepositoryNotFoundException(tr('Cannot patch Phoundation core libraries, no Phoundation templates repository found'));
            }

            // Ensure we have repositories for all vendors that have changes
        }

        // Start patching
        if ($this->patch_core) {
            if (empty($this->source['core'])) {
                throw new RepositoryNotFoundException(tr('Cannot patch Phoundation core libraries, no Phoundation core installation found'));
            }
        }

        if ($this->patch_plugins) {
            $repositories = $this->getPluginRepositories();

            if (!$repositories->getCount()) {
                throw new RepositoryNotFoundException(tr('Cannot patch Phoundation core libraries, no Phoundation core installation found'));
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
}
