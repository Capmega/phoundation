<?php

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

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Web\Html\Enums\EnumAudioPreload;
use Phoundation\Web\Http\Url;
use Stringable;

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
     * @return ?FsFileInterface
     */
    public function getFile(): ?FsFileInterface
    {
        $file = $this->attributes->get('src', false);

        if ($file) {
            $file = new FsFile($file);
        }

        return $file;
    }


    /**
     * Sets how audio should be preloaded
     *
     * @param FsFileInterface $file
     *
     * @return static
     */
    public function setFile(FsFileInterface $file): static
    {
        return $this->setAttribute($file, 'src');
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
                    <source src="' . Url::getCdn(Url::getCdn($this->attributes->get('src', false) ?? $this->content)) . '" type="audio/mpeg">
                </audio>';
    }
}