<?php
/**
 * Trait TraitButtonProperties
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use Phoundation\Web\Html\Components\Icons\Icons;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Http\UrlBuilder;
use Stringable;

trait TraitButtonProperties
{
    use TraitMode;
    use TraitUsesSize;


    /**
     * Button type
     *
     * @var EnumButtonType|null $button_type
     */
    protected ?EnumButtonType $button_type = null;

    /**
     * Sets if this is an anchor button or not
     *
     * @var string|null $anchor_url
     */
    protected ?string $anchor_url = null;

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

        return $this;
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
     * @return string|null
     */
    public function getAnchorUrl(): ?string
    {
        return $this->anchor_url;
    }


    /**
     * Returns the button's anchor URL
     *
     * @param Stringable|string|null $anchor_url
     *
     * @return Button
     */
    public function setAnchorUrl(Stringable|string|null $anchor_url): static
    {
        $this->setElement('a');
        $this->anchor_url  = (string) UrlBuilder::getWww($anchor_url);
        $this->button_type = null;

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
     * @param bool $block
     *
     * @return Button
     */
    public function setBlock(bool $block): static
    {
        $this->block = $block;

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
        $this->resetButtonClasses();
        $this->attributes->set($this->button_type?->value, 'type');
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
            $this->setContent(Icons::new()
                                   ->setContent($this->content)
                                   ->render());
        }
    }
}