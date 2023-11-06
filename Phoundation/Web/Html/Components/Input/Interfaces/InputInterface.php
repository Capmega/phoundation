<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Interfaces;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;


/**
 * Interface Input
 *
 * This interface describes the basic input class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface InputInterface extends ElementInterface
{
    /**
     * Input class constructor
     */
    function __construct();

    /**
     * Returns a new input element from
     *
     * @param DefinitionInterface $field
     * @return static
     */
    public static function newFromDataEntryField(DefinitionInterface $field): static;

    /**
     * Render and return the HTML for this Input Element
     *
     * @return string|null
     */
    function render(): ?string;
}