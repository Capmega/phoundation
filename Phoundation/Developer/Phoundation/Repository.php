<?php

/**
 * Repository class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */

declare(strict_types=1);

namespace Phoundation\Developer\Phoundation;

use Phoundation\Core\Libraries\Interfaces\LibraryInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Developer\Phoundation\Exception\NotARepositoryException;
use Phoundation\Developer\Phoundation\Interfaces\RepositoryInterface;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\Interfaces\DirectoryInterface;
use Phoundation\Filesystem\Interfaces\PathInterface;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;

class Repository implements RepositoryInterface
{
    /**
     * The directory of this repository
     *
     * @var DirectoryInterface
     */
    protected DirectoryInterface $path;


    /**
     * Repository class constructor
     */
    public function __construct(PathInterface|string $path, ?RestrictionsInterface $restrictions = null)
    {
        $this->path = Directory::new($path, $restrictions);
    }


    /**
     * Returns the repository path
     *
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path->getPath();
    }


    /**
     * Returns true if this repository exist
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->path->exists();
    }


    /**
     * Returns true if this repository can be read from
     *
     * @return bool
     */
    public function isReadable(): bool
    {
        return $this->path->isReadable();
    }


    /**
     * Returns true if this repository can be written to
     *
     * @return bool
     */
    public function isWritable(): bool
    {
        return $this->path->isWritable();
    }


    /**
     * Returns true if this repository is a Phoundation repository
     *
     * @return bool
     */
    public function isRepository(): bool
    {
        return $this->isPhoundationCore() or $this->isPhoundationPlugins() or $this->isPhoundationTemplates();
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
            ':path' => $this->path->getPath()
        ]));
    }


    /**
     * Returns true if this repository is a phoundation project
     *
     * @return bool
     */
    public function isPhoundationCore(): bool
    {
        if (!$this->isReadable()) {
            return false;
        }

        $path  = $this->path;

        // The path basename must be "phoundation"
        if ($path->getBasename() !== 'phoundation') {
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
            if (!file_exists($path . $file)) {
                return false;
            }
        }

        // All these files and directories must NOT be available.
        $files = [
            'Templates',
            'config/project',
            'config/version',
        ];

        foreach ($files as $file) {
            if (file_exists($path . $file)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Returns true if this repository is a Phoundation repository
     *
     * @return static
     */
    public function checkPhoundationCore(): static
    {
        if ($this->isPhoundationCore()) {
            return $this;
        }

        throw new NotARepositoryException(tr('The path ":path" is not a Phoundation core repository', [
            ':path' => $this->path->getPath()
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

        $path  = $this->path;

        // All these files and directories must be available.
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
    public function isPhoundationPlugins(): bool
    {
        if (!$this->isReadable() or $this->isPhoundationProject() or $this->isPhoundationCore()) {
            return false;
        }

        $path = $this->path;

        // The path basename must be "phoundation-plugins"
        if ($path->getBasename() !== 'phoundation-plugins') {
            return false;
        }

        // All these files and directories must be available.
        $files = [
            'Plugins',
            'Templates',
            'Phoundation',
            'README.md',
        ];

        foreach ($files as $file) {
            if (!file_exists($path . $file)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Returns true if this repository is a Phoundation repository
     *
     * @return static
     */
    public function checkPhoundationPlugins(): static
    {
        if ($this->isPhoundationPlugins()) {
            return $this;
        }

        throw new NotARepositoryException(tr('The path ":path" is not a Phoundation plugins repository', [
            ':path' => $this->path->getPath()
        ]));
    }


    /**
     * Get the value of is_template
     *
     * @return bool
     */
    public function isPhoundationTemplates(): bool
    {
        if (!$this->isReadable() or $this->isPhoundationProject() or $this->isPhoundationCore()) {
            return false;
        }

        $path = $this->path;

        // The path basename must be "phoundation-templates"
        if ($path->getBasename() !== 'phoundation-templates') {
            return false;
        }

        return true;
    }


    /**
     * Returns true if this repository is a Phoundation repository
     *
     * @return static
     */
    public function checkPhoundationTemplates(): static
    {
        if ($this->isPhoundationTemplates()) {
            return $this;
        }

        throw new NotARepositoryException(tr('The path ":path" is not a Phoundation templates repository', [
            ':path' => $this->path->getPath()
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

        $name = basename(dirname($this->path->getPath()));

        if ($this->isPhoundationCore()) {
            return $name . '-core';
        }

        if ($this->isPhoundationPlugins()) {
            return $name . '-plugins';
        }

        if ($this->isPhoundationTemplates()) {
            return $name . '-templates';
        }

        return $name . '-unknown';
    }


    /**
     * If this repository is a Plugins repository, this method will return the found vendors
     *
     * @return IteratorInterface
     */
    public function getVendors(): IteratorInterface
    {
        $this->checkPhoundationPlugins();
        return $this->path->scan();
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
}
