<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Web\Http\Html\Components\Input\Input;
use Phoundation\Web\Http\Html\Enums\ButtonType;
use Phoundation\Web\Http\Html\Enums\InputType;
use Stringable;

/**
 * Button class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Button extends Input
{
    use ButtonProperties;


    /**
     * Floating buttons
     *
     * @var bool $floating
     */
    protected bool $floating = false;


    /**
     * Button class constructor
     * @todo Get rid of the web.defaults.elements.classes.button path as this was an idea before the templating system
     */
    public function __construct()
    {
        parent::__construct();

        $this->setElement('button');
        $this->setType(ButtonType::submit);
        $this->setClasses('btn');
    }


    /**
     * Set if the button is floating or not
     *
     * @param bool $floating
     * @return Button
     */
    public function setFloating(bool $floating): static
    {
        $this->floating = $floating;
        return $this;
    }


    /**
     * Returns if the button is floating or not
     *
     * @return bool
     */
    public function getFloating(): bool
    {
        return $this->floating;
    }


    /**
     * Set the content for this button
     *
     * @todo add documentation for when button is floating as it is unclear what is happening there
     * @param Stringable|string|float|int|null $content
     * @return static
     */
    public function setContent(Stringable|string|float|int|null $content): static
    {
        if ($this->floating) {
            // What does this do?????????????
            $this->addClass('btn-floating');
            Icons::new()->setContent($this->content)->render();
            return $this;
        }

        return parent::setContent($content);
    }


    /**
     * Set the content for this button
     *
     * @param Stringable|string|float|int|null $value
     * @return static
     * @todo add documentation for when button is floating as it is unclear what is happening there
     */
    public function setValue(Stringable|string|float|int|null $value): static
    {
        if ($this->floating) {
            // What does this do?????????????
            $this->addClass('btn-floating');
            Icons::new()->setContent($this->content)->render();
            return $this;
        }

        return parent::setValue($value);
    }


    /**
     * Set the classes for this button
     *
     * @return void
     */
    protected function resetButtonClasses(): void
    {
        // Remove the current button mode
        foreach ($this->classes as $id => $class) {
            if (str_starts_with($id, 'btn-')) {
                unset($this->classes[$id]);
            }
        }

        if ($this->mode->value) {
            $this->addClass('btn-' . ($this->outlined ? 'outline-' : '') . $this->mode->value);
        } else {
            if ($this->outlined) {
                $this->addClass('btn-outline');
            }
        }

        if ($this->flat) {
            $this->addClass('btn-flat');
        }

        if ($this->size->value) {
            $this->addClass('btn-' . $this->size->value);
        }

        if ($this->rounded) {
            $this->addClass('btn-rounded');
        }
        if (!$this->wrapping) {
            $this->addClass('text-nowrap');
        }

        if ($this->floating) {
            $this->addClass('btn-floating');
            $this->setContent(Icons::new()->setContent($this->content)->render());
        }
    }


    /**
     * Renders and returns the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->resetButtonClasses();
        $this->attributes['type'] = $this->type?->value;

        if ($this->anchor_url) {
            unset($this->attributes['type']);
            $this->attributes['href'] = $this->anchor_url;
        }

        return parent::render();
    }
}
