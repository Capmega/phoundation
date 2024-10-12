<?php

/**
 * Trait TraitButtons
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonsInterface;


trait TraitButtons
{
    /**
     * The bottom buttons content for this modal
     *
     * @var ButtonsInterface
     */
    protected ButtonsInterface $buttons;


    /**
     * Returns if any buttons have been defined
     *
     * @return bool
     */
    public function hasButtons(): bool
    {
        if (empty($this->buttons)) {
            return false;
        }

        return $this->buttons->isNotEmpty();
    }


    /**
     * Returns the modal buttons
     *
     * @return ButtonsInterface
     */
    public function getButtons(): ButtonsInterface
    {
        if (empty($this->buttons)) {
            $this->buttons = new Buttons();
        }

        return $this->buttons;
    }


    /**
     * Sets the modal buttons
     *
     * @param ButtonsInterface|null $buttons
     *
     * @return static
     */
    public function setButtons(?ButtonsInterface $buttons): static
    {
        if ($buttons) {
            $this->buttons = $buttons;

        } else {
            unset($this->buttons);
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
    public function addButtons(?ButtonsInterface $buttons): static
    {
        $this->getButtons()->addSource($buttons);
        return $this;
    }


    /**
     * Sets the modal buttons
     *
     * @param ButtonInterface|null $button
     *
     * @return static
     */
    public function addButton(?ButtonInterface $button): static
    {
        if ($button) {
            $this->getButtons()->addButton($button);
        }

        return $this;
    }
}
