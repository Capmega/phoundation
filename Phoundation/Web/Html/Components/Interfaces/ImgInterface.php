<?php

namespace Phoundation\Web\Html\Components\Interfaces;

use Phoundation\Web\Html\Components\Img;
use Stringable;


/**
 * interface ImgInterface
 *
 * This class generates <img> elements
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface ImgInterface
{
    /**
     * Sets the HTML alt element attribute
     *
     * @param bool $lazy_load
     * @return Img
     */
    public function setLazyLoad(?bool $lazy_load): static;

    /**
     * Returns the HTML alt element attribute
     *
     * @return bool
     */
    public function getLazyLoad(): bool;

    /**
     * Sets the HTML alt element attribute
     *
     * @param string|null $alt
     * @return Img
     */
    public function setAlt(?string $alt): static;

    /**
     * Returns the HTML alt element attribute
     *
     * @return string|null
     */
    public function getAlt(): ?string;

    /**
     * Returns if this image is hosted on an external domain (that is, a domain NOT in the "web.domains" configuration
     *
     * @return bool
     */
    public function getExternal(): bool;

    /**
     * Sets the HTML src element attribute
     *
     * @param Stringable|string|null $src
     * @return Img
     */
    public function setSrc(Stringable|string|null $src): static;

    /**
     * Returns the HTML src element attribute
     *
     * @return Stringable|string|null
     */
    public function getSrc(): Stringable|string|null;

    /**
     * Generates and returns the HTML string for a <select> control
     *
     * @return string|null
     */
    public function render(): ?string;
}