<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use JetBrains\PhpStorm\ExpectedValues;

/**
 * Trait TraitBackground
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
trait TraitBackground
{
    /**
     * The type of infobox to show
     *
     * @var string|null $background
     */
    #[ExpectedValues([
        null,
        'primary',
        'info',
        'warning',
        'danger',
        'success',
    ])]
    protected ?string $background = null;


    /**
     * Sets the type of infobox to show
     *
     * @return string|null
     */
    #[ExpectedValues([
        null,
        'primary',
        'info',
        'warning',
        'danger',
        'success',
    ])]
    public function getBackground(): ?string
    {
        return $this->background;
    }


    /**
     * Returns the type of infobox to show
     *
     * @param string|null $background
     *
     * @return static
     */
    public function setBackground(#[ExpectedValues([
        null,
        'primary',
        'info',
        'warning',
        'danger',
        'success',
    ])] ?string $background): static
    {
        $this->background = $background;

        return $this;
    }
}