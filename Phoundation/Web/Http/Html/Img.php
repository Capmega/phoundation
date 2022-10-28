<?php

namespace Phoundation\Web\Http\Html;

use Phoundation\Web\Http\Html\Exception\HtmlException;

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
     * Img constructor
     */
    public function __construct()
    {
        parent::__construct('img');
    }



    /**
     * Sets the HTML alt element attribute
     *
     * @param string|null $alt
     * @return Element
     */
    public function setAlt(?string $alt): self
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
     * Sets the HTML src element attribute
     *
     * @param string|null $src
     * @return Element
     */
    public function setSrc(?string $src): self
    {
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