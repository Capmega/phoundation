<?php

namespace Templates\Mdb\Elements;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Web\Http\Html\Elements\Element;



/**
 * MDB Plugin Icons class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
 */
class Icons extends Element
{
    /**
     * The icon size
     *
     * @var string|null $size
     */
    #[ExpectedValues(values:["xs", "sm", "lg", "2x", "3x", "4x", "5x", "6x", "7x", "8x", "9x", "10x"])]
    protected ?string $size = null;



    /**
     * Sets the icon size
     *
     * @return string
     */
    #[ExpectedValues(values:["xs", "sm", "lg", "2x", "3x", "4x", "5x", "6x", "7x", "8x", "9x", "10x"])] public function getSize(): string
    {
        return $this->size;
    }



    /**
     * Sets the icon size
     *
     * @param string $size
     * @return static
     */
    public function setSize(#[ExpectedValues(values:["xs", "sm", "lg", "2x", "3x", "4x", "5x", "6x", "7x", "8x", "9x", "10x"])] string $size): static
    {
        $this->size = $size;
        return $this;
    }



    /**
     * Render the icon HTML
     *
     * @note This render skips the parent Element class rendering for speed and simplicity
     * @return string
     */
    public function render(): string
    {
        if (preg_match('/[a-z0-9-_]*]/i', $this->content)) {
            // icon names should only have letters, numbers and dashes and underscores
            return $this->content;
        }

        return '<i class="fas fa-' . $this->content . ($this->size ? ' fa-' . $this->size : '') .'"></i>';
    }
}