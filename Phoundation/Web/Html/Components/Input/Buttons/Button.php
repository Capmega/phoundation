<?php

/**
 * Class Button
 *
 * This class represents an HTML <button> element. It accepts a wide variety of button properties, like content, class, float-right, etc, and can render itself
 * into the HTML text required for a browser to display the button the way you need it.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Buttons;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Icons\Icons;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonInterface;
use Phoundation\Web\Html\Components\Input\Input;
use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Html\Enums\EnumJavascriptWrappers;
use Phoundation\Web\Html\Traits\TraitButtonProperties;
use Phoundation\Web\Html\Traits\TraitUrlRightsRendering;
use Stringable;


class Button extends Input implements ButtonInterface
{
    use TraitUrlRightsRendering;
    use TraitButtonProperties {
        render as protected __render;
    }


    /**
     * Button class constructor
     *
     * @param RenderInterface|callable|string|float|int|null $content   [null]  The content for this button. To clarify the difference between content and
     *                                                                          value: The content is the (usually) text located between
     *                                                                          <button>CONTENT HERE</button> whereas the value refers to the attribute value in
     *                                                                          the HTML button tag, like <button value='VALUE HERE'>
     * @param bool                                           $make_safe [false] If true, passes $value through Html::safe() before rendering ensuring it will not contain
     *                                                                          anything that can break the page
     *
     * @todo Get rid of the web.defaults.elements.classes.button path as this was an idea before the templating system
     */
    public function __construct(RenderInterface|callable|string|float|int|null $content = null, bool $make_safe = false)
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
     * @param Stringable|string|float|int|null $value             The value for this button. To clarify the difference between content and value: The content is
     *                                                            the (usually) text located between <button>CONTENT HERE</button> whereas the value refers to
     *                                                            the attribute value in the HTML button tag, like <button value='VALUE HERE'>
     * @param bool                             $make_safe [false] If true, passes $value through Html::safe() before rendering ensuring it will not contain
     *                                                            anything that can break the page
     *
     * @return static
     * @todo add documentation for when button is floating as it is unclear what is happening there
     */
    public function setValue(Stringable|string|float|int|null $value, bool $make_safe = false): static
    {
        if ($this->floating) {
            // TODO What does this do? When setting a value while floating is on, we render an icon but don't do anything with it? This code makes little sense
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
    public function setReadonly(bool $readonly, ?bool $set_disabled = null, string|false|null $title = false): static
    {
        return parent::setReadonly($readonly, $set_disabled, $title);
    }


    /**
     * @inheritDoc
     */
    public function setDisabled(bool $disabled, ?bool $set_readonly = null, string|false|null $title = false): static
    {
        return parent::setDisabled($disabled, $set_readonly, $title);
    }


    /**
     * Set the content for this button
     *
     * @param RenderInterface|callable|string|float|int|null $content           The content for this button. To clarify the difference between content and
     *                                                                          value: The content is the (usually) text located between
     *                                                                          <button>CONTENT HERE</button> whereas the value refers to the attribute value in
     *                                                                          the HTML button tag, like <button value="VALUE HERE">
     * @param bool                                           $make_safe [false] If true, passes $value through Html::safe() before rendering ensuring it will not contain
     *                                                                          anything that can break the page
     *
     * @return static
     * @todo add documentation for when button is floating as it is unclear what is happening there
     */
    public function setContent(RenderInterface|callable|string|float|int|null $content, bool $make_safe = false): static
    {
        if ($this->floating) {
            // TODO What does this do? When setting a value while floating is on, we render an icon but don't do anything with it? This code makes little sense
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
        $script = null;

        if ($this->getDisableAfterClick()) {
            // This button will disable itself after having been clicked
            $this->addClass('button-disable-click');
            $script .= Script::new('$(".button-disable-click").on("click", function (e) {
                // Disable this button and submit
                $(e.target).closest("form").submit();
                $(e.target).addClass("disabled").prop("readonly", true);
            })');
        }

        if ($this->getRequireKeysToEnable()) {
            // Disable this button by default, only enable it with a special key combination.
            // This button will then also need a tooltip indicating that it is "disabled" until you press those keys.
            $title = tr('To enable this button, please press down the :keys :label', [
                ':keys'  => Strings::force($this->getRequireKeysToEnable(), ' and '),
                ':label' => Strings::plural(get_element_count($this->getRequireKeysToEnable()), tr('key'), tr('keys'))
            ]);

            $this->setDisabled(true)
                 ->addData($this->getTitle(), 'title')
                 ->addData($title           , 'require-keys-title')
                 ->setTitle($title);

            if ($this->getRequireKeysToEnableClass()) {
                // We will apply this to a class of buttons
                $selector = '.' . $this->getRequireKeysToEnableClass();
                $this->addClass($this->getRequireKeysToEnableClass());

            } else {
                // We will apply this to this single button
                $selector = '.' . $this->getRequireKeysToEnableClass();
            }

            $script .= Script::new('
                window.phoundation.addModifierkeyDownCallback("' . $this->getRequireKeysToEnableString() . '", function () {
                    $("' . $selector . '.button-require-modifiers").each(function (index, button) {
                        $button = $(button);

                        $(button).prop("title", $button.data("title") || "")
                                 .prop("disabled", false)
                                 .removeClass("disabled");
                    });
                });
                
                window.phoundation.addModifierkeyUpCallback("' . $this->getRequireKeysToEnableString() . '", function () {
                    $("' . $selector . '.button-require-modifiers").each(function (index, button) {
                        $button = $(button);

                        $(button).prop("title", $button.data("require-keys-title") || "")
                                 .prop("disabled", true)
                                 .addClass("disabled");
                    });
                });            
            ')->setJavascriptWrapper(EnumJavascriptWrappers::window);
        }

        if (empty($this->getContent())) {
            // Content takes the value
            throw new OutOfBoundsException(tr('Cannot render ":class" button object with name ":name", no content specified', [
                ':name'  => $this->getName(),
                ':class' => static::class,
            ]));

        } else {
            // By default, use the content as value
            $this->setValue(strip_tags($this->getContent()));
        }

        if (empty($this->getName()) and !$this->getReadonly() and !$this->getDisabled()) {
            // Content takes the value
            throw OutOfBoundsException::new(tr('Cannot render ":class" button object with value ":value" and content ":content", it has no name specified', [
                ':value'   => $this->getValue(),
                ':content' => $this->getContent(),
                ':class'   => static::class,
            ]))->setData([
                'id'       => $this->getId(),
                'name'     => $this->getName(),
                'readonly' => $this->getReadonly(),
                'disabled' => $this->getDisabled(),
                'value'    => $this->getValue(),
                'content'  => $this->getContent(),
            ]);
        }

        if (empty($this->getValue())) {
            if ($this->isButtonType(EnumButtonType::submit)) {
                if (empty($this->getUrlObject())) {
                    // Value takes the content
                    throw new OutOfBoundsException(tr('Cannot render ":class" submit button object with name ":name", no value or anchor URL specified', [
                        ':name'  => $this->getName(),
                        ':class' => static::class,
                    ]));
                }
            }
        }

        // Should we render this URL at all?
        if ($this->o_url) {
            // Should we render this URL at all?
            if (!$this->hasRenderRights()) {
                return null;
            }

            if ($this->getUrlObject()->isEmpty()) {
                if (empty($this->content)) {
                    // This Anchor contains no URL nor text content to display. Render nothing instead
                    return null;
                }

                $this->setElement('span')->addClass('anchor');
            }
        }

        return $this->__render() . $script;
    }
}
