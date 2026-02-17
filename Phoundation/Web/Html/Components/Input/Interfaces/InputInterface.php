<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Interfaces;

use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Web\Html\Components\Icons\Interfaces\IconInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonsInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;

interface InputInterface extends ElementInterface, BeforeAfterContentInterface
{
    /**
     * Input class constructor
     */
    function __construct();


    /**
     * Returns a new input element from
     *
     * @param DefinitionInterface $_definition
     *
     * @return static
     */
    public static function newFromDataEntryDefinition(DefinitionInterface $_definition): static;


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
     * @param bool        $make_safe
     *
     * @return static
     */
    public function setDescription(?string $description, bool $make_safe = false): static;


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
     *
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
     *
     * @return static
     */
    public function setClearButton(bool $clear_button): static;
}
