<?php

namespace Phoundation\Web\Http\Html\Components;



/**
 * Class ElementsBlock
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class ElementsBlock
{
    use ElementAttributes;



    /**
     * A form around this element block
     *
     * @var Form|null
     */
    protected ?Form $form = null;

    /**
     * The data source for this element
     *
     * @var array|null $source
     */
    protected ?array $source = null;



    /**
     * Sets the content of the element to display
     *
     * @param bool $use_form
     * @return static
     */
    public function useForm(bool $use_form): static
    {
        if ($use_form) {
            if (!$this->form) {
                $this->form = Form::new();
            }
        } else {
            $this->form = null;
        }

        return $this;
    }



    /**
     * Returns the form for this elements block
     *
     * @return Form
     */
    public function getForm(): Form
    {
        if (!$this->form) {
            $this->form = Form::new();
        }

        return $this->form;
    }



    /**
     * Returns the source for this element
     *
     * @return array|null
     */
    public function getSource(): ?array
    {
        return $this->source;
    }



    /**
     * Sets the data source for this element
     *
     * @param array|null $source
     * @return $this
     */
    public function setSource(?array $source): static
    {
        $this->source = $source;
        return $this;
    }



    /**
     * Render the ElementsBlock
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if ($this->form) {
            $this->form->setContent($this->render);
            return $this->form->render();
        }

        return $this->render;
    }

}