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