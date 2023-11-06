<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Boxes;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Data\Traits\DataTitle;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Widgets\Widget;
use Phoundation\Web\Html\Html;


/**
 * Box class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class Box extends Widget
{
    use DataTitle;


    /**
     * The icon to display on this infobox
     *
     * @var string|null $icon
     */
    protected ?string $icon = null;

    /**
     * The value to display on this infobox
     *
     * @var string|null $value
     */
    protected ?string $value = null;

    /**
     * The value for the progress bar in %, if shown
     *
     * @var int|null $progress
     */
    protected ?int $progress = null;

    /**
     * The type of shadow to display with the infobox, if any
     *
     * @var string|null $shadow
     */
    #[ExpectedValues(null, 'shadow-sm', 'shadow', 'shadow-lg')]
    protected ?string $shadow = null;

    /**
     * The description to display on this infobox
     *
     * @var string|null $description
     */
    protected ?string $description = null;

    /**
     * The URL to where a infobox click will go
     *
     * @var string|null $url
     */
    protected ?string $url = null;


    /**
     * Returns the icon for this infobox
     *
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }


    /**
     * Returns the icon for this infobox
     *
     * @param string|null $icon
     * @return static
     */
    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }


    /**
     * Returns the icon for this infobox
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }


    /**
     * Sets the URL for this infobox
     *
     * @param string|null $url
     * @return static
     */
    public function setUrl(?string $url): static
    {
        $this->url = $url;
        return $this;
    }


    /**
     * Returns the type of shadow to display with the infobox, if any
     *
     * @return string|null
     */
    #[ExpectedValues(null, 'shadow-sm', 'shadow', 'shadow-lg')]
    public function getShadow(): ?string
    {
        return $this->shadow;
    }


    /**
     * Sets the type of shadow to display with the infobox, if any
     *
     * @param string|null $shadow
     * @return static
     */
    public function setShadow(#[ExpectedValues(null, 'shadow-sm', 'shadow', 'shadow-lg')] ?string $shadow): static
    {
        $this->shadow = $shadow;
        return $this;
    }


    /**
     * Returns the value for this infobox
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }


    /**
     * Sets the value for this infobox
     *
     * @param string $value
     * @param bool $make_safe
     * @return static
     */
    public function setValue(string $value, bool $make_safe = true): static
    {
        if ($make_safe) {
            $this->value = Html::safe($value);
        } else {
            $this->value = $value;
        }

        return $this;
    }


    /**
     * Returns the value for the progress bar in %, if shown
     *
     * @return int|null
     */
    public function getProgress(): ?int
    {
        return $this->progress;
    }


    /**
     * Sets the value for the progress bar in %, if shown
     *
     * @param int|null $progress
     * @return static
     */
    public function setProgress(?int $progress): static
    {
        if (($progress < 0) or ($progress > 100)) {
            throw new OutOfBoundsException(tr('The progress bar value ":value" is invalid, it should be between 0 and 100', [
                ':value' => $progress
            ]));
        }

        $this->progress = $progress;
        return $this;
    }


    /**
     * Returns the description for this infobox
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }


    /**
     * Sets the description for this infobox
     *
     * @param string|null $description
     * @return static
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }
}