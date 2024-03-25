<?php

/**
 * interface AInterface
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */

namespace Phoundation\Web\Html\Components\Interfaces;

use Phoundation\Web\Html\Enums\Interfaces\EnumAnchorTargetInterface;


interface AInterface extends SpanInterface
{
    /**
     * Returns the href for this anchor
     *
     * @return string|null
     */
    public function getHref(): ?string;

    /**
     * Sets the href for this anchor
     *
     * @param string|null $href
     * @return $this
     */
    public function setHref(?string $href): static;

    /**
     * Returns the target for this anchor
     *
     * @return EnumAnchorTargetInterface|null
     */
    public function getTarget(): ?EnumAnchorTargetInterface;

    /**
     * Sets the target for this anchor
     *
     * @param EnumAnchorTargetInterface|null $target
     * @return $this
     */
    public function setTarget(?EnumAnchorTargetInterface $target): static;
}