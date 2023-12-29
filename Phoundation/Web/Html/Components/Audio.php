<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Web\Html\Enums\EnumAudioPreload;
use Phoundation\Web\Html\Enums\Interfaces\EnumAudioPreloadInterface;
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
     * @return EnumAudioPreloadInterface|null
     */
    public function getPreload(): ?EnumAudioPreloadInterface
    {
        return EnumAudioPreload::from($this->attributes->get('preload', false));
    }


    /**
     * Sets how audio should be preloaded
     *
     * @param EnumAudioPreloadInterface $value
     * @return static
     */
    public function setPreload(EnumAudioPreloadInterface $value): static
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
     * @return static
     */
    public function setFile(Stringable|string|null $file): static
    {
        return $this->setAttribute((string) $file, 'src');
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
                    <source src="' . UrlBuilder::getCdn(UrlBuilder::getCdn($this->attributes->get('src', false))) . '" type="audio/mpeg">
                </audio>';
    }
}