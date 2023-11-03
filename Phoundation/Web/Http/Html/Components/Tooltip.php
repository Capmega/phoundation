<?php

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Data\Iterator;


/**
 * Class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Tooltip
{
    /**
     * The data-* store for the element to which this tooltip belongs
     *
     * @var Iterator
     */
    protected Iterator $data;


    /**
     * Tooltip constructor
     *
     * @param Iterator $data
     */
    public function __construct(Iterator $data)
    {
        $this->data = $data;
    }


    /**
     * Sets the tooltip toggle mode
     *
     * @param string $toggle
     * @return Tooltip
     */
    public function setToggle(string $toggle): static
    {
        $this->data->set('toggle', $toggle);
        return $this;
    }


    /**
     * Returns the tooltip toggle mode
     *
     * @return string
     */
    public function getToggle(): string
    {
        $this->data->set();
        return $this;
    }
}