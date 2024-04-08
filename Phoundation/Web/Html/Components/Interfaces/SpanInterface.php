<?php

namespace Phoundation\Web\Html\Components\Interfaces;

/**
 * interface SpanInterface
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
interface SpanInterface extends ElementInterface
{
    /**
     * Returns the parent for this anchor
     *
     * @return ElementInterface|null
     */
    public function getChildElement(): ?ElementInterface;


    /**
     * Sets the parent for this anchor
     *
     * @param ElementInterface|null $parent
     *
     * @return $this
     */
    public function setChildElement(?ElementInterface $parent): static;
}