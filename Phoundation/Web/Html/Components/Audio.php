<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Web\Html\Enums\EnumAudioPreload;
use Phoundation\Web\Http\UrlBuilder;
use Stringable;

/**
 * Audio class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation/Web
 */
class Audio extends Element
{
    /**
     * Audio class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);
        $this->setElement('audio');
    }


    /**
     * Returns how audio should be preloaded
     *
     * @return EnumAudioPreload|null
     */
    public function getPreload(): ?EnumAudioPreload
    {
        return EnumAudioPreload::from($this->attributes->get('preload', false));
    }


    /**
     * Sets how audio should be preloaded
     *
     * @param EnumAudioPreload $value
     *
     * @return static
     */
    public function setPreload(EnumAudioPreload $value): static
    {
        return $this->setAttribute($value, 'preload');
    }


    /**
     * Returns how audio should be preloaded
     *
     * @return ?string
     */
    public function getFile(): ?string
    {
        return $this->attributes->get('src', false);
    }


    /**
     * Sets how audio should be preloaded
     *
     * @param Stringable|string|null $file
     *
     * @return static
     */
    public function setFile(Stringable|string|null $file): static
    {
        return $this->setAttribute((string) $file, 'src');
    }


    /**
     * Renders and returns the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string
    {
        // For the moment generate the HTML directly
        return '<audio class="' . $this->getClass() . '" preload="auto">
                    <source src="' . UrlBuilder::getCdn(UrlBuilder::getCdn($this->attributes->get('src', false) ?? $this->content)) . '" type="audio/mpeg">
                </audio>';
    }
}