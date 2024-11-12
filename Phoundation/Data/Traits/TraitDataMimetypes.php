<?php

/**
 * Trait TraitDataMimetypes
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;


trait TraitDataMimetypes
{
    /**
     * The mimetypes for this object
     *
     * @var IteratorInterface|null $mimetypes
     */
    protected ?IteratorInterface $mimetypes = null;


    /**
     * Returns the mimetypes
     *
     * @return IteratorInterface|null
     */
    public function getMimetypes(): IteratorInterface|null
    {
        return $this->mimetypes;
    }


    /**
     * Sets the mimetypes
     *
     * @param IteratorInterface|array|string|null $mimetypes
     *
     * @return static
     */
    public function setMimetypes(IteratorInterface|array|string|null $mimetypes): static
    {
        $mimetypes = get_null($mimetypes);

        if ($mimetypes) {
            $mimetypes = Iterator::force($mimetypes);
        }

        $this->mimetypes = $mimetypes;
        return $this;
    }
}
