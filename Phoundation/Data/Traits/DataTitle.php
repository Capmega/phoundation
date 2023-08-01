<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait DataTitle
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opentitle.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataTitle
{
    /**
     * The title to use
     *
     * @var string|null $title
     */
    protected ?string $title = null;


    /**
     * Returns the title
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }


    /**
     * Sets the title
     *
     * @param string|null $title
     * @return static
     */
    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }
}