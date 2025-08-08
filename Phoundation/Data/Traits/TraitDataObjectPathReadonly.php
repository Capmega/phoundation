<?php

/**
 * Trait TraitDataObjectPathReadonly
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openpath.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Filesystem\Interfaces\PhoPathInterface;


trait TraitDataObjectPathReadonly
{
    /**
     * The path to use
     *
     * @var PhoPathInterface|null $o_path
     */
    protected ?PhoPathInterface $o_path = null;


    /**
     * Returns the path object
     *
     * @return PhoPathInterface|null
     */
    public function getPathObject(): ?PhoPathInterface
    {
        return $this->o_path;
    }


    /**
     * Sets the path object
     *
     * @param PhoPathInterface|null $o_path
     * @param string|null           $prefix
     * @param bool                  $must_exist
     *
     * @return static
     */
    protected function setPathObject(PhoPathInterface|null $o_path, ?string $prefix = null, bool $must_exist = true): static
    {
        $this->o_path = $o_path?->makeAbsolute($prefix, $must_exist);
        return $this;
    }
}
