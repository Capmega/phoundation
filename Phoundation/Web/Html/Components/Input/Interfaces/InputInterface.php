<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Interfaces;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Web\Html\Components\Icons\Interfaces\IconInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;


/**
 * Interface InputInterface
 *
 * This interface describes the basic input class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * Returns the description
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Sets the description
     *
     * @param string|null $description
     * @param bool $make_safe
     * @return static
     */
    public function setDescription(?string $description, bool $make_safe = true): static;

    /**
     * Returns the icon
     *
     * @return IconInterface|null
     */
    public function getIcon(): ?IconInterface;

    /**
     * Sets the icon
     *
     * @param IconInterface|null $icon
     * @return static
     */
    public function setIcon(?IconInterface $icon): static;

    /**
     * Returns if the input element has a clear button or not
     *
     * @return bool
     */
    public function getClearButton(): bool;

    /**
     * Sets if the input element has a clear button or not
     *
     * @param bool $clear_button
     * @return static
     */
    public function setClearButton(bool $clear_button): static;
}