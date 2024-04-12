<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Components\Input\Buttons\InputButton;
use Phoundation\Web\Html\Enums\EnumDisplayMode;

/**
 * Class InputMultiButtonText
 *
 *
 *
 * @see       \Phoundation\Data\DataEntry\DataList
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
class InputMultiButtonText extends ElementsBlock
{
    /**
     * The button for this text input
     *
     * @var InputButton $button
     */
    protected InputButton $button;

    /**
     * The input text box object
     *
     * @var Input $input
     */
    protected Input $input;


    /**
     * InputMultiButtonText class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->setButton(InputButton::new()
                                    ->setMode(EnumDisplayMode::info)
                                    ->setContent(tr('Action')));

        return parent::__construct($content);
    }


    /**
     * Returns the internal button object
     *
     * @return InputButton
     */
    public function getButton(): InputButton
    {
        return $this->button;
    }


    /**
     * Returns the internal button object
     *
     * @param InputButton $button
     *
     * @return static
     */
    public function setButton(InputButton $button): static
    {
        $button->addClasses([
            'btn',
            'dropdown-toggle',
        ]);
        $button->getData()
               ->add('toggle', 'dropdown');
        $button->getAria()
               ->add('false', 'expanded');
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
     *
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