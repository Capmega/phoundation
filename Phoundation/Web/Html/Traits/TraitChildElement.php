<?php

/**
 * Trait TraitChildElement
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use Phoundation\Web\Html\Components\Interfaces\ElementInterface;

trait TraitChildElement
{
    /**
     * The child where this anchor sits around
     *
     * @var ElementInterface|null $child_element
     */
    protected ?ElementInterface $child_element = null;


    /**
     * Returns the child for this anchor
     *
     * @return ElementInterface|null
     */
    public function getChildElement(): ?ElementInterface
    {
        return $this->child_element;
    }


    /**
     * Sets the child for this anchor
     *
     * @param ElementInterface|null $child_element
     *
     * @return static
     */
    public function setChildElement(?ElementInterface $child_element): static
    {
        $this->child_element = $child_element;

        return $this;
    }
}
