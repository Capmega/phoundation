<?php

namespace Phoundation\Web\Http\Html;

use Phoundation\Core\Config;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Exception\HtmlException;
use Phoundation\Web\Http\Url;



/**
 * Class Img
 *
 * This class generates <img> elements
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Img extends Element
{
    /**
     * Sets whether the image will be lazily loaded as-needed or directly
     *
     * @var bool $lazy_load
     */
    protected bool $lazy_load = true;

    /**
     * The source URL for this image
     *
     * @var string|null $src
     */
    protected ?string $src = null;

    /**
     * The alt text for this image
     *
     * @var string|null $alt
     */
    protected ?string $alt = null;

    /**
     * The image width
     *
     * @var int|null $width
     */
    protected ?int $width = null;

    /**
     * The image height
     *
     * @var int|null $height
     */
    protected ?int $height = null;



    /**
     * Img constructor
     */
    public function __construct()
    {
        parent::__construct('img');
        $this->lazy_load = Config::get('web.images.lazy-load', true);
    }



    /**
     * Sets the HTML alt element attribute
     *
     * @param bool $lazy_load
     * @return Img
     */
    public function setLazyLoad(?bool $lazy_load): static
    {
        if ($lazy_load === null) {
            $lazy_load = Config::get('web.images.lazy-load', true);
        }

        $this->lazy_load = $lazy_load;
        return $this;
    }



    /**
     * Returns the HTML alt element attribute
     *
     * @return bool
     */
    public function getLazyLoad(): bool
    {
        return $this->lazy_load;
    }



    /**
     * Sets the HTML alt element attribute
     *
     * @param string|null $alt
     * @return Img
     */
    public function setAlt(?string $alt): static
    {
        $this->alt = $alt;
        return $this;
    }



    /**
     * Returns the HTML alt element attribute
     *
     * @return string|null
     */
    public function getAlt(): ?string
    {
        return $this->alt;
    }



    /**
     * Sets the image width in pixels
     *
     * @param int|null $width
     * @return Img
     */
    public function setWidth(?int $width): static
    {
        if ($width < 1) {
            throw new OutOfBoundsException(tr('Invalid image width ":value" specified, it should be 1 or above', [':value' => $width]));
        }

        $this->width = $width;
        return $this;
    }



    /**
     * Returns the image width in pixels
     *
     * @return int|null
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }



    /**
     * Sets the image height in pixels
     *
     * @param int|null $height
     * @return Img
     */
    public function setHeight(?int $height): static
    {
        if ($height < 1) {
            throw new OutOfBoundsException(tr('Invalid image height ":value" specified, it should be 1 or above', [':value' => $height]));
        }

        $this->height = $height;
        return $this;
    }



    /**
     * Returns the image height in pixels
     *
     * @return int|null
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }



    /**
     * Sets the HTML src element attribute
     *
     * @param string|null $src
     * @return Img
     */
    public function setSrc(?string $src): static
    {
        $src = Url::build($src)->cdn();

        $this->src = $src;
        return $this;
    }



    /**
     * Returns the HTML src element attribute
     *
     * @return string|null
     */
    public function getSrc(): ?string
    {
        return $this->src;
    }



    /**
     * Generates and returns the HTML string for a <select> control
     *
     * @return string
     */
    public function render(): string
    {
        if (!$this->src) {
            throw new HtmlException(tr('No src attribute specified'));
        }

        if (!$this->alt) {
            throw new HtmlException(tr('No alt attribute specified'));
        }

        return parent::render();
    }



    /**
     * Add the system arguments to the arguments list
     *
     * @return array
     */
    protected function buildAttributes(): array
    {
        return array_merge(parent::buildAttributes(), [
            'src' => $this->src,
            'alt' => $this->alt,
        ]);
    }
}