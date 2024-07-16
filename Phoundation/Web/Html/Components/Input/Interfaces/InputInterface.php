<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Interfaces;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Web\Html\Components\Icons\Interfaces\IconInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonsInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;

interface InputInterface extends ElementInterface
{
    /**
     * Input class constructor
     */
    function __construct();


    /**
     * Returns a new input element from
     *
     * @param DefinitionInterface $definition
     *
     * @return static
     */
    public static function newFromDataEntryDefinition(DefinitionInterface $definition): static;


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

    /**
     * Returns if this input element has after buttons
     *
     * @return bool
     */
    public function hasAfterButtons(): bool;

    /**
     * Returns the modal after_buttons
     *
     * @return ButtonsInterface|null
     */
    public function getAfterButtons(): ?ButtonsInterface;

    /**
     * Sets the modal after_buttons
     *
     * @param ButtonsInterface|null $after_buttons
     *
     * @return static
     */
    public function setAfterButtons(?ButtonsInterface $after_buttons): static;

    /**
     * Sets the modal after_buttons
     *
     * @param ButtonInterface|null $button
     *
     * @return static
     */
    public function addAfterButton(?ButtonInterface $button): static;

    /**
     * Returns if this input element has before buttons
     *
     * @return bool
     */
    public function hasBeforeButtons(): bool;

    /**
     * Returns the modal before_buttons
     *
     * @return ButtonsInterface|null
     */
    public function getBeforeButtons(): ?ButtonsInterface;

    /**
     * Sets the modal before_buttons
     *
     * @param ButtonsInterface|null $before_buttons
     *
     * @return static
     */
    public function setBeforeButtons(?ButtonsInterface $before_buttons): static;

    /**
     * Sets the modal before_buttons
     *
     * @param ButtonInterface|null $button
     *
     * @return static
     */
    public function addBeforeButton(?ButtonInterface $button): static;
}