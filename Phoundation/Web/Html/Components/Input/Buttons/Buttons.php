<?php

/**
 * Buttons class
 *
 * This class manages and can render a set of multiple buttons
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Buttons;

use Iterator;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonsInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\DropdownButtonInterface;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Html\Traits\TraitButtonProperties;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use ReturnTypeWillChange;
use Stringable;


class Buttons extends ElementsBlock implements ButtonsInterface
{
    use TraitButtonProperties;


    /**
     * If true, the buttons will be grouped in one larger button
     *
     * @var bool $group
     */
    protected bool $group = false;


    /**
     * Sets the buttons list
     *
     * @param ArrayableInterface|array $buttons
     *
     * @return static
     */
    public function setButtons(ArrayableInterface|array $buttons): static
    {
        $this->source = [];
        return $this->addButtons($buttons);
    }


    /**
     * Adds multiple buttons to button list
     *
     * @param ArrayableInterface|array $buttons
     *
     * @return static
     */
    public function addButtons(ArrayableInterface|array $buttons): static
    {
        foreach ($buttons as $button) {
            $this->addButton($button);
        }

        return $this;
    }


    /**
     * Adds a single "Save" button to the button list
     *
     * @param bool $float_right [false] If true, will add a float-right class to the button
     *
     * @return static
     */
    public function addSaveButton(bool $float_right = false): static
    {
        return $this->addButton(SaveButton::new()->setFloatRight($float_right));
    }


    /**
     * Adds a single "Back" button to the button list
     *
     * @param UrlInterface $_url                The URL where the audit button should point to
     * @param bool         $float_right [false] If true, will add a float-right class to the button
     *
     * @return static
     */
    public function addBackButton(UrlInterface $_url, bool $float_right = false): static
    {
        return $this->addButton(BackButton::new()
                                           ->setUrlObject($_url)
                                           ->setFloatRight($float_right));
    }


    /**
     * Adds a single "Create" button to the button list
     *
     * @param UrlInterface $_url                The URL where this button should point to
     * @param bool         $float_right [false] If true, will add a float-right class to the button
     *
     * @return static
     */
    public function addCreateButton(UrlInterface $_url, bool $float_right = false): static
    {
        return $this->addButton(CreateButton::new()
                                            ->setUrlObject($_url)
                                            ->setFloatRight($float_right));
    }


    /**
     * Adds a single "Audit" button to the button list
     *
     * @param UrlInterface $_url                The URL where the audit button should point to
     * @param bool         $float_right [false] If true, will add a float-right class to the button
     *
     * @return static
     */
    public function addAuditButton(UrlInterface $_url, bool $float_right = false): static
    {
        return $this->addButton(AuditButton::new()
                                           ->setUrlObject($_url)
                                           ->setFloatRight($float_right));
    }


    /**
     * Adds a single "Delete" button to the button list
     *
     * @param bool $float_right [false] If true, will add a float-right class to the button
     *
     * @return static
     */
    public function addDeleteButton(bool $float_right = false): static
    {
        return $this->addButton(DeleteButton::new()->setFloatRight($float_right));
    }


    /**
     * Adds a single "Undelete" button to the button list
     *
     * @param bool $float_right [false] If true, will add a float-right class to the button
     *
     * @return static
     */
    public function addUndeleteButton(bool $float_right = false): static
    {
        return $this->addButton(UndeleteButton::new()->setFloatRight($float_right));
    }


    /**
     * Adds a single "Lock" button to the button list
     *
     * @param bool $float_right [false] If true, will add a float-right class to the button
     *
     * @return static
     */
    public function addLockButton(bool $float_right = false): static
    {
        return $this->addButton(LockButton::new()->setFloatRight($float_right));
    }


    /**
     * Adds a single "Unlock" button to the button list
     *
     * @param bool $float_right [false] If true, will add a float-right class to the button
     *
     * @return static
     */
    public function addUnlockButton(bool $float_right = false): static
    {
        return $this->addButton(UnlockButton::new()->setFloatRight($float_right));
    }


    /**
     * Adds a single button to the button list
     *
     * @param ButtonInterface|DropdownButtonInterface|string|null $button
     * @param EnumDisplayMode                                     $mode
     * @param EnumButtonType|Stringable|string                    $type_or_url
     * @param bool                                                $outline
     * @param bool                                                $float_right
     *
     * @return static
     */
    public function addButton(ButtonInterface|DropdownButtonInterface|string|null $button, EnumDisplayMode $mode = EnumDisplayMode::primary, EnumButtonType|Stringable|string $type_or_url = EnumButtonType::submit, bool $outline = false, bool $float_right = false): static
    {
        if (!$button) {
            // Do not add anything
            return $this;
        }

        if (is_string($button)) {
            // Button was specified as string, create a button first
            $button = Button::new()
                            ->setWrapping($this->wrapping)
                            ->setOutlined($this->outlined)
                            ->setRounded($this->rounded)
                            ->addClasses($this->o_classes)
                            ->setOutlined($outline)
                            ->setContent($button)
                            ->setValue($value ?? $button)
                            ->setFloatRight($float_right)
                            ->setMode($mode)
                            ->setName('submit-button');

            switch ($type_or_url) {
                case EnumButtonType::submit:
                    // no break

                case EnumButtonType::button:
                    // no break

                case EnumButtonType::reset:
                    // One of the submit, reset, or button buttons
                    $button->setButtonType($type_or_url);
                    break;

                default:
                    // This is a URL button, place an anchor with href instead
                    $button->setUrlObject($type_or_url);
            }
        }

        $button->setReadonly($button->getReadonly() or $this->getReadonly())
               ->setDisabled($button->getDisabled() or $this->getDisabled());

        if (empty($button->getValue())) {
            if (empty($button->getContent())) {
                throw new OutOfBoundsException(tr('No name specified for button ":button"', [
                    ':button' => $button,
                ]));
            }

            $this->source[$button->getContent()] = $button;

        } else {
            $this->source[$button->getValue()] = $button;
        }

        return $this;
    }


    /**
     * Renders and returns the string for this Buttons class
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $o_aria  = $this->getAriaObject();
        $o_data = $this->getDataObject();
        $o_class = $this->getClassesObject();

        foreach ($this as $button) {
            $button->getAriaObject()->addSource($o_aria);
            $button->getDataObject()->addSource($o_data);
            $button->getClassesObject()->addSource($o_class);
        }

        return parent::render();
    }


    /**
     * Returns the button grouping
     *
     * @return bool
     */
    public function getGroup(): bool
    {
        return $this->group;
    }


    /**
     * Sets the button grouping
     *
     * @param bool $group
     *
     * @return static
     */
    public function setGroup(bool $group): static
    {
        $this->group = $group;
        return $this;
    }


    /**
     * Returns the current button
     *
     * @return ButtonInterface|DropdownButtonInterface
     */
    #[ReturnTypeWillChange] public function current(): ButtonInterface|DropdownButtonInterface
    {
        return current($this->source);
    }


    /**
     * Progresses the internal pointer to the next button
     *
     * @return void
     */
    #[ReturnTypeWillChange] public function next(): void
    {
        next($this->source);
    }


    /**
     * Returns the current key for the current button
     *
     * @return string
     */
    #[ReturnTypeWillChange] public function key(): string
    {
        return key($this->source);
    }


    /**
     * Returns if the current pointer is valid or not
     *
     * @todo Is this really really required? Since we are using internal array pointers anyway, it always SHOULD be valid
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->source[key($this->source)]);
    }


    /**
     * Rewinds the internal pointer
     *
     * @return void
     */
    #[ReturnTypeWillChange] public function rewind(): void
    {
        reset($this->source);
    }
}
