<?php

namespace Phoundation\Web\Http\Html\Components\FlashMessages;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Content\Images\Image;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\ElementsBlock;
use Phoundation\Web\Http\Html\Components\Mode;
use Phoundation\Web\Http\Html\Components\Script;
use Phoundation\Web\Page;


/**
 * Class FlashMessage
 *
 * This class contains a single Flash message and can render it to HTML
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class FlashMessage extends ElementsBlock
{
    use Mode;


    /**
     * Title of the flash message
     *
     * @var string|null $title
     */
    protected ?string $title = null;

    /**
     * Subtitle of the flash message
     *
     * @var string|null $sub_title
     */
    protected ?string $sub_title = null;

    /**
     * If specified, the user can manually close the flash message
     *
     * @var bool $can_close
     */
    protected bool $can_close = true;

    /**
     * If specified will auto close the flash message in $auto_close milliseconds
     *
     * @var int|null $auto_close
     */
    protected ?int $auto_close = null;

    /**
     * If specified will display an icon in the flash message
     *
     * @var string|null $icon
     */
    protected ?string $icon = null;

    /**
     * Image to show with the flash message
     *
     * @var Image|null $image
     */
    protected ?Image $image = null;

    /**
     * If true, show the flash message on the top, else on the bottom
     *
     * @var bool $top
     */
    protected bool $top = true;

    /**
     * If true, show the flash message on the left, else on the right
     *
     * @var bool $left
     */
    protected bool $left = false;


    /**
     * Returns the flash message contents
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->content;
    }


    /**
     * Sets the flash message contents
     *
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): static
    {
        $this->content = $message;
        return $this;
    }


    /**
     * Returns the flash message title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }


    /**
     * Sets the flash message title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }


    /**
     * Returns the flash message subtitle
     *
     * @return string
     */
    public function getSubTitle(): string
    {
        return $this->sub_title;
    }


    /**
     * Sets the flash message subtitle
     *
     * @param string $sub_title
     * @return $this
     */
    public function setSubTitle(string $sub_title): static
    {
        $this->sub_title = $sub_title;
        return $this;
    }


    /**
     * Returns the flash icon contents
     *
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }


    /**
     * Sets the flash icon contents
     *
     * @param string|null $icon
     * @return $this
     */
    public function setIcon(?string $icon): static
    {
        if ($icon) {
            if ($this->image) {
                throw new OutOfBoundsException(tr('Cannot specify icon for flash message, an image was already set'));
            }
        }

        $this->icon = get_null($icon);
        return $this;
    }


    /**
     * Returns the flash image contents
     *
     * @return Image
     */
    public function getImage(): Image
    {
        return $this->image;
    }


    /**
     * Sets the flash image contents
     *
     * @param Image|string|null $image
     * @param string|null $alt
     * @return $this
     */
    public function setImage(Image|string|null $image, ?string $alt = null): static
    {
        if ($image) {
            if ($this->icon) {
                throw new OutOfBoundsException(tr('Cannot specify image for flash message, an icon was already set'));
            }

            if (is_string($image)) {
                // image was specified as a string, make an image object
                $image = Image::new()
                    ->setFile($image)
                    ->setDescription($image);
            }
        }

        $this->image = get_null($image);
        return $this;
    }


    /**
     * Returns if the flash message is shown on the left side of the screen
     *
     * @return string
     */
    public function getLeft(): string
    {
        return $this->left;
    }


    /**
     * Sets if the flash message is shown on the right side of the screen
     *
     * @param string $left
     * @return $this
     */
    public function setLeft(string $left): static
    {
        $this->left = $left;
        return $this;
    }


    /**
     * Returns if the flash message is shown at the top of the screen
     *
     * @return string
     */
    public function getTop(): string
    {
        return $this->top;
    }


    /**
     * Sets if the flash message is shown at the top of the screen
     *
     * @param string $top
     * @return $this
     */
    public function setTop(string $top): static
    {
        $this->top = $top;
        return $this;
    }


    /**
     * Returns if the flash message will close automatically after N milliseconds
     *
     * @return int|null
     */
    public function getAutoClose(): ?int
    {
        return $this->auto_close;
    }


    /**
     * Sets if the flash message will close automatically after N milliseconds
     *
     * @param int|null $auto_close
     * @return $this
     */
    public function setAutoClose(?int $auto_close): static
    {
        $this->auto_close = get_null($auto_close);
        return $this;
    }


    /**
     * Returns if the flash message can be closed
     *
     * @return bool
     */
    public function getCanClose(): bool
    {
        return $this->can_close;
    }


    /**
     * Sets if the flash message can be closed
     *
     * @param bool $can_close
     * @return $this
     */
    public function setCanClose(bool $can_close): static
    {
        $this->can_close = $can_close;
        return $this;
    }


    /**
     * Renders and returns the HTML for this flash message
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->render = Script::new()->setContent($this->renderBare())->render();
        return parent::render();
    }


    /**
     * Renders and returns the HTML for this flash message without javascript tags
     *
     * @return string|null
     */
    public function renderBare(): ?string
    {
        $image = $this->image?->getHtmlElement();

        if ($this->top) {
            if ($this->left) {
                $position = 'topLeft';
            } else {
                $position = 'topRight';
            }
        } else {
            if ($this->left) {
                $position = 'bottomLeft';
            } else {
                $position = 'bottomRight';
            }
        }

        return '
            $(document).Toasts("create", {
                class: "bg-' . $this->mode->value . '",
                title: "' . Strings::escape($this->title) . '",
                subtitle: "' . Strings::escape($this->sub_title) . '",
                position: "' . $position . '",
                ' . ($image ? 'image: "' . Strings::escape($image->getSrc()) . '", image-alt: "' . Strings::escape($image->getAlt()) . '",' : null) . '                           
                ' . ($this->icon ? 'icon: "fas fa-' . Strings::escape($this->icon) . ' fa-lg",' : null) . '                           
                ' . ($this->auto_close ? 'autohide: true, delay: ' . $this->auto_close . ',' .  PHP_EOL : null) . '
                body: "' . Strings::escape($this->content) . '"
            });';
    }


    /**
     * Import the flash message object data from the specified array
     *
     * @param array $source
     * @return $this
     */
    public function import(array $source): static
    {
        foreach ($source as $key => $value) {
            match ($key) {
                'top'        => $this->top        = $value,
                'left'       => $this->left       = $value,
                'mode'       => $this->mode       = $this->mode::from($value),
                'icon'       => $this->icon       = $value,
                'image'      => $this->image      = $value,
                'title'      => $this->title      = $value,
                'message'    => $this->content    = $value,
                'can_close'  => $this->can_close  = $value,
                'auto_close' => $this->auto_close = $value
            };
        }

        return $this;
    }


    /**
     * Export this flash message object to an array
     *
     * @return array
     */
    public function export(): array
    {
        return [
            'top'        => $this->top,
            'left'       => $this->left,
            'mode'       => $this->mode->value,
            'icon'       => $this->icon,
            'image'      => $this->image,
            'title'      => $this->title,
            'message'    => $this->content,
            'can_close'  => $this->can_close,
            'auto_close' => $this->auto_close
        ];
    }

}