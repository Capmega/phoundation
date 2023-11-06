<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Web\Html\Components\Button;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Enums\DisplayMode;


/**
 * Class InputMultiButtonText
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputMultiButtonText extends ElementsBlock
{
    /**
     * The button for this text input
     *
     * @var Button $button
     */
    protected Button $button;

    /**
     * The input text box object
     *
     * @var Input $input
     */
    protected Input $input;

    /**
     * InputMultiButtonText class constructor
     */
    public function __construct()
    {
        $this->setButton(Button::new()
            ->setMode(DisplayMode::info)
            ->setContent(tr('Action')));
        return parent::__construct();
    }


    /**
     * Returns the internal button object
     *
     * @return Button
     */
    public function getButton(): Button
    {
        return $this->button;
    }


    /**
     * Returns the internal button object
     *
     * @param Button $button
     * @return static
     */
    public function setButton(Button $button): static
    {
        $button->addClasses(['btn', 'dropdown-toggle']);
        $button->getData()->add('toggle', 'dropdown');
        $button->getAria()->add('false', 'expanded');

        $this->button = $button;
        return $this;
    }


    /**
     * Returns the internal input object
     *
     * @return Input
     */
    public function getInput(): Input
    {
        if (!isset($this->input)) {
            // Default to input text
            $this->input = new InputText();
        }

        return $this->input;
    }


    /**
     * Returns the internal input object
     *
     * @param Input $input
     * @return static
     */
    public function setInput(Input $input): static
    {
        if ($this->name) {
            // Apply the object name to the input
            $input->setName($this->name);
        }

        $this->input = $input;
        return $this;
    }


    /**
     * Clears the button options
     *
     * @return static
     */
    public function clearOptions(): static
    {
        $this->options = [];
        return $this;
    }
}