<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Traits;

/**
 * Trait TraitDataImage
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Virtualization
 */
trait TraitDataImage
{
    protected string $image;


    /**
     * Returns the docker image
     *
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }


    /**
     * Sets the docker image
     *
     * @param string $image
     *
     * @return $this
     */
    public function setImage(string $image): static
    {
// Add validation for the image
//        ArrayValidator::new(['image' => $image])
//            ->select('image')->hasMinCharacters(2)->hasMaxCharacters(32)->matchesRegex('/[a-z-]+/i')
//            ->validate();
        $this->image = $image;

        return $this;
    }
}