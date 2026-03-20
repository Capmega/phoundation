<?php

/**
 * Trait TraitButtonProperties
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use Phoundation\Data\Traits\TraitDataTarget;
use Phoundation\Data\Traits\TraitDataUrlObject;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Enums\EnumModifierKeys;
use Phoundation\Web\Html\Components\Icons\Icons;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Phoundation\Web\Http\Url;
use Stringable;


trait TraitButtonProperties
{
    use TraitMode;
    use TraitUsesSize;
    use TraitDataTarget;
    use TraitDataUrlObject {
        setUrlObject as protected __setUrlObject;
    }


    /**
     * Button type
     *
     * @var EnumButtonType|null $button_type
     */
    protected ?EnumButtonType $button_type = null;

    /**
     * Outlined buttons
     *
     * @var bool $outlined
     */
    protected bool $outlined = false;

    /**
     * Block (full width) buttons
     *
     * @var bool $block
     */
    protected bool $block = false;

    /**
     * Flat buttons
     *
     * @var bool $flat
     */
    protected bool $flat = false;

    /**
     * Rounded buttons
     *
     * @var bool $rounded
     */
    protected bool $rounded = false;

    /**
     * Text wrapping
     *
     * @var bool $wrapping
     */
    protected bool $wrapping = true;

    /**
     * Floating buttons
     *
     * @var bool $floating
     */
    protected bool $floating = false;

    /**
     * Auto disabling buttons
     *
     * @var bool $disable_after_click
     */
    protected bool $disable_after_click = false;

    /**
     * Tracks if the button is disabled and requires one or more keys down to enable
     *
     * @var array|null $require_keys_to_enable
     */
    protected ?array $require_keys_to_enable = null;

    /**
     * Tracks if the "keys to enable button" code is for the specified HTML class, or for this button uniquely
     *
     * @var string|null $require_keys_to_enable_class
     */
    protected ?string $require_keys_to_enable_class = null;


    /**
     * Set the button type
     *
     * @param EnumButtonType|null $type
     *
     * @return Button
     */
    public function setButtonType(?EnumButtonType $type): static
    {
        $this->setElement('button');
        $this->button_type = $type;

        if ($type === EnumButtonType::submit) {
            // By default, all submit buttons will disable themselves automatically after submission
            $this->setDisableAfterClick(true);
        }

        return $this;
    }


    /**
     * Returns true if the button type is the same as the specified type
     *
     * @param EnumButtonType $type
     *
     * @return bool
     */
    public function isButtonType(EnumButtonType $type): bool
    {
        return $this->button_type === $type;
    }


    /**
     * Returns the button type
     *
     * @return EnumButtonType|null
     */
    public function getButtonType(): ?EnumButtonType
    {
        return $this->button_type;
    }


    /**
     * Returns system wide disable-after-click setting
     *
     * Returns true if disable-after-click is enabled
     *
     * Returns false if disable-after-click is disabled and should not be available
     *
     * @return bool
     */
    public function getConfigDisableAfterClick(): bool
    {
        return config()->getBoolean('platforms.web.controls.buttons.disable-after-click', true);
    }


    /**
     * Returns if the button is disabled after a mouse click, or not
     *
     * @return bool
     */
    public function getDisableAfterClick(): bool
    {
        return $this->getConfigDisableAfterClick() and $this->disable_after_click;
    }


    /**
     * Set if the button is disabled after a mouse click, or not
     *
     * @param bool $disable_after_click
     *
     * @return Button
     */
    public function setDisableAfterClick(bool $disable_after_click): static
    {
        $this->disable_after_click = $disable_after_click;
        return $this;
    }


    /**
     * Returns if the button is disabled and requires one or more keys down to enable
     *
     * @return array|null
     */
    public function getRequireKeysToEnable(): ?array
    {
        return $this->require_keys_to_enable;
    }


    /**
     * Returns if the button is disabled and requires one or more keys down to enable
     *
     * @return string|null
     */
    public function getRequireKeysToEnableClass(): ?string
    {
        return $this->require_keys_to_enable_class;
    }


    /**
     * Returns the identifier string containing the modifier keys to enable the button if any have been specified, or NULL
     *
     * This method will make sure that the modifier keys are in the correct order, as required by the jquery-phoundation library
     *
     * @return string|null
     */
    public function getRequireKeysToEnableString(): ?string
    {
        $return = [];

        if (empty($this->require_keys_to_enable)) {
            return null;
        }

        $keys = array_flip(Arrays::ensureScalar($this->require_keys_to_enable));

        foreach (['ctrl', 'alt', 'shift'] as $key) {
            if (array_key_exists($key, $keys)) {
                $return[] = $key;
            }
        }

        return implode(',', $return);
    }


    /**
     * Sets if the button is disabled and requires one or more keys down to enable
     *
     * @param EnumModifierKeys|array|true|null $keys  [true] The buttons that need to be pressed down to enable the button
     * @param string|null                      $class [null] If specified, the JavaScript code will apply this for all elements with that class. If not, the
     *                                                       JavaScript will apply to the unique button ID
     *
     * @return static
     */
    public function setRequireKeysToEnable(EnumModifierKeys|array|true|null $keys = true, ?string $class = null): static
    {
        if (Arrays::containsNeedles($class, ' ')) {
            throw OutOfBoundsException::new(ts('The specified class ":class" contains spaces, which is not allowed', [
                'class' => $class
            ]));
        }

        if ($keys === true) {
            // Use the system-wide default modifier keys
            $keys  = $this->getDefaultRequireKeysToEnable();
            $class = $class ?? $this->getDefaultRequireKeysToEnableClass();
        }

        $this->require_keys_to_enable_class = $class;
        $this->require_keys_to_enable       = get_null(Arrays::force($keys, null));

        if ($this->require_keys_to_enable) {
            return $this->addClass('button-require-modifiers');
        }

        return $this->removeClass('button-require-modifiers');
    }


    /**
     * Returns the default keys to enable a button
     *
     * @return array
     */
    public function getDefaultRequireKeysToEnable(): array
    {
        return config()->getArray('platforms.web.html.components.buttons.default.modifier-keys', ['ctrl', 'alt']);
    }


    /**
     * Returns the default keys to enable a button class
     *
     * @return string
     */
    public function getDefaultRequireKeysToEnableClass(): string
    {
        return config()->getString('platforms.web.html.components.buttons.default.class', 'button-lock');
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
     * @return Button
     */
    public function setFloating(bool $floating): static
    {
        $this->floating = $floating;
        return $this;
    }


    /**
     * Returns the button's anchor URL
     *
     * @param UrlInterface|string|null $_url
     *
     * @return Button
     */
    public function setUrlObject(UrlInterface|string|null $_url): static
    {
        if ($_url) {
            $this->setElement('a');
            $this->_url       = Url::new($_url);
            $this->button_type = null;

        } else {
            $this->_url = null;
        }

        return $this;
    }


    /**
     * Returns if the button is outlined or not
     *
     * @return bool
     */
    public function getOutlined(): bool
    {
        return $this->outlined;
    }


    /**
     * Set if the button is outlined or not
     *
     * @param bool $outlined
     *
     * @return Button
     */
    public function setOutlined(bool $outlined): static
    {
        $this->outlined = $outlined;
        return $this;
    }


    /**
     * Returns if the button is block or not
     *
     * @return bool
     */
    public function getBlock(): bool
    {
        return $this->block;
    }


    /**
     * Set if the button is block or not
     *
     * @param bool $block                    The value for block mode for the button. True will enable block mode, false will disable it
     * @param bool $reset_float_right [true] If true, will reset the "float right" property to false, as these two are mutually exclusive
     *
     * @return Button
     */
    public function setBlock(bool $block, bool $reset_float_right = true): static
    {
        $this->block = $block;

        if ($block and $reset_float_right) {
            return $this->setFloatRight(false, false);
        }

        return $this;
    }


    /**
     * Returns if the button is flat or not
     *
     * @return bool
     */
    public function getFlat(): bool
    {
        return $this->flat;
    }


    /**
     * Set if the button is flat or not
     *
     * @param bool $flat
     *
     * @return Button
     */
    public function setFlat(bool $flat): static
    {
        $this->flat = $flat;
        return $this;
    }


    /**
     * Returns if the button is rounded or not
     *
     * @return bool
     */
    public function getRounded(): bool
    {
        return $this->rounded;
    }


    /**
     * Set if the button is rounded or not
     *
     * @param bool $rounded
     *
     * @return Button
     */
    public function setRounded(bool $rounded): static
    {
        $this->rounded = $rounded;
        return $this;
    }


    /**
     * Returns if the button is wrapping or not
     *
     * @return bool
     */
    public function getWrapping(): bool
    {
        return $this->wrapping;
    }


    /**
     * Set if the button is wrapping or not
     *
     * @param bool $wrapping
     *
     * @return Button
     */
    public function setWrapping(bool $wrapping): static
    {
        $this->wrapping = $wrapping;
        return $this;
    }


    /**
     * Renders and returns the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->resetButtonClasses()->_attributes->set($this->button_type?->value, 'type');

        if ($this->_url) {
            // Use an <a> anchor button
            $this->_attributes->removeKeys('type');
            $this->_attributes->set($this->_url, 'href');

            // Adds support for target="" attribute
            if ($this->target) {
                $this->_attributes->set($this->target, 'target');
            }
        }

        return parent::render();
    }


    /**
     * Set the classes for this button
     *
     * @return static
     */
    protected function resetButtonClasses(): static
    {
        // Remove the current button mode
        foreach ($this->_classes as $class => $value) {
            if (str_starts_with($class, 'btn-')) {
                $this->_classes->removeKeys($class);
            }
        }

        if ($this->mode->value) {
            $this->addClasses('btn-' . ($this->outlined ? 'outline-' : '') . $this->mode->value);

        } else {
            if ($this->outlined) {
                $this->addClasses('btn-outline');
            }
        }

        if ($this->flat) {
            $this->addClasses('btn-flat');
        }

        if ($this->size->value) {
            $this->addClasses('btn-' . $this->size->value);
        }

        if ($this->block) {
            $this->addClasses('btn-block');
        }

        if ($this->rounded) {
            $this->addClasses('btn-rounded');
        }

        if (!$this->wrapping) {
            $this->addClasses('text-nowrap');
        }

        if ($this->floating) {
            $this->addClasses('btn-floating');
            $this->setContent(Icons::new()->setContent($this->content));
        }

        return $this;
    }
}
