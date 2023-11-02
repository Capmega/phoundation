<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use Composer\Package\Package;
use Phoundation\Web\Http\Html\Enums\Interfaces\EnumAudioPreloadInterface;
use Phoundation\Web\Http\UrlBuilder;
use Stringable;


/**
 * Audio class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Web
 */
class Audio extends Element
{
    /**
     * Returns how audio should be preloaded
     *
     * @return string|null
     */
    public function getPreload(): ?string
    {
        return isset_get($this->attributes['preload'])?->value;
    }


    /**
     * Sets how audio should be preloaded
     *
     * @param EnumAudioPreloadInterface $value
     * @return static
     */
    public function setPreload(EnumAudioPreloadInterface $value): static
    {
        $this->attributes['preload'] = $value->value;
        return $this;
    }


    /**
     * Returns how audio should be preloaded
     *
     * @return ?string
     */
    public function getFile(): ?string
    {
        return isset_get($this->attributes['src']);
    }


    /**
     * Sets how audio should be preloaded
     *
     * @param Stringable|string|null $file
     * @return static
     */
    public function setFile(Stringable|string|null $file): static
    {
        $this->attributes['src'] = (string) $file;
        return $this;
    }


    /**
     * Audio constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setElement('audio');
    }


    /**
     * Renders and returns the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string
    {
        // For the moment just generate the HTML directly
        return '<audio class="' . $this->getClass() . '" preload="auto">
                    <source src="' . UrlBuilder::getCdn(UrlBuilder::getCdn($this->attributes['src'])) . '" type="audio/mpeg">
                </audio>';
    }
}