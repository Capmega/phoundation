<?php

namespace Phoundation\Web\Http\Html\Layouts;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Exception\OutOfBoundsException;



/**
 * GridColumn class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class GridColumn extends Layout
{
    /**
     * The size of this column
     *
     * @var int|null
     */
    #[ExpectedValues(values: [null, 1, 2, 3, 4, 5, 6, 7 ,8, 9, 10, 11, 12])]
    protected ?int $size = 12;

    /**
     * The tier class for this column
     *
     * @var string
     */
    #[ExpectedValues(values: ["xs", "sm", "md", "lg", "xl"])]
    protected string $tier = '';



    /**
     * GridColumn class constructor
     */
    public function __construct()
    {
        $this->tier = 'md';
        parent::__construct();
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
     * @return int|null
     */
    #[ExpectedValues(values: [null, 1, 2, 3, 4, 5, 6, 7 ,8, 9, 10, 11, 12])] public function getSize(): ?int
    {
        return $this->size;
    }



    /**
     * Render this grid column
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!$this->size) {
            throw new OutOfBoundsException(tr('Cannot render GridColumn, no size specified'));
        }

        return parent::render();
    }
}