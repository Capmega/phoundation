<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Interfaces;

use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonsInterface;


interface BeforeAfterButtonsInterface
{
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
