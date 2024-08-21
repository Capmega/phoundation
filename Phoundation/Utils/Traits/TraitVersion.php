<?php

/**
 * Trait TraitVersion
 *
 * This trait contains various methods to verify and manipulate versions
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils\Traits;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Exception\OutOfBoundsException;


trait TraitVersion
{
    /**
     * The major part of this version
     *
     * @var int $major
     */
    protected int $major;

    /**
     * The minor part of this version
     *
     * @var int $minor
     */
    protected int $minor;

    /**
     * The revision part of this version
     *
     * @var int $revision
     */
    protected int $revision;


    /**
     * Version trait constructor
     *
     * @param IteratorInterface|array|string $version
     */
    public function __construct(IteratorInterface|array|string $version)
    {
        // Split the version
        $parts = Arrays::force($version);
        $parts = array_values($parts);
        // Validate
        if (count($parts) !== 3) {
            throw new OutOfBoundsException(tr('Invalid version ":version" specified, should be format MAJOR.MINOR.REVISION where each part is an integer between 0 and 1000', [
                ':version' => $version,
            ]));
        }
        foreach ($parts as $part) {
            if (!is_numeric($part) or ($part < 0) or ($part > 1000)) {
                throw new OutOfBoundsException(tr('Invalid version ":version" specified, should be format MAJOR.MINOR.REVISION where each part is an integer between 0 and 1000', [
                    ':version' => $version,
                ]));
            }
        }
        // Store
        $this->major    = $parts[0];
        $this->minor    = $parts[1];
        $this->revision = $parts[2];
    }


    /**
     * Decreases the major version
     *
     * @return static
     */
    public function decreaseMajor(): static
    {
        $this->major--;
        if ($this->major < 0) {
            throw new OutOfBoundsException(tr('Invalid major version ":version", it should be in the range between 0 and 1000', [
                ':version' => $this->major,
            ]));
        }

        return $this;
    }


    /**
     * Increases the major version
     *
     * @return static
     */
    public function increaseMajor(): static
    {
        $this->major++;
        if ($this->major > 1000) {
            throw new OutOfBoundsException(tr('Invalid major version ":version", it should be in the range between 0 and 1000', [
                ':version' => $this->major,
            ]));
        }

        return $this;
    }


    /**
     * Decreases the minor version
     *
     * @return static
     */
    public function decreaseMinor(): static
    {
        $this->minor--;
        if ($this->minor < 0) {
            throw new OutOfBoundsException(tr('Invalid minor version ":version", it should be in the range between 0 and 1000', [
                ':version' => $this->minor,
            ]));
        }

        return $this;
    }


    /**
     * Increases the minor version
     *
     * @return static
     */
    public function increaseMinor(): static
    {
        $this->minor++;
        if ($this->minor > 1000) {
            throw new OutOfBoundsException(tr('Invalid minor version ":version", it should be in the range between 0 and 1000', [
                ':version' => $this->minor,
            ]));
        }

        return $this;
    }


    /**
     * Decreases the revision version
     *
     * @return static
     */
    public function decreaseRevision(): static
    {
        $this->revision--;
        if ($this->revision < 0) {
            throw new OutOfBoundsException(tr('Invalid revision version ":version", it should be in the range between 0 and 1000', [
                ':version' => $this->revision,
            ]));
        }

        return $this;
    }


    /**
     * Increases the revision version
     *
     * @return static
     */
    public function increaseRevision(): static
    {
        $this->revision++;
        if ($this->revision > 1000) {
            throw new OutOfBoundsException(tr('Invalid revision version ":version", it should be in the range between 0 and 1000', [
                ':version' => $this->revision,
            ]));
        }

        return $this;
    }


    /**
     * Returns the version from this object
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->major . '.' . $this->minor . '.' . $this->revision;
    }
}
