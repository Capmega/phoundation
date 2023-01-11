<?php

namespace Phoundation\Web\Http\Html\Components\Input;



/**
 * TextArea class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class TextArea extends Input
{
    /**
     * The amount of columns to use for this text area
     *
     * @var int|null $cols
     */
    protected ?int $cols = null;

    /**
     * The amount of rows to use for this text area
     *
     * @var int|null $cols
     */
    protected ?int $rows = null;



    /**
     * Form class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->requires_closing_tag = true;
        $this->element              = 'textarea';
        $this->type                 = null;
    }



    /**
     * Returns the amount of cols to use for this text area
     *
     * @return int|null
     */
    public function getCols(): ?int
    {
        return $this->cols;
    }



    /**
     * Sets the amount of cols to use for this text area
     *
     * @param int|null $cols
     * @return $this
     */
    public function setCols(?int $cols): static
    {
        $this->cols = $cols;
        return $this;
    }



    /**
     * Returns the amount of rows to use for this text area
     *
     * @return int|null
     */
    public function getRows(): ?int
    {
        return $this->rows;
    }



    /**
     * Sets the amount of rows to use for this text area
     *
     * @param int|null $rows
     * @return $this
     */
    public function setRows(?int $rows): static
    {
        $this->rows = $rows;
        return $this;
    }



    /**
     * Add the system arguments to the arguments list
     *
     * @note The system attributes (id, name, class, autofocus, readonly, disabled) will overwrite those same
     *       values that were added as general attributes using Element::addAttribute()
     * @return array
     */
    protected function buildAttributes(): array
    {
        $return = [
            'cols' => $this->cols,
            'rows' => $this->rows,
        ];

        // Merge the system values over the set attributes
        return array_merge(parent::buildAttributes(), $this->attributes, $return);
    }
}