<?php

/**
 * Trait TraitObjectTopButtons
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


trait TraitObjectTopButtons
{
    /**
     * The bottom buttons content for this modal
     *
     * @var ButtonsInterface
     */
    protected ButtonsInterface $_top_buttons;


    /**
     * Returns if any buttons have been defined
     *
     * @return bool
     */
    public function hasTopButtons(): bool
    {
        if (empty($this->_top_buttons)) {
            return false;
        }

        return $this->_top_buttons->isNotEmpty();
    }


    /**
     * Returns the modal buttons
     *
     * @return ButtonsInterface
     */
    public function getTopButtons(): ButtonsInterface
    {
        if (empty($this->_top_buttons)) {
            $this->_top_buttons = new Buttons();
        }

        return $this->_top_buttons;
    }


    /**
     * Sets the modal buttons
     *
     * @param ButtonsInterface|null $_top_buttons
     *
     * @return static
     */
    public function setTopButtons(?ButtonsInterface $_top_buttons): static
    {
        if ($_top_buttons) {
            $this->_top_buttons = $_top_buttons;

        } else {
            unset($this->_top_buttons);
        }

        return $this;
    }


    /**
     * Adds the specified buttons to this buttons list
     *
     * @param ButtonsInterface|null $buttons
     *
     * @return static
     */
    public function addTopButtons(?ButtonsInterface $buttons): static
    {
        $this->getTopButtons()->addSource($buttons);
        return $this;
    }


    /**
     * Sets the modal buttons
     *
     * @param DropdownButtonInterface|ButtonInterface|null $button
     *
     * @return static
     */
    public function addTopButton(DropdownButtonInterface|ButtonInterface|null $button): static
    {
        if ($button) {
            $this->getTopButtons()->addButton($button);
        }

        return $this;
    }
}
