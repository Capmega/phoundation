<?php

namespace Phoundation\Web\Http\Html\Elements;



/**
 * Class ElementsBlock
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class ElementsBlock
{
    use ElementAttributes;



    /**
     * The data source for this element
     *
     * @var array|null $source
     */
    protected ?array $source;



    /**
     * ElementsBlock class constructor
     *
     * @param array|string|null $source
     */
    public function __construct(array|string|null $source = null)
    {
        if (is_string($source)) {
            $this->setContent($source);
        } elseif(is_array($source)) {
            $this->setSource(($source));
        } else {
            $this->source  = null;
            $this->content = null;
        }
    }



    /**
     * Returns a new ElementsBlock object
     *
     * @param array|string|null $source
     * @return static
     */
    public static function new(array|string|null $source = null): static
    {
        return new static($source);
    }



    /**
     * Returns the source for this element
     *
     * @return array|null
     */
    public function getSource(): ?array
    {
        return $this->source;
    }



    /**
     * Sets the data source for this element
     *
     * @param array|null $source
     * @return $this
     */
    public function setSource(?array $source): static
    {
        $this->source = $source;
        return $this;
    }



    /**
     * Render the ElementsBlock
     *
     * @return string|null
     */
    abstract public function render(): ?string;
}