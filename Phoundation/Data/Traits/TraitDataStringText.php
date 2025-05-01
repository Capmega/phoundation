<?php

/**
 * Trait TraitDataText
 *
 * This trait adds support for a string containing a text
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataStringText
{
    /**
     * Registers if this object is text or not
     *
     * @var string|null $text
     */
    protected ?string $text = null;


    /**
     * Returns if this object is text or not
     *
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->text;
    }


    /**
     * Returns if this object is text or not
     *
     * @param string|null $text
     *
     * @return static
     */
    public function setText(?string $text): static
    {
        $this->text = $text;
        return $this;
    }
}
