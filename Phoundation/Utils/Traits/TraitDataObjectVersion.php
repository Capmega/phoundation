<?php

/**
 * Trait TraitDataObjectVersion
 *
 * This trait adds support for managing an $o_version object in your class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openpath.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils\Traits;

use Phoundation\Utils\Interfaces\VersionInterface;
use Phoundation\Utils\Version;

trait TraitDataObjectVersion
{
    /**
     * The path to use
     *
     * @var VersionInterface|null $o_version
     */
    protected ?VersionInterface $o_version = null;


    /**
     * Returns the version object
     *
     * @return VersionInterface|null
     */
    public function getVersionObject(): ?VersionInterface
    {
        return $this->o_version;
    }


    /**
     * Sets the version object
     *
     * @param VersionInterface|null $o_version
     *
     * @return static
     */
    public function setVersionObject(?VersionInterface $o_version): static
    {
        $this->o_version = $o_version;
        return $this;
    }


    /**
     * Returns the version string
     *
     * @return string|null
     */
    public function getVersion(): ?string
    {
        return $this->o_version->getSource();
    }


    /**
     * Sets the version string
     *
     * @param string|null $version
     *
     * @return static
     */
    public function setVersion(?string $version): static
    {
        $this->o_version = new Version($version);
        return $this;
    }
}
