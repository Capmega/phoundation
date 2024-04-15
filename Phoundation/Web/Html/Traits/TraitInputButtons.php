<?php

/**
 * Trait TraitInputButtons
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

namespace Phoundation\Web\Html\Traits;

use Phoundation\Web\Html\Components\Input\Buttons\InputButtons;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\InputButtonsInterface;

trait TraitInputButtons
{
    /**
     * The bottom buttons content for this modal
     *
     * @var InputButtonsInterface
     */
    protected InputButtonsInterface $buttons;

    /**
     * Returns the modal buttons
     *
     * @return InputButtonsInterface
     */
    public function getInputButtons(): InputButtonsInterface
    {
        if (empty($this->buttons)) {
            $this->buttons = new InputButtons();
        }

        return $this->buttons;
    }


    /**
     * Sets the modal buttons
     *
     * @param InputButtonsInterface|null $buttons
     *
     * @return static
     */
    public function setInputButtons(?InputButtonsInterface $buttons): static
    {
        if ($buttons) {
            $this->buttons = $buttons;

        } else {
            unset($this->buttons);
        }

        return $this;
    }
}
