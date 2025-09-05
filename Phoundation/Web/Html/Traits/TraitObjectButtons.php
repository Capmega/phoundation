<?php

/**
 * Trait TraitObjectButtons
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonsInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\DropdownButtonInterface;


trait TraitObjectButtons
{
    /**
     * The bottom buttons content for this modal
     *
     * @var ButtonsInterface
     */
    protected ButtonsInterface $o_buttons;


    /**
     * Returns if any buttons have been defined
     *
     * @return bool
     */
    public function hasButtons(): bool
    {
        if (empty($this->o_buttons)) {
            return false;
        }

        return $this->o_buttons->isNotEmpty();
    }


    /**
     * Returns the modal buttons
     *
     * @return ButtonsInterface
     */
    public function getButtonsObject(): ButtonsInterface
    {
        if (empty($this->o_buttons)) {
            $this->o_buttons = new Buttons();
        }

        return $this->o_buttons;
    }


    /**
     * Sets the modal buttons
     *
     * @param ButtonsInterface|null $o_buttons
     *
     * @return static
     */
    public function setButtonsObject(?ButtonsInterface $o_buttons): static
    {
        if ($o_buttons) {
            $this->o_buttons = $o_buttons;

        } else {
            unset($this->o_buttons);
        }

        return $this;
    }


    /**
     * Adds the specified buttons to this buttons list
     *
     * @param ButtonsInterface|null $o_buttons
     *
     * @return static
     */
    public function addButtons(?ButtonsInterface $o_buttons): static
    {
        $this->getButtonsObject()->addSource($o_buttons);
        return $this;
    }


    /**
     * Sets the modal buttons
     *
     * @param DropdownButtonInterface|ButtonInterface|null $o_button
     *
     * @return static
     */
    public function addButton(DropdownButtonInterface|ButtonInterface|null $o_button): static
    {
        if ($o_button) {
            $this->getButtonsObject()->addButton($o_button);
        }

        return $this;
    }
}
