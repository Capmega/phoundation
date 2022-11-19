<?php

namespace Plugins\Mdb\Layouts;

use JetBrains\PhpStorm\ExpectedValues;



/**
 * MDB Plugin GridColumn class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Mdb
 */
class GridColumn extends Layout
{
    /**
     * The size of this column
     *
     * @var string
     */
    #[ExpectedValues(values: [1, 2, 3, 4, 5, 6, 7 ,8, 9, 10, 11, 12])]
    protected string $size;


    /**
     * The tier class for this column
     *
     * @var string
     */
    #[ExpectedValues(values: ["xs", "sm", "md", "lg", "xl"])]
    protected string $tier = '';

    /**
     * The content for this column
     *
     * @var string|null $content
     */
    protected ?string $content = null;



    /**
     * GridColumn class constructor
     *
     * @param int $size
     * @param string $tier
     */
    public function __construct(int $size, #[ExpectedValues(values: ["xs", "sm", "md", "lg", "xl"])] string $tier = 'md')
    {
        $this->size = $size;
        $this->tier = $tier;
    }



    /**
     * Returns a new GridColumn object
     *
     * @param int $size
     * @param string $tier
     * @return static
     */
    public static function new(int $size, #[ExpectedValues(values: ["xs", "sm", "md", "lg", "xl"])] string $tier = 'md'): static
    {
        return new static($size, $tier);
    }



    /**
     * Sets the tier class
     *
     * @param string $tier
     * @return static
     */
    public function setTier(#[ExpectedValues(values: ["xs", "sm", "md", "lg", "xl"])] string $tier): static
    {
        $this->tier = $tier;
        return $this;
    }



    /**
     * Returns the tier class
     *
     * @return string
     */
    #[ExpectedValues(values: ["xs", "sm", "md", "lg", "xl"])] public function getTier(): string
    {
        return $this->tier;
    }



    /**
     * Sets the column size
     *
     * @param int $size
     * @return static
     */
    public function setSize(#[ExpectedValues(values: [1, 2, 3, 4, 5, 6, 7 ,8, 9, 10, 11, 12])] int $size): static
    {
        $this->size = $size;
        return $this;
    }



    /**
     * Returns the column size
     *
     * @return int
     */
    #[ExpectedValues(values: [1, 2, 3, 4, 5, 6, 7 ,8, 9, 10, 11, 12])] public function getSize(): int
    {
        return $this->size;
    }



    /**
     * Sets the column content
     *
     * @param string $content
     * @return static
     */
    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }



    /**
     * Returns the column content
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }



    /**
     * Render this grid column
     *
     * @return string
     */
    public function render(): string
    {
        return '<div class="col' . ($this->tier ? '-' . $this->tier : '') . '-' . $this->size . '">' . $this->content . '</div>';
    }
}