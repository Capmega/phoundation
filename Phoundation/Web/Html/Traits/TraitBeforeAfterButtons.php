<?php

/**
 * Trait TraitBeforeAfterButtons
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

namespace Phoundation\Web\Html\Traits;

use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonsInterface;

trait TraitBeforeAfterButtons
{
    /**
     * The buttons added after the input element
     *
     * @var ButtonsInterface
     */
    protected ButtonsInterface $after_buttons;

    /**
     * The buttons added before the input element
     *
     * @var ButtonsInterface
     */
    protected ButtonsInterface $before_buttons;


    /**
     * Returns if this input element has after buttons
     *
     * @return bool
     */
    public function hasAfterButtons(): bool
    {
        return isset($this->after_buttons);
    }


    /**
     * Returns the modal after_buttons
     *
     * @return ButtonsInterface
     */
    public function getAfterButtons(): ButtonsInterface
    {
        if (empty($this->after_buttons)) {
            $this->after_buttons = new Buttons();
        }

        return $this->after_buttons;
    }


    /**
     * Sets the modal after_buttons
     *
     * @param ButtonsInterface|null $after_buttons
     *
     * @return static
     */
    public function setAfterButtons(?ButtonsInterface $after_buttons): static
    {
        if ($after_buttons) {
            $this->after_buttons = $after_buttons;

        } else {
            unset($this->after_buttons);
        }

        return $this;
    }


    /**
     * Sets the modal after_buttons
     *
     * @param ButtonInterface|null $button
     *
     * @return static
     */
    public function addAfterButton(?ButtonInterface $button): static
    {
        if (!$button) {
            return $this;
        }

        $this->getAfterButtons()->addButton($button);
        return $this;
    }


    /**
     * Returns if this input element has before buttons
     *
     * @return bool
     */
    public function hasBeforeButtons(): bool
    {
        return isset($this->before_buttons);
    }


    /**
     * Returns the modal before_buttons
     *
     * @return ButtonsInterface
     */
    public function getBeforeButtons(): ButtonsInterface
    {
        if (empty($this->before_buttons)) {
            $this->before_buttons = new Buttons();
        }

        return $this->before_buttons;
    }


    /**
     * Sets the modal before_buttons
     *
     * @param ButtonsInterface|null $before_buttons
     *
     * @return static
     */
    public function setBeforeButtons(?ButtonsInterface $before_buttons): static
    {
        if ($before_buttons) {
            $this->before_buttons = $before_buttons;

        } else {
            unset($this->before_buttons);
        }

        return $this;
    }


    /**
     * Sets the modal before_buttons
     *
     * @param ButtonInterface|null $button
     *
     * @return static
     */
    public function addBeforeButton(?ButtonInterface $button): static
    {
        if (!$button) {
            return $this;
        }

        $this->getBeforeButtons()->addButton($button);
        return $this;
    }
}
