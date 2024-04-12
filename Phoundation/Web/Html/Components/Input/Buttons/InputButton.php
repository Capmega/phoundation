<?php
/**
 * Button class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Buttons;

use Phoundation\Web\Html\Components\Icons\Icons;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\InputButtonInterface;
use Phoundation\Web\Html\Components\Input\Input;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Html\Traits\TraitInputButtonProperties;
use Stringable;

class InputButton extends Input implements InputButtonInterface
{
    use TraitInputButtonProperties;

    /**
     * Floating buttons
     *
     * @var bool $floating
     */
    protected bool $floating = false;


    /**
     * Button class constructor
     *
     * @param string|null $content
     *
     * @todo Get rid of the web.defaults.elements.classes.button path as this was an idea before the templating system
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);
        $this->setName('submit');
        $this->setClasses('btn');
        $this->setElement('button');
        $this->setType(EnumButtonType::submit);
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
     * Set if the button is floating or not
     *
     * @param bool $floating
     *
     * @return InputButton
     */
    public function setFloating(bool $floating): static
    {
        $this->floating = $floating;

        return $this;
    }


    /**
     * Set the content for this button
     *
     * @param Stringable|string|float|int|null $value
     * @param bool                             $make_safe
     *
     * @return static
     * @todo add documentation for when button is floating as it is unclear what is happening there
     */
    public function setValue(Stringable|string|float|int|null $value, bool $make_safe = true): static
    {
        if ($this->floating) {
            // What does this do?????????????
            $this->addClass('btn-floating');
            Icons::new()
                 ->setContent($this->content)
                 ->render();

            return $this;
        }
        parent::setValue($value, $make_safe);

        return parent::setContent($value, $make_safe);
    }


    /**
     * Renders and returns the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->resetButtonClasses();
        $this->attributes->set($this->input_type?->value, 'type');
        if ($this->anchor_url) {
            $this->attributes->removeKeys('type');
            $this->attributes->set($this->anchor_url, 'href');
        }

        return parent::render();
    }


    /**
     * Set the classes for this button
     *
     * @return void
     */
    protected function resetButtonClasses(): void
    {
        // Remove the current button mode
        foreach ($this->classes as $class => $value) {
            if (str_starts_with($class, 'btn-')) {
                $this->classes->removeKeys($class);
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
        if ($this->block) {
            $this->addClass('btn-block');
        }
        if ($this->rounded) {
            $this->addClass('btn-rounded');
        }
        if (!$this->wrapping) {
            $this->addClass('text-nowrap');
        }
        if ($this->floating) {
            $this->addClass('btn-floating');
            $this->setContent(Icons::new()
                                   ->setContent($this->content)
                                   ->render());
        }
    }


    /**
     * Set the content for this button
     *
     * @param Stringable|string|float|int|null $content
     * @param bool                             $make_safe
     *
     * @return static
     * @todo add documentation for when button is floating as it is unclear what is happening there
     */
    public function setContent(Stringable|string|float|int|null $content, bool $make_safe = false): static
    {
        if ($this->floating) {
            // What does this do?????????????
            $this->addClass('btn-floating');
            Icons::new()
                 ->setContent($this->content, $make_safe)
                 ->render();

            return $this;
        }

        return parent::setContent($content, $make_safe);
    }
}
