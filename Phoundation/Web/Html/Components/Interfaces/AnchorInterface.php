<?php

/**
 * interface AInterface
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Interfaces;

use Phoundation\Web\Html\Enums\EnumAnchorTarget;
use Phoundation\Web\Http\Interfaces\UrlInterface;

interface AnchorInterface extends SpanInterface
{
    /**
     * Returns the href for this anchor
     *
     * @return UrlInterface|null
     */
    public function getHref(): ?UrlInterface;


    /**
     * Sets the href for this anchor
     *
     * @param UrlInterface|string|null $o_href
     *
     * @return static
     */
    public function setHref(UrlInterface|string|null $o_href): static;


    /**
     * Returns the target for this anchor
     *
     * @return EnumAnchorTarget|null
     */
    public function getTarget(): ?EnumAnchorTarget;


    /**
     * Sets the target for this anchor
     *
     * @param EnumAnchorTarget|null $o_target
     *
     * @return static
     */
    public function setTarget(?EnumAnchorTarget $o_target): static;
}
