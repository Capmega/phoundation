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
    protected ButtonsInterface $_buttons;


    /**
     * Returns if any buttons have been defined
     *
     * @return bool
     */
    public function hasButtons(): bool
    {
        if (empty($this->_buttons)) {
            return false;
        }

        return $this->_buttons->isNotEmpty();
    }


    /**
     * Returns the modal buttons
     *
     * @return ButtonsInterface
     */
    public function getButtonsObject(): ButtonsInterface
    {
        if (empty($this->_buttons)) {
            $this->_buttons = new Buttons();
        }

        return $this->_buttons;
    }


    /**
     * Sets the modal buttons
     *
     * @param ButtonsInterface|null $_buttons
     *
     * @return static
     */
    public function setButtonsObject(?ButtonsInterface $_buttons): static
    {
        if ($_buttons) {
            $this->_buttons = $_buttons;

        } else {
            unset($this->_buttons);
        }

        return $this;
    }


    /**
     * Adds the specified buttons to this buttons list
     *
     * @param ButtonsInterface|null $_buttons
     *
     * @return static
     */
    public function addButtons(?ButtonsInterface $_buttons): static
    {
        $this->getButtonsObject()->addSource($_buttons);
        return $this;
    }


    /**
     * Sets the modal buttons
     *
     * @param DropdownButtonInterface|ButtonInterface|null $_button
     *
     * @return static
     */
    public function addButton(DropdownButtonInterface|ButtonInterface|null $_button): static
    {
        if ($_button) {
            $this->getButtonsObject()->addButton($_button);
        }

        return $this;
    }
}
