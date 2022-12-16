<?php

namespace Templates\AdminLte\Components\Widgets;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Elements\ElementsBlock;



/**
 * AdminLte Plugin Widget class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
abstract class Widget extends ElementsBlock
{
    /**
     * The type of infobox to show
     *
     * @var string $type
     */
    #[ExpectedValues('primary', 'info', 'warning', 'danger', 'success')]
    protected string $type = 'info';

    /**
     * Show the type color as gradient or not
     *
     * @var bool $gradient
     */
    protected bool $gradient = false;



    /**
     * Sets the type of infobox to show
     *
     * @return string
     */
    #[ExpectedValues('primary', 'info', 'warning', 'danger', 'success')]
    public function getType(): string
    {
        return $this->type;
    }



    /**
     * Returns if this card is shown with gradient color or not
     *
     * @return bool
     */
    public function getGradient(): bool
    {
        return $this->gradient;
    }



    /**
     * Sets if this card is shown with gradient color or not
     *
     * @param bool $gradient
     * @return static
     */
    public function setGradient(bool $gradient): static
    {
        $this->gradient = $gradient;
        return $this;
    }



    /**
     * Returns the type of infobox to show
     *
     * @param string $type
     * @return static
     */
    public function setType(#[ExpectedValues('primary', 'info', 'warning', 'danger', 'success')] string $type): static
    {
        $this->type = $type;
        return $this;
    }
}