<?php

/**
 * Audio class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation/Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Web\Html\Enums\EnumAudioPreload;
use Phoundation\Web\Http\Url;


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
        return EnumAudioPreload::from($this->o_attributes->get('preload', false));
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
     * @return ?PhoFileInterface
     */
    public function getFile(): ?PhoFileInterface
    {
        $file = $this->o_attributes->get('src', false);

        if ($file) {
            $file = new PhoFile($file);
        }

        return $file;
    }


    /**
     * Sets how audio should be preloaded
     *
     * @param PhoFileInterface $file
     *
     * @return static
     */
    public function setFile(PhoFileInterface $file): static
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
                    <source src="' . Url::new($this->o_attributes->get('src', false) ?? $this->content)->makeCdn() . '" type="audio/mpeg">
                </audio>';
    }
}
