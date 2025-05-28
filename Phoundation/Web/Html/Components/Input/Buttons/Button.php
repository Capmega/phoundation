<?php

/**
 * Button class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Buttons;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Icons\Icons;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonInterface;
use Phoundation\Web\Html\Components\Input\Input;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Html\Traits\TraitButtonProperties;
use Stringable;


class Button extends Input implements ButtonInterface
{
    use TraitButtonProperties {
        render as protected __render;
    }


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

        $this->setName('submit-button')
             ->setValue($content)
             ->setClassesObject('btn')
             ->setElement('button')
             ->setButtonType(EnumButtonType::submit);
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
            $this->addClasses('btn-floating');

            Icons::new()
                 ->setContent($this->content)
                 ->render();

            return $this;
        }

        return parent::setValue($value, $make_safe);
    }


    /**
     * @inheritDoc
     */
    public function setReadonly(bool $readonly, ?bool $set_disabled = null): static {
        return parent::setReadonly($readonly, $set_disabled);
    }


    /**
     * @inheritDoc
     */
    public function setDisabled(bool $disabled, ?bool $set_readonly = null): static {
        return parent::setDisabled($disabled, $set_readonly);
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
    public function setContent(Stringable|string|float|int|null $content, bool $make_safe = true): static
    {
        if ($this->floating) {
            // What does this do?????????????
            $this->addClasses('btn-floating');

            Icons::new()
                 ->setContent($this->content, $make_safe)
                 ->render();

            return $this;
        }

        return parent::setContent($content, $make_safe);
    }


    /**
     * Renders and returns the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (empty($this->getContent())) {
            // Content takes the value
            throw new OutOfBoundsException(tr('Cannot render ":class" button object with name ":name", no content specified', [
                ':name'  => $this->getName(),
                ':class' => static::class,
            ]));
        }

        if (empty($this->getName()) and !$this->getReadonly() and !$this->getDisabled()) {
            // Content takes the value
            throw new OutOfBoundsException(tr('Cannot render ":class" button object with value ":value" and content ":content", it has no name specified', [
                ':value'   => $this->getValue(),
                ':content' => $this->getContent(),
                ':class'   => static::class,
            ]));
        }

        if (empty($this->getValue())) {
            if ($this->isButtonType(EnumButtonType::submit)) {
                if (empty($this->getContent())) {
                    if (empty($this->getAnchorUrl())) {
                        // Value takes the content
                        throw new OutOfBoundsException(tr('Cannot render ":class" submit button object with name ":name", no value or anchor URL specified', [
                            ':name'  => $this->getName(),
                            ':class' => static::class,
                        ]));
                    }

                } else {
                    // By default, use the content as value
                    $this->setValue(strip_tags($this->getContent()));
                }
            }
        }

        return $this->__render();
    }
}
