<?php

/**
 * Trait TraitDataObjectPath
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Filesystem\Interfaces\PhoPathInterface;


trait TraitDataObjectPath
{
    use TraitDataObjectPathReadonly {
        setPathObject as protected __TraitSetPathObject;
    }


    /**
     * Sets the path
     *
     * @param PhoPathInterface|null $o_path
     * @param string|null           $prefix
     * @param bool                  $must_exist
     *
     * @return static
     */
    public function setPathObject(?PhoPathInterface $o_path, ?string $prefix = null, bool $must_exist = true): static
    {
        return $this->__TraitSetPathObject($o_path, $prefix, $must_exist);
    }
}
