<?php

/**
 * Trait TraitDataEntryPost
 *
 * This trait contains methods for DataEntry objects that require a name and post
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


trait TraitDataEntryPost
{
    /**
     * Returns the post for this object
     *
     * @return array|null
     */
    public function getPost(): ?array
    {
        return $this->getTypesafe('array', 'post');
    }


    /**
     * Sets the post data for this object
     *
     * @param array|null $post
     *
     * @return static
     */
    public function setPost(?array $post): static
    {
        return $this->set($post, 'post');
    }
}
